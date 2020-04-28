<?php

class CRM_Hubsync_Synchronizer {
  private $custom_field_hub_id;
  private $custom_field_updated_at;
  private $custom_field_deleted_in_hub;

  public function __construct() {
    // get the name of the custom field HUB ID, Updated At, Deleted in HUB
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'hub_id']);
    $this->custom_field_hub_id = 'custom_' . $result['id'];
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'updated_at']);
    $this->custom_field_updated_at = 'custom_' . $result['id'];
    $result = civicrm_api3('CustomField', 'getsingle', ['name' => 'deleted_in_hub']);
    $this->custom_field_deleted_in_hub = 'custom_' . $result['id'];
  }

  public function syncPriorities($queue, $dryRun = FALSE) {
    $runNow = FALSE;

    // clear the sync status of the priorities temp table
    $sql = "update civicrm_beuc_hub_priorities set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // see if we have to create a new queue
    if ($queue === '') {
      $queue = $this->getQueue();
      $runNow = TRUE;
    }

    // store all priorities in the queue
    $sql = "select id from civicrm_beuc_hub_priorities";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $task = new CRM_Queue_Task(['CRM_Hubsync_Synchronizer', 'syncPriorityTask'], [$dryRun, $dao->id]);
      $queue->createItem($task);
    }

    if ($runNow) {
      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'BEUC HUB Sync',
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEndUrl' => CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'),
      ]);
      $runner->runAll();
    }
  }

  public function syncOrganizations($queue, $dryRun = FALSE) {
    $runNow = FALSE;

    // clear the sync status of the orgs temp table
    $sql = "update civicrm_beuc_hub_orgs set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // see if we have to create a new queue
    if ($queue === '') {
      $queue = $this->getQueue();
      $runNow = TRUE;
    }

    // store all orgs in the queue
    $sql = "select id from civicrm_beuc_hub_orgs";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $task = new CRM_Queue_Task(['CRM_Hubsync_Synchronizer', 'syncContactTask'], [$dryRun, 'orgs', $dao->id, $this->custom_field_hub_id, $this->custom_field_updated_at, $this->custom_field_deleted_in_hub]);
      $queue->createItem($task);
    }

    if ($runNow) {
      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'BEUC HUB Sync',
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEndUrl' => CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'),
      ]);
      $runner->runAll();
    }
  }

  public function syncUsers($queue, $dryRun = FALSE) {
    $runNow = FALSE;

    // clear the sync status of the users temp table
    $sql = "update civicrm_beuc_hub_users set sync_status = NULL";
    CRM_Core_DAO::executeQuery($sql);

    // see if we have to create a new queue
    if ($queue === '') {
      $queue = $this->getQueue();
      $runNow = TRUE;
    }

    // store all the not deleted users in the queue
    $sql = "select id from civicrm_beuc_hub_users where is_deleted = 0";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $task = new CRM_Queue_Task(['CRM_Hubsync_Synchronizer', 'syncContactTask'], [$dryRun, 'users', $dao->id, $this->custom_field_hub_id, $this->custom_field_updated_at, $this->custom_field_deleted_in_hub]);
      $queue->createItem($task);
    }

    if ($runNow) {
      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'BEUC HUB Sync',
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEndUrl' => CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'),
      ]);
      $runner->runAll();
    }
  }

  public function processDeletedContacts($queue, $dryRun = FALSE) {
    $runNow = FALSE;

    // see if we have to create a new queue
    if ($queue === '') {
      $queue = $this->getQueue();
      $runNow = TRUE;
    }

    // store all the deleted users in the queue
    $sql = "select id from civicrm_beuc_hub_users where is_deleted = 1";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $task = new CRM_Queue_Task(['CRM_Hubsync_Synchronizer', 'syncDeletedContactTask'], [$dryRun, 'users', $dao->id, $this->custom_field_hub_id, $this->custom_field_updated_at, $this->custom_field_deleted_in_hub]);
      $queue->createItem($task);
    }

    if ($runNow) {
      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'BEUC HUB Sync',
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEndUrl' => CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'),
      ]);
      $runner->runAll();
    }
  }

  public function syncAll($dryRun = FALSE) {
    // create a queue
    $queue = $this->getQueue();

    // store everything in the queue
    $this->syncPriorities($queue, $dryRun);
    $this->syncOrganizations($queue, $dryRun);
    $this->syncUsers($queue, $dryRun);
    $this->processDeletedContacts($queue, $dryRun);

    // run the queue via web
    $runner = new CRM_Queue_Runner([
      'title' => 'BEUC HUB Sync',
      'queue' => $queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
      'onEndUrl' => CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'),
    ]);
    $runner->runAllViaWeb();
  }

  private function getQueue() {
    // create a queue
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => 'beuchubsync',
      'reset' => TRUE, // flush queue upon creation
    ]);

    return $queue;
  }

  public static function syncPriorityTask(CRM_Queue_TaskContext $ctx, $dryRun, $id) {
    // get the priority
    $sql = "select * from civicrm_beuc_hub_priorities where id = $id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
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
        if ($dryRun) {
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

    return TRUE;
  }

  public static function syncContactTask(CRM_Queue_TaskContext $ctx, $dryRun, $contactType, $id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub) {
    $createContact = FALSE;
    $syncContact = FALSE;
    $contactID = 0;

    // get the contact from civicrm_beuc_hub_orgs or civicrm_beuc_hub_users
    $sql = "select * from civicrm_beuc_hub_$contactType where id = $id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      // lookup contact by HUB id
      $result = self::findContactByHubID($contactType, $dao->id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub);
      if ($result['count'] == 1) {
        // save the contact ID
        $contactID = $result['values'][0]['id'];

        // OK, found. Check the update timestamp
        if ($result['values'][0][$custom_field_updated_at] == $dao->updated_at) {
          // the contact exists, and there are no changes
          $status = 'already exists, and same timestamp - no sync needed';
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
          $contactID = self::findIndividualByNameAndEmail($dao, $custom_field_hub_id);
        }
        else {
          $contactID = self::findOrgByName($dao);
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

      if ($dryRun == TRUE || $syncContact == FALSE) {
        // just update the status
        $sqlUpdate = "update civicrm_beuc_hub_$contactType set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
        // create or update the contact
        $params = [
          'sequential' => 1,
          $custom_field_hub_id => $dao->id,
          $custom_field_updated_at => $dao->updated_at,
          $custom_field_deleted_in_hub => 0,
        ];

        if ($contactType == 'users') {
          $employer = self::findContactByHubID('orgs', $dao->org_id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub);

          $params['first_name'] = $dao->first_name;
          $params['last_name'] = $dao->last_name;
          $params['employer_id'] = $employer['values'][0]['id'];
          $params['job_title'] = $dao->job_title;
          $params['contact_type'] = 'Individual';
        }
        else {
          $params['contact_type'] = 'Organization';
          $params['organization_name'] = $dao->name;
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
              $params['country_id'] = self::getCountryID($dao->country);
              civicrm_api3('Address', 'create', $params);
            }
          }
          else {
            // create
            $params['street_address'] = $dao->address;
            $params['city'] = $dao->city;
            $params['postal_code'] = $dao->postcode;
            $params['country_id'] = self::getCountryID($dao->country);
            $params['location_type_id'] = 2;
            civicrm_api3('Address', 'create', $params);
          }
        }

        if ($contactType == 'users') {
          self::createOrUpdateUserPriorities($contactID, $dao->priorities);
        }

        // update the status
        $sqlUpdate = "update civicrm_beuc_hub_$contactType set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
    }

    return TRUE;
  }

  public static function syncDeletedContactTask(CRM_Queue_TaskContext $ctx, $dryRun, $contactType, $id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub) {
    $createContact = FALSE;
    $syncContact = FALSE;
    $contactID = 0;

    // get the contact
    $sql = "select * from civicrm_beuc_hub_users where id = $id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      // lookup contact by HUB id
      $result = self::findContactByHubID($contactType, $dao->id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub);
      if ($result['count'] == 1) {
        // save the contact ID
        $contactID = $result['values'][0]['id'];

        // OK, found. Check the "deleted" flag
        if ($result['values'][0][$custom_field_deleted_in_hub] == 1) {
          // already flagged as deleted
          $status = 'already flagged as deleted - no sync needed';
        }
        else {
          // the contact exists, but it's not flagged
          $status = 'already exists, but not flagged as deleted - sync needed';
          $syncContact = TRUE;
        }
      }
      elseif ($result['count'] > 1) {
        throw new Exception('Multiple contacts with HUB ID = ' . $dao->id);
      }
      else {
        // contact not found by HUB ID, try by name
        $contactID = self::findIndividualByNameAndEmail($dao, $custom_field_hub_id);
        if ($contactID > 0) {
          // OK
          $status = 'already exists, but no HUB ID and not flagged as deleted - sync needed';
          $syncContact = TRUE;
        }
        elseif ($contactID == -1) {
          // multiple contacts
          $status = 'multiple contacts with that name - please merge contacts or manually fill in the HUB ID';
        }
        else {
          // not found
          $status = 'deleted contact not in civicrm - no action needed';
        }
      }

      if ($dryRun == TRUE || $syncContact == FALSE) {
        // just update the status
        $sqlUpdate = "update civicrm_beuc_hub_$contactType set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
      else {
        // flag as deleted
        $params = [
          'sequential' => 1,
          'id' => $contactID,
          'job_title' => '',
          $custom_field_hub_id => $dao->id,
          $custom_field_updated_at => $dao->updated_at,
          $custom_field_deleted_in_hub => 1,
        ];
        $result = civicrm_api3('Contact', 'create', $params);
        $status = 'flagged as deleted';

        // get the civi id of the employer
        $employer = self::findContactByHubID('orgs', $dao->org_id, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub);
        if ($employer['count'] > 0) {
          // end the relationships
          $relationships = civicrm_api3('Relationship', 'get', [
            'sequential' => 1,
            'contact_id_a' => $contactID,
            'contact_id_b' => $employer['values'][0]['id'],
            'is_active' => 1,
          ]);
          for ($i = 0; $i < $relationships['count']; $i++) {
            civicrm_api3('Relationship', 'create', [
              'sequential' => 1,
              'id' => $relationships['values'][0]['id'],
              'end_date' => date('Y-m-d'),
              'is_active' => 0,
            ]);
          }
        }

        // remove work phone, email, and address
        $sql = "delete from civicrm_phone where contact_id = $contactID and location_type_id = 2";
        CRM_Core_DAO::executeQuery($sql);
        $sql = "delete from civicrm_email where contact_id = $contactID and location_type_id = 2";
        CRM_Core_DAO::executeQuery($sql);
        $sql = "delete from civicrm_address where contact_id = $contactID and location_type_id = 2";
        CRM_Core_DAO::executeQuery($sql);

        // remove from the priorities groups
        self::createOrUpdateUserPriorities($contactID, '');

        // update the status
        $sqlUpdate = "update civicrm_beuc_hub_$contactType set sync_status = '$status' where id = " . $dao->id;
        CRM_Core_DAO::executeQuery($sqlUpdate);
      }
    }

    return TRUE;
  }

  private function findOrgByName($dao) {
    $contactID = 0;

    // find org by name
    $params = [
      'sequential' => 1,
      'is_deleted' => 0,
      'organization_name' => $dao->name,
      'contact_type' => 'Organization',
    ];
    $result = civicrm_api3('Contact', 'get', $params);

    // not found, try by name + initials
    if ($result['count'] == 0) {
      $params = [
        'sequential' => 1,
        'is_deleted' => 0,
        'organization_name' => $dao->name . ' - ' . $dao->initials,
        'contact_type' => 'Organization',
      ];
      $result = civicrm_api3('Contact', 'get', $params);
    }

    if ($result['count'] == 1) {
      $contactID = $result['values'][0]['id'];
    }
    elseif ($result['count'] > 1) {
      $contactID = -1;
    }

    return $contactID;
  }

  public static function findIndividualByNameAndEmail($dao, $custom_field_hub_id) {
    $contactID = 0;

    // try to find by e-mail address (and without hub id, because that search should be covered first)
    $sql = "
      select
        c.id,
        c.first_name,
        c.last_name,
        hub.hub_id
      from
        civicrm_contact c
      inner join
        civicrm_email e on e.contact_id = c.id
      inner join
        civicrm_value_hub_sync_information hub on hub.entity_id = c.id
      where
        c.is_deleted = 0
      and
        c.contact_type = 'Individual'
      and
        e.email = %1
      and
        ifnull(hub.hub_id, 0) = 0
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
        'return' => [$custom_field_hub_id],
      ];
      $result = civicrm_api3('Contact', 'get', $params);
      if ($result['count'] == 1) {
        // make sure the contact does not have a HUB ID
        if ($result['values'][0][$custom_field_hub_id]) {
          // error: this contact already has a HUB ID
          $contactID = 0;
        }
        else {
          // OK, found the contact
          $contactID = $result['values'][0]['id'];
        }
      }
      elseif ($result['count'] > 1) {
        // error: multiple contacts found
        $contactID = -1;
      }
    }

    return $contactID;
  }

  public static function findContactByHubID($contactType, $hubID, $custom_field_hub_id, $custom_field_updated_at, $custom_field_deleted_in_hub) {
    $params = [
      'sequential' => 1,
      'is_deleted' => 0,
      'contact_type' => $contactType == 'users' ? 'Individual' : 'Organization',
      $custom_field_hub_id => $hubID,
      'return' => "$custom_field_hub_id,$custom_field_updated_at,$custom_field_deleted_in_hub",
    ];
    $result = civicrm_api3('Contact', 'get', $params);

    return $result;
  }

  public static function createOrUpdateUserPriorities($contactID, $priorities) {
    // create an array with the HUB priority ID as key, and the corresponding CiviCRM group ID as value
    $sql = "
      select
	      id civicrm_id,
	      CONVERT(
          replace(name,	'hub_priority_',	''),
	        UNSIGNED INTEGER
	      ) hub_id
      from
	      civicrm_group
      where
	      name like 'hub_priority_%'
      order by
	      2
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $civiAndHubID = $dao->fetchMap('hub_id', 'civicrm_id');

    // remove all priority groups for that contact
    $sql = "
      delete from
        civicrm_group_contact
      where
        contact_id = $contactID
      and
        group_id in (select id from civicrm_group where name like 'hub_priority_%')
    ";
    CRM_Core_DAO::executeQuery($sql);

    // now add the groups
    if ($priorities) {
      $prioArr = explode(',', $priorities);
      foreach ($prioArr as $prioID) {
        $params = [
          'sequential' => 1,
          'contact_id' => $contactID,
          'group_id' => $civiAndHubID[$prioID],
        ];
        civicrm_api3('GroupContact', 'create', $params);
      }
    }
  }

  public static function getCountryID($country) {
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
