# eu.beuc.hubsync

Synchronizes contacts from HUB with CiviCRM.

## Connection settings

There is a settings page where you can specify the connection parameters.
See civicrm/beuchubsync/settings

## The Synchronization Process

The synchronization happens in a few phases.

 * Phase 1: getting the data from HUB
 * Phase 2: storing the users, organizations and priorities in a temp table
 * Phase 3: sync'ing the data from the temp tables with CiviCRM

The synchronization can be run manually, or using one or more scheduled jobs.
 
## Techtalk
 
There are 3 helper classes in eu.beuc.hubsync/CRM:
 
 * **Connector**: gets the connection settings and returns the endpoint URL
 * **Fetcher**: gets the data from the HUB and stores it in "temp" tables:
   * civicrm_beuc_hub_priorities
   * civicrm_beuc_hub_orgs
   * civicrm_beuc_hub_users  
* **Synchronizer**: does the hard work of processing the data from the "temp" tables and creates or updates the corresponding groups and contacts in CiviCRM.

The API calls are provided:
 * hubsync.getRemoteData
 * hubsync.synchronize
 
