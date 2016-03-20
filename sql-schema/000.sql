DROP TABLE IF EXISTS `device_config_global`;
CREATE TABLE `device_config_global` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `attribute` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Attribute, Unique',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Default Value',
  `desc` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'A description of the value',
  `display` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'How does the system display this',
  PRIMARY KEY (`id`),
  KEY `attribute` (`attribute`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Default configuration values for a device';

DROP TABLE IF EXISTS `device_config_group`;
CREATE TABLE `device_config_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `group` int(11) unsigned NOT NULL COMMENT 'ID of group',
  `attribute` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Attribute, Unique',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Default Value',
  PRIMARY KEY (`id`),
  KEY `attribute` (`attribute`),
  CONSTRAINT `device_config_group_ibfk_1` FOREIGN KEY (`attribute`) REFERENCES `device_config_global` (`attribute`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Group configuration values for a device';

DROP TABLE IF EXISTS `device_config_local`;
CREATE TABLE `device_config_local` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID for each entry',
  `device` int(11) unsigned NOT NULL COMMENT 'ID of device',
  `attribute` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Attribute, Unique',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Default Value',
  PRIMARY KEY (`id`),
  KEY `attribute` (`attribute`),
  CONSTRAINT `device_config_local_ibfk_1` FOREIGN KEY (`attribute`) REFERENCES `device_config_global` (`attribute`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Local configuration values for a device';

