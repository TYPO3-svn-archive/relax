<?php

tslib_eidtools::connectDB();
tslib_eidtools::initFeUser();

// initialize TSFE
require_once(PATH_tslib . 'class.tslib_fe.php');
require_once(PATH_t3lib . 'class.t3lib_page.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couch.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couchDocument.php');
require_once(PATH_typo3conf . '/ext/relax/lib/couchClient.php');

$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
$GLOBALS['TSFE'] = new $temp_TSFEclassName($TYPO3_CONF_VARS, $pid, 0, true);
$GLOBALS['TSFE']->connectToDB();
$GLOBALS['TSFE']->initFEuser();
$GLOBALS['TSFE']->determineId();
$GLOBALS['TSFE']->getCompressedTCarray();
$GLOBALS['TSFE']->initTemplate();
$GLOBALS['TSFE']->getConfigArray();

$abbrev = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_relax_pi1.']['couch.'];
$couchHost = $abbrev['host'];
$couchPort = $abbrev['port'];
$couchDbName = $abbrev['dbName'];

$couchDsn = $couchHost . ':' . $couchPort . '/';

$docId = $_POST['docID'];

$db = t3lib_div::makeInstance('couchClient', $couchDsn, $couchDbName);
try {
	$doc = $db->getDoc($docId);
} catch (Exception $e) {
	if ($e->getCode() == 404) {
		echo "The chosen Document does not exist";
	}
	exit(1);
}
$doc = couchDocument::getInstance($db, $docId);

echo "<h3>Data from your Document</h3>";
getSavedData($doc);

//display data
function getSavedData($doc) {

	foreach ($doc->getFields() as $key => $val) {
		echo $key . ' - ' . $val . '<br />';
	}
}

?>