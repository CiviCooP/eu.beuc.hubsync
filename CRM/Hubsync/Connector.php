<?php

class CRM_Hubsync_Connector {
  private $connectionURL = '';

  public function getConnectionURL() {
    if (!$this->connectionURL) {
      $this->createConnectionURL();
    }

    return $this->connectionURL;
  }

  private function createConnectionURL() {
    // get the settings
    $beuchub_endpoint = Civi::settings()->get('beuchub_endpoint');
    $beuchub_private_key = Civi::settings()->get('beuchub_private_key');
    $beuchub_cn = Civi::settings()->get('beuchub_cn');
    $beuchub_ou = Civi::settings()->get('beuchub_ou');

    // make sure the settings are not blank
    if (empty($beuchub_endpoint) || empty($beuchub_private_key) || empty($beuchub_cn) || empty($beuchub_ou)) {
      throw new Exception('BEUC HUB settings are empty. Cannot connect!', 999);
    }

    // build the URL
    $t = time();
    $hash = md5($beuchub_cn . $beuchub_ou . $t . $beuchub_private_key);
    $this->connectionURL = "$beuchub_endpoint?cn=$beuchub_cn&ou=$beuchub_ou&time=$t&hash=$hash";
  }
}
