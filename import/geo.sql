-- Creates MySQL database structure for geoplanet/woeids
-- Copyright (c) Tyler Bell 2009-2011 tylerwbell[at]gmail[dot]com
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
  `country` varchar(2)  DEFAULT NULL,
  KEY `woeid_idx` (`woeid`),
  KEY `name_idx` (`name`),
  KEY `nametype_idx` (`nametype`),
  KEY `placetype_idx` (`placetype`),
  KEY `pref_idx` (`pref`),
  KEY `country_idx` (`country`)
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
-- Create table `cache_geocode`
--
CREATE TABLE IF NOT EXISTS `geo`.`cache_geocode` (
  `md5` char(32)  NOT NULL,
  `query` text  NOT NULL,
  `response` text  NOT NULL,
  `timestamp` timestamp  NOT NULL,
  PRIMARY KEY (`md5`)
)
ENGINE = MyISAM
COMMENT = 'saved geocode content';

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

-- Countries shortcut
CREATE TABLE `geo_countries` (
  `woeid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `alpha2` varchar(2) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2',
  `alpha3` varchar(3) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2',
  PRIMARY KEY (`woeid`),
  KEY `alpha2_ids` (`alpha2`),
  KEY `alpha3_idx` (`alpha3`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Static Country lookup';

--populate countries
INSERT INTO `geo_countries` VALUES (23424739,'Afghanistan','AF','AFG'),(23424742,'Albania','AL','ALB'),(23424740,'Algeria','DZ','DZA'),(23424746,'American Samoa','AS','ASM'),(23424744,'Andorra','AD','AND'),(23424745,'Angola','AO','AGO'),(23424751,'Anguilla','AI','AIA'),(28289409,'Antarctica','AQ','ATA'),(23424737,'Antigua and Barbuda','AG','ATG'),(23424747,'Argentina','AR','ARG'),(23424743,'Armenia','AM','ARM'),(23424736,'Aruba','AW','ABW'),(23424748,'Australia','AU','AUS'),(23424750,'Austria','AT','AUT'),(23424741,'Azerbaijan','AZ','AZE'),(23424758,'The Bahamas','BS','BHS'),(23424753,'Bahrain','BH','BHR'),(23424759,'Bangladesh','BD','BGD'),(23424754,'Barbados','BB','BRB'),(23424765,'Belarus','BY','BLR'),(23424757,'Belgium','BE','BEL'),(23424760,'Belize','BZ','BLZ'),(23424764,'Benin','BJ','BEN'),(23424756,'Bermuda','BM','BMU'),(23424770,'Bhutan','BT','BTN'),(23424762,'Bolivia','BO','BOL'),(23424761,'Bosnia and Herzegovina','BA','BIH'),(23424755,'Botswana','BW','BWA'),(23424768,'Brazil','BR','BRA'),(23424849,'British Indian Ocean Territory','IO','IOT'),(23424983,'British Virgin Islands','VG','VGB'),(23424773,'Brunei','BN','BRN'),(23424771,'Bulgaria','BG','BGR'),(23424978,'Burkina Faso','BF','BFA'),(23424763,'Myanmar','MM','MMR'),(23424774,'Burundi','BI','BDI'),(23424776,'Cambodia','KH','KHM'),(23424785,'Cameroon','CM','CMR'),(23424775,'Canada','CA','CAN'),(23424794,'Cape Verde','CV','CPV'),(23424783,'Cayman Islands','KY','CYM'),(23424792,'Central African Republic','CF','CAF'),(23424777,'Chad','TD','TCD'),(23424782,'Chile','CL','CHL'),(23424781,'China','CN','CHN'),(23424869,'Christmas Island','CX','CXR'),(23424784,'Cocos (Keeling) Islands','CC','CCK'),(23424787,'Colombia','CO','COL'),(23424786,'Comoros','KM','COM'),(23424795,'Cook Islands','CK','COK'),(23424791,'Costa Rica','CR','CRC'),(23424843,'Croatia','HR','HRV'),(23424793,'Cuba','CU','CUB'),(26812346,'Cyprus','CY','CYP'),(23424810,'Czech Republic','CZ','CZE'),(23424779,'Congo','CD','COD'),(23424796,'Denmark','DK','DNK'),(23424797,'Djibouti','DJ','DJI'),(23424798,'Dominica','DM','DMA'),(23424800,'Dominican Republic','DO','DOM'),(23424801,'Ecuador','EC','ECU'),(23424802,'Egypt','EG','EGY'),(23424807,'El Salvador','SV','SLV'),(23424804,'Equatorial Guinea','GQ','GNQ'),(23424806,'Eritrea','ER','ERI'),(23424805,'Estonia','EE','EST'),(23424808,'Ethiopia','ET','ETH'),(23424814,'Falkland Islands','FK','FLK'),(23424816,'Faroe Islands','FO','FRO'),(23424813,'Fiji','FJ','FJI'),(23424812,'Finland','FI','FIN'),(23424819,'France','FR','FRA'),(23424817,'French Polynesia','PF','PYF'),(23424822,'Gabon','GA','GAB'),(23424821,'Gambia','GM','GMB'),(23424823,'Georgia','GE','GEO'),(23424829,'Germany','DE','DEU'),(23424824,'Ghana','GH','GHA'),(23424825,'Gibraltar','GI','GIB'),(23424833,'Greece','GR','GRC'),(23424828,'Greenland','GL','GRL'),(23424826,'Grenada','GD','GRD'),(23424832,'Guam','GU','GUM'),(23424834,'Guatemala','GT','GTM'),(23424835,'Guinea','GN','GIN'),(23424929,'Guinea-Bissau','GW','GNB'),(23424836,'Guyana','GY','GUY'),(23424839,'Haiti','HT','HTI'),(23424986,'Holy See (Vatican City)','VA','VAT'),(23424841,'Honduras','HN','HND'),(24865698,'Hong Kong','HK','HKG'),(23424844,'Hungary','HU','HUN'),(23424845,'Iceland','IS','ISÂ'),(23424848,'India','IN','IND'),(23424846,'Indonesia','ID','IDN'),(23424851,'Iran','IR','IRN'),(23424855,'Iraq','IQ','IRQ'),(23424803,'Ireland','IE','IRL'),(23424852,'Israel','IL','ISR'),(23424853,'Italy','IT','ITA'),(23424854,'Ivory Coast','CI','CIV'),(23424858,'Jamaica','JM','JAM'),(23424856,'Japan','JP','JPN'),(23424860,'Jordan','JO','JOR'),(23424871,'Kazakhstan','KZ','KAZ'),(23424863,'Kenya','KE','KEN'),(23424867,'Kiribati','KI','KIR'),(23424870,'Kuwait','KW','KWT'),(23424864,'Kyrgyzstan','KG','KGZ'),(23424872,'Laos','LA','LAO'),(23424874,'Latvia','LV','LVA'),(23424873,'Lebanon','LB','LBN'),(23424880,'Lesotho','LS','LSO'),(23424876,'Liberia','LR','LBR'),(23424882,'Libya','LY','LBY'),(23424879,'Liechtenstein','LI','LIE'),(23424875,'Lithuania','LT','LTU'),(23424881,'Luxembourg','LU','LUX'),(20070017,'Macau','MO','MAC'),(23424890,'Macedonia','MK','MKD'),(23424883,'Madagascar','MG','MDG'),(23424889,'Malawi','MW','MWI'),(23424901,'Malaysia','MY','MYS'),(23424899,'Maldives','MV','MDV'),(23424891,'Mali','ML','MLI'),(23424897,'Malta','MT','MLT'),(23424932,'Marshall Islands','MH','MHL'),(23424896,'Mauritania','MR','MRT'),(23424894,'Mauritius','MU','MUS'),(23424886,'Mayotte','YT','MYT'),(23424900,'Mexico','MX','MEX'),(23424815,'Micronesia','FM','FSM'),(23424885,'Moldova','MD','MDA'),(23424892,'Monaco','MC','MCO'),(23424887,'Mongolia','MN','MNG'),(20069817,'Montenegro','ME','MNE'),(23424888,'Montserrat','MS','MSR'),(23424893,'Morocco','MA','MAR'),(23424902,'Mozambique','MZ','MOZ'),(23424987,'Namibia','NA','NAM'),(23424912,'Nauru','NR','NRU'),(23424911,'Nepal','NP','NPL'),(23424909,'Netherlands','NL','NLD'),(23424914,'Netherlands Antilles','AN','ANT'),(23424903,'New Caledonia','NC','NCL'),(23424916,'New Zealand','NZ','NZL'),(23424915,'Nicaragua','NI','NIC'),(23424906,'Niger','NE','NER'),(23424908,'Nigeria','NG','NGA'),(23424904,'Niue','NU','NIU'),(23424905,'Norfolk Island','NF','NFK'),(23424865,'North Korea','KP','PRK'),(23424788,'Northern Mariana Islands','MP','MNP'),(23424910,'Norway','NO','NOR'),(23424898,'Oman','OM','OMN'),(23424922,'Pakistan','PK','PAK'),(23424927,'Palau','PW','PLW'),(23424924,'Panama','PA','PAN'),(23424926,'Papua New Guinea','PG','PNG'),(23424917,'Paraguay','PY','PRY'),(23424919,'Peru','PE','PER'),(23424934,'Philippines','PH','PHL'),(23424918,'Pitcairn Islands','PN','PCN'),(23424923,'Poland','PL','POL'),(23424925,'Portugal','PT','PRT'),(23424935,'Puerto Rico','PR','PRI'),(23424930,'Qatar','QA','QAT'),(23424780,'Democratic Republic of Congo','CG','COG'),(23424933,'Romania','RO','ROU'),(23424936,'Russia','RU','RUS'),(23424937,'Rwanda','RW','RWA'),(56042304,'Saint Barthelemy','BL','BLM'),(23424944,'Saint Helena','SH','SHN'),(23424940,'Saint Kitts and Nevis','KN','KNA'),(23424951,'Saint Lucia','LC','LCA'),(56042305,'Saint Martin','MF','MAF'),(23424939,'Saint Pierre and Miquelon','PM','SPM'),(23424981,'Saint Vincent and the Grenadines','VC','VCT'),(23424992,'Samoa','WS','WSM'),(23424947,'San Marino','SM','SMR'),(23424966,'Sao Tome and Principe','ST','STP'),(23424938,'Saudi Arabia','SA','SAU'),(23424943,'Senegal','SN','SEN'),(20069818,'Serbia','RS','SRB'),(23424941,'Seychelles','SC','SYC'),(23424946,'Sierra Leone','SL','SLE'),(23424948,'Singapore','SG','SGP'),(23424877,'Slovakia','SK','SVK'),(23424945,'Slovenia','SI','SVN'),(23424766,'Solomon Islands','SB','SLB'),(23424949,'Somalia','SO','SOM'),(23424942,'South Africa','ZA','ZAF'),(23424868,'South Korea','KR','KOR'),(23424950,'Spain','ES','ESP'),(23424778,'Sri Lanka','LK','LKA'),(23424952,'Sudan','SD','SDN'),(23424913,'Suriname','SR','SUR'),(28289413,'Svalbard','SJ','SJM'),(23424993,'Swaziland','SZ','SWZ'),(23424954,'Sweden','SE','SWE'),(23424957,'Switzerland','CH','CHE'),(23424956,'Syria','SY','SYR'),(23424971,'Taiwan','TW','TWN'),(23424961,'Tajikistan','TJ','TJK'),(23424973,'Tanzania','TZ','TZA'),(23424960,'Thailand','TH','THA'),(23424968,'Timor-Leste','TL','TLS'),(23424965,'Togo','TG','TGO'),(23424963,'Tokelau','TK','TKL'),(23424964,'Tonga','TO','TON'),(23424958,'Trinidad and Tobago','TT','TTO'),(23424967,'Tunisia','TN','TUN'),(23424969,'Turkey','TR','TUR'),(23424972,'Turkmenistan','TM','TKM'),(23424962,'Turks and Caicos Islands','TC','TCA'),(23424970,'Tuvalu','TV','TUV'),(23424974,'Uganda','UG','UGA'),(23424976,'Ukraine','UA','UKR'),(23424738,'United Arab Emirates','AE','ARE'),(23424975,'United Kingdom','GB','GBR'),(23424977,'United States','US','USA'),(23424979,'Uruguay','UY','URY'),(23424985,'US Virgin Islands','VI','VIR'),(23424980,'Uzbekistan','UZ','UZB'),(23424907,'Vanuatu','VU','VUT'),(23424982,'Venezuela','VE','VEN'),(23424984,'Vietnam','VN','VNM'),(23424989,'Wallis and Futuna','WF','WLF'),(23424990,'Western Sahara','EH','ESH'),(23425002,'Yemen','YE','YEM'),(23425003,'Zambia','ZM','ZMB'),(23425004,'Zimbabwe','ZW','ZWE');



