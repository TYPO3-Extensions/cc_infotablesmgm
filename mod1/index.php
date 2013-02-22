<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2011 René Fritz (r.fritz@colorcube.de)
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
 * Module 'Static Info Tables Manager' for the 'cc_infotablesmgm' extension.
 *
 * @author	Ren� Fritz <r.fritz@colorcube.de>
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($GLOBALS['BACK_PATH'].'init.php');
require ($GLOBALS['BACK_PATH'].'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:cc_infotablesmgm/mod1/locallang.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$GLOBALS['BE_USER']->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once (t3lib_extMgm::extPath('cc_infotablesmgm').'class.tx_ccinfotablesmgm_div.php');

if (!function_exists('array_diff_key')) {
	/**
	 * Computes the difference of arrays using keys for comparison
	 *
	 * @param    array    $valuesBase            Base elements for comparison, associative
	 * @param    array    $valuesComp[,..]    Comparison elements, associative
	 *
	 * @param    array                        Elements, not existing in comparison element, associative
	 */
	function array_diff_key() {
		$argCount  = func_num_args();
		$argValues  = func_get_args();
		$valuesDiff = array();
		
		if ($argCount < 2) {
			return false;
		}
		
		foreach ($argValues as $argParam) {
			if (!is_array($argParam)) {
				return false;
			}
		}
		
		foreach ($argValues[0] as $valueKey => $valueData) {
			for ($i = 1; $i < $argCount; $i++) {
				if (isset($argValues[$i][$valueKey])) {
					continue 2;
				}
			}
			$valuesDiff[$valueKey] = $valueData;
		}
		return $valuesDiff;
	}
}

class tx_ccinfotablesmgm_module1 extends t3lib_SCbase {

	var $index = array(
			'static_territories' =>  array('tr_iso_nr'),
			'static_countries' =>  array('cn_iso_2'),
			'static_country_zones' =>  array('zn_country_iso_2', 'zn_code'),
			'static_currencies' =>  array('cu_iso_3'),
			'static_languages' =>  array('lg_iso_2', 'lg_country_iso_2'),
		);
	
	
	var $langFields = array (
			'static_countries' => array (
				'cn_short_en' => 'cn_short_###LANG_ISO_LOWER###',
			),
			'static_country_zones' => array (
				'zn_name_local' => 'zn_name_###LANG_ISO_LOWER###',
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


	var $tableLayout = array (
			'table' => array ('<table border="0" cellpadding="1" cellspacing="1">', '</table>'),
			'0' => array (
				'tr' => array('<tr class="bgColor5">','</tr>'),
				'defCol' => array('<td valign="top"><strong>','</strong></td>')
			),
			'defRowEven' => array (
				'tr' => array('<tr class="bgColor3-20">','</tr>'),
				'defCol' => array('<td valign="top">','</td>')
			),
			'defRowOdd' => array (
				'tr' => array('<tr class="bgColor4">','</tr>'),
				'defCol' => array('<td valign="top">','</td>')
			),
		);


	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		
		$this->MOD_MENU = array (
			'function' => array (
				'info' => $GLOBALS['LANG']->getLL('info'),
				'display' => $GLOBALS['LANG']->getLL('display'),
				'compare' => $GLOBALS['LANG']->getLL('compare'),
				'-' => '------------------------',
				'add_labels' => $GLOBALS['LANG']->getLL('add_labels'),
				't3func' => $GLOBALS['LANG']->getLL('t3func'),
				'--' => '------------------------',
				'import' => $GLOBALS['LANG']->getLL('import'),
				'export' => $GLOBALS['LANG']->getLL('exportStatic'),
				'check_out_in' => $GLOBALS['LANG']->getLL('move'),
			),
			'table' => array (
				'static_territories' =>  $GLOBALS['LANG']->sl($GLOBALS['TCA']['static_territories']['ctrl']['title']),
				'static_countries' =>  $GLOBALS['LANG']->sl($GLOBALS['TCA']['static_countries']['ctrl']['title']),
				'static_country_zones' =>  $GLOBALS['LANG']->sl($GLOBALS['TCA']['static_country_zones']['ctrl']['title']),
				'static_languages' =>  $GLOBALS['LANG']->sl($GLOBALS['TCA']['static_languages']['ctrl']['title']),
				'static_currencies' =>  $GLOBALS['LANG']->sl($GLOBALS['TCA']['static_currencies']['ctrl']['title']),
			),
			'selectedTable' => '',
			'showDiffOnly' => 1,
			'useUidAsIndex' => 0,
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id))	{

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('bigDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->form='<form action="" method="post">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';


			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);

			$icon = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/refresh_n.gif', 'width="14" height="14"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.reload',1).'" class="absmiddle" alt="" />';
			$headerSection = '<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('unique' => uniqid('')))).'">'.$icon.'</a>';
			$this->content .= $this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content .= $this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
				$this->content .= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->spacer(10);
		}
	}


	/**
	 * Prints out the module HTML
	 */
	function printContent()	{

		$this->content .= $this->doc->endPage();
		echo $this->content;
	}


	/**
	 * Generates the module content
	 */
	function moduleContent()	{

		switch((string)$this->MOD_SETTINGS['function'])	{

			case 'display':
				$this->content .= $this->renderDisplay();
			break;

			case 't3func':
				$this->content .= $this->renderTypo3Func();
			break;

			case 'check_out_in':
				$this->content .= $this->renderCheckOutIn();
			break;

			case 'compare':
				$this->content .= $this->renderCompare();
			break;

			case 'import':
				$this->cldrBaseFolder = t3lib_extMgm::extPath('cc_infotablesmgm').'data/cldr-1.3/';
				$this->content .= $this->renderImport();
			break;

			case 'export':
				$this->content .= $this->renderExport();
				$this->content .= $this->doc->spacer(10);
				$this->content .= $this->renderCreateLangpack();
			break;

			case 'add_labels':
				$this->content .= $this->renderAddLabels();
			break;

			case 'info':
			default:
				$this->content .= $this->renderInfo();
			break;
		}
	}

	/**
	 * renderInfo
	 */
	function renderInfo () {

		$content = '';
		$content .= '</form><form action="" method="post">';
		$content .= 'Current edit tables with some example data:';

		$tables = array_keys($this->MOD_MENU['table']);

		foreach ($tables as $table) {
			$tableOrig = $table;
			$table = 'cc_'.$table;
			t3lib_div::loadTCA($table);


			$tableInfo = $GLOBALS['TYPO3_DB']->admin_get_fields($tableOrig);


			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) as count', $table, '');
			$cnt = $rows[0]['count'];

			$outTable = array();

			$outTable[0][0] = 'fields:';
			$outTable[1][0] = 'example:';
			$outTable[2][0] = 'max strlen:';

			$tr = 0;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '1'.t3lib_BEfunc::deleteClause($table), '', 'RAND()', '1');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				unset($row['pid']);

				$td = 1;

				if (!$tr) {
					foreach ($row as $key => $value) {
						$outTable[$tr][$td++] = str_replace('_', ' ', $key);
					}
					$tr++;
				}

				foreach ($row as $field => $value) {
					$outTable[$tr][$td] = $value;

						// get longest entries to see if the table definition matches
					if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['_is_string']) {
							// LENGTH measures chars as bytes - that's what we want: bytes
						$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('LENGTH('.$field.') as length, uid', $table, '', 'uid', 'length DESC', '1');
						$length = $rows[0]['length'];
						$uid = $rows[0]['uid'];

							// get field length
						preg_match('#\(([0-9]+)\)#', $tableInfo[$field]['Type'], $matches='');
						$max_length = intval($matches[1]);

						if($length>=$max_length) {
							$outTable[$tr+1][$td] = '<strong style="color:red">'.$length.'&nbsp;['.$max_length.']<br />uid:'.$uid.'</strong>';
						} elseif(($length+5)>=$max_length) {
							$outTable[$tr+1][$td] = '<strong>'.$length.'&nbsp;['.$max_length.']<br />uid:'.$uid.'</strong>';
						} else {
							$outTable[$tr+1][$td] = $length.' ['.$max_length.']<br />uid:'.$uid;
						}
					} else {
						$outTable[$tr+1][$td] = '';
					}
					$td++;
				}
				$tr++;
			}

			$content .= '<h4>'.$table.' ('.$cnt.' rows)</h4>';
			$content .= $this->doc->table($outTable, $this->tableLayout);
			$content .= $this->doc->spacer(10);
		}

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($this->MOD_MENU['function']['info'],$content,0,1);
	}

	/**
	 * renderDisplay
	 */
	function renderDisplay () {

		$content = '';

		$content .= '</form><form action="" method="post">';

		$content .= t3lib_BEfunc::getFuncMenu($this->id, 'SET[table]', $this->MOD_SETTINGS['table'], $this->MOD_MENU['table']);
		$content .=$this->doc->spacer(5);

		$tables = array();
		if ($this->MOD_SETTINGS['selectedTable'] == 'all') {
			$tables = array_keys($this->MOD_MENU['table']);
		} else {
			$tables[] = $this->MOD_SETTINGS['table'];
		}


		foreach ($tables as $table) {
			$table = 'cc_'.$table;

			$outTable = array();
			$tr = 0;

			$cnt = 0;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '1'.t3lib_BEfunc::deleteClause($table));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				unset($row['pid']);

				$cnt++;

				$td = 0;

				if (!$tr) {
					$outTable[$tr][$td++] = '';
					foreach ($row as $key => $value) {
						$outTable[$tr][$td++] = str_replace('_', ' ', $key);
					}
					$tr++;
				}

				$outTable[$tr][$td++] = tx_ccinfotablesmgm_div::getItemFromRecord($table, $row);
				foreach ($row as $field => $value) {
//					if(strlen($value) > 2) {
//						$outTable[$tr][$td++] = tx_ccinfotablesmgm_div::wrapEditLink(htmlspecialchars($value), $table, $row['uid']);
//					} else {
						$outTable[$tr][$td++] = htmlspecialchars($value);
//					}
				}
				$tr++;
			}
			$content .= '<h4>'.$table.' ('.$cnt.' rows)</h4>';
			$content .= $this->doc->table($outTable, $this->tableLayout);
			$content .= $this->doc->spacer(10);
		}

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($this->MOD_MENU['function']['display'],$content,0,1);
	}

	/**
	 *
	 */
	function update_lg_typo3($iso2typo) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_static_languages', '', array('lg_typo3' => ''));
		foreach ($iso2typo as $isoKey => $t3Key) {
			$row = array('lg_typo3' => $t3Key);
			if(strlen($isoKey)==2) {
				$isoKey = strtoupper($isoKey);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_static_languages', 'lg_iso_2='.$GLOBALS['TYPO3_DB']->fullQuoteStr($isoKey, 'cc_static_languages'), $row);
			} else {
				// not yet supported, eg: 'zh_CN'
			}
		}
	}

	/**
	 *
	 */
	function get_iso2typo() {
		
		$csconv = t3lib_div::makeInstance('t3lib_cs');
		$isoArray = $csconv->isoArray;
		$charSetArray = $csconv->charSetArray;
		ksort($charSetArray);
		
		$iso2typo = array();
		foreach ($charSetArray as $t3Key => $isoKey) {
			if (!$isoArray[$t3Key]) {
				$iso2typo[$t3Key] = $t3Key;
			} else {
				$iso2typo[$isoArray[$t3Key]] = $t3Key;
			}
		}
		ksort($iso2typo);

		return $iso2typo;
	}

	/**
	 * renderTypo3Func
	 */
	function renderTypo3Func () {
		
		$content = '';
		$content .= '</form><form action="" method="post">';
		
		$csconv = t3lib_div::makeInstance('t3lib_cs');
		ksort($csconv->isoArray);
		$iso2typo = $this->get_iso2typo();
		
		if (t3lib_div::_GP('update')) {
			$this->update_lg_typo3($iso2typo);
		}

		$iso2typoDB = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('lg_iso_2,lg_country_iso_2,lg_typo3', 'cc_static_languages', 'lg_typo3!=\'\''.t3lib_BEfunc::deleteClause('static_languages'));
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$iso2typoDB[$row['lg_iso_2'].($row['lg_country_iso_2']?'_'.strtoupper($row['lg_country_iso_2']):'')] = $row['lg_typo3'];
		}
		ksort($iso2typoDB);

		//$renderedIsoArray = 'Count t3lib_cs::isoArray: '.count($csconv->isoArray).t3lib_utility_Debug::viewArray($csconv->isoArray);
		$renderedIso2typo = 'Count iso2typo (t3lib_cs): '.count($iso2typo) . t3lib_utility_Debug::viewArray($iso2typo);
		$renderedTypo2iso = 'Count iso2typo (static_languages): '.count($iso2typoDB)  . t3lib_utility_Debug::viewArray($iso2typoDB);

		$differs = array();

		$renderedDiff = 'Diff:';
		$arrDiff = array_diff($iso2typo, $iso2typoDB);
		$differs = array_merge($differs, array_keys($arrDiff));
		$renderedDiff.= '<br />' . t3lib_utility_Debug::viewArray($arrDiff);
		$arrDiff = array_diff($iso2typoDB, $iso2typo);
		$differs = array_merge($differs, array_keys($arrDiff));
		$renderedDiff.= '<br />' . t3lib_utility_Debug::viewArray($arrDiff);
		$arrDiff = array_diff_key($iso2typo, $iso2typoDB);
		$differs = array_merge($differs, array_keys($arrDiff));
		$renderedDiff.= '<br />' . t3lib_utility_Debug::viewArray($arrDiff);
		$arrDiff = array_diff_key($iso2typoDB, $iso2typo);
		$differs = array_merge($differs, array_keys($arrDiff));
		$renderedDiff.= '<br />' . t3lib_utility_Debug::viewArray($arrDiff);

		$content .= '<br /><input type="submit" name="update" value="Update DB" />';
		$content .= '<br /><br />';

		$content .= '<table cellpadding="10"><tr>
			<!--<td valign="top">'.$renderedIsoArray.'</td>-->
			<td valign="top">'.$renderedIso2typo.'</td>
			<td valign="top">'.$renderedTypo2iso.'</td>
			<!--<td valign="top">'.$renderedDiff.'</td>-->
			</tr></table>';

		$iso2typoArrayString = '';
		$iso2typoArrayString .= '$iso2typo = array(';
		$iso2typoArrayString .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;'default' => 'en',";
		foreach ($iso2typoDB as $lg_iso_2 => $lg_typo3) {
			$iso2typoArrayString .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;'".$lg_typo3."' => '".$lg_iso_2."',";
		}
		$iso2typoArrayString .= '<br/>);';

		//$content .= '<br />'.$iso2typoArrayString;

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($this->MOD_MENU['function']['t3func'],$content,0,1);
	}

	/**
	 * renderAddLabels
	 */
	function renderAddLabels () {

		$iso2typo = $this->get_iso2typo ();

		$content = '';
		$content .= '</form><form action="" method="post">';

		if(t3lib_div::_GP('create') && $iso_code = t3lib_div::_GP('iso_code')) {

			$content .=  $this->addLabels(strtolower($iso_code));
			$content .= '<br />Done.';

		}  elseif(t3lib_div::_GP('create_all')) {

			foreach ($iso2typo as $iso_code => $t3code) {
				$content .=  $this->addLabels(strtolower($iso_code));
			}
			$content .= '<br /> ... language labels added.';

		} else {

			$content .= '<label for="iso_code">' . $GLOBALS['LANG']->getLL('languageISOCode') . '</label> ' . '<input id="iso_code" type="input" name="iso_code" value="" /> ' . $GLOBALS['LANG']->getLL('ISOCodeExample');
			$content .= '<br /><br /><input type="submit" name="create" value="'.$GLOBALS['LANG']->getLL('add_labels').'" />';

			$content .= '<br /><br /><br />';
			$content .= '<div style="width:40%">'.implode(', ', array_keys($iso2typo)).'</div>';
			$content .= '<br /><input type="submit" name="create_all" value="'.$GLOBALS['LANG']->getLL('add_all_labels').'" />';
		}

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('add_labels'),$content,0,1);
	}

	/**
	 *
	 */
	function addLabels ($iso_code) {

		$content = '';

		$tables = array_keys($this->index);

		foreach ($tables as $table) {
			$tableOrig = $table;
			$tableLocal = 'cc_'.$table;

			$tableInfo = $GLOBALS['TYPO3_DB']->admin_get_fields($tableOrig);

			foreach($tableInfo as $field => $fieldInfo) {
				if ($field=='cn_official_name_en') { continue; }
				if (preg_match('#_en$#', $field, $matches='')) {

						// make new field name
					$fieldNew = preg_replace('#_en$#', '_'.$iso_code, $field);
					
					if($tableInfo[$fieldNew]) {
						$content .= '<br />'.htmlspecialchars($fieldNew).' already exists.';
					} else {
							// get field length
						preg_match('#\(([0-9]+)\)#', $fieldInfo['Type'], $matches='');
						$fieldLength = intval($matches[1]);
						
						$query = 'ALTER TABLE '.$tableOrig.' ADD '.$fieldNew.' varchar('.$fieldLength.') DEFAULT \'\' NOT NULL;';
						$res = $GLOBALS['TYPO3_DB']->admin_query($query);
						
						$content .= '<br />'.htmlspecialchars($query);
						
						$query = 'ALTER TABLE '.$tableLocal.' ADD '.$fieldNew.' varchar(99) DEFAULT \'\' NOT NULL;';
						$res = $GLOBALS['TYPO3_DB']->admin_query($query);
					}
				}
			}
		}
		return $content;
	}

	/**
	 *
	 */
	function createLangpack($iso_code) {

		$content = array();

		$iso_code_lower = strtolower($iso_code);
		$iso_code_upper = strtoupper($iso_code);

		$sourcePath = t3lib_extMgm::extPath('cc_infotablesmgm').'res/static_info_tables_lang_template/';
		$sourceFiles = t3lib_div::getFilesInDir($sourcePath);
		$destExtKey = 'static_info_tables_'.$iso_code_lower;
		$destPath = PATH_site.'typo3conf/ext/'.$destExtKey.'/';
		
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('lg_name_en', 'cc_static_languages', 'lg_iso_2='.$GLOBALS['TYPO3_DB']->fullQuoteStr(strtoupper($iso_code), 'cc_static_languages'));
		$row = current($row);
		
		$xmlString = t3lib_div::getUrl(t3lib_extMgm::extPath('static_info_tables').'locallang_db.xml');
		$xmlContent = t3lib_div::xml2array($xmlString);
		$tcaLabels = '';
		foreach ($xmlContent['data'] as $langKey => $labelArr) {
			$tcaLabels .= "\n	" . '<languageKey index="' . $langKey . '" type="array">';
			foreach ($labelArr as $labelkey => $label) {
				if(substr($labelkey, -3)=='_en') {
					$labelkey = substr($labelkey, 0, -2).$iso_code_lower;
					$label = str_replace('(EN)', '('.$iso_code_upper.')', $label);
					$tcaLabels .= "\n		" .'<label index="'. $labelkey . '">' . $label . '</label>';
				}
			}
			$tcaLabels .= "\n	" . '</languageKey>';
		}
		
		$updateQueries = '';
		$tables = array_keys($this->index);
		foreach ($tables as $table) {
			$tableFields = $this->getTableFields('cc_'.$table);
			$exportFields = array();
			foreach($tableFields as $field) {
				if (preg_match('#_'.$iso_code_lower.'$#', $field, $matches='')) {
					$exportFields[] = $field;
				}
			}
			$updateQueries .= '## '.$table."\n";
			if (count($exportFields)) {
				$exportFields = array_merge($exportFields, $this->index[$table]);
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(implode(',', $exportFields), 'cc_'.$table, '');
				foreach ($rows as $row) {
					$set = array();
					foreach ($row as $field => $value) {
						if (!in_array($field, $this->index[$table])) {
							$set[] = $field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value, 'cc_'.$table);
						}
					}
					$whereClause = ' WHERE ';
					$i = 0;
					foreach ($this->index[$table] as $field) {
						$whereClause .= ($i?' AND ':'').$field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($row[$field], 'cc_'.$table);
						$i++;
					}
					$updateQueries .= 'UPDATE '.$table.' SET '.implode(',', $set).$whereClause.";\n";
				}
			}
			$updateQueries .= "\n\n";
		}

		$replace = array (
				'###LANG_ISO_LOWER###' => $iso_code_lower,
				'###LANG_ISO_UPPER###' => $iso_code_upper,
				'###LANG_NAME###' => $row['lg_name_en'],
				'###LANG_TCA_LABELS###' => $tcaLabels,
				'###LANG_SQL_UPDATE###' => $updateQueries,
			);

		$tables = array_keys($this->index);

		if(!is_dir($destPath)) {
			t3lib_div::mkdir($destPath);
		}
		if (is_file($destPath.'ext_emconf.php')) {
			unset($sourceFiles[array_search('ext_emconf.php', $sourceFiles)]);
		}
		foreach ($sourceFiles as $file) {
			$fileContent = t3lib_div::getUrl($sourcePath.$file);
			foreach ($replace as $replMarker => $replStr) {
				$fileContent = str_replace($replMarker, $replStr, $fileContent);
			}
			if (t3lib_div::writeFile($destPath.$file, $fileContent)) {
				$content[] = $GLOBALS['LANG']->getLL('createdFile') . ' '.$destPath.$file;
			} else {
				$content[] = 'Could not write file '.$destPath.$file;
			}
		}

		require_once (t3lib_extMgm::extPath('cc_infotablesmgm').'mod1/class.tx_ccinfotablesmgm_emfunc.php');
		$emfunc = new tx_ccinfotablesmgm_emfunc;
		$emfunc->extUpdateEMCONF($destExtKey, $destPath);

		return $content;
	}

	/**
	 * renderExport
	 */
	function renderExport () {

		$filename = 'export-ext_tables_static+adt.sql';

		$content = '';
		$content .= '</form><form action="" method="post">';
		$content .= '<br />';

		if(t3lib_div::_GP('exportStatic')) {

			$exportContent ='';

			$ext_tables = t3lib_div::getUrl(t3lib_extMgm::extPath('static_info_tables').'ext_tables.sql');


			require_once (t3lib_extMgm::extPath('cc_infotablesmgm').'mod1/class.tx_ccinfotablesmgm_emfunc.php');
			$emfunc = new tx_ccinfotablesmgm_emfunc;

			$tables = array_keys($this->index);
			foreach ($tables as $table) {
				$includeFields = array();
				$tableFields = $this->getTableFields('cc_'.$table);
				foreach ($tableFields as $field) {
						// this is a very simple check if the field is from static_info_tables and not from a language pack
					if (preg_match('#'.preg_quote($field).'#m', $ext_tables, $match='')) {
						$includeFields[$field] = $field;
					}
				}

					// the extension manager export the fields from the language packs
				$exportContent .= $emfunc->dumpStaticTable('cc_'.$table, $table, $includeFields) . LF;
			}
			t3lib_div::writeFile(t3lib_extMgm::extPath('cc_infotablesmgm').$filename, $exportContent);

			$content .= '<br />' . $GLOBALS['LANG']->getLL('exportedTo') . ' <a href="../'.htmlspecialchars($filename).'">'.htmlspecialchars($filename).'</a>';

		} else {

			$content .= $GLOBALS['LANG']->getLL('exportTo') . ' '.htmlspecialchars($filename). ' ' . $GLOBALS['LANG']->getLL('withoutLocalizedData') .'<br />';
			$content .= '<br /><input type="submit" name="exportStatic" value="'.$GLOBALS['LANG']->getLL('exportStatic').'" />';
		}
		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('exportStatic'),$content,0,1);
	}

	/**
	 * renderCreateLangpack
	 */
	function renderCreateLangpack () {

		$iso2typo = $this->get_iso2typo();

		$content = '';
		$content .= '</form><form action="" method="post">';
		$content .= '<br />';

		if(t3lib_div::_GP('create') AND $iso_code = t3lib_div::_GP('iso_code')) {

			$out = $this->createLangpack($iso_code);
			$content .= implode('<br />', $out);
			$content .= '<br /><br />' .$GLOBALS['LANG']->getLL('languagePackCreated') . ' ' . $iso_code;

		} elseif(t3lib_div::_GP('create_all')) {

			foreach ($iso2typo as $iso_code => $t3code) {
				$out = $this->createLangpack($iso_code);
				$content .= '<br /><br />'.$iso_code.':<br />';
				$content .= implode('<br />', $out);
				$content .= '<br /><br />' .$GLOBALS['LANG']->getLL('languagePackCreated') . ' ' . $iso_code;
			}

		} else {

			$content .= '<label for="iso_code">' . $GLOBALS['LANG']->getLL('languageISOCode') . '</label> ';
			$content .= '<input id="iso_code" type="input" name="iso_code" value="" /> ' . $GLOBALS['LANG']->getLL('ISOCodeExample') . ' ';
			$content .= '<br /><br /><input type="submit" name="create" value="'.$GLOBALS['LANG']->getLL('create_langpack').'" />';

			$content .= '<br /><br /><br />';
			$content .= '<div style="width:40%">'.implode(', ', array_keys($iso2typo)).'</div>';
			$content .= '<br /><input type="submit" name="create_all" value="'.$GLOBALS['LANG']->getLL('create_langpack_all').'" />';
		}
		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('create_langpack'),$content,0,1);
	}

	/**
	 *
	 */
	function getCurrentTableIndexes($table, $indexField) {
		$currentIndexes = array();

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(implode(',', $indexField), $table, '');
		foreach ($rows as $row) {
			$currentIndexes[] = $row[$indexField];
		}
		return $currentIndexes;
	}

	/**
	 *
	 */
	function checkTableEntries($table, $indexField, $validIndexes) {

		$content = array();

		$currentIndexes = $this->getCurrentTableIndexes($table, $indexField);

		$invalidIndexes = array_diff($currentIndexes, $validIndexes);
		$newIndexes = array_diff($validIndexes, $currentIndexes);


		foreach ($newIndexes as $indexValue) {
			$row = array($indexField => $indexValue);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $row);
			$content[] = 'Added row: '.$table.'.'.$indexField.' = '.$indexValue;
		}

		foreach ($invalidIndexes as $indexValue) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $indexField.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, $table));
			$content[] = 'Removed row: '.$table.'.'.$indexField.' = '.$indexValue;
		}
		return $content;
	}

	/**
	 *
	 */
	function getTableFields($table) {
		$tableFields = array();

		$fieldInfo = $GLOBALS['TYPO3_DB']->admin_get_fields($table);
		$tableFields = array_keys($fieldInfo);

		return $tableFields;
	}

	/**
	 * importTerritories
	 */
	function importTerritories () {

		$content = '';


		//
		// update territories
		//

		//CREATE TABLE cc_static_territories (
		//  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
		//  pid int(11) unsigned DEFAULT '0' NOT NULL,
		//  tr_iso_nr int(11) unsigned DEFAULT '0' NOT NULL,
		//  tr_parent_iso_nr int(11) unsigned DEFAULT '0' NOT NULL,
		//  tr_name_en varchar(99) DEFAULT '0' NOT NULL,
		//  UNIQUE uid (uid)
		//  PRIMARY KEY (uid),
		//);

		$name = 'territories';
		$table = 'static_territories';
		$nameFieldPrefix = 'tr_name_';


		$content .= '<h3>Update '.$name.'</h3>';

		$importedFields = array();
		$tableFields = $this->getTableFields ('cc_'.$table);

		//
		// get valid entries
		//

//		$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/en.xml') or die ('Unable to load XML file!');
//		$validEntries = array();
//		$unusedEntries = array();
//		foreach ($xml->xpath('//ldml/localeDisplayNames/territories/territory') as $key => $node) {
//			$indexValue = intval($node['type']);
//			if ($indexValue > 1) {
//				$validEntries[$indexValue] = $indexValue;
//			} else {
//				$unusedEntries[] = (string)$node['type'];
//			}
//		}

		$xml = simplexml_load_file($this->cldrBaseFolder.'common/supplemental/supplementalData.xml') or die ('Unable to load XML file!');
		$validEntries = array();
		$unusedEntries = array();
		foreach ($xml->xpath('//supplementalData/territoryContainment/group') as $key => $node) {
			$parent = intval($node['type']);
			if($parent==1) {
				$contains = explode(' ', $node['contains']);
				foreach($contains as $indexValue) {
					$indexValue = intval($indexValue);
					$validEntries[$indexValue] = $indexValue;
				}
			} else {
				$contains = explode(' ', $node['contains']);
				foreach ($contains as $indexValue) {
					if($parent > 1 AND intval($indexValue)) {
						$indexValue = intval($indexValue);
						$validEntries[$indexValue] = $indexValue;
					} else {
						$unusedEntries[] = (string)$indexValue;
					}
				}
			}
		}

		$content .= '<h4>Unused source entries</h4>';
		$content .= implode(', ', $unusedEntries);


		//
		// add/remove entries when needed
		//

		$content .= '<h4>Check '.$name.' entries</h4>';
		$out = $this->checkTableEntries('cc_'.$table, $this->index[$table][0], $validEntries);
		$content .= implode('<br />', $out);
		$importedFields[$this->index[$table][0]] = $this->index[$table][0];

		//
		// update territories parents
		//

		$content .= '<h4>Update '.$name.' parents</h4>';

		$xml = simplexml_load_file($this->cldrBaseFolder.'common/supplemental/supplementalData.xml') or die ('Unable to load XML file!');
		foreach ($xml->xpath('//supplementalData/territoryContainment/group') as $key => $node) {
			$parent = intval($node['type']);
			$contains = explode(' ', $node['contains']);
			foreach ($contains as $child) {
				$child = intval($child);
				if($parent > 1 && $child) {
					$dataUpdate = array('tr_parent_iso_nr' => (string)$parent);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($child, 'cc_'.$table), $dataUpdate);
			    	$content .= $child.'->'.$parent.', ';
				}
			}
		}
		$importedFields['tr_parent_iso_nr'] = 'tr_parent_iso_nr';

		//
		// update localized names
		//

		$content .= '<h4>Update ' . $name . ' localized names</h4>';

		foreach($tableFields as $field) {
			if (preg_match('#^'.$nameFieldPrefix.'(..)$#', $field, $matches='')) {
				$langIso = $matches[1];

				$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/' . $langIso . '.xml');
				if (!$xml) { debug ('Unable to load ' . $langIso . '.xml'); continue; }
				foreach ($xml->xpath('//ldml/localeDisplayNames/territories/territory') as $key => $node) {
					$indexValue = intval($node['type']);

					if($validEntries[$indexValue]) {
						$dataUpdate = array();
			    		$dataUpdate[$nameFieldPrefix . $langIso] = (string)$node;
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $dataUpdate);

			    		$content .= $indexValue.', ';
					}
				}
				$importedFields[$nameFieldPrefix . $langIso] = $nameFieldPrefix . $langIso;
			}
		}

		$content .= '<h4>Imported fields</h4>';
		$content .= implode(', ', $importedFields);
		$content .= '<h4>NOT Imported fields</h4>';
		$tableFields = array_diff($tableFields, $importedFields, array('uid', 'pid'));
		$content .= implode(', ', $tableFields);

		return $content;
	}

	/**
	 * importCountries
	 */
	function importCountries () {

		$content = '';

		//
		// update countries
		//

		//CREATE TABLE cc_static_countries (
		//  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
		//  pid int(11) unsigned DEFAULT '0' NOT NULL,
		//  cn_iso_2 char(2) DEFAULT '' NOT NULL,
		//  cn_iso_3 char(3) DEFAULT '' NOT NULL,
		//  cn_iso_nr int(11) unsigned DEFAULT '0' NOT NULL,
		//  cn_official_name_local varchar(99) DEFAULT '' NOT NULL,
		//  cn_official_name_en varchar(99) DEFAULT '' NOT NULL,
		//  cn_capital varchar(99) DEFAULT '' NOT NULL,
		//  cn_tldomain char(2) DEFAULT '' NOT NULL,
		//  cn_currency_iso_3 char(3) DEFAULT '' NOT NULL,
		//  cn_currency_iso_nr int(10) unsigned DEFAULT '0' NOT NULL,
		//  cn_phone int(10) unsigned DEFAULT '0' NOT NULL,
		//  cn_eu_member tinyint(3) unsigned DEFAULT '0' NOT NULL,
		//  cn_address_format tinyint(3) unsigned DEFAULT '0' NOT NULL,
		//  cn_zone_flag tinyint(4) DEFAULT '0' NOT NULL,
		//  cn_short_local varchar(99) DEFAULT '' NOT NULL,
		//  cn_short_en varchar(99) DEFAULT '' NOT NULL,
		//  UNIQUE uid (uid)
		//  PRIMARY KEY (uid),
		//);

		$name = 'countries';
		$table = 'static_countries';
		$nameFieldPrefix = 'cn_short_';


		$content .= '<h3>Update '.$name.'</h3>';


		$importedFields = array();
		$tableFields = $this->getTableFields ('cc_'.$table);


		//
		// get valid languages
		//

		// The de.xml territories seems to be more reliable than en.xml where for example Eastern germany still exists

		$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/de.xml') or die ('Unable to load XML file!');
		$validEntries = array();
		$unusedEntries = array();
		foreach ($xml->xpath('//ldml/localeDisplayNames/territories/territory') as $key => $node) {
			$indexValue = strtoupper((string)$node['type']);
			if (strlen($indexValue) == 2) {
				$validEntries[$indexValue] = $indexValue;
			} elseif(intval($indexValue)==0) {
				$unusedEntries[] = $indexValue;
			}
		}
		$content .= '<h4>Unused source entries</h4>';
		$content .= implode(', ', $unusedEntries);


		//
		// add/remove entries when needed
		//

		$content .= '<h4>Check '.$name.' entries</h4>';
		$out = $this->checkTableEntries('cc_'.$table, $this->index[$table][0], $validEntries);
		$content .= implode('<br />', $out);
		$importedFields[$this->index[$table][0]] = $this->index[$table][0];


		//
		// update countrs territory parent
		//

		$content .= '<h4>Update '.$name.' territory parent</h4>';

		$territoryParents = array();
		$xml = simplexml_load_file($this->cldrBaseFolder.'common/supplemental/supplementalData.xml') or die ('Unable to load XML file!');
		foreach ($xml->xpath('//supplementalData/territoryContainment/group') as $key => $node) {
			$parent = intval($node['type']);
			$contains = explode(' ', $node['contains']);
			foreach ($contains as $child) {
				if($parent AND intval($child)==0 AND strlen($child)==2) {	// is string an two char code
					$child = strtoupper($child);
					$dataUpdate = array('cn_parent_tr_iso_nr' => (string)$parent);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($child, 'cc_'.$table), $dataUpdate);
			    	$content .= $child.'->'.$parent.', ';
				}
			}
		}
		$importedFields['cn_parent_tr_iso_nr'] = 'cn_parent_tr_iso_nr';

		//
		// update localized names
		//

		$content .= '<h4>Update '.$name.' localized names</h4>';

		foreach($tableFields as $field) {
			if (preg_match('#^'.$nameFieldPrefix.'(..)$#', $field, $matches='')) {
				$langIso = $matches[1];

				$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/' . $langIso . '.xml');
				if (!$xml) { debug ('Unable to load ' . $langIso . '.xml'); continue; }
				foreach ($xml->xpath('//ldml/localeDisplayNames/territories/territory') as $key => $node) {
					$indexValue = (string)$node['type'];

					if($validEntries[$indexValue]) {
						$dataUpdate = array();
			    		$dataUpdate[$nameFieldPrefix . $langIso] = (string)$node;
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $dataUpdate);

			    		$content .= $indexValue.', ';
					}
				}
				$importedFields[$nameFieldPrefix . $langIso] = $nameFieldPrefix . $langIso;
			}
		}

		//
		// update cn_short_local names if empty
		//

