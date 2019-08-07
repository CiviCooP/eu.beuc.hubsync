CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_priorities` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `updated_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_orgs` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `postcode` varchar(255) NOT NULL,
  `country` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `civicrm_beuc_hub_users` (
  `id` int(10) unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `org_id` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `deleted` int(10) unsigned NOT NULL,
  `priorities` varchar(255) NOT NULL,
  `updated_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX hub_prio_updated_at (`updated_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
