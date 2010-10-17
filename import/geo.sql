-- Creates MySQL database structure for geoplanet/woeids
-- Copyright (c) Tyler Bell 2009,2010 tylerwbell[at]gmail[dot]com

--
-- Designed to be loaded from import script, if used independently, create database then:
-- mysql -u username -p databasename < dumpfile    
--

--
-- Create table `cache_names`
--
CREATE TABLE IF NOT EXISTS `cache_disambiguate` (
  `q` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'query string',
  `focus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'focus of query as woeid',
  `woeid` int(10) unsigned NOT NULL COMMENT 'Most likely place returned',
  PRIMARY KEY (`q`,`focus`) USING BTREE,
  KEY `focus_idx` (`focus`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cached disambiguation queries from Geoplanet web service';
--
-- Create table `geo_adjacencies`
--
CREATE TABLE IF NOT EXISTS `geo_adjacencies` (
  `woeid` int(10) unsigned NOT NULL,
  `adjacencies` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Adjacencies (neighbors) lookup';

--
-- Create table `geo_ancestors`
--
CREATE TABLE IF NOT EXISTS `geo_ancestors` (
  `woeid` int(10) unsigned NOT NULL,
  `ancestors` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Ancestors lookup';

--
-- Create table `geo_belongto`
--
CREATE TABLE IF NOT EXISTS `geo_belongto` (
  `woeid` int(10) unsigned NOT NULL,
  `belongto` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Belongto lookup';

--
-- Create table `geo_children`
--
CREATE TABLE IF NOT EXISTS `geo_children` (
  `woeid` int(10) unsigned NOT NULL,
  `children` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Children lookup';

--
-- Create table `geo_consistof`
--
CREATE TABLE IF NOT EXISTS `geo_consistof` (
  `woeid` int(10) unsigned NOT NULL,
  `consistof` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Conssists of lookup';

--
-- Create table `geo_descendants`
--
CREATE TABLE IF NOT EXISTS `geo_descendants` (
  `woeid` int(10) unsigned NOT NULL,
  `descendants` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Descendants lookup';

--
-- Create table `geo_parents`
--
CREATE TABLE IF NOT EXISTS `geo_parents` (
  `woeid` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`woeid`),
  KEY `parent_idx` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Parents lookup';

--
-- Create table `geo_placenames`
--
CREATE TABLE IF NOT EXISTS  `geo_placenames` (
  `woeid` int(10) unsigned NOT NULL,
  `pref` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nametype` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `placetype` tinyint(3) NOT NULL,
  `lang` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  KEY `woeid_idx` (`woeid`),
  KEY `name_idx` (`name`),
  KEY `nametype_idx` (`nametype`),
  KEY `placetype_idx` (`placetype`),
  KEY `pref_idx` (`pref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='All placenames';
--
-- Create table `geo_places`
--
CREATE TABLE IF NOT EXISTS  `geo_places` (
  `woeid` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `placetypename` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `placetype` tinyint(3) unsigned DEFAULT NULL,
  `centroid_lat` double DEFAULT NULL,
  `centroid_lon` double DEFAULT NULL,
  `bbox_sw_lat` double DEFAULT NULL,
  `bbox_sw_lon` double DEFAULT NULL,
  `bbox_ne_lat` double DEFAULT NULL,
  `bbox_ne_lon` double DEFAULT NULL,
  `country` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`woeid`),
  KEY `placetype_idx` (`placetype`) USING BTREE,
  KEY `centroid_lat_idx` (`centroid_lat`),
  KEY `centroid_lon_idx` (`centroid_lon`),
  KEY `bbox_sw_lat_idx` (`bbox_sw_lat`),
  KEY `bbox_sw_lon_idx` (`bbox_sw_lon`),
  KEY `bbox_ne_lat_idx` (`bbox_ne_lat`),
  KEY `bbox_ne_lon_idx` (`bbox_ne_lon`),
  KEY `country_idx` (`country`),
  KEY `placetypename_idx` (`placetypename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Main places table';

--
-- Create table `geo_placetypes`
--
CREATE TABLE IF NOT EXISTS `geo_placetypes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_shortname` (`shortname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Placetypes lookup';

--
-- Create table `geo_siblings`
--
CREATE TABLE IF NOT EXISTS `geo_siblings` (
  `woeid` int(10) unsigned NOT NULL,
  `siblings` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Siblings lookup';

--
-- Create table `raw_adjacencies`
--
CREATE TABLE IF NOT EXISTS `raw_adjacencies` (
  `woeid` int(11) NOT NULL,
  `iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `neighbor` int(11) NOT NULL,
  `neighbor_iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  KEY `woeid_idx` (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';

--
-- Create table `raw_aliases`
-- 
CREATE TABLE IF NOT EXISTS `raw_aliases` (
  `woeid` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nametype` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `lang` char(3) COLLATE utf8_unicode_ci NOT NULL,
  KEY `woeid_idx` (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';

--
-- Create table `raw_places`
--
CREATE TABLE IF NOT EXISTS `raw_places` (
  `woeid` int(11) NOT NULL,
  `iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lang` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `placetype` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `parent` int(11) NOT NULL,
  PRIMARY KEY (`woeid`),
  KEY `placetype_idx` (`placetype`),
  KEY `parent_idx` (`parent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';


