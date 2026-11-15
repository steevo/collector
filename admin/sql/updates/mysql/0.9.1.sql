-- Templates de collection (vue liste / vue détaillée) : contenu HTML à balises enregistré en base.
-- L'ancienne gestion par fichiers PHP n'a jamais été branchée : ses colonnes home et column sont supprimées.

ALTER TABLE `#__collector_templates` MODIFY `alias` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__collector_templates` DROP COLUMN `home`;
ALTER TABLE `#__collector_templates` DROP COLUMN `column`;
ALTER TABLE `#__collector_templates` ADD `content` mediumtext NOT NULL AFTER `client`;
ALTER TABLE `#__collector_templates` ADD `css` mediumtext NOT NULL AFTER `content`;
ALTER TABLE `#__collector_templates` ADD `state` tinyint(3) NOT NULL DEFAULT 1 AFTER `css`;
ALTER TABLE `#__collector_templates` ADD `created` datetime NULL DEFAULT NULL AFTER `state`;
ALTER TABLE `#__collector_templates` ADD `created_by` int(11) NOT NULL DEFAULT 0 AFTER `created`;
ALTER TABLE `#__collector_templates` ADD `modified` datetime NULL DEFAULT NULL AFTER `created_by`;
ALTER TABLE `#__collector_templates` ADD `modified_by` int(11) NOT NULL DEFAULT 0 AFTER `modified`;
ALTER TABLE `#__collector_templates` ADD `checked_out` int(11) NOT NULL DEFAULT 0 AFTER `modified_by`;
ALTER TABLE `#__collector_templates` ADD `checked_out_time` datetime NULL DEFAULT NULL AFTER `checked_out`;

ALTER TABLE `#__collector` ADD `template_list` int(11) NOT NULL DEFAULT 0 AFTER `custom`;
ALTER TABLE `#__collector` ADD `template_item` int(11) NOT NULL DEFAULT 0 AFTER `template_list`;
