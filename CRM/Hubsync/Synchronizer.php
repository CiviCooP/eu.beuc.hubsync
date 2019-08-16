<?php

class CRM_Hubsync_Synchronizer {
  private $dryRun = TRUE;

  public function __construct($dryRun) {
    $this->dryRun = $dryRun;
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
    // clear the sync status
    $sql = "update civicrm_beuc_hub_users set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // loop over all users
    $sql = "select * from civicrm_beuc_hub_users";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {

      $params = [
        'sequential' => 1,
        'is_deleted' => 0,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'api.create.email' => [
          'location_type_id' => 2,
          'email' => $dao->email,
        ],
      ];

    }
  }

  public function syncOrgs() {
    $createContact = FALSE;
    $syncContact = FALSE;
    $contactID = 0;

    // get the ID of the custm field HUB ID, and Updated At
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'hub_id']);
    $hubID = $result['id'];
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'updated_at']);
    $updatedAtID = $result['id'];

    // clear the sync status
    $sql = "update civicrm_beuc_hub_orgs set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // loop over all orgs
    $sql = "select * from civicrm_beuc_hub_orgs";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // lookup contact by HUB id
      $params = [
        'sequential' => 1,
        'is_deleted' => 0,
        'contact_type' => 'Organization',
        "custom_$hubID" => $dao->id,
        'return' => "custom_$hubID,custom_$updatedAtID",
      ];
      $result = civicrm_api3('Contact', 'get', $params);
      if ($result['count'] > 0) {
        // OK, found. Check the update timestamp
        if ($result['values'][0]["custom_$updatedAtID"] == $dao->updated_at) {
          // the contact exists, and there are no changes
          $status = 'already exists, and no changes - no sync needed';
          $contactID = $result['values'][0]['id'];
        }
        else {
          // the contact exists, but the timestamp differs
          $status = 'already exists, but timestap differs - sync needed';
          $syncContact = TRUE;
          $contactID = $result['values'][0]['id'];
        }
      }
      else {
        // contact not found by HUB ID, try org name
        $params = [
          'sequential' => 1,
          'is_deleted' => 0,
          'organization_name' => $dao->name,
          'contact_type' => 'Organization',
        ];
        $result = civicrm_api3('Contact', 'get', $params);
        if ($result['count'] > 0) {
          $status = 'already exists, but no HUB ID - sync needed';
          $syncContact = TRUE;
          $contactID = $result['values'][0]['id'];
        }
        else {
          $status = 'new contact - sync needed';
          $createContact = TRUE;
          $syncContact = TRUE;
        }
      }

      if ($this->dryRun) {
        // just update the status
        $sqlUpdate = "update civicrm_beuc_hub_orgs set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
echo '<h1>' . $dao->name . '</h1>';
        if ($contactID) {
          $params['id'] = $contactID;
          $status = 'already exists, but timestap differs - synchronized';
        }

        if ($syncContact || $createContact) {
          // create of update the contact
          $params['organization_name'] = $dao->name;
          $params["custom_$hubID"] = $dao->id;
          $params["custom_$updatedAtID"] = $dao->updated_at;
          $result = civicrm_api3('Contact', 'create', $params);

          if ($createContact) {
            die('ok');
            $contactID = $result['id'];
            $status = 'new contact - synchronized';
          }

          // update the email address (if needed)
          if ($dao->email && $dao->email != 'NULL') {
            // see if the email exists in civi
            $params = [
              'contact_id' => $contactID,
              'is_primary' => 1,
              'sequential' => 1,
            ];
            $result = civicrm_api3('Email', 'get', $params);
            if ($result['count'] > 0 && $result['count'][0]['email'] != $dao->email) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['email'] = $dao->email;
              civicrm_api3('Email', 'create', $params);
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
            if ($result['count'] > 0 && $result['count'][0]['phone'] != $dao->tel) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['phone'] = $dao->tel;
              civicrm_api3('Phone', 'create', $params);
            }
            else {
              // create
              $params['phone'] = $dao->tel;
              $params['location_type_id'] = 2;
              $params['phone_type_id'] = 1;
              civicrm_api3('Phone', 'create', $params);
            }
          }

          // update the address (if needed)
          if ($dao->address) {
            $params = [
              'contact_id' => $contactID,
              'is_primary' => 1,
              'sequential' => 1,
            ];
            $result = civicrm_api3('Address', 'get', $params);
            if ($result['count'] > 0 && ($result['count'][0]['street_address'] != $dao->address || $result['count'][0]['city'] != $dao->city)) {
              // update
              $params['id'] = $result['values'][0]['id'];
              $params['street_address'] = $dao->address;
              $params['city'] = $dao->city;
              $params['postal_code'] = $dao->postcode;
              $params['country_id'] = $this->getCountryID($dao->country);
              civicrm_api3('Address', 'create', $params);
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
        }
        else {
          $status = 'already exists, timestap are equal - no sync needed';
        }
echo $status;
        $sqlUpdate = "update civicrm_beuc_hub_orgs set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
    }

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
