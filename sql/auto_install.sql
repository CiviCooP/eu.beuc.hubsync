CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_priorities` (
  `id` int(10),
  `name` varchar(255),
  `updated_at` varchar(30),
  sync_status varchar(255),
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_orgs` (
  `id` int(10),
  `name` varchar(255),
  `status` varchar(255),
  `email` varchar(255),
  `tel` varchar(255),
  `address` varchar(255),
  `city` varchar(255),
  `postcode` varchar(255),
  `country` varchar(30),
  `updated_at` varchar(30),
  sync_status varchar(255),
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_users` (
  `id` int(10),
  `first_name` varchar(255),
  `last_name` varchar(255),
  `org_id` int(10),
  `email` varchar(255),
  `tel` varchar(255),
  `mobile` varchar(255),
  `deleted` int(10),
  `priorities` varchar(255),
  `updated_at` varchar(30),
  sync_status varchar(255),
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
