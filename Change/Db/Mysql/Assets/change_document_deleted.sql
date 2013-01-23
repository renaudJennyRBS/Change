CREATE TABLE IF NOT EXISTS `change_document_deleted` (
  `document_id` int(11) NOT NULL DEFAULT 0,
  `document_model` varchar(50) NOT NULL default '',
  `deletiondate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datas` mediumtext NULL,
  PRIMARY KEY  (`document_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;