<?php
/**
 * Instructions:
 * (1) Use geo.sql to create database
 * (2) Download geoplanet data from http://developer.yahoo.com/geo/geoplanet/data/
 * (3) Add file names to the file variables below
 * (4) Run this script from the command line, i.e. "php import.php"
 * Status will be echoed
 * Script takes several hours
 * ensure that mysql has access to a multi-gigabyte temp dir
 * 
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 * @license GNU General Public License
 * Ensure php.ini memory_limit is set to 128MB for handling large arrays of values (64MB not enough)
 * Temp tables require 50 GB
 */

//Path to raw geoplanet files
$aliasFile = "/tmp/geoplanet_aliases_7.5.2.tsv";
$placesFile = "/tmp/geoplanet_places_7.5.2.tsv";
$adjacenciesFile = "/tmp/geoplanet_adjacencies_7.5.2.tsv";
//runtime error reporting
error_reporting(E_ERROR);

//==================== Usually no need to edit below this line =================
set_time_limit(0);																//this script takes some time
echo "Importing Yahoo Geoplanet Data\n";
require_once ('class.geoimport.php');
$importEngine = new geoimport;

/*
if (!$importEngine->importAdjacencies($adjacenciesFile)){exit;}
if (!$importEngine->importPlaces($placesFile)){exit;}
if (!$importEngine->importAliases($aliasFile)){exit;}
if (!$importEngine->populatePlaces()){exit;}
if (!$importEngine->populatePlaceNames()){exit;}
if (!$importEngine->populateAdjacencies()){exit;}
if (!$importEngine->populateParents()){exit;}
if (!$importEngine->populateChildren()){exit;}

*/

if (!$importEngine->populateAncestors()){exit;}

//if (!$importEngine->populateDescendants()){exit;}

