CREATE TABLE IF NOT EXISTS `rest_logs` (
  `id` bigint(20) UNSIGNED NOT NULL auto_increment,
  `controller` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `action` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `fullrequest` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `httpmethod` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `ip` varchar(16) character set utf8 collate utf8_unicode_ci NOT NULL,
  `httpcode` smallint(3) UNSIGNED  NOT NULL,
  `error` varchar(255) character set utf8 collate utf8_unicode_ci NULL,
  `errorcode` smallint(3) UNSIGNED  NULL,
  `requested` datetime NOT NULL,
  `responded` datetime NOT NULL,
  `data_in` text character set utf8 collate utf8_unicode_ci NULL,
  `data_out` text character set utf8 collate utf8_unicode_ci NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);
