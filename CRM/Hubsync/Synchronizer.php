<?php

class CRM_Hubsync_Synchronizer {
  private $dryRun = TRUE;
  private $custom_field_hub_id;
  private $custom_field_updated_at;

  public function __construct($dryRun) {
    $this->dryRun = $dryRun;

    // get the name of the custom field HUB ID, and Updated At
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'hub_id']);
    $this->custom_field_hub_id = 'custom_' . $result['id'];
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'updated_at']);
    $this->custom_field_updated_at = 'custom_' . $result['id'];
  }

  public function syncPriorities() {
    // clear the sync status
    $sql = "update civicrm_beuc_hub_priorities set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // loop over all priorities
    $sql = "select * from civicrm_beuc_hub_priorities";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // see if the group exists
      $params = [
        'name' => 'hub_priority_' . $dao->id,
      ];
      $group = civicrm_api3('Group', 'get', $params);
      if ($group['count'] > 0) {
        // already exists
        $sqlUpdate = "update civicrm_beuc_hub_priorities set sync_status = 'already exists - no sync needed' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
        // new
        if ($this->dryRun) {
          // just update the status
          $sqlUpdate = "update civicrm_beuc_hub_priorities set sync_status = 'new priority - will be created' where id = " . $dao->id;
          CRM_Core_DAO::executeQuery($sqlUpdate);
        }
        else {
          // create the group
          $params = [
            'name' => 'hub_priority_' . $dao->id,
            'title' => 'HUB Priority - ' . $dao->name,
          ];
          $group = civicrm_api3('Group', 'create', $params);

          // update the status
          $sqlUpdate = "update civicrm_beuc_hub_priorities set sync_status = 'new priority - created new group' where id = " . $dao->id;
          CRM_Core_DAO::executeQuery($sqlUpdate);
        }
      }
    }
  }

  public function syncUsers() {
    $this->syncContacts('users');
  }

  public function syncOrgs() {
    $this->syncContacts('orgs');
  }

  private function syncContacts($contactType) {
    $createContact = FALSE;
    $syncContact = FALSE;
    $contactID = 0;

    // clear the sync status
    $sql = "update civicrm_beuc_hub_$contactType set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // loop over all contacts in civicrm_beuc_hub_orgs or civicrm_beuc_hub_users table
    $sql = "select * from civicrm_beuc_hub_$contactType";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // lookup contact by HUB id
      $result = $this->findContactByHubID($contactType, $dao->id);
      if ($result['count'] == 1) {
        // save the contact ID
        $contactID = $result['values'][0]['id'];

        // OK, found. Check the update timestamp
        if ($result['values'][0][$this->custom_field_updated_at] == $dao->updated_at) {
          // the contact exists, and there are no changes
          $status = 'already exists, and same timestamp - no sync needed ';
        }
        else {
          // the contact exists, but the timestamp differs
          $status = 'already exists, but timestamp differs - sync needed';
          $syncContact = TRUE;
        }
      }
      elseif ($result['count'] > 1) {
        throw new Exception('Multiple contacts with HUB ID = ' . $dao->id);
      }
      else {
        // contact not found by HUB ID, try by name
        if ($contactType == 'users') {
          $contactID = $this->findIndividualByNameAndEmail($dao);
        }
        else {
          $contactID = $this->findOrgByName($dao);
        }

        // see if we found the contact by name
        if ($contactID > 0) {
          // OK
          $status = 'already exists, but no HUB ID - sync needed';
          $syncContact = TRUE;
        }
        elseif ($contactID == -1) {
          // multiple contacts
          $status = 'multiple contacts with that name - please merge contacts or manually fill in the HUB ID';
        }
        else {
          // not found
          $status = 'new contact - sync needed';
          $createContact = TRUE;
          $syncContact = TRUE;
        }
      }

      if ($this->dryRun == TRUE || $syncContact == FALSE) {
        // just update the status
        $sqlUpdate = "update civicrm_beuc_hub_$contactType set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
        // create or update the contact
        $params = [
          'sequential' => 1,
          $this->custom_field_hub_id => $dao->id,
          $this->custom_field_updated_at => $dao->updated_at,
        ];

        if ($contactType == 'users') {
          $employer = $this->findContactByHubID('orgs', $dao->org_id);

          $params['first_name'] = $dao->first_name;
          $params['last_name'] = $dao->last_name;
          $params['employer_id'] = $employer['values'][0]['id'];
          $params['contact_type'] = 'Individual';
        }
        else {
          $params['organization_name'] = $dao->name;
          $params['contact_type'] = 'Organization';
        }

        if ($createContact) {
          $status = 'new contact - created';
        }
        else {
          $params['id'] = $contactID;
          $status = 'already exists, but timestap differs - synchronized';
        }
        $result = civicrm_api3('Contact', 'create', $params);
        $contactID = $result['id'];

        // update the email address (if needed)
        if ($dao->email && $dao->email != 'NULL') {
          // see if the email exists in civi
          $params = [
            'contact_id' => $contactID,
            'is_primary' => 1,
            'sequential' => 1,
          ];
          $result = civicrm_api3('Email', 'get', $params);
          if ($result['count'] > 0) {
            if ($result['values'][0]['email'] != $dao->email) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['email'] = $dao->email;
              civicrm_api3('Email', 'create', $params);
            }
          }
          else {
            // create
            $params['email'] = $dao->email;
            $params['location_type_id'] = 2;
            civicrm_api3('Email', 'create', $params);
          }
        }

        // update the phone (if needed)
        if ($dao->tel) {
          // see if the phone exists in civi
          $params = [
            'contact_id' => $contactID,
            'is_primary' => 1,
            'sequential' => 1,
          ];
          $result = civicrm_api3('Phone', 'get', $params);
          if ($result['count'] > 0) {
            if ($result['values'][0]['phone'] != $dao->tel) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['phone'] = $dao->tel;
              civicrm_api3('Phone', 'create', $params);
            }
          }
          else {
            // create
            $params['phone'] = $dao->tel;
            $params['location_type_id'] = 2;
            $params['phone_type_id'] = 1;
            civicrm_api3('Phone', 'create', $params);
          }
        }

        // update the mobile phone (if needed)
        if ($contactType == 'users' && $dao->mobile) {
          // see if the mobile phone exists in civi
          $params = [
            'contact_id' => $contactID,
            'location_type_id' => 2,
            'phone_type_id' => 2,
            'sequential' => 1,
          ];
          $result = civicrm_api3('Phone', 'get', $params);
          if ($result['count'] > 0) {
            if ($result['values'][0]['phone'] != $dao->mobile) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['phone'] = $dao->mobile;
              civicrm_api3('Phone', 'create', $params);
            }
          }
          else {
            // create
            $params['phone'] = $dao->mobile;
            $params['location_type_id'] = 2;
            $params['phone_type_id'] = 2;
            civicrm_api3('Phone', 'create', $params);
          }
        }

        // update the address (if needed)
        if ($contactType == 'orgs' && $dao->address) {
          $params = [
            'contact_id' => $contactID,
            'is_primary' => 1,
            'sequential' => 1,
          ];
          $result = civicrm_api3('Address', 'get', $params);
          if ($result['count'] > 0) {
            if ($result['values'][0]['street_address'] != $dao->address || $result['values'][0]['city'] != $dao->city) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['street_address'] = $dao->address;
              $params['city'] = $dao->city;
              $params['postal_code'] = $dao->postcode;
              $params['country_id'] = $this->getCountryID($dao->country);
              civicrm_api3('Address', 'create', $params);
            }
          }
          else {
            // create
            $params['street_address'] = $dao->address;
            $params['city'] = $dao->city;
            $params['postal_code'] = $dao->postcode;
            $params['country_id'] = $this->getCountryID($dao->country);
            $params['location_type_id'] = 2;
            civicrm_api3('Address', 'create', $params);
          }
        }

        if ($contactType == 'users') {
          $this->createOrUpdateUserPriorities($contactID, $dao->priorities);
        }

        // update the status
        $sqlUpdate = "update civicrm_beuc_hub_orgs set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
    }
  }

  private function findOrgByName($dao) {
    $contactID = 0;

    $params = [
      'sequential' => 1,
      'is_deleted' => 0,
      'organization_name' => $dao->name,
      'contact_type' => 'Organization',
    ];
    $result = civicrm_api3('Contact', 'get', $params);
    if ($result['count'] == 1) {
      $contactID = $result['values'][0];
    }
    elseif ($result['count'] > 1) {
      $contactID = -1;
    }

    return $contactID;
  }

  private function findIndividualByNameAndEmail($dao) {
    $contactID = 0;

    // try to find by e-mail address
    $sql = "
      select
        c.id,
        c.first_name,
        c.last_name
      from
        civicrm_contact c
      inner join
        civicrm_email e on e.contact_id = c.id
      where 
        c.is_deleted = 0
      and
        c.contact_type = 'Individual'
      and
        e.email = %1
    ";
    $sqlParams = [
      1 => [$dao->email, 'String'],
    ];
    $contactDao = CRM_Core_DAO::executeQuery($sql, $sqlParams);

    if ($contactDao->N == 1) {
      // gotcha
      $contactDao->fetch();
      $contactID = $contactDao->id;
    }
    else {
      // try by name
      $params = [
        'sequential' => 1,
        'is_deleted' => 0,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'contact_type' => 'Individual',
      ];
      $result = civicrm_api3('Contact', 'get', $params);
      if ($result['count'] == 1) {
        $contactID = $result['values'][0];
      }
      elseif ($result['count'] > 1) {
        $contactID = -1;
      }
    }

    return $contactID;
  }

  private function findContactByHubID($contactType, $hubID) {
    $params = [
      'sequential' => 1,
      'is_deleted' => 0,
      'contact_type' => $contactType == 'users' ? 'Individual' : 'Organization',
      $this->custom_field_hub_id => $hubID,
      'return' => "{$this->custom_field_hub_id},{$this->custom_field_updated_at}",
    ];
    $result = civicrm_api3('Contact', 'get', $params);

    return $result;
  }

  private function createOrUpdateUserPriorities($contactID, $priorities) {
    // TODO
  }

  private function getCountryID($country) {
    if ($country) {
      $sql = "select id from civicrm_country where name = %1";
      $sqlParams = [
        1 => [$country, 'String'],
      ];
      $countryID = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

      if (!$countryID) {
        if ($country == 'Macedonia') {
          return 1128;
        }
        elseif ($country == 'Slovak Republic') {
          return 1192;
        }
        elseif ($country == 'Croatia (Hrvatska)') {
          return 1055;
        }
        else {
          throw new Exception("Cannot find civicrm id of country = $country");
        }
      }

      return $countryID;
    }
    else {
      return 0;
    }
  }
}
