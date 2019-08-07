<?php

class CRM_Hubsync_Helper {
  private $connectionURL = '';
  public $data = [];

  /**
   * Gets the data from the HUB, and stores it in the public property $data
   *
   * @return bool TRUE if the data can be fetched, else FALSE
   * @throws \Exception An excpetion is thrown if the settings are not available
   */
  public function getRemoteData() {
    if ($this->connectionURL == '') {
      $this->createConnectionURL();
    }

    // connect to the HUB
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->connectionURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $curlReturn = curl_exec($ch);
    curl_close($ch);

    // convert the json return string
    $convertedReturn = json_decode($curlReturn);

    if ($convertedReturn === NULL) {
      // cannot decode the returned string
      return FALSE;
    }
    else {
      $this->data = $convertedReturn;
    }

    return TRUE;
  }

  public function storeDataPriorities() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hup_priorities";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hup_priorities (id, name, updated_at) values (%1, %2, %3)";
    foreach ($this->data['priorities'] as $priority) {
      $sqlParams = [
        [$priority->id, 'Integer'],
        [$priority->name, 'String'],
        [$priority->updated_at, 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  public function storeDataOrgs() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hub_orgs";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hub_orgs (id, name, status, email, tel, address, city, postcode, country, updated_at) values (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10)";
    foreach ($this->data['orgs'] as $org) {
      $sqlParams = [
        [$org->id, 'Integer'],
        [$org->name, 'String'],
        [$org->status, 'String'],
        [$org->email, 'String'],
        [$org->tel, 'String'],
        [$org->address, 'String'],
        [$org->city, 'String'],
        [$org->postcode, 'String'],
        [$org->country, 'String'],
        [$org->updated_at, 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  public function storeDataUsers() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hub_users";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hub_orgs (id, first_name, last_name, org_id, email, tel, mobile, deleted, priorities, updated_at) values (%1, %2, %3)";
    foreach ($this->data['users'] as $user) {
      $sqlParams = [
        [$user->id, 'Integer'],
        [$user->first_name, 'String'],
        [$user->last_name, 'String'],
        [$user->org_id, 'String'],
        [$user->email, 'String'],
        [$user->tel, 'String'],
        [$user->mobile, 'String'],
        [$user->deleted, 'String'],
        [implode(',', $user->priorities), 'String'],
        [$user->updated_at, 'String'],
      ];

      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
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
