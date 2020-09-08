<?php
namespace OCA\NdcVersionStatus\AppInfo;
use OCP\Util;

date_default_timezone_set('Asia/Taipei');
$appName = 'ndcversionstatus';



$timeStr_lastCheck = \OC::$server->getSession()->get('ndcversionstatus_lastCheck'); // '2020-09-07 17:36:46'
$timeStr_current = date("Y-m-d H:i:s");

// $date_session = '2020-09-07 17:36:46';
// $date_now     = '2020-09-07 18:36:46';
$datetime1 = new \DateTime($timeStr_lastCheck);
$datetime2 = new \DateTime($timeStr_current);
$needAlert = 1;// $datetime1 > $datetime2 ? true : false;

$loadScripts = function() use ($appName, $needAlert) {
	// @codeCoverageIgnoreStart
	if($needAlert)  Util::addScript($appName, 'alertIcon');
};// @codeCoverageIgnoreEnd

\OC::$server->getEventDispatcher()->addListener('OCA\Files::loadAdditionalScripts', $loadScripts);
