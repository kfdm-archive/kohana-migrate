CREATE TABLE IF NOT EXISTS `migration_table` (
	`created_on` DATETIME NOT NULL COMMENT  'Date the migration was created on',
	`migrated_on` DATETIME NOT NULL COMMENT  'Date the migration was run on',
	`tag` VARCHAR( 64 ) NOT NULL ,
	`status` VARCHAR( 64 ) NOT NULL ,
	PRIMARY KEY (  `created_on` )
) ENGINE = MYISAM;