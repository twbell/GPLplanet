#GPLPlanet

##INTRODUCTION
GPLplanet: an open source PHP library to assist in employing local instances of Yahoo 
GeoPlanet(tm) in production. 
 
"Yahoo! GeoPlanet is a resource for managing all geo-permanent named places 
on Earth. It provides the geographic developer community with the vocabulary 
and grammar to describe the world's geography in an unequivocal, permanent, 
and language-neutral manner. Developers can geo-enable their applications 
by using GeoPlanet to traverse the global spatial hierarchy, identify the 
geography relevant to their users and their businesses, and in turn, 
unambiguously geotag, geotarget, and geolocate data across the Web" 
http://developer.yahoo.com/geo/geoplanet/

Yahoo makes GeoPlanet available as both a Web service and data download.  
Due to call-per-day restrictions, latency concerns, or the need for offline use, 
small businesses and startups may be either unwilling or unable to create their core geographic 
platforms on Yahoo Web services.   This library uses a local version of 
GeoPlanet to obtain similar functionality, in a local environment, allowing 
developers easy access to GeoPlanet in a PHP class with access to Web services 
where required.  In a nutshell:

* Creates highly optimized (read: pre-cached, not normalized) MySQL geoplanet database
* Imports GeoPlanet TSV
* Provides PHP wrappers for getParents(), getChildren(), disambiguate() etc. methods
* Wraps geocode(), reverseGeocode(), and getElevation() calls (YSQL Web Service only)
* Gets and caches coordinate data for woeids (coordinates not supplied with data dump)
* Provides sample Web service and commandline scripts

These libraries are intended to make a local instance of GeoPlanet more accessible and easier to 
understand before implementing in production. If you are looking for a simple GeoPlanet
Web-service-only wrapper, see Tyler Hall's http://github.com/tylerhall/php-geoplanet/. Also consider
using Chris Heilmann's 'GeoPlanet Explorer' website for an easy, ad-hoc woeid and placename lookup: 
http://isithackday.com/geoplanet-explorer/

Lastly: a reminder that the geoplanet data dump does not contain coordinates, which must be obtained from the GeoPlanet web service directly.  However, gplplanet wraps the GeoPlanet web service transparently.
  
##GETTING STARTED
### Create and Populate Database
```bash
gunzip [path/to/gplplanet]/import/gplplanet.sql.zip
mysql create database geo
mysql -u [username] -p --max_allowed_packet=1GB geo < [path/to/]gplplanet.sql
```

### or Import the Geoplanet TSV files

(see below)

Default database is 'geo'.  You can select any database name, but ensure that it is configured in config.ini

## METHOD EXAMPLES
Require the geoengine class and get an instance thereof:

``` php
require_once('class.geoengine.php');			
$engine = geoengine::getInstance();             //geoengine is a factory singleton
```
Start geoplaneting:

``` php
$engine->getChildren(31278);                    //children of woeid 31278 (Oxford, UK)
$engine->getParent(31278);                    	//parent of woeid 31278
$engine->getElevationByWOEID(2461928);          //elevation of woeid 2461928 (Northfield, MN) -- (uses web service)
$engine->getByName("springfield","UK");         //all places called "Springfield" in UK
$engine->disambiguate("springfield");           //the most likely 'Springfield'
$engine->geocode("1 infinity loop, Cupertino, ca"); //geocodes -- (uses web service)
$engine->reverseGeocode(0.151490,52.145329);    //reverse geocodes -- (uses web service)
$engine->getBbox(727232);                       //bounding box of Amsterdam -- (uses web service)
$engine->filterByType($engine->getDescendants(23424977),7); //all towns in US
$engine->getAdjacencies(2468964);               //all entities surrounding woeid 2468964 (Pasedena, CA)
engine->getGeo(2468964);			//get an object representation of woeid 2468964		
```
## GEO OBJECTS
Geo objects are lightweight entities that share a common geoengine singleton -- basically a factory class -- where the bulk of 
the code (and processing power) lies. They are intended to be employed as handy encapsulations but are not required.  All methods 
return WOEIDs as INT or arrays of INT, which can be instantiated as geo objects.  

``` php
	$engine->getGeo(INT);				//instantiates single woeid as geo object
	$engine->getMultiGeo(ARRAY);		//instantiates multiple woeids as array of geo objects
```	
Geo objects contain mostly identical methods using the same naming convention as the geoengine factory class:

``` php
	$engine->getParent(31278);
```	
serves the same purpose as:

``` php	
	$geo = $engine->getGeo(31278);	
	$geo->getParent();
```	

and is equally efficient.

##USE OF EXTERNAL WEB SERVICES
Some methods call Yahoo and other web services via YQL.  The results of these calls are cached so (for example) if you 
request the Bounding box of an entity, the webservice will not be called if request a second time.  The general idea is 
to seamlessly incorporate the webservice calls only where required.

##COMMAND LINE SCRIPTS
Example command line scripts live in the scripts folder:

```bash
	php children.php 12345	//returns children of woeid 12345 as JSON array
	php get.php 12345	//returns JSON hash representation of woeid 12345
```

##DIR OVERVIEW
* import					*Scripts, class for importing the geoplanet tsv files*
    * import/class.import.php  *Methods for tsv data import*
    * import/import.php		*Procedural script for importing tsv data*
    * import/geo.sql			*SQL script for creating database*
    * import/gplplanet.sql.zip   *SQL dump of geoplanet optimized for gplplanet*
* scripts					*Command line examples*
    * script/children.php		*Getting children of woeid*
    * script/get.php			*Instantiating woeid*
    * (and others...)
* webservice				*Webservice examples*
    * webservice/disambiguate.php *Get most probably place of given name*
    * webservice/children.php     *Get children of woeid*
    * webservice/reversegeocode   *Reverse geocoding*
    * (and others...)
* class.geoengine.php         *Core methods and factory class; instantiate as singleton*
* class.geoservice.php        *Methods wrapping YQL calls; instantiated as singleton, when required, by geoengine*
* class.geo.php               *Geo object representing named-place; instantiated as required by geoengine*
* class.db.php               	*Database object and methods*
* config.ini                  *Configurations incl. database connection*

##REQUIRED LIBS
* PHP 5 with MySQLi
* MySQL

Not tested with version 4 of either one.

##IMPORTING GEOPLANET DATA
1. Add database vars to config.ini
2. Download geoplanet data from http://developer.yahoo.com/geo/geoplanet/data/
3. Assign tsv filenames to the variables in import.php
4. Run import.php from the command line (e.g. "php import.php")

###Import Notes:
* See more detailed notes in import.php
* Import status will be echoed
* Script takes several hours or even days, depending on processor
* Needs disk and memory resources; see import.php for requirements
* Largest array produced at runtime (after import) is just under 18MB (UK descendants)

##TODO
* BelongTo service integration
* YQL oauth implmentation for higher query throughput
* Name search JS code examples for as-you-type lookup
* Experiment with SimpleDB (currently MySQL)
* Reconsider how relationships (descendants etc) are cached.  Now just larger arrays in single field -- not elegant.

##SOURCE
```php
/**
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2012 - Tyler Bell
 * @license GNU General Public License
 */
```
