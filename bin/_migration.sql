CREATE TABLE IF NOT EXISTS `migration_table` (
	`created_on` DATETIME NOT NULL COMMENT  'Date the migration was created on',
	`migrated_on` DATETIME NOT NULL COMMENT  'Date the migration was run on',
	`class` VARCHAR( 64 ) NOT NULL COMMENT 'Migration''s class name for reference',
	`status` VARCHAR( 64 ) NOT NULL ,
	PRIMARY KEY (  `created_on` )
) ENGINE = MYISAM;