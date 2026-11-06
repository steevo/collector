/*
* Creation de la table des collections
*/
CREATE TABLE IF NOT EXISTS `#__collector` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`name` varchar(30) NOT NULL default '',
	`alias` varchar(30) NOT NULL default '',
	`description` text NOT NULL,
	`custom` varchar(30) NOT NULL default '0',
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	`modified_by` int(11) unsigned NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00'
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00'
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	`image` text NOT NULL,
	`access` int(11) unsigned NOT NULL default '0',
	`home` int(1) NOT NULL default '0',
	`metakey` text NOT NULL,
	`metadesc` text NOT NULL,
	`metadata` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des types de champs
*/
CREATE TABLE IF NOT EXISTS `#__collector_fields_type` (
	`id` int(11) NOT NULL auto_increment,
	`type` varchar(30) NOT NULL,
	`state` tinyint(3) NOT NULL default '0',
	`unikable` int(1) NOT NULL default '0',
	`sortable` int(1) NOT NULL default '0',
	`searchable` int(1) NOT NULL default '0',
	`filterable` int(1) NOT NULL default '0',
	`intitle` int(1) NOT NULL default '0',
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des champs
*/
CREATE TABLE IF NOT EXISTS `#__collector_fields` (
	`id` int(11) NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`collection` int(11) NOT NULL,
	`field` varchar(30) NOT NULL,
	`description` text NOT NULL,
	`tablecolumn` text NOT NULL,
	`type` int(11) NOT NULL,
	`ordering` int(11) NOT NULL,
	`state` TINYINT(3) NOT NULL default '0',
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	`created_by` INT(11) NOT NULL default '0',
	`created_by_alias` VARCHAR(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	`modified_by` INT(11) NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00'
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00'
	`checked_out` INT(11) NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	`access` INT(11) NOT NULL default '0',
	`home` int(1) NOT NULL default '0',
	`unik` int(1) NOT NULL default '0',
	`edit` int(1) NOT NULL default '0',
	`listing` int(1) NOT NULL default '0',
	`filter` int(1) NOT NULL default '0',
	`sort` int(1) NOT NULL default '0',
	`required` int(1) NOT NULL default '0',
	`next_sorted_field` varchar(30) NOT NULL default '0',
	`column_width` varchar(30) NOT NULL default '',
	`attribs` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `fk_collection`(`collection`),
	KEY `fk_type`(`type`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des champs predefinis
*/
CREATE TABLE IF NOT EXISTS `#__collector_defined` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(30) NOT NULL default '',
	`alias` varchar(30) NOT NULL default '',
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	`modified_by` int(11) unsigned NOT NULL default '0',
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	`access` INT(11) NOT NULL default '0',
	`width` INT(11) NOT NULL default '0',
	`height` INT(11) NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_access` (`access`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des contenus des champs predefinis
*/
CREATE TABLE IF NOT EXISTS `#__collector_defined_content` (
	`id` int(11) NOT NULL auto_increment,
	`defined` int(11) NOT NULL,
	`parent_id` varchar(30) NOT NULL,
	`level` INT(10) unsigned NOT NULL,
	`path` varchar(30) NOT NULL default '',
	`content` varchar(30) NOT NULL default '',
	`image` text NOT NULL,
	`lft` INT(11) NOT NULL default '0',
	`rgt` INT(11) NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_defined` (`defined`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

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
* Creation de la table des objets des collections
*/
CREATE TABLE IF NOT EXISTS `#__collector_items` (
	`id` int(11) NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`alias` varchar(100) NOT NULL default '',
	`fulltitle` text NOT NULL,
	`collection` int(11) NOT NULL,
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`hits` int(11) NOT NULL default '0',
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	`modified_by` int(11) unsigned NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00'
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00'
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
	`access` int(11) unsigned NOT NULL default '0',
	`to_validate` int(11) NOT NULL DEFAULT '0'
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_collection` (`collection`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des templates
*/
CREATE TABLE IF NOT EXISTS `#__collector_templates` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`alias` varchar(255) NOT NULL,
	`collection` int(11) NOT NULL,
	`client` tinyint(1) NOT NULL default '0',
	`home` tinyint(1) NOT NULL default '0',
	`column` int(11) NOT NULL default '1',
	PRIMARY KEY (`id`),
	KEY `idx_collection` (`collection`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des types de fichiers
*/
CREATE TABLE IF NOT EXISTS `#__collector_files_type` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(30) NOT NULL,
	`text` varchar(30) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des extensions de fichier
*/
CREATE TABLE IF NOT EXISTS `#__collector_files_ext` (
	`id` int(11) NOT NULL auto_increment,
	`ext` varchar(30) NOT NULL,
	`type` int(11) NOT NULL,
	`ico` text NOT NULL,
	`state` TINYINT(3) NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_type` (`type`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des listes utilisateurs
*/
CREATE TABLE IF NOT EXISTS `#__collector_userslists` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`collection` int(11) NOT NULL,
	`name` varchar(30) NOT NULL default '',
	`type` int(11) NOT NULL,
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`access` int(11) unsigned NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_collection` (`collection`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des utilisateurs
*/
CREATE TABLE IF NOT EXISTS `#__collector_userlist` (
	`id` int(11) NOT NULL auto_increment,
	`user` int(11) NOT NULL,
	`userslist` int(11) NOT NULL,
	`access` int(3) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_user` (`user`),
	KEY `idx_userslist` (`userslist`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Creation de la table des objets utilisateurs
*/
CREATE TABLE IF NOT EXISTS `#__collector_usersitems` (
	`id` int(11) NOT NULL auto_increment,
	`itemid` int(11) NOT NULL,
	`userlist` int(11) NOT NULL,
	`note` text NOT NULL,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
	PRIMARY KEY (`id`),
	KEY `idx_userlist` (`userlist`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

/*
* Historique des imports
*/
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

/*
* Détail des objets affectés par chaque import (pour rollback)
*/
CREATE TABLE IF NOT EXISTS `#__collector_import_history_items` (
    `id`             int(11)     NOT NULL AUTO_INCREMENT,
    `import_id`      int(11)     NOT NULL,
    `item_id`        int(11)     NOT NULL,
    `action`         varchar(10) NOT NULL DEFAULT '',  -- 'imported' | 'merged'
    `version_before` int(11)     DEFAULT NULL,         -- historyId avant merge
    `version_after`  int(11)     DEFAULT NULL,         -- historyId après action
	`rolled_back_at` datetime    DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_import_id` (`import_id`),
    KEY `idx_item_id`   (`item_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;