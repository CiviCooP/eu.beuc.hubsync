<?php
use CRM_Hubsync_ExtensionUtil as E;


function _civicrm_api3_beuchubsync_Sync_spec(&$spec) {
}

function civicrm_api3_beuchubsync_Sync($params) {
  /*
   * We divide the sync in the folowing phases:
   *   - fetching the data and storing it in temp tables
   *   - sync priorities and organizations
   *   - sync users
   *   - process deleted contacts
   *
   * A phase can be devided in steps.
   *
   * Due to timeouts on the server, we can't execute everything at once.
   * The phase to be executed is stored in a setting.
   * When the job is executed, it executes the phase it finds in the setting.
   * After successful execution, the next phase is stored in the setting.
   */
  $phaseDescriptions = [
    'Fetch the data from HUB + store the data in temp tables',
    'Sync priorities and organizations',
    'Sync users',
    'Process deleted users',
  ];

  $step = '';
  $phase = Civi::settings()->get('beuchub_sync_phase');
  if (!$phase) {
    $phase = 0;
  }
  elseif ($phase > count($phaseDescriptions) - 1) {
    $phase = 0;
  }

  try {
    $step = 'Creating connector';
    $connector = new CRM_Hubsync_Connector();

    if ($phase == 0) {
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
    }
    elseif ($phase == 1) {
      $step = 'Creating synchronizer';
      $synchronizer = new CRM_Hubsync_Synchronizer();

      $step = 'Sync priorities';
      $synchronizer->syncPriorities('', FALSE);

      $step = 'Sync organizations';
      $synchronizer->syncOrganizations('', FALSE);
    }
    elseif ($phase == 2) {
      $step = 'Creating synchronizer';
      $synchronizer = new CRM_Hubsync_Synchronizer();

      $step = 'Sync users';
      $synchronizer->syncUsers('', FALSE);

    }
    elseif ($phase == 3) {
      $step = 'Creating synchronizer';
      $synchronizer = new CRM_Hubsync_Synchronizer();

      $step = 'Sync deleted contacts';
      $synchronizer->processDeletedContacts('', FALSE);
    }

    // success, store the next phase
    $newPhase = ($phase + 1) % count($phaseDescriptions);
    Civi::settings()->set('beuchub_sync_phase', $newPhase);

    return civicrm_api3_create_success('OK', $params, 'beuchubsync', 'Sync');
  }
  catch (Exception $e) {
    throw new API_Exception('BEUC HUB Sync failed in phase "' . $phaseDescriptions[$phase] . '", step "' . $step . '"', 999);
  }
}
