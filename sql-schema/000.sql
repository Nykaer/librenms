DROP TABLE IF EXISTS `component_datasource`;
CREATE TABLE `component_datasource` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `component` int(11) unsigned NOT NULL COMMENT 'id from the component table',
  `updated` int(11) unsigned NOT NULL COMMENT 'when the record was last updated',
  `rrd` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Full RRD Filename with path',
  `ds` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Data Source name',
  `current` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'most recent polled value',
  `last` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'second most recent',
  `15min` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '15 minute average',
  `1hour` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '1 hour average',
  `1day` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '1 day average',
  PRIMARY KEY (`id`),
  KEY `type` (`component`),
  UNIQUE KEY `unique_index` `unique_index` (`component`, `rrd`, `ds`),
  CONSTRAINT `component_ds_summary_ibfk_1` FOREIGN KEY (`component`) REFERENCES `component` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Performance statistic summary information for each DS';
