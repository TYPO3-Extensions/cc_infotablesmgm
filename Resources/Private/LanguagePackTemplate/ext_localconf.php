<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['extendingTCA'][] = $_EXTKEY;

?>