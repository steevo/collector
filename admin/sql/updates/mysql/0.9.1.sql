-- Templates de collection (vue liste / vue détaillée) : contenu HTML à balises enregistré en base.
-- L'ancienne gestion par fichiers PHP n'a jamais été branchée : ses colonnes home et column sont supprimées.

ALTER TABLE `#__collector_templates`
	MODIFY `alias` varchar(255) NOT NULL DEFAULT '',
	DROP COLUMN `home`,
	DROP COLUMN `column`,
	ADD `content` mediumtext NOT NULL AFTER `client`,
	ADD `css` mediumtext NOT NULL AFTER `content`,
	ADD `state` tinyint(3) NOT NULL DEFAULT 1 AFTER `css`,
	ADD `created` datetime NULL DEFAULT NULL AFTER `state`,
	ADD `created_by` int(11) NOT NULL DEFAULT 0 AFTER `created`,
	ADD `modified` datetime NULL DEFAULT NULL AFTER `created_by`,
	ADD `modified_by` int(11) NOT NULL DEFAULT 0 AFTER `modified`,
	ADD `checked_out` int(11) NOT NULL DEFAULT 0 AFTER `modified_by`,
	ADD `checked_out_time` datetime NULL DEFAULT NULL AFTER `checked_out`;

ALTER TABLE `#__collector`
	ADD `template_list` int(11) NOT NULL DEFAULT 0 AFTER `custom`,
	ADD `template_item` int(11) NOT NULL DEFAULT 0 AFTER `template_list`;
