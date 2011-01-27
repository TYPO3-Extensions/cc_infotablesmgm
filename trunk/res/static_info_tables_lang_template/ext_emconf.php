<?php
########################################################################
# Extension Manager/Repository config file for ext: "static_info_tables_###LANG_ISO_LOWER###"
#
# Auto generated 20-07-2005 12:06
#
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################
$EM_CONF[$_EXTKEY] = Array (
	'title' => 'Static Info Tables (###LANG_ISO_LOWER###)',
	'description' => '###LANG_NAME### (###LANG_ISO_LOWER###) language pack for the Static Info Tables providing localized names for countries, currencies and so on.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.0.0',	// Don't modify this! Managed automatically during upload to repository.
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'static_countries,static_country_zones,static_languages,static_currencies,static_territories',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'René Fritz',
	'author_email' => 'r.fritz@colorcube.de',
	'author_company' => 'Colorcube - digital media lab, www.colorcube.de',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array (
		'depends' => array(
		    'static_info_tables' => '2.2.0-',
		    'php' => '5.2.0-0.0.0',
		    'typo3' => '4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
);
?>