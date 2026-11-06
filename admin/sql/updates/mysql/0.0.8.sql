ALTER TABLE `#__collector` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_fields_type` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_fields` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_defined` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_defined_content` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_items` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_templates` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_files_type` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_files_ext` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_userslists` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_userlist` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__collector_usersitems` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__collector` CHANGE `created` `created` DATETIME NOT NULL;
ALTER TABLE `#__collector` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;

ALTER TABLE `#__collector_items` CHANGE `created` `created` DATETIME NOT NULL;
ALTER TABLE `#__collector_items` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_items` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_items` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_items` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;

ALTER TABLE `#__collector_fields` CHANGE `created` `created` DATETIME NOT NULL;
ALTER TABLE `#__collector_fields` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_fields` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_fields` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__collector_fields` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;

ALTER TABLE `#__collector` ENGINE=InnoDB;
ALTER TABLE `#__collector_fields_type` ENGINE=InnoDB;
ALTER TABLE `#__collector_fields` ENGINE=InnoDB;
ALTER TABLE `#__collector_defined` ENGINE=InnoDB;
ALTER TABLE `#__collector_defined_content` ENGINE=InnoDB;
ALTER TABLE `#__collector_items` ENGINE=InnoDB;
ALTER TABLE `#__collector_templates` ENGINE=InnoDB;
ALTER TABLE `#__collector_files_type` ENGINE=InnoDB;
ALTER TABLE `#__collector_files_ext` ENGINE=InnoDB;
ALTER TABLE `#__collector_userlist` ENGINE=InnoDB;
ALTER TABLE `#__collector_users` ENGINE=InnoDB;
ALTER TABLE `#__collector_userslists` ENGINE=InnoDB;
ALTER TABLE `#__collector_usersitems` ENGINE=InnoDB;