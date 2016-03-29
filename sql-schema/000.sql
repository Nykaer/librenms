CREATE TABLE `component_type` (
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'name from the component_type table',
  PRIMARY KEY (`type`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ID Table for components';

DROP TABLE IF EXISTS `component_ds_global`;
CREATE TABLE `component_ds_global` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'type from the component type table',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Data Source name',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  CONSTRAINT `component_ds_global_ibfk_1` FOREIGN KEY (`type`) REFERENCES `component_type` (`type`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Default DS values for a component type';
DROP TABLE IF EXISTS `component_ds_local`;
CREATE TABLE `component_ds_device` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'type from the component type table',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Data Source name',
  `component` int(11) NOT NULL COMMENT 'id from component table',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  CONSTRAINT `component_ds_local_ibfk_1` FOREIGN KEY (`type`) REFERENCES `component_type` (`type`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `component_ds_local_ibfk_2` FOREIGN KEY (`name`) REFERENCES `component_ds_global` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Device Level DS values for a component type';

