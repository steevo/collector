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
ALTER TABLE `#__collector_items` ADD `to_validate` int(11) NOT NULL DEFAULT '0' AFTER `access`;

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
ALTER TABLE `#__collector_userslists` ENGINE=InnoDB;
ALTER TABLE `#__collector_usersitems` ENGINE=InnoDB;

/*
 * Table de persistance des sessions d'import.
 * Une session est identifiée par (collection, created_by) : un seul import
 * en cours par utilisateur et par collection.
 * La session est supprimée en fin d'import ou sur abandon explicite.
 */
CREATE TABLE IF NOT EXISTS `#__collector_import_sessions` (
    `id`          int(11)      NOT NULL AUTO_INCREMENT,
    `collection`  int(11)      NOT NULL,
    `created_by`  int(11)      NOT NULL,
    `file`        varchar(255) NOT NULL DEFAULT '',
    `first_line`  int(11)      NOT NULL DEFAULT 2,
    `mapping`     text         NOT NULL,
    `current_row` int(11)      NOT NULL DEFAULT 0,
    `total_rows`  int(11)      NOT NULL DEFAULT 0,
    `imported`    int(11)      NOT NULL DEFAULT 0,
    `skipped`     int(11)      NOT NULL DEFAULT 0,
    `merged`      int(11)      NOT NULL DEFAULT 0,
    `updated`     datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session` (`collection`, `created_by`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
 * Table de persistance de la configuration d'import par collection.
 * Contrairement à #__collector_import_sessions (état d'un import en cours,
 * supprimé en fin d'import), cette table est permanente : elle conserve
 * le mapping colonnes→champs et le mapping valeurs (define) afin de
 * pré-remplir les étapes 2 et 3 lors de l'import d'un nouveau fichier.
 *
 * Une seule ligne par collection.
 */
CREATE TABLE IF NOT EXISTS `#__collector_import_config` (
    `id`                  int(11)      NOT NULL AUTO_INCREMENT,
    `collection`          int(11)      NOT NULL,
    `col_mapping`         text         NOT NULL COMMENT 'JSON : [tablecolumn => lettre]',
    `value_mapping`       text         NOT NULL COMMENT 'JSON : [tablecolumn => {empty, values:[]}]',
    `first_line`          int(11)      NOT NULL DEFAULT 2,
    `duplicate_strategy`  varchar(20)  NOT NULL DEFAULT 'unik' COMMENT 'unik | composite',
    `composite_fields`    text         NOT NULL COMMENT 'JSON : [tablecolumn, ...]',
    `updated`             datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_collection` (`collection`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;