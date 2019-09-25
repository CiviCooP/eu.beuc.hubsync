<?php
use CRM_Hubsync_ExtensionUtil as E;


function _civicrm_api3_beuchubsync_Sync_spec(&$spec) {
}

function civicrm_api3_beuchubsync_Sync($params) {
  $step = '';

  try {
    $step = 'Creating connector';
    $connector = new CRM_Hubsync_Connector();

    $step = 'Creating fetcher';
    $fetcher = new CRM_Hubsync_Fetcher($connector->getConnectionURL());

    $step = 'Getting remote data';
    $fetcher->getRemoteData();

    $step = 'Storing priorities';
    $fetcher->storeDataPriorities();

    $step = 'Storing organizations';
    $fetcher->storeDataOrgs();

    $step = 'Storing users';
    $fetcher->storeDataUsers();

    $step = 'Creating synchronizer';
    $synchronizer = new CRM_Hubsync_Synchronizer();

    $step = 'Synchronizing data';
    $synchronizer->syncAll(FALSE, FALSE);

    return civicrm_api3_create_success('OK', $params, 'beuchubsync', 'Sync');
  }
  catch (Exception $e) {
    throw new API_Exception('BEUC HUB Sync failed at during step: ' . $step, 999);
  }
}