//		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $table, 'cn_short_local=\'\' OR cn_official_name_local=\'\'');
//		foreach ($rows as $row) {
//			$langIso = $row[$this->index[$table]];
//			$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/'.$langIso.'.xml');
//				if (!$xml) { debug ('Unable to load '.$langIso.'.xml'); continue; }
//			foreach ($xml->xpath('//ldml/localeDisplayNames/territories/territory') as $key => $node) {
//				if (($indexValue = (string)$node['type']))== {
//				}
//			}
//			$dataUpdate = array();
//			if ($row['cn_short_local']=='') {
//    			$dataUpdate['cn_short_local'] = (string)$node;
//			}
//# TODO is it better to leave it empty?
////			if ($row['cn_official_name_local']=='') {
////    			$dataUpdate['cn_official_name_local'] = (string)$node;
////			}
//			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $dataUpdate);
//		}


# cn_currency_iso_3, cn_currency_iso_nr

#cn_official_name_local, cn_official_name_en

		$content .= '<h4>Imported fields</h4>';
		$content .= implode(', ', $importedFields);
		$content .= '<h4>NOT Imported fields</h4>';
		$tableFields = array_diff($tableFields, $importedFields, array('uid', 'pid'));
		$content .= implode(', ', $tableFields);

		return $content;
	}

	/**
	 * importLanguages
	 */
	function importLanguages () {

		$content = '';

		//
		// update languages
		//

		$name = 'languages';
		$table = 'static_languages';
		$nameFieldPrefix = 'lg_name_';

		$content .= '<h3>Update '.$name.'</h3>';
		$importedFields = array();
		$tableFields = $this->getTableFields('cc_'.$table);

		//
		// get valid languages
		//

		$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/en.xml') or die ('Unable to load XML file!');
		$validEntries = array();
		$unusedEntries = array();
		foreach ($xml->xpath('//ldml/localeDisplayNames/languages/language') as $key => $node) {
			$indexValue = strtoupper((string)$node['type']);
			if (strlen($indexValue) == 2) {
				$validEntries[$indexValue] = $indexValue;
			} else {
				$unusedEntries[] = $indexValue;
			}
		}
		$content .= '<h4>Unused source entries</h4>';
		$content .= implode(', ', $unusedEntries);

		//
		// add/remove entries when needed
		//

		$content .= '<h4>Check '.$name.' entries</h4>';
		$out = $this->checkTableEntries('cc_'.$table, $this->index[$table][0], $validEntries);
		$content .= implode('<br />', $out);
		$importedFields[$this->index[$table][0]] = $this->index[$table][0];


		//
		// update localized names
		//

		$content .= '<h4>Update '.$name.' localized names</h4>';

		foreach($tableFields as $field) {
			if (preg_match('#^'.$nameFieldPrefix.'(..)$#', $field, $matches='')) {
				$langIso = $matches[1];

				$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/' . $langIso . '.xml');
				if (!$xml) { debug ('Unable to load ' . $langIso . '.xml'); continue; }

				foreach ($xml->xpath('//ldml/localeDisplayNames/languages/language') as $key => $node) {
					$indexValue = strtoupper((string)$node['type']);

					if($validEntries[$indexValue]) {
						$dataUpdate = array();
			    		$dataUpdate[$nameFieldPrefix . $langIso] = (string)$node;
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $dataUpdate);

			    		$content .= $indexValue.', ';
					}
				}
				$importedFields[$nameFieldPrefix . $langIso] = $nameFieldPrefix . $langIso;
			}
		}

		$content .= '<h4>Imported fields</h4>';
		$content .= implode(', ', $importedFields);
		$content .= '<h4>NOT Imported fields</h4>';
		$tableFields = array_diff($tableFields, $importedFields, array('uid', 'pid'));
		$content .= implode(', ', $tableFields);

		return $content;
	}

	/**
	 * importCurrencies
	 */
	function importCurrencies () {

		$content = '';

		//
		// Update currencies
		//

		//CREATE TABLE cc_static_currencies (
		//  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
		//  pid int(11) unsigned DEFAULT '0' NOT NULL,
		//  cu_iso_3 char(3) DEFAULT '' NOT NULL,
		//  cu_iso_nr int(11) unsigned DEFAULT '0' NOT NULL,
		//  cu_name_en varchar(99) DEFAULT '0' NOT NULL,
		//  cu_symbol_left varchar(12) DEFAULT '' NOT NULL,
		//  cu_symbol_right varchar(12) DEFAULT '' NOT NULL,
		//  cu_thousands_point char(1) DEFAULT '' NOT NULL,
		//  cu_decimal_point char(1) DEFAULT '' NOT NULL,
		//  cu_decimal_digits tinyint(3) unsigned DEFAULT '0' NOT NULL,
		//  cu_sub_name_en varchar(20) DEFAULT '' NOT NULL,
		//  cu_sub_divisor int(11) DEFAULT '1' NOT NULL,
		//  cu_sub_symbol_left varchar(12) DEFAULT '' NOT NULL,
		//  cu_sub_symbol_right varchar(12) DEFAULT '' NOT NULL,
		//  cu_name_de varchar(99) DEFAULT '' NOT NULL,
		//  cu_sub_name_de varchar(20) DEFAULT '' NOT NULL,
		//  UNIQUE uid (uid)
		//  PRIMARY KEY (uid),
		//);

		$name = 'currencies';
		$table = 'static_currencies';
		$nameFieldPrefix = 'cu_name_';


		$content .= '<h3>Update '.$name.'</h3>';


		$importedFields = array();
		$tableFields = $this->getTableFields ('cc_'.$table);


		//
		// get valid currencies
		//

		$xml = simplexml_load_file($this->cldrBaseFolder.'common/supplemental/supplementalData.xml') or die ('Unable to load XML file!');
		$validEntries = array();
		foreach ($xml->xpath('//supplementalData/currencyData/region/currency') as $key => $node) {
			if($node['from'] AND !$node['to']) {
				$indexValue = (string)$node['iso4217'];
				$validEntries[$indexValue] = $indexValue;
			}
		}


		//
		// add/remove entries when needed
		//

		$content .= '<h4>Check '.$name.' entries</h4>';
		$out = $this->checkTableEntries('cc_'.$table, $this->index[$table][0], $validEntries);
		$content .= implode('<br />', $out);
		$importedFields[$this->index[$table][0]] = $this->index[$table][0];


		//
		// update currencies fractions
		//

		$content .= '<h4>Update '.$name.' (cu_decimal_digits)</h4>';

		$dataUpdate = array();
		foreach ($xml->xpath('//supplementalData/currencyData/fractions/info') as $key => $node) {

			// <info iso4217="ADP" digits="0" rounding="0"/>

			$indexValue = (string)$node['iso4217'];
			if($validEntries[$indexValue] OR $indexValue=='DEFAULT') {
			    $row = array();
			    $row['cu_decimal_digits'] = intval($node['digits']);
			    // ?? $row[''] = $node['rounding'];

			    $dataUpdate[$indexValue] = $row;
			    $content .= $indexValue.', ';
			}
		}

			// set default
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, '1=1', $dataUpdate['DEFAULT']);
		unset($dataUpdate['DEFAULT']);

			// set other values
		foreach ($dataUpdate as $indexValue => $row) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $row);
		}

		$importedFields['cu_decimal_digits'] = 'cu_decimal_digits';

		//
		// update localized names
		//

		$content .= '<h4>Update '.$name.' ('.$nameFieldPrefix.'XX)</h4>';

		foreach($tableFields as $field) {
			if (preg_match('#^'.$nameFieldPrefix.'(..)$#', $field, $matches='')) {
				$langIso = $matches[1];

				$xml = simplexml_load_file($this->cldrBaseFolder.'common/main/' . $langIso . '.xml');
				if (!$xml) { debug ('Unable to load ' . $langIso . '.xml'); continue; }

				foreach ($xml->xpath('//ldml/numbers/currencies/currency') as $key => $node) {
					$indexValue = (string)$node['type'];

					if($validEntries[$indexValue]) {
						$dataUpdate = array();
						foreach ($node->children() as $key => $node) {

							switch ($key) {

								case 'displayName':
			    						$dataUpdate[$nameFieldPrefix . $langIso] = (string)$node;
										$importedFields[$nameFieldPrefix . $langIso] = $nameFieldPrefix . $langIso;
									break;

								case 'symbol':
										$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('cu_symbol_left,cu_symbol_right', 'cc_static_currencies', $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table));

										if ($rows[0]['cu_symbol_right']) {
			    							$dataUpdate['cu_symbol_right'] = (string)$node;
											$importedFields['cu_symbol_right'] = 'cu_symbol_right';

										} else {
			    							$dataUpdate['cu_symbol_left'] = (string)$node;
											$importedFields['cu_symbol_left'] = 'cu_symbol_left';
										}
									break;
							}
						}

			    		$content .= $indexValue.', ';
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, $this->index[$table][0].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($indexValue, 'cc_'.$table), $dataUpdate);
					}
				}
			}
		}
