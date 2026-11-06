CREATE TABLE IF NOT EXISTS `#__collector_import_history` (
    `id`              int(11)      NOT NULL AUTO_INCREMENT,
    `collection`      int(11)      NOT NULL,
    `file`            varchar(255) NOT NULL DEFAULT '',
    `strategy`        varchar(20)  NOT NULL DEFAULT 'unik',
    `imported`        int(11)      NOT NULL DEFAULT 0,
    `merged`          int(11)      NOT NULL DEFAULT 0,
    `skipped`         int(11)      NOT NULL DEFAULT 0,
    `errors`          int(11)      NOT NULL DEFAULT 0,
    `conflicts`       int(11)      NOT NULL DEFAULT 0,
    `multiconflicts`  int(11)      NOT NULL DEFAULT 0,
    `dry_run`         tinyint(1)   NOT NULL DEFAULT 0,
    `created`         datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by`      int(11)      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_collection` (`collection`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__collector_import_history_items` (
    `id`             int(11)     NOT NULL AUTO_INCREMENT,
    `import_id`      int(11)     NOT NULL,
    `item_id`        int(11)     NOT NULL,
    `action`         varchar(10) NOT NULL DEFAULT '',
    `version_before` int(11)     DEFAULT NULL,
    `version_after`  int(11)     DEFAULT NULL,
	`rolled_back_at` datetime    DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_import_id` (`import_id`),
    KEY `idx_item_id`   (`item_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__collector_usersitems` 
	CHANGE `comment` `note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `#__collector_usersitems`
    ADD COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `note`;

/*
 * Nouveautés 0.9.0 initialement ajoutées dans 0.0.8.sql : déplacées ici car 0.0.8.sql
 * a déjà été appliqué sur les sites en 0.8.0 et Joomla ne rejoue pas un script passé.
 */
ALTER TABLE `#__collector_items` ADD `to_validate` int(11) NOT NULL DEFAULT '0' AFTER `access`;

ALTER TABLE `#__collector_defined_content` MODIFY `path` varchar(30) NOT NULL DEFAULT '';

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

/*
 * Listes utilisateurs : colonnes abandonnées dans la 0.9.0
 */
ALTER TABLE `#__collector_userslists`
    DROP COLUMN `alias`,
    DROP COLUMN `description`,
    DROP COLUMN `created`,
    DROP COLUMN `created_by`,
    DROP COLUMN `created_by_alias`,
    DROP COLUMN `modified`,
    DROP COLUMN `modified_by`,
    DROP COLUMN `publish_up`,
    DROP COLUMN `publish_down`,
    DROP COLUMN `checked_out`,
    DROP COLUMN `checked_out_time`;