<?php

class CRM_Hubsync_Fetcher {
  private $connectionURL = '';
  public $data = [];

  public function __construct($connectionURL) {
    $this->connectionURL = $connectionURL;
  }

  /**
   * Gets the data from the HUB, and stores it in the public property $data
   *
   * @return bool TRUE if the data can be fetched, else FALSE
   * @throws \Exception An excpetion is thrown if the settings are not available
   */
  public function getRemoteData() {
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
      throw new Exception("The returned data is not valid");
    }
    else {
      $this->data = $convertedReturn;
    }

    return TRUE;
  }

  public function storeDataPriorities() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hub_priorities";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hub_priorities (id, name, updated_at) values (%1, %2, %3)";
    foreach ($this->data->priorities as $priority) {
      $sqlParams = [
        1 => [$priority->id, 'Integer'],
        2 => [$priority->name, 'String'],
        3 => [$priority->updated_at, 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  public function storeDataOrgs() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hub_orgs";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hub_orgs (id, name, initials, status, email, tel, address, city, postcode, country, updated_at) values (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11)";
    foreach ($this->data->orgs as $org) {
      $sqlParams = [
        1 => [$org->id, 'Integer'],
        2 => [$org->name . '', 'String'],
        3 => [$org->initials . '', 'String'],
        4 => [$org->status . '', 'String'],
        5 => [$org->email . '', 'String'],
        6 => [$org->address->tel . '', 'String'],
        7 => [$org->address->address . '', 'String'],
        8 => [$org->address->city . '', 'String'],
        9 => [$org->address->postcode . '', 'String'],
        10 => [$org->address->country . '', 'String'],
        11 => [$org->updated_at . '', 'String'],
      ];

      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  public function storeDataUsers() {
    // clear the temp table
    $sql = "TRUNCATE TABLE civicrm_beuc_hub_users";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "insert into civicrm_beuc_hub_users (id, first_name, last_name, org_id, job_title, email, tel, mobile, is_deleted, priorities, updated_at) values (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11)";
    foreach ($this->data->users as $user) {
      $sqlParams = [
        1 => [$user->id, 'Integer'],
        2 => [$user->first_name, 'String'],
        3 => [$user->last_name, 'String'],
        4 => [$user->org_id, 'Integer'],
        5 => [$user->job_title, 'String'],
        6 => [$user->email, 'String'],
        7 => [$user->tel, 'String'],
        8 => [$user->mobile, 'String'],
        9 => [$user->deleted, 'Integer'],
        10 => [implode(',', $user->priorities), 'String'],
        11 => [$user->updated_at, 'String'],
      ];

      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }
}
