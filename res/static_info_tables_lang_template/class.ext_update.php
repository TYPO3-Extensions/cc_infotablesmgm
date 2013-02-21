<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 René Fritz (r.fritz@colorcube.de)
*  (c) 2005-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Class for updating the db
 */
class ext_update {
	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		$content = '';
		$content.= '<p>Update the Static Info Tables with new language labels.</p>';

		$extPath = t3lib_extMgm::extPath('static_info_tables_###LANG_ISO_LOWER###');
		$fileContent = explode("\n", t3lib_div::getUrl($extPath.'ext_tables_static_update.sql'));

		foreach ($fileContent as $line) {
			$line = trim($line);
			if ($line && preg_match('#^UPDATE#i', $line)) {
				$res = $GLOBALS['TYPO3_DB']->admin_query($line);
			}
		}
		$content .= '<p>Done.</p>';
		return $content;
	}

	function access() {
		return true;
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/static_info_tables_###LANG_ISO_LOWER###/class.ext_update.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/static_info_tables_###LANG_ISO_LOWER###/class.ext_update.php']);
}
?>