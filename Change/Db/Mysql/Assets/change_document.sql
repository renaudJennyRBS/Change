CREATE TABLE IF NOT EXISTS `change_document` (
  `document_id` int(11) NOT NULL auto_increment,
  `document_model` varchar(50) NOT NULL default '',
  `tree_name` varchar(50) NULL,
  PRIMARY KEY  (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100000 CHARACTER SET utf8 COLLATE utf8_unicode_ci;
