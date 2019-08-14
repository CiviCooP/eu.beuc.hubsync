<?php

use CRM_Hubsync_ExtensionUtil as E;

class CRM_Hubsync_Form_BeucHubSyncSettings extends CRM_Core_Form {
  public function buildQuickForm() {
    CRM_Utils_System::setTitle('BEUC HUB Sync - Settings');

    // add fields
    $this->add('text', 'beuchub_endpoint', 'Endpoint', ['style' => 'width: 300px'], TRUE);
    $this->add('text', 'beuchub_private_key', 'Private Key', ['style' => 'width: 300px'], TRUE);
    $this->add('text', 'beuchub_cn', 'CN', ['style' => 'width: 300px'], TRUE);
    $this->add('text', 'beuchub_ou', 'OU', ['style' => 'width: 300px'], TRUE);
    $this->addYesNo('perform_test', 'Test settings after save?', FALSE);

    // set defaults
    $defaults = [];
    $defaults['beuchub_endpoint'] = Civi::settings()->get('beuchub_endpoint');
    $defaults['beuchub_private_key'] = Civi::settings()->get('beuchub_private_key');
    $defaults['beuchub_cn'] = Civi::settings()->get('beuchub_cn');
    $defaults['beuchub_ou'] = Civi::settings()->get('beuchub_ou');
    $defaults['perform_test'] = 0;
    $this->setDefaults($defaults);


    // add buttons
    $cancelURL = CRM_Utils_System::url('civicrm/beuchubsync', 'reset=1');
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
          'type' => 'cancel',
          'name' => E::ts('Cancel'),
          'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
      ]
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    // get the submitted values
    $values = $this->exportValues();

    // store them as settings
    Civi::settings()->set('beuchub_endpoint', $values['beuchub_endpoint']);
    Civi::settings()->set('beuchub_private_key', $values['beuchub_private_key']);
    Civi::settings()->set('beuchub_cn', $values['beuchub_cn']);
    Civi::settings()->set('beuchub_ou', $values['beuchub_ou']);

    // test the connection?
    if ($values['perform_test']) {
      $this->testConnection();
    }
    else {
      // show success message
      CRM_Core_Session::setStatus('Settings saved!', '', 'success');
    }

    parent::postProcess();
  }

  private function testConnection() {
    try {
      // get the data from the HUB
      $helper = new CRM_Hubsync_Helper();
      $retval = $helper->getRemoteData();
      if ($retval === TRUE) {
        CRM_Core_Session::setStatus('Connection with HUB is OK!', '', 'success');
        CRM_Core_Error::debug('alain', $helper->data);
      }
      else {
        CRM_Core_Session::setStatus('Connection with HUB failed.', '', 'error');
      }
    }
    catch (Excpetion $e) {
      CRM_Core_Session::setStatus($e->getMessage(), 'Connection Error', 'error');
    }
  }

  public function getRenderableElementNames() {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
