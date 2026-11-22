-- 0.9.7 : date de chaque action d'import, pour repérer les objets modifiés après l'import sans suivi des versions
ALTER TABLE `#__collector_import_history_items` ADD COLUMN `done_at` datetime DEFAULT NULL AFTER `version_after`;
-- État d'un import (rapport de simulation compris) et correspondances de valeurs : plus de 64 Ko possibles
ALTER TABLE `#__collector_import_sessions` MODIFY `mapping` mediumtext NOT NULL;
ALTER TABLE `#__collector_import_config` MODIFY `value_mapping` mediumtext NOT NULL;
-- Valeurs de listes créées par un import (supprimées s'il est annulé et qu'elles ne servent plus)
CREATE TABLE IF NOT EXISTS `#__collector_import_history_values` (
    `id`         int(11)      NOT NULL AUTO_INCREMENT,
    `import_id`  int(11)      NOT NULL,
    `defined`    int(11)      NOT NULL,
    `value_id`   int(11)      NOT NULL,
    `content`    varchar(255) NOT NULL DEFAULT '',
    `created`    datetime     DEFAULT NULL,
    `removed_at` datetime     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_import_id` (`import_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
