<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2011 René Fritz (r.fritz@colorcube.de)
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

class tx_ccinfotablesmgm_emfunc {

	var $maxUploadSize = 100000;

	/**
	 * Update extension EM_CONF...
	 *
	 * @param	string		Extension key
	 * @param	string		Extension path
	 * @return	string		HTML content.
	 */
	function extUpdateEMCONF ($extKey, $path) {
		$extInfo = $this->getExtensionInfo($path);
		return $this->updateLocalEM_CONF($extKey,$extInfo);
	}

	/**
	 * Gathers all extensions in $path
	 *
	 * @param	string		Absolute path to local, global or system extensions
	 * @param	array		Array with information for each extension key found. Notice: passed by reference
	 * @param	array		Categories index: Contains extension titles grouped by various criteria.
	 * @param	string		Path-type: L, G or S
	 * @return	void		"Returns" content by reference
	 * @access private
	 * @see getInstalledExtensions()
	 */
	function getExtensionInfo ($path) {
		$extInfo = array();
		$extKey = dirname($path);
		if (@is_dir($path) && @is_file($path . '/ext_emconf.php')) {
				$emConf = $this->includeEMCONF($path . '/ext_emconf.php', $extKey);
				if (is_array($emConf)) {
					$extInfo['EM_CONF'] = $emConf;
					$extInfo['path'] = $path;
					$extInfo['files'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($path . $extKey);

				}
		}
		return $extInfo;
	}

	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param	string		Absolute path to EMCONF file.
	 * @param	string		Extension key.
	 * @return	array		EMconf array values.
	 */
	function includeEMCONF ($path, $_EXTKEY) {
		$EM_CONF = array();
		include($path);
		return $EM_CONF[$_EXTKEY];
	}

	/**
	 * Forces update of local EM_CONF. This will renew the information of changed files.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		Status message
	 */
	function updateLocalEM_CONF ($extKey, $extInfo) {
		$EM_CONF = $extInfo['EM_CONF'];
		$EM_CONF['_md5_values_when_last_written'] = serialize($this->serverExtensionMD5Array($extKey,$extInfo));
			// Update extension constraints with those of this extension
		$EM_CONF['constraints'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cc_infotablesmgm']['constraints'];
		$emConfFileContent = $this->construct_ext_emconf_file($extKey, $EM_CONF);
		if ($emConfFileContent) {
			$absPath = $extInfo['path'];
			$emConfFileName = $absPath . 'ext_emconf.php';
			if (@is_file($emConfFileName)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($emConfFileName,$emConfFileContent);
				return '"'.substr($emConfFileName,strlen($absPath)).'" was updated with a cleaned up EM_CONF array.';
			} else die('Error: No file "'.$emConfFileName.'" found.');
		}
	}

	/**
	 * Compiles the ext_emconf.php file
	 *
	 * @param	string		Extension key
	 * @param	array		EM_CONF array
	 * @return	string		PHP file content, ready to write to ext_emconf.php file
	 */
	function construct_ext_emconf_file ($extKey, $EM_CONF) {
		$fMsg = array(
			'version' => '	// Don\'t modify this! Managed automatically during upload to repository.'
		);
			// Clean version number
		$vDat = $this->renderVersion($EM_CONF['version']);
		$EM_CONF['version'] = $vDat['version'];

		$lines = array();
		$lines[] = '<?php';
		$lines[] = '';
		$lines[] = '########################################################################';
		$lines[] = '# Extension Manager/Repository config file for ext: "'.$extKey.'"';
		$lines[] = '# ';
		$lines[] = '# Auto generated '.date('d-m-Y H:i');
		$lines[] = '# ';
		$lines[] = '# Manual updates:';
		$lines[] = '# Only the data in the array - anything else is removed by next write';
		$lines[] = '########################################################################';
		$lines[] = '';
		$lines[] = '$EM_CONF[$_EXTKEY] = array (';
		$indent = chr(9);
		foreach ($EM_CONF as $k => $v) {
			if (is_array($v)) {
				$lines[] = $indent . "'" . $k . "' => " . 'array(';
				$indentMore = 
				$lines = array_merge($lines, $this->constructArrayDefinition($v, $indent . chr(9)));
				$lines[] = $indent . '),';
			} else {
				$lines[] = $indent . "'" . $k . "' => " . (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($v) ? intval($v) : "'" . \TYPO3\CMS\Core\Utility\GeneralUtility::slashJS(trim($v), 1) . "'"). ',' . $fMsg[$k];
			}
		}
		$lines [] =');';
		$lines [] ='';
		$lines [] ='?>';

		return implode(LF, $lines);
	}
	
	/**
	 * Output an array declaration
	 *
	 * @param	array		the array for which a declaration is required
	 * @return	array		lines of array declaration
	 */
	 function constructArrayDefinition ($array, $indent) {
	 	 $lines = array();
	 	 foreach ($array as $key => $value) {
			if (is_array($value)) {
				$lines[] = $indent . "'" . $key . "'" . ' => array (';
				$lines = array_merge($lines, $this->constructArrayDefinition($value, $indent . chr(9)));
				$lines[] = $indent . '),';
			} else {
				$lines[] = $indent . "'" . $key . "' => " . (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value) ? intval($value) : "'" . \TYPO3\CMS\Core\Utility\GeneralUtility::slashJS(trim($value), 1) . "'"). ',';
			} 
	 	 }
	 	 return $lines;
	 }
	 
	/**
	 * Make upload array out of extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	mixed		Returns array with extension upload array on success, otherwise an error string.
	 */
	function makeUploadArray($extKey,$extInfo) {
		$extPath = $extInfo['path'];

		if ($extPath) {

				// Get files for extension:
			$fileArr = array();
			$fileArr = \TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath($fileArr,$extPath);

				// Calculate the total size of those files:
			$totalSize = 0;
			foreach ($fileArr as $file) {
				$totalSize+=filesize($file);
			}

				// If the total size is less than the upper limit, proceed:
			if ($totalSize < $this->maxUploadSize) {

					// Initialize output array:
				$uploadArray = array();
				$uploadArray['extKey'] = $extKey;
				$uploadArray['EM_CONF'] = $extInfo['EM_CONF'];
				$uploadArray['misc']['codelines'] = 0;
				$uploadArray['misc']['codebytes'] = 0;

#				$uploadArray['techInfo'] = $this->makeDetailedExtensionAnalysis($extKey,$conf,1);

					// Read all files:
				foreach ($fileArr as $file) {
					$relFileName = substr($file,strlen($extPath));
					$fI = pathinfo($relFileName);
					if ($relFileName!='ext_emconf.php') {		// This file should be dynamically written...
						$uploadArray['FILES'][$relFileName] = array(
							'name' => $relFileName,
							'size' => filesize($file),
							'mtime' => filemtime($file),
							'is_executable' => (TYPO3_OS=='WIN' ? 0 : is_executable($file)),
							'content' => \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file)
						);
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('php,inc',strtolower($fI['extension']))) {
							$uploadArray['FILES'][$relFileName]['codelines']=count(explode(LF,$uploadArray['FILES'][$relFileName]['content']));
							$uploadArray['misc']['codelines']+=$uploadArray['FILES'][$relFileName]['codelines'];
							$uploadArray['misc']['codebytes']+=$uploadArray['FILES'][$relFileName]['size'];

								// locallang*.php files:
							if (substr($fI['basename'],0,9)=='locallang' && strstr($uploadArray['FILES'][$relFileName]['content'],'$LOCAL_LANG'))	{
								$uploadArray['FILES'][$relFileName]['LOCAL_LANG']=$this->getSerializedLocalLang($file,$uploadArray['FILES'][$relFileName]['content']);
							}
						}
						$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
					}
				}

					// Return upload-array:
				return $uploadArray;
			} else return 'Error: Total size of uncompressed upload ('.$totalSize.') exceeds '.\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($this->maxUploadSize);
		}
	}

	/**
	 * Include a locallang file and return the $LOCAL_LANG array serialized.
	 *
	 * @param	string		Absolute path to locallang file to include.
	 * @param	string		Old content of a locallang file (keeping the header content)
	 * @return	array		Array with header/content as key 0/1
	 * @see makeUploadArray()
	 */
	function getSerializedLocalLang($file,$content) {
		$returnParts = explode('$LOCAL_LANG',$content,2);

		$LOCAL_LANG = '';

		include($file);
		if (is_array($LOCAL_LANG)) {
			$returnParts[1] = serialize($LOCAL_LANG);
			return $returnParts;
		}
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	array		MD5-keys
	 */
	function serverExtensionMD5Array($extKey,$conf) {

			// Creates upload-array - including filelist.
		$mUA = $this->makeUploadArray($extKey,$conf);

		$md5Array = array();
		if (is_array($mUA['FILES'])) {

				// Traverse files.
			foreach($mUA['FILES'] as $fN => $d) {
				if ($fN!='ext_emconf.php') {
					$md5Array[$fN] = substr($d['content_md5'],0,4);
				}
			}
		} else debug($mUA);
		return $md5Array;
	}

	/**
	 * Returns version information
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		part: "", "int", "main", "sub", "dev"
	 * @return	string
	 * @see renderVersion()
	 */
	function makeVersion($v,$mode) {
		$vDat = $this->renderVersion($v);
		return $vDat['version_'.$mode];
	}

	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		Increase version part: "main", "sub", "dev"
	 * @return	string
	 */
	function renderVersion($v,$raise='') {
		$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('.', $v . '..');
		$parts[0] = \TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange($parts[0],0,999);
		$parts[1] = \TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange($parts[1],0,999);
		$parts[2] = \TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange($parts[2],0,999);

		switch ((string)$raise) {
			case 'main':
				$parts[0]++;
				$parts[1]=0;
				$parts[2]=0;
			break;
			case 'sub':
				$parts[1]++;
				$parts[2]=0;
			break;
			case 'dev':
				$parts[2]++;
			break;
		}

		$res = array();
		$res['version'] = $parts[0].'.'.$parts[1].'.'.$parts[2];
		$res['version_int'] = intval(str_pad($parts[0],3,'0',STR_PAD_LEFT).str_pad($parts[1],3,'0',STR_PAD_LEFT).str_pad($parts[2],3,'0',STR_PAD_LEFT));
		$res['version_main'] = $parts[0];
		$res['version_sub'] = $parts[1];
		$res['version_dev'] = $parts[2];

		return $res;
	}

	/*******************************
	 *
	 * Dumping database (MySQL compliant)
	 *
	 ******************************/

	/**
	 * Makes a dump of the tables/fields definitions for an extension
	 *
	 * @param	array		Array with table => field/key definition arrays in
	 * @return	string		SQL for the table definitions
	 * @see dumpStaticTables()
	 */
	function dumpTableAndFieldStructure($arr) {
		$tables = array();

		if (count($arr)) {

				// Get file header comment:
			$tables[] = $this->dumpHeader();

				// Traverse tables, write each table/field definition:
			foreach($arr as $table => $fieldKeyInfo) {
				$tables[] = $this->dumpTableHeader($table,$fieldKeyInfo);
			}
		}

			// Return result:
		return implode(LF.LF.LF,$tables);
	}

	/**
	 * Dump content for static table
	 *
	 * @param	string		Table from which to dump content
	 * @param	string		Table name to dump as
	 * @param	array		Array Fields to exclude from dump
	 * @return	string		Returns the content
	 * @see dumpTableAndFieldStructure()
	 */
	function dumpStaticTable($table, $asTable, $includeFields) {
		require_once(PATH_t3lib.'class.t3lib_install.php');
		$instObj = new t3lib_install;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);

		$out = '';
#debug($dbFields[$table]);

		$fields = array();
		if (count($includeFields)) {
			foreach ($includeFields as $field) {
				$fields[$field] = $dbFields[$asTable]['fields'][$field];
			}
			$dbFields[$asTable]['fields'] = $fields;
		}

#debug($dbFields[$table]);
		if (is_array($dbFields[$asTable]['fields'])) {
			$dHeader = $this->dumpHeader();
			$header = $this->dumpTableHeader($asTable,$dbFields[$asTable],1);
			$insertStatements = $this->dumpTableContent($table,$asTable,$dbFields[$asTable]['fields']);

			$out.= $dHeader.LF.LF.LF.
					$header.LF.LF.LF.
					$insertStatements.LF.LF.LF;
		} else {
			die('Fatal error: Table for dump not found in database...');
		}
		return $out;
	}

	/**
	 * Header comments of the SQL dump file
	 *
	 * @return	string		Table header
	 */
	function dumpHeader() {
		return trim('
# TYPO3 Extension Manager dump 1.1
#
#--------------------------------------------------------
');
	}

	/**
	 * Dump CREATE TABLE definition
	 *
	 * @param	string		Table name
	 * @param	array		Field and key information (as provided from Install Tool class!)
	 * @param	boolean		If true, add "DROP TABLE IF EXISTS"
	 * @return	string		Table definition SQL
	 */
	function dumpTableHeader($table,$fieldKeyInfo,$dropTableIfExists=0) {
		$lines = array();

			// Create field definitions
		if (is_array($fieldKeyInfo['fields'])) {
			foreach ($fieldKeyInfo['fields'] as $fieldN => $data) {
				$lines[]='  '.$fieldN.' '.$data;
			}
		}

			// Create index key definitions
		if (is_array($fieldKeyInfo['keys'])) {
			foreach ($fieldKeyInfo['keys'] as $fieldN => $data) {
				$lines[]='  '.$data;
			}
		}

			// Compile final output:
		if (count($lines)) {
			return trim('
#
# Table structure for table "'.$table.'"
#
'.($dropTableIfExists ? 'DROP TABLE IF EXISTS '.$table.';
' : '').'CREATE TABLE '.$table.' (
'.implode(','.LF,$lines).'
);'
			);
		}
	}

	/**
	 * Dump table content
	 * Is DBAL compliant, but the dump format is written as MySQL standard. If the INSERT statements should be imported in a DBMS using other quoting than MySQL they must first be translated. t3lib_sqlengine can parse these queries correctly and translate them somehow.
	 *
	 * @param	string		Table name
	 * @param	string		Table name to dump as
	 * @param	array		Field structure
	 * @return	string		SQL Content of dump (INSERT statements)
	 */
	function dumpTableContent($table,$asTable,$fieldStructure) {

			// Substitution of certain characters (borrowed from phpMySQL):
		$search = array('\\', '\'', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\0', '\n', '\r', '\Z');

		$lines = array();

			// Select all rows from the table:
		$fields = implode(',', array_keys($fieldStructure));
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, '');

			// Traverse the selected rows and dump each row as a line in the file:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$values = array();
			foreach ($fieldStructure as $field => $type) {
				$values[] = isset($row[$field]) ? "'".str_replace($search, $replace, $row[$field])."'" : 'NULL';
			}
			$lines[] = 'INSERT INTO '.$asTable.' VALUES ('.implode(', ',$values).');';
		}

			// Free DB result:
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

			// Implode lines and return:
		return implode(LF,$lines);
	}

	/**
	 * Gets the table and field structure from database.
	 * Which fields and which tables are determined from the ext_tables.sql file
	 *
	 * @param	string		Array with table.field values
	 * @return	array		Array of tables and fields splitted.
	 */
	function getTableAndFieldStructure($parts) {
			// Instance of install tool
		require_once(PATH_t3lib.'class.t3lib_install.php');
		$instObj = new t3lib_install;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);


		$outTables = array();
		foreach ($parts as $table) {
			$tP = explode('.',$table);
			if ($tP[0] && isset($dbFields[$tP[0]])) {
				if ($tP[1]) {
					$kfP = explode('KEY:',$tP[1],2);
					if (count($kfP)==2 && !$kfP[0])	{	// key:
						if (isset($dbFields[$tP[0]]['keys'][$kfP[1]]))	$outTables[$tP[0]]['keys'][$kfP[1]] = $dbFields[$tP[0]]['keys'][$kfP[1]];
					} else {
						if (isset($dbFields[$tP[0]]['fields'][$tP[1]]))	$outTables[$tP[0]]['fields'][$tP[1]] = $dbFields[$tP[0]]['fields'][$tP[1]];
					}
				} else {
					$outTables[$tP[0]] = $dbFields[$tP[0]];
				}
			}
		}

		return $outTables;
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/mod1/class.tx_ccinfotablesmgm_emfunc.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cc_infotablesmgm/mod1/class.tx_ccinfotablesmgm_emfunc.php']);
}
?>