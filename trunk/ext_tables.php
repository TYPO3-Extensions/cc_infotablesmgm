<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{

	t3lib_extMgm::addModule('tools','txccinfotablesmgmM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');

	t3lib_div::loadTCA('static_territories');
	$TCA['cc_static_territories'] = $TCA['static_territories'];
	$TCA['cc_static_territories']['ctrl']['readOnly'] = 0;

	t3lib_div::loadTCA('static_countries');
	$TCA['cc_static_countries'] = $TCA['static_countries'];
	$TCA['cc_static_countries']['ctrl']['readOnly'] = 0;

	t3lib_div::loadTCA('static_languages');
	$TCA['cc_static_languages'] = $TCA['static_languages'];
	$TCA['cc_static_languages']['ctrl']['readOnly'] = 0;

	t3lib_div::loadTCA('static_country_zones');
	$TCA['cc_static_country_zones'] = $TCA['static_country_zones'];
	$TCA['cc_static_country_zones']['ctrl']['readOnly'] = 0;

	t3lib_div::loadTCA('static_currencies');
	$TCA['cc_static_currencies'] = $TCA['static_currencies'];
	$TCA['cc_static_currencies']['ctrl']['readOnly'] = 0;

}
?>