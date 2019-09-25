<?php
use CRM_Hubsync_ExtensionUtil as E;

class CRM_Hubsync_Page_BeucHubSyncStatus extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle('BEUC HUB Sync - Status');

    // the url of the main hub sync page
    $mainURL = CRM_Utils_System::url('civicrm/beuchubsync', 'reset=1');
    $this->assign('mainPage', $mainURL);

    // get time of execution
    $this->assign('lastRun', Civi::settings()->get('beuchubsynclastrun'));

    // get the number of items in the queue
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => 'beuchubsync',
      'reset' => FALSE, // do not flush queue upon creation
    ]);
    $this->assign('queueItems', $queue->numberOfItems());

    // get counts
    $countPriorities = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_priorities");
    $this->assign('countPriorities', $countPriorities);
    $countUsers = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_users");
    $this->assign('countUsers', $countUsers);
    $countOrgs = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_orgs");
    $this->assign('countOrgs', $countOrgs);

    // get the priorities
    $dao = CRM_Core_DAO::executeQuery("select * from civicrm_beuc_hub_priorities order by sync_status, name");
    $priorities = $dao->fetchAll();
    $this->assign('priorities', $priorities);

    // get the users
    $dao = CRM_Core_DAO::executeQuery("select * from civicrm_beuc_hub_users order by sync_status, last_name, first_name");
    $users = $dao->fetchAll();
    $this->assign('users', $users);

    // get the users
    $dao = CRM_Core_DAO::executeQuery("select * from civicrm_beuc_hub_orgs order by sync_status, name");
    $orgs = $dao->fetchAll();
    $this->assign('orgs', $orgs);

    parent::run();
  }

}

