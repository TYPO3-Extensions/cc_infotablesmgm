<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {

	t3lib_extMgm::addModule('tools','txccinfotablesmgmM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');

	\SJBR\StaticInfoTables\Utility\TcaUtility::loadTCA('static_countries');
	$GLOBALS['TCA']['cc_static_countries'] = $GLOBALS['TCA']['static_countries'];
	$GLOBALS['TCA']['cc_static_countries']['ctrl']['readOnly'] = 0;

	\SJBR\StaticInfoTables\Utility\TcaUtility::loadTCA('static_country_zones');
	$GLOBALS['TCA']['cc_static_country_zones'] = $GLOBALS['TCA']['static_country_zones'];
	$GLOBALS['TCA']['cc_static_country_zones']['ctrl']['readOnly'] = 0;

	\SJBR\StaticInfoTables\Utility\TcaUtility::loadTCA('static_currencies');
	$GLOBALS['TCA']['cc_static_currencies'] = $GLOBALS['TCA']['static_currencies'];
	$GLOBALS['TCA']['cc_static_currencies']['ctrl']['readOnly'] = 0;

	\SJBR\StaticInfoTables\Utility\TcaUtility::loadTCA('static_languages');
	$GLOBALS['TCA']['cc_static_languages'] = $GLOBALS['TCA']['static_languages'];
	$GLOBALS['TCA']['cc_static_languages']['ctrl']['readOnly'] = 0;

	\SJBR\StaticInfoTables\Utility\TcaUtility::loadTCA('static_territories');
	$GLOBALS['TCA']['cc_static_territories'] = $GLOBALS['TCA']['static_territories'];
	$GLOBALS['TCA']['cc_static_territories']['ctrl']['readOnly'] = 0;

}
?>