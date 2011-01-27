<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$tempTablesDef = array (
	'static_countries' => array (
		'cn_short_en' => 'cn_short_###LANG_ISO_LOWER###',
	),
	'static_country_zones' => array (
		'zn_name_en' => 'zn_name_###LANG_ISO_LOWER###',
	),
	'static_currencies' => array (
		'cu_name_en' => 'cu_name_###LANG_ISO_LOWER###',
		'cu_sub_name_en' => 'cu_sub_name_###LANG_ISO_LOWER###',
	),
	'static_languages' => array (
		'lg_name_en' => 'lg_name_###LANG_ISO_LOWER###',
	),
	'static_territories' => array (
		'tr_name_en' => 'tr_name_###LANG_ISO_LOWER###',
	),
);

foreach ($tempTablesDef as $tempTable => $tempFieldDef) {
	t3lib_div::loadTCA($tempTable);
	foreach ($tempFieldDef as $tempSourceField => $tempDestField) {
		$tempColumns = array();
		$tempColumns[$tempDestField] = $TCA[$tempTable]['columns'][$tempSourceField];
		$tempColumns[$tempDestField]['label'] = 'LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:'.$tempTable.'_item.'.$tempDestField;
		t3lib_extMgm::addTCAcolumns($tempTable, $tempColumns, 1);
		t3lib_extMgm::addToAllTCAtypes($tempTable, $tempDestField, '', 'after:'.$tempSourceField);
	}
}

?>