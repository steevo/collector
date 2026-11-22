-- 0.9.7 : date de chaque action d'import, pour repérer les objets modifiés après l'import sans suivi des versions
ALTER TABLE `#__collector_import_history_items` ADD COLUMN `done_at` datetime DEFAULT NULL AFTER `version_after`;
-- État d'un import (rapport de simulation compris) et correspondances de valeurs : plus de 64 Ko possibles
ALTER TABLE `#__collector_import_sessions` MODIFY `mapping` mediumtext NOT NULL;
ALTER TABLE `#__collector_import_config` MODIFY `value_mapping` mediumtext NOT NULL;
