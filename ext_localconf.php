<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Configuration of the extension
 *
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 */
if (!defined ('TYPO3_MODE')) {
 	 die ('Access denied.');
}

// Make the extension constraints available when creating language packs
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'ext_emconf.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['constraints'] = $EM_CONF[$_EXTKEY]['constraints'];

if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['tables'])) {
	foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['tables'] as $table => $conf) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['tables']['cc_' . $table] = $conf;
	}
}

?>