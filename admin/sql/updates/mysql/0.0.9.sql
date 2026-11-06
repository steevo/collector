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