#TODO cu_iso_nr, cu_thousands_point, cu_decimal_point, cu_sub_name_en, cu_sub_divisor, cu_sub_symbol_left, cu_sub_symbol_right


#TODO
//	<numbers>
//		<currencyFormats>
//			<currencyFormatLength>
//				<currencyFormat>
//					<pattern>¤#,##0.00</pattern>




			// set defaults which are hopefully Ok
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, 'cu_sub_divisor=0 AND cu_decimal_digits=2', array('cu_sub_divisor' => '100'));
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cc_'.$table, 'cu_sub_divisor=0', array('cu_sub_divisor' => '1'));


		$content .= '<h4>Imported fields</h4>';
		$content .= implode(', ', $importedFields);
		$content .= '<h4>NOT Imported fields</h4>';
		$tableFields = array_diff($tableFields, $importedFields, array('uid', 'pid'));
		$content .= implode(', ', $tableFields);

		return $content;
	}

	/**
	 * renderImport
	 */
	function renderImport () {

		$content = '';

		$content .= '</form><form action="" method="post">';

		if (t3lib_div::_GP('import')) {

			$content .= $this->importTerritories();
			$content .= $this->importCountries();
			$content .= $this->importLanguages();
			$content .= $this->importCurrencies();

		} else {
			$content .= '<input type="submit" name="import" value="'.$GLOBALS['LANG']->getLL('import').'" />';
		}

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('import'),$content,0,1);
	}

	/**
	 * renderCheckOutIn
	 */
	function renderCheckOutIn () {

		$content = '';

		$content .= '</form><form action="" method="post">';

		$table = t3lib_div::_GP('move_table');
		$mode = t3lib_div::_GP('mode');
		$mode = $mode['checkout'] ? 'checkout' : 'checkin';

		if ($table) {
			$tables = array();
			if ($table == 'all') {
				$tables = array_keys($this->MOD_MENU['table']);
			} else {
				$tables[] = $table;
			}

			foreach ($tables as $table) {
				if ($mode == 'checkout') {
					$tableSource = $table;
					$tableDest = 'cc_'.$table;
				} else {
					$tableSource = 'cc_'.$table;
					$tableDest = $table;
				}

				$content .= '<br /><h4>'.$tableSource.' > '.$tableDest.'</h4>';

					// clear destination table
				$GLOBALS['TYPO3_DB']->exec_DELETEquery($tableDest, '1=1');

					// clone missing fields
				$tableFieldsSource = $this->getTableFields ($tableSource);
				$tableFieldsDest = $this->getTableFields ($tableDest);
				$missing = array_diff($tableFieldsSource, $tableFieldsDest);
				if(count($missing)) {
					$tableSourceInfo = $GLOBALS['TYPO3_DB']->admin_get_fields($tableSource);
					foreach ($missing as $field) {
						$fieldInfo = $tableSourceInfo[$field];
						$query = 'ALTER TABLE '.$tableDest.' ADD '.$field.' '.$fieldInfo['Type'].' DEFAULT \''.$fieldInfo['Default'].'\' NOT NULL;';
						$res = $GLOBALS['TYPO3_DB']->admin_query($query);

					}
				}

				$cnt = 0;
					// Do not apply deleteClause as some tables need to keep the deleted flag in order to keep historical data
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $tableSource, '');
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					#$where = $this->index[$tableSource].'="'.$row[$this->index[$tableSource]].'"';
					$where = 'uid='.intval($row['uid']);
					#$content .= '<br />'.$GLOBALS['TYPO3_DB']->DELETEquery($tableDest, $where);
					#$GLOBALS['TYPO3_DB']->exec_DELETEquery($tableDest, $where);
					#$content .= '<br />'.$GLOBALS['TYPO3_DB']->INSERTquery($tableDest, $row);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($tableDest, $row);
					$cnt++;
				}
				$content .= $cnt.' rows moved';
			}

		} else {

			$tables = array ('all'=>'All') + $this->MOD_MENU['table'];
			$content .= tx_ccinfotablesmgm_div::getFuncMenu('move_table', $table, $tables);
			$content .= $this->doc->spacer(5);
			$content .= '<br />Copy table(s) from "Static Info Tables" to local management tables.';
			$content .= '<br /><input type="submit" name="mode[checkout]" value="Check out" />';
			$content .= $this->doc->spacer(5);
			$content .= '<br />Copy table(s) from local management tables back to "Static Info Tables".';
			$content .= '<br /><input type="submit" name="[mode]checkin" value="Check in" />';
		}

		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('move'),$content,0,1);
	}

	/**
	 * renderCompare
	 */
	function renderCompare () {
		$content = '';

		$content .= '</form><form action="" method="post">';

		$content .= t3lib_BEfunc::getFuncMenu($this->id,'SET[table]',$this->MOD_SETTINGS['table'],$this->MOD_MENU['table']);
		$content .= '<br />'.t3lib_BEfunc::getFuncCheck($this->id, 'SET[showDiffOnly]', $this->MOD_SETTINGS['showDiffOnly']).' '.$GLOBALS['LANG']->getLL('showDiffOnly');
		$content .= '<br />'.t3lib_BEfunc::getFuncCheck($this->id, 'SET[useUidAsIndex]', $this->MOD_SETTINGS['useUidAsIndex']).' use uid as index';
		$content .= '<br /><br />';

		$this->mergeData();

		$indexField = $this->MOD_SETTINGS['useUidAsIndex'] ? 'uid' : $this->index[$this->MOD_SETTINGS['table']];
		$content .= $this->compareTable('cc_'.$this->MOD_SETTINGS['table'], $this->MOD_SETTINGS['table'], $this->MOD_SETTINGS['showDiffOnly'], $indexField);
		$content .= '<br /><br /><input type="submit" name="merge" value="Merge selected data" />';
		$content .= '</form><form action="" method="post">';

		return $this->doc->section($GLOBALS['LANG']->getLL('compare'),$content,0,1);
	}

	/**
	 * Merges data from one table to another
	 */
	function mergeData() {
		if (t3lib_div::_GP('merge')) {
			if (is_array($recs = t3lib_div::_GP('recs'))) {

				foreach ($recs as $table => $recArr) {
					foreach ($recArr as $uid => $row) {
						if (intval($uid)) {
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $row);
						}
					}

				}
			}
			if (is_array($recs = t3lib_div::_GP('import'))) {
				foreach ($recs as $table => $recArr) {
					foreach ($recArr as $uid => $row) {
						if (intval($uid)) {
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($uid).t3lib_BEfunc::deleteClause($table));
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
								unset($row['uid']);
								$row = tx_ccinfotablesmgm_div::cleanupRecordArray('cc_'.$table, $row);
								$GLOBALS['TYPO3_DB']->exec_INSERTquery('cc_'.$table, $row);
							}
						}
					}

				}
			}
		}
	}

	/**
	 * Render a table/form to compare two tables data
	 *
	 * @param   string      table name
	 * @return  string      Rendered Table
	 */
	function compareTable($table, $cmpTable, $showDiffOnly, $indexField) {

		$content = '';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $cmpTable, '1'.t3lib_BEfunc::deleteClause($cmpTable));
		$cmpRows = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))    {
			unset ($row['pid']);
			$cmpRows[$this->getIndex($row, $indexField)] = $row;
		}

		$outTable = array();
		$tr = 0;
		$diffCount = 0;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '1'.t3lib_BEfunc::deleteClause($table));
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))    {
			unset ($row['pid']);

			$td = 0;
			$diff = 0;

			if (!$tr) {
				$cmpRowDef = current($cmpRows);

				$outTable[$tr][$td++] = '';
				foreach ($row as $key => $value) {
					#$outTable[$tr][$td++] = $key;
					$outTable[$tr][$td++] = str_replace('_', ' ', $key);
					unset($cmpRowDef[$key]);
				}
				$tr++;
			}

			$firstRow = array();
			$secondRow = array();

				// Create output item for records
			$contentElementLink = tx_ccinfotablesmgm_div::getItemFromRecord($table, $row);
			$firstRow[$td] = $contentElementLink;
			$secondRow[$td++] = '&nbsp;';

			$uid = $row['uid'];
			$indexVal = $this->getIndex($row, $indexField);

				// compare and output fields
			foreach ($row as $field => $value) {
				$valueCmp = $cmpRows[$indexVal][$field];

				$firstRow[$td] = htmlspecialchars($value);
				if (strcmp($value, $valueCmp)) {
					#$check = '<input type="checkbox" name="recs['.$table.']['.$uid.']['.$field.']" value="'.htmlspecialchars($valueCmp).'" checked="checked">';
					$check = '<input type="checkbox" name="recs['.$table.']['.$uid.']['.$field.']" value="'.htmlspecialchars($valueCmp).'">';
					$valueCmp = '<span style="display:block; background-color:#eb7a6e;">'.$check.'&nbsp;'.htmlspecialchars($valueCmp).'<span>';
					$diff++;
					$diffCount++;
				}
				$secondRow[$td++] = $valueCmp;

			}
			unset($cmpRows[$indexVal]);

			if (!$showDiffOnly OR ($diff AND $showDiffOnly)) {
				$outTable[$tr++] = $firstRow;
				$outTable[$tr++] = $secondRow;
			}
		}

			// Return rendered table
		$content .= $this->doc->table($outTable, $this->tableLayout);
		$content .= $diffCount.' rows with differencies';
		$content .= '<br /><br />additional fields: '.implode(', ', array_keys($cmpRowDef));
		$content .= '<br /><br />additional rows: '.tx_ccinfotablesmgm_div::simpleTable($cmpRows, $cmpTable, false);

		return $content;
	}

	/**
	 * Returns a value for an index from an array
	 * One value from row or multiple values with "_" concatenated from multiple indexes
	 *
	 * @param array $row Table row
	 * @param mixed $indexField One or more indexes as string or array
	 * @return string Value from row or with "_" concatenated values from multiple indexes
	 */
	function getIndex($row, $indexField) {
		if (is_array($indexField)) {
			$indexVal = array();
			foreach ($indexField as $field) {
				$indexVal[] = $row[$field];
			}
			$indexVal = implode('_',$indexVal);

		} else {
			$indexVal = $row[$indexField];
		}
		return $indexVal;
	}

}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/mod1/index.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/mod1/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_ccinfotablesmgm_module1');
$SOBE->init();

// Include files?
reset($SOBE->include_once);
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();

?>