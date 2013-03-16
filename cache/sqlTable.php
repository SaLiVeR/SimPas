<?php
$TABLE[] = 'CREATE TABLE IF NOT EXISTS simpas_pastes(
	`id` INT NOT NULL AUTO_INCREMENT,
	`unique_id` VARCHAR(128), 
	`time` INT(128),
	`size` BIGINT,
	`length` BIGINT,
	`syntax` TINYTEXT,
	`content` LONGTEXT,
	`ip_address` VARCHAR(46),
	`raw_content` LONGTEXT,
	`title` VARCHAR(255) DEFAULT \'\',
	`author` VARCHAR(255) DEFAULT \'\',
	PRIMARY KEY(id)
)ENGINE=MyISAM DEFAULT CHARSET = \'utf8\' DEFAULT COLLATE = \'utf8_general_ci\';';
?>