-- Creates MySQL database structure for geoplanet/woeids
-- Copyright (c) Tyler Bell 2009,2010 tylerwbell[at]gmail[dot]com

--
-- Create geo database
--
CREATE DATABASE IF NOT EXISTS `geo`;

--
-- Create table `cache_names`
--
CREATE TABLE  `geo`.`cache_disambiguate` (
  `q` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'query string',
  `focus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'focus of query as woeid',
  `woeid` int(10) unsigned NOT NULL COMMENT 'Most likely place returned',
  PRIMARY KEY (`q`,`focus`) USING BTREE,
  KEY `focus_idx` (`focus`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cached disambiguation queries from Geoplanet web service'
--
-- Create table `geo_adjacencies`
--
CREATE TABLE `geo_adjacencies` (
  `woeid` int(10) unsigned NOT NULL,
  `adjacencies` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Adjacencies (neighbors) lookup';

--
-- Create table `geo_ancestors`
--
CREATE TABLE `geo_ancestors` (
  `woeid` int(10) unsigned NOT NULL,
  `ancestors` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Ancestors lookup';

--
-- Create table `geo_belongto`
--
CREATE TABLE `geo_belongto` (
  `woeid` int(10) unsigned NOT NULL,
  `belongto` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Belongto lookup';

--
-- Create table `geo_children`
--
CREATE TABLE `geo_children` (
  `woeid` int(10) unsigned NOT NULL,
  `children` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Children lookup';

--
-- Create table `geo_consistof`
--
CREATE TABLE `geo_consistof` (
  `woeid` int(10) unsigned NOT NULL,
  `consistof` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Conssists of lookup';

--
-- Create table `geo_descendants`
--
CREATE TABLE `geo_descendants` (
  `woeid` int(10) unsigned NOT NULL,
  `descendants` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Descendants lookup';

--
-- Create table `geo_parents`
--
CREATE TABLE `geo_parents` (
  `woeid` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`woeid`),
  KEY `parent_idx` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Parents lookup';

--
-- Create table `geo_placenames`
--
CREATE TABLE `geo_placenames` (
  `woeid` int(10) unsigned NOT NULL,
  `pref` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nametype` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `placetype` tinyint(3) NOT NULL,
  
  KEY `woeid_idx` (`woeid`),
  KEY `name_idx` (`name`),
  KEY `nametype_idx` (`nametype`),
  KEY `placetype_idx` (`placetype`),
  KEY `pref_idx` (`pref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Create table `geo_places`
--
CREATE TABLE `geo_places` (
  `woeid` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `contextname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `placetypename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `placetype` tinyint(3) unsigned DEFAULT NULL,
  `centroid_lat` double DEFAULT NULL,
  `centroid_lon` double DEFAULT NULL,
  `bbox_sw_lat` double DEFAULT NULL,
  `bbox_sw_lon` double DEFAULT NULL,
  `bbox_ne_lat` double DEFAULT NULL,
  `bbox_ne_lon` double DEFAULT NULL,
  `country` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`woeid`),
  KEY `country_idx`(`country`),
  KEY `placetype_idx` (`placetype`),
  KEY `centroid_lat_idx` (`centroid_lat`),
  KEY `centroid_lon_idx` (`centroid_lon`),
  KEY `bbox_sw_lat_idx` (`bbox_sw_lat`),
  KEY `bbox_sw_lon_idx` (`bbox_sw_lon`),
  KEY `bbox_ne_lat_idx` (`bbox_ne_lat`),
  KEY `bbox_ne_lon_idx` (`bbox_ne_lon`)
  
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Main places table';

--
-- Create table `geo_placetypes`
--
CREATE TABLE `geo_placetypes` (
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
CREATE TABLE `geo_siblings` (
  `woeid` int(10) unsigned NOT NULL,
  `siblings` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  COMMENT='Siblings lookup';

--
-- Create table `raw_adjacencies`
--
CREATE TABLE `raw_adjacencies` (
  `woeid` int(11) NOT NULL,
  `iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `neighbor` int(11) NOT NULL,
  `neighbor_iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  KEY `woeid_idx` (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';

--
-- Create table `raw_aliases`
-- 
CREATE TABLE `raw_aliases` (
  `woeid` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nametype` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `lang` char(3) COLLATE utf8_unicode_ci NOT NULL,
  KEY `woeid_idx` (`woeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';

--
-- Create table `raw_places`
--
CREATE TABLE `raw_places` (
  `woeid` int(11) NOT NULL,
  `iso` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lang` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `placetype` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `parent` int(11) NOT NULL,
  PRIMARY KEY (`woeid`),
  KEY `placetype_idx` (`placetype`)
  KEY `parent_idx` (`parent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Raw import table; can be deleted when import complete';

--
-- Create Placetypes table and populate with data
--
CREATE TABLE `geo_placetypes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_shortname` (`shortname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- add data
LOCK TABLES `geo_placetypes` WRITE;
INSERT INTO `geo_placetypes` VALUES (6,'Street','A street','Street'),(7,'Town','A populated settlement such as a city, town, village','Town'),(8,'State','One of the primary administrative areas within a country','State'),(9,'County','One of the secondary administrative areas within a country','County'),(10,'Local Administrative Area','One of the tertiary administrative areas within a country','LocalAdmin'),(11,'Postal Code','A partial or full postal code','Zip'),(12,'Country','One of the countries or dependent territories defined by the ISO 3166-1 standard','Country'),(13,'Island','An island','Island'),(14,'Airport','An airport','Airport'),(15,'Drainage','A water feature such as a river, canal, lake, bay, ocean','Drainage'),(16,'Land Feature','A land feature such as a park, mountain, beach','LandFeature'),(17,'Miscellaneous','A uncategorized place','Miscellaneous'),(18,'Nationality','An area affiliated with a nationality','Nationality'),(19,'Supername','An area covering multiple countries','Supername'),(20,'Point of Interest','A point of interest such as a school, hospital, tourist attraction','POI'),(21,'Region','An area covering portions of several countries','Region'),(22,'Suburb','A subdivision of a town such as a suburb or neighborhood','Suburb'),(23,'Sports Team','A sports team','Sports Team'),(24,'Colloquial','A place known by a colloquial name','Colloquial'),(25,'Zone','An area known within a specific context such as MSA or area code','Zone'),(26,'Historical State','A historical primary administrative area within a country','HistoricalState'),(27,'Historical County','A historical secondary administrative area within a country','HistoricalCounty'),(29,'Continent','One of the major land masses on the Earth','Continent'),(31,'Time Zone','An area defined by the Olson standard (tz database)','Timezone'),(32,'Nearby Intersection','An intersection of streets that is nearby to the streets in a query string','Nearby Intersection'),(33,'Estate','A housing development or subdivision known by name','Estate'),(35,'Historical Town','A historical populated settlement that is no longer known by its original name','HistoricalTown'),(36,'Aggregate','An aggregate place','Aggregate'),(37,'Ocean','One of the five major bodies of water on the Earth','Ocean'),(38,'Sea','An area of open water smaller than an ocean','Sea');
UNLOCK TABLES;
