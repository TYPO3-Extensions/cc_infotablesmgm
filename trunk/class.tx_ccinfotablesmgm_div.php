<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 René Fritz (r.fritz@colorcube.de)
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

class tx_ccinfotablesmgm_div {

	/*************************************
	 *
	 * Common GUI functions
	 *
	 *************************************/

	/**
	 * Returns a linked icon with title from a record
	 *
	 * @param   string      Table name (tt_content,...)
	 * @param   array       Record array
	 * @return  string      Rendered icon
	 */
	function getItemFromRecord($table, $row) {

		$iconAltText = t3lib_BEfunc::getRecordIconAltText($row, $table);

			// Prepend table description
		$iconAltText = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['ctrl']['title']).': '.$iconAltText;

		$elementTitle = t3lib_BEfunc::getRecordTitle($table, $row, 1);

			// Create icon for record
		$elementIcon = t3lib_iconworks::getIconImage($table, $row, $GLOBALS['BACK_PATH'], 'class="c-recicon" align="top" title="'.$iconAltText.'"');

			// Return item with edit link
		return tx_ccinfotablesmgm_div::wrapEditLink($elementIcon.'&nbsp;'.$elementTitle, $table, $row['uid']);
	}

	/**
	 * Wraps an edit link around a string.
	 * Creates a page module link for pages, edit link for other tables.
	 *
	 * @param   string      The string to be wrapped
	 * @param   string      Table name (tt_content,...)
	 * @param   integer     uid of the record
	 * @return  string      Rendered link
	 */
	function wrapEditLink($str, $table, $id) {

		if($table=='pages') {
			$editOnClick = "top.fsMod.recentIds['web']=".$id.";top.goToModule('web_layout',1);";
		} else {
			$params = '&edit['.$table.']['.$id.']=edit';
			$editOnClick = t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH']);
		}
		return '<a href="#" onclick="'.htmlspecialchars($editOnClick).'">'.$str.'</a>';
	}

	/**
	 * Render a table/form to compare two tables data
	 *
	 * @param   string      table name
	 * @return  string      Rendered Table
	 */
	function simpleTable($rows, $table, $checked=false) {

		$content = '';

		$checked = $checked ? ' checked="checked"' : '';

		$outTable = array();
		$tr = 0;

		foreach ($rows as $row) {
			$td = 0;
			$diff = 0;

			if (!$tr) {
				$outTable[$tr][$td++] = '';
				foreach ($row as $key => $value) {
					$outTable[$tr][$td++] = $key;
				}
				$tr++;
			}

			$tRow = array();
			$tRow[$td++] = '<input type="checkbox" name="import['.$table.']['.$row['uid'].']" value="1"'.$checked.'>';
			foreach ($row as $field => $value) {
				$tRow[$td++] = $value;
			}

			$outTable[$tr++] = $tRow;
		}

		$content .= $GLOBALS['SOBE']->doc->table($outTable, $GLOBALS['SOBE']->tableLayout);
		return $content;
	}

	/**
	 * Returns a selector box
	 *
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	array		$menuItems is an array with the menu items for the selector box
	 * @return	string		HTML code for selector box
	 */
	function getFuncMenu($elementName,$currentValue,$menuItems) {
		if (is_array($menuItems)) {

			$options = array();
			foreach($menuItems as $value => $label)	{
				$options[] = '<option value="'.htmlspecialchars($value).'"'.(!strcmp($currentValue,$value)?' selected="selected"':'').'>'.
								t3lib_div::deHSCentities(htmlspecialchars($label)).
								'</option>';
			}
			if (count($options))	{
				return '

					<!-- Function Menu of module -->
					<select name="'.$elementName.'">
						'.implode('
						',$options).'
					</select>
							';
			}
		}
	}

	/*************************************
	 *
	 * Misc TCA and field related functions
	 *
	 *************************************/

	/**
	 * Returns field list with table name prepended
	 *
	 * @param	string		Table name
	 * @param	mixed		Field list as array or comma list as string
	 * @param	boolean		If set the fields are checked if set in TCA
	 * @param	boolean		If set the fields are prepended with table.
	 * @return	string		Comma list of fields with table name prepended
	 */
	function compileFieldList($table, $fields, $checkTCA=TRUE, $prependTableName=TRUE) {

		$fieldList = array();

		$fields = is_array($fields) ? $fields : t3lib_div::trimExplode(',', $fields, 1);

		if ($checkTCA) {
			if (is_array($GLOBALS['TCA'][$table])) {
				$fields = tx_ccinfotablesmgm_div::cleanupFieldList($table, $fields);
			} else {
				$table = NULL;
			}
		}
		if ($table) {
			foreach ($fields as $field) {
				if ($prependTableName) {
					$fieldList[$table.'.'.$field] = $table.'.'.$field;
				} else {
					$fieldList[$field] = $field;
				}
			}
		}
		return implode(',',$fieldList);
	}

	/**
	 * Removes fields from a record row array that are not configured in TCA
	 *
	 * @param	string		Table name
	 * @param	array		Record row
	 * @return	array		Cleaned row
	 */
	function cleanupRecordArray($table, $row) {
		$allowedFields = tx_ccinfotablesmgm_div::getTCAFieldListArray($table);
		foreach ($row as $field => $val) {
			if (!in_array($field, $allowedFields)) {
				unset($row[$field]);
			}
		}
		return $row;
	}

	/**
	 * Removes fields from a field list that are not configured in TCA
	 *
	 * @param	string		Table name
	 * @param	mixed		Field list as array or comma list as string
	 * @return	array		Cleaned field list as array
	 */
	function cleanupFieldList($table, $fields) {
		$allowedFields = tx_ccinfotablesmgm_div::getTCAFieldListArray($table);
		$fields = is_array($fields) ? $fields : t3lib_div::trimExplode(',', $fields, 1);

		foreach ($fields as $key => $field) {
			if (!in_array($field, $allowedFields)) {
				unset($fields[$key]);
			}
		}
		return $fields;
	}

	/**
	 * Returns an array of fields which are configured in TCA for a table.
	 * This includes uid, pid, and ctrl fields.
	 *
	 * @param	string		Table name
	 * @param	boolean		If true not all fields from the TCA columns-array will be used but the ones from the ctrl-array
	 * @param	array		Field list array which should be appended to the list
	 * @return	array		Field list array
	 */
	function getTCAFieldListArray($table, $mainFieldsOnly=FALSE, $addFields=array()) {

		$fieldListArr=array();

		if (!is_array($addFields)) {
			$addFields = t3lib_div::trimExplode(';', $addFields, 1);
		}
		foreach ($addFields as $field)	{
			#if ($GLOBALS['TCA'][$table]['columns'][$field]) {
				$fieldListArr[$field] = $field;
			#}
		}

		if (is_array($GLOBALS['TCA'][$table]))	{
			t3lib_div::loadTCA($table);
			if (!$mainFieldsOnly) {
				foreach($GLOBALS['TCA'][$table]['columns'] as $fieldName => $dummy) {
					$fieldListArr[$fieldName] = $fieldName;
				}
			}
			$fieldListArr['uid'] = 'uid';
			$fieldListArr['pid'] = 'pid';

			$ctrlFields = array ('label','label_alt','type','typeicon_column','tstamp','crdate','cruser_id','sortby','delete','fe_cruser_id','fe_crgroup_id');
			foreach ($ctrlFields as $field)	{
				if ($GLOBALS['TCA'][$table]['ctrl'][$field]) {
					$subFields = t3lib_div::trimExplode(',',$GLOBALS['TCA'][$table]['ctrl'][$field],1);
					foreach ($subFields as $subField)	{
						$fieldListArr[$subField] = $subField;
					}
				}
			}

			if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
				foreach ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] as $field)	{
					if ($field) {
						$fieldListArr[$field] = $field;
					}
				}
			}
		}
		return $fieldListArr;
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/class.tx_ccinfotablesmgm_div.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/class.tx_ccinfotablesmgm_div.php']);
}
?>