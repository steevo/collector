/*
* Creation de la table des collections
*/
CREATE TABLE IF NOT EXISTS `#__collector` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`name` varchar(30) NOT NULL default '',
	`alias` varchar(30) NOT NULL default '',
	`description` text NOT NULL default '',
	`custom` varchar(30) NOT NULL default '0',
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`created` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00',
	`modified_by` int(11) unsigned NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00',
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
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
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

/*
* Creation de la table des champs
*/
CREATE TABLE IF NOT EXISTS `#__collector_fields` (
	`id` int(11) NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`collection` int(11) NOT NULL,
	`field` varchar(30) NOT NULL,
	`description` text NOT NULL default '',
	`tablecolumn` text NOT NULL,
	`type` int(11) NOT NULL,
	`ordering` int(11) NOT NULL,
	`state` TINYINT(3) NOT NULL default '0',
	`created` DATETIME NOT NULL default '0000-00-00 00:00:00',
	`created_by` INT(11) NOT NULL default '0',
	`created_by_alias` VARCHAR(255) NOT NULL default '',
	`modified` DATETIME NOT NULL default '0000-00-00 00:00:00',
	`modified_by` INT(11) NOT NULL default '0',
	`publish_up` DATETIME NOT NULL default '0000-00-00 00:00:00',
	`publish_down` DATETIME NOT NULL default '0000-00-00 00:00:00',
	`checked_out` INT(11) NOT NULL default '0',
	`checked_out_time` DATETIME NOT NULL default '0000-00-00 00:00:00',
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
	`attribs` text NOT NULL default '',
	PRIMARY KEY (`id`),
	KEY `fk_collection`(`collection`),
	KEY `fk_type`(`type`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`)
) ENGINE=MyISAM;

/*
* Creation de la table des champs predefinis
*/
CREATE TABLE IF NOT EXISTS `#__collector_defined` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(30) NOT NULL default '',
	`alias` varchar(30) NOT NULL default '',
	`created` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00',
	`modified_by` int(11) unsigned NOT NULL default '0',
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
	`access` INT(11) NOT NULL default '0',
	`width` INT(11) NOT NULL default '0',
	`height` INT(11) NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_access` (`access`)
) ENGINE=MyISAM;

/*
* Creation de la table des contenus des champs predefinis
*/
CREATE TABLE IF NOT EXISTS `#__collector_defined_content` (
	`id` int(11) NOT NULL auto_increment,
	`defined` int(11) NOT NULL,
	`parent_id` varchar(30) NOT NULL,
	`level` INT(10) unsigned NOT NULL,
	`path` varchar(30) NOT NULL,
	`content` varchar(30) NOT NULL default '',
	`image` text NOT NULL,
	`lft` INT(11) NOT NULL default '0',
	`rgt` INT(11) NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_defined` (`defined`)
) ENGINE=MyISAM;

/*
* Creation de la table des objets des collections
*/
CREATE TABLE IF NOT EXISTS `#__collector_items` (
	`id` int(11) NOT NULL auto_increment,
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0',
	`alias` varchar(100) NOT NULL default '',
	`fulltitle` text NOT NULL default '',
	`collection` int(11) NOT NULL,
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`hits` int(11) NOT NULL default '0',
	`created` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00',
	`modified_by` int(11) unsigned NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00',
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
	`access` int(11) unsigned NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_collection` (`collection`)
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

/*
* Creation de la table des types de fichiers
*/
CREATE TABLE IF NOT EXISTS `#__collector_files_type` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(30) NOT NULL,
	`text` varchar(30) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

/*
* Creation de la table des listes utilisateurs
*/
CREATE TABLE IF NOT EXISTS `#__collector_userslists` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`collection` int(11) NOT NULL,
	`type` int(11) NOT NULL,
	`name` varchar(30) NOT NULL default '',
	`alias` varchar(30) NOT NULL default '',
	`description` text NOT NULL default '',
	`state` tinyint(3) NOT NULL default '0',
	`ordering` int(11) NOT NULL,
	`created` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) unsigned NOT NULL default '0',
	`created_by_alias` varchar(255) NOT NULL default '',
	`modified` datetime NOT NULL default '0000-00-00 00:00:00',
	`modified_by` int(11) unsigned NOT NULL default '0',
	`publish_up` datetime NOT NULL default '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL default '0000-00-00 00:00:00',
	`checked_out` int(11) unsigned NOT NULL default '0',
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
	`access` int(11) unsigned NOT NULL default '0',
	PRIMARY KEY (`id`),
	KEY `idx_access` (`access`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_state` (`state`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_collection` (`collection`)
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

/*
* Creation de la table des objets utilisateurs
*/
CREATE TABLE IF NOT EXISTS `#__collector_usersitems` (
	`id` int(11) NOT NULL auto_increment,
	`itemid` int(11) NOT NULL,
	`userlist` int(11) NOT NULL,
	`comment` text NOT NULL default '',
	PRIMARY KEY (`id`),
	KEY `idx_userlist` (`userlist`)
) ENGINE=MyISAM;
