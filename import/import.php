<?php
/**
 * Instructions:
 * (1) Configure database vars in config.ini
 * (2) Download geoplanet data from http://developer.yahoo.com/geo/geoplanet/data/
 * (3) Add file names to the file variables below
 * (4) Run this script from the command line, i.e. "php import.php"
 * 
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 * @license GNU General Public License
 * Ensure php.ini memory_limit is set to 1GB for handling large arrays
 * Temp tables require 50 GB
 */

//Full path to raw geoplanet files (change to suit your own path)
$files['aliases'] = "/tmp/geoplanet_aliases_7.5.2.tsv";
$files['places'] = "/tmp/geoplanet_places_7.5.2.tsv";
$files['adjacencies'] = "/tmp/geoplanet_adjacencies_7.5.2.tsv";
//==================== Usually no need to edit below this line =================
set_time_limit(0);		  //no timeout
error_reporting(E_ERROR); //runtime error reporting
require_once ('class.geoimport.php');
$importEngine = new geoimport;									
//check files
foreach ($files as $file){
	if (!file_exists($file)){
		echo "Cannot find file ".$file;
		exit;
	}
}
echo "Files Verified\n";
//create database
echo "Creating Data Structure\n";
if (!$importEngine->createDatabase()){exit;}
//import files
echo "Importing Yahoo Geoplanet Data\n";
if (!$importEngine->populatePlaceTypes()){exit;}
if (!$importEngine->importAdjacencies($files['adjacencies'])){exit;}
if (!$importEngine->importPlaces($files['places'])){exit;}
if (!$importEngine->importAliases($files['aliases'])){exit;}
//optimize data
if (!$importEngine->populatePlaces()){exit;}
if (!$importEngine->addPlaceTypeCodes()){exit;}
if (!$importEngine->populatePlaceNames()){exit;}
if (!$importEngine->populateAdjacencies()){exit;}
if (!$importEngine->populateParents()){exit;}
if (!$importEngine->populateChildren()){exit;}
if (!$importEngine->populateAncestors()){exit;}
if (!$importEngine->populateDescendants()){exit;}
echo "Import complete\n";
