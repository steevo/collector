-- 0.9.7 : date de chaque action d'import, pour repérer les objets modifiés après l'import sans suivi des versions
ALTER TABLE `#__collector_import_history_items` ADD COLUMN `done_at` datetime DEFAULT NULL AFTER `version_after`;
