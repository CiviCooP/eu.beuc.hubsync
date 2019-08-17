<?php

use CRM_Hubsync_ExtensionUtil as E;

class CRM_Hubsync_Form_BeucHubSync extends CRM_Core_Form {
  public function buildQuickForm() {
    CRM_Utils_System::setTitle('BEUC HUB Sync');

    // add form elements
    $this->addRadio('action','What do you want to do?', $this->getActions(), [], '<br>', TRUE);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    // settings and status url
    $settingsURL = CRM_Utils_System::url('civicrm/beuchubsync/settings', 'reset=1');
    $statusURL = CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1');
    $this->assign('settingsPage', $settingsURL);
    $this->assign('statusPage', $statusURL);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    $action = $values['action'];
    switch ($action) {
      case 'get':
        $status = $this->getRemoteData();
        CRM_Core_Session::setStatus($status, '', 'success');
        break;
      case 'analyze':
        $this->analyzeData();
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'));
        break;
      case 'sync':
        $this->syncData();
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/beuchubsync/status', 'reset=1'));
        break;
      default:
        CRM_Core_Session::setStatus("Unknown action: $action", '', 'error');
    }

    parent::postProcess();
  }

  public function getRemoteData() {
    $status = '<br>';

    try {
      $connector = new CRM_Hubsync_Connector();
      $fetcher = new CRM_Hubsync_Fetcher($connector->getConnectionURL());

      // get the data
      $status .= 'Getting remote data... ';
      $fetcher->getRemoteData();
      $status .= 'OK<br>';

      // store the data in the temp tables
      $status .= 'Storing priorities... ';
      $fetcher->storeDataPriorities();

      $status .= 'OK<br>Storing organizations... ';
      $fetcher->storeDataOrgs();

      $status .= 'OK<br>Storing users... ';
      $fetcher->storeDataUsers();

      $status .= 'OK<br><br>Data successfully retrieved from the HUB';
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($status . '<br><br>' . $e->getMessage(), 'Error', 'error');
    }

    return $status;
  }

  public function analyzeData() {
    $status = '<br>';

    try {
      $synchronizer = new CRM_Hubsync_Synchronizer(TRUE);
      $synchronizer->syncPriorities();
      $synchronizer->syncOrgs();
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($status . '<br><br>' . $e->getMessage(), 'Error', 'error');
    }
  }

  public function syncData() {
    try {
      $synchronizer = new CRM_Hubsync_Synchronizer(FALSE);
      //$synchronizer->syncPriorities();
      $synchronizer->syncOrgs();
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($e->getFile() . ', line ' . $e->getLine() . '<br><br>' . $e->getMessage(), 'Error', 'error');
    }
  }

  public function getActions() {
    $options = [
      'get' => 'Retrieve the data from the HUB (does not modify your CiviCRM data)',
      'analyze' => 'Analyze the retrieved data (does not modify your CiviCRM data)' . '<br>',
      'sync' => 'Synchronize the retrieved data with CiviCRM (modifies your CiviCRM data!!!)',
    ];
    return $options;
  }

  public function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
