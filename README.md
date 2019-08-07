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
 
  
