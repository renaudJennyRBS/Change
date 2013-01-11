CREATE TABLE IF NOT EXISTS `change_document_metas` (
  `document_id` int(11) NOT NULL DEFAULT 0,
  `metas` mediumtext NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`document_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;