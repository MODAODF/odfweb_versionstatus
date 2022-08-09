<?php
namespace OCA\NdcVersionStatus\AppInfo;

use OCP\Util;
use OCP\IConfig;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {

	const APP_NAME = 'ndcversionstatus';

	public function __construct() {
		parent::__construct(self::APP_NAME);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		date_default_timezone_set('Asia/Taipei');

		// Get time in DB appconfig
		$config = $this->getContainer()->query(IConfig::class);
		$timeStr_lastCheck = $config->getAppValue('ndcversionstatus', 'lastCheckTime', '');
		$timeStr_current = date("Y-m-d H:i:s");
		$expSec = 7*24*60*60; // 7 days

		// Diff timestamp
		$needAlert = strtotime($timeStr_lastCheck) + $expSec < strtotime($timeStr_current) ? true : false;
		if($needAlert) Util::addScript('ndcversionstatus', 'alertIcon');
	}
}
