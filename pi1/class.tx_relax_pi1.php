<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Ingo Pfennigstorf <ingo.pfennigstorf@gmail.com>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(PATH_typo3conf . '/ext/relax/lib/Document.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couch.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couchClient.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couchDocument.php');

/**
 * Plugin 'Relax and take it easy' for the 'relax' extension.
 *
 * @author	Ingo Pfennigstorf <ingo.pfennigstorf@fh-brandenburg.de>
 * @package	TYPO3
 * @subpackage	tx_relax
 */
class tx_relax_pi1 extends tslib_pibase {

	var $prefixId = 'tx_relax_pi1';  // Same as class name
	var $scriptRelPath = 'pi1/class.tx_relax_pi1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'relax';
	var $pi_checkCHash = false;
	/**
	 * Couch Options
	 */
	private $couchDsn;
	private $couch;
	private $defaultFields;
	private $couchDB;
	/**
	 *
	 * Avalable Databses
	 * @var array
	 */
	private $couches;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;

		//get default fields From TYPO3 Conf
		$this->defaultFields = $this->getDefaultFields($conf['defaultFields']);

		//Database selected via TypoScript
		$this->couchDB = $conf['couch.']['dbName'];

		//Compute DSN
		$this->couchDsn = $conf['couch.']['host'] . ':' . $conf['couch.']['port'] . '/';

		//Start Connection to CouchDB Server
		$this->couch = $this->initDB();

		//check if we have some parameters
		if (t3lib_div::_GP('tx-relax-pi1')) {
			$gp = t3lib_div::_GP('tx-relax-pi1');

			$gp = $this->optimiereFelder($gp);

			$content .= $this->addToCouch($gp);
		}

		//Get all DBs and load into an Array
		$this->couches = $this->getAvailableDBs();

		//Comparing defined DB with available
		$this->compareAvailableSelected();


		//Add the HTML Form to Content
		$content .= $this->addForm();

		$content .= $this->readCouch();

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Get all Document IDs from our DB
	 * @return <type>
	 */
	private function readCouch() {

		$documents = $this->couch->getAllDocs();

		$c = '<ul>';
		foreach ($documents->rows as $document) {

			$c .= '<li>' . $document->id . '</li>';
		}
		$c .='</ul>';

		return $c;
	}

	/**
	 * Optimizing the Key-Value-Pairs to fit
	 * Removing Tempfields to Key/Value pairs
	 * @param array $gp
	 * @return array
	 */
	private function optimiereFelder($gp) {
		$data = array();

		foreach ($gp as $key => $value) {

			if (is_array($value)) {
				$key = $value['fieldname'];
				$value = $value['fieldvalue'];
			}

			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 *
	 * Get default Fields From TypoScript
	 * @param string $felder
	 */
	private function getDefaultFields($felder) {
		$defaultFelder = explode(',', $felder);

		$fields = array();
		foreach ($defaultFelder as $feld) {
			$fields[] = trim($feld);
		}

		return $fields;
	}

	/**
	 * Add Data to Your couchDB Server
	 * @return string Message from CouchDB Server
	 */
	private function addToCouch($daten) {

		//Instantiate Dummy Document
		$doc = t3lib_div::makeInstance('tx_relax_lib_Document');

		//Pass all attributes to the document
		foreach ($daten as $key => $val) {
			$doc->$key = $val;
		}

		//and try to store this document to our CouchDB
		try {
			$nachricht = $this->couch->storeDoc($doc);
		} catch (Exception $e) {
			$nachricht = $e->getTraceAsString();
		}

		return $nachricht;
	}

	/**
	 * Initializing Connection
	 * @return couchClient
	 */
	private function initDB() {

		$db = t3lib_div::makeInstance('couchClient', $this->couchDsn, $this->couchDB);

		if ($db) {
			return $db;
		} else {
			throw new Exception('Connection to CouchDB failed ...', 4151351342344);
		}
	}

	/**
	 * Yeah
	 * Generating the Form
	 * @TODO Extract to Template
	 * @TODO Deliver Javascript
	 */
	private function addForm() {
		$html .= '
		
		<form name="couchfields" action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post">';
		foreach ($this->defaultFields as $field) {


			$html .= '
			<label for="tx-relax-pi1-' . $field . '">' . ucfirst($field) . '</label>
			<input type="text" name="tx-relax-pi1[' . $field . ']" id="tx-relax-pi1-' . $field . '" />
				<br />';
		}
		$tempField = $this->getRandomFieldName();
		$html .= '
		
			<input type="text" name="tx-relax-pi1[' . $tempField . '][fieldname]" id="tx-relax-pi1-' . $tempField . '[label]" />
			<input type="text" name="tx-relax-pi1[' . $tempField . '][fieldvalue]" id="tx-relax-pi1-' . $tempField . '[value]" />
			<div class="addmore">+</div>
			<br />
			<input type="submit" name="tx-relax-pi1[add]" value="add" />
			
		</form>
		';

		return $html;
	}

	/**
	 * Generates a random String
	 * @return string Random String
	 */
	private function getRandomFieldName() {
		return t3lib_div::shortMD5(time());
	}

	/**
	 * Show all Databases in your Couch Instance
	 */
	private function getAvailableDBs() {

		$databases = $this->couch->listDatabases();
		return $databases;
	}

	/**
	 * Check if the configured DB is in the list of availables
	 * @return boolean
	 */
	private function compareAvailableSelected() {
		if (in_array($this->couchDB, $this->couches)) {
			return true;
		} else {
			throw new Exception("Selected Database not available", 900843510546, $previous);
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/relax/pi1/class.tx_relax_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/relax/pi1/class.tx_relax_pi1.php']);
}
?>