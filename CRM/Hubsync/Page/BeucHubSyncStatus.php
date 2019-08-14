<?php
use CRM_Hubsync_ExtensionUtil as E;

class CRM_Hubsync_Page_BeucHubSyncStatus extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle('BEUC HUB Sync - Status');

    // the url of the main hub sync page
    $mainURL = CRM_Utils_System::url('civicrm/beuchubsync', 'reset=1');
    $this->assign('mainPage', $mainURL);

    // get counts
    $countPriorities = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_priorities");
    $this->assign('countPriorities', $countPriorities);
    $countUsers = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_users");
    $this->assign('countUsers', $countUsers);
    $countOrgs = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_beuc_hub_orgs");
    $this->assign('countOrgs', $countOrgs);

    parent::run();
  }

}

