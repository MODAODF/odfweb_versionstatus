<?php
namespace OCA\NdcVersionStatus\Controller;

use OCP\IURLGenerator;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;
use OCP\IConfig;

class PageController extends Controller {

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	const RedirectUrl = "https://odf.nat.gov.tw/versionStatus/update.php";

	public function __construct($AppName, IConfig $config, IRequest $request, IURLGenerator $urlGenerator){
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index() {

		// odfweb version
		$version_odfweb = file_get_contents(\OC::$SERVERROOT.'/version-odfweb.txt');
		if ($version_odfweb) {
			$versionParams['odfweb'] = preg_replace('/\r|\n/', '', $version_odfweb);
		}

		// online(NDCODFWEB) version
		$wopi_url = $this->config->getAppValue('richdocuments', 'wopi_url');
		if ($wopi_url) {
			$response = file_get_contents($wopi_url . "/hosting/version");
			if ($response) {
				$obj = json_decode($response);
				if ($versionStr = $obj->loolserver->Version ?? $obj->OxOOL) {
					// remove '-x' in version string
					$pieces = explode("-", $versionStr);
					$versionParams['online'] = $pieces[0];
				}
			}
		}

		// Prepare parameters for TemplateResponse
		foreach($versionParams as $key => $val) {
			$parameters[$key] = $val;
		}
		$parameters['redirectUrl'] = self::RedirectUrl;
		$parameters['odfwebReferrer'] = $this->urlGenerator->getAbsoluteURL('index.php/apps/ndcversionstatus');

		$lastCheckTime = $this->config->getAppValue($this->appName, 'lastCheckTime');
		if ($lastCheckTime && !empty($lastCheckTime)) {
			$parameters['lastCheckTime'] = $lastCheckTime;
		}

		return new TemplateResponse('ndcversionstatus', 'index', $parameters);
	}

	/**
	 * Set appconfig lastCheckTime
	 */
	public function setTimeConfig() {
		date_default_timezone_set('Asia/Taipei');
		$this->config->setAppValue($this->appName, 'lastCheckTime', date("Y-m-d H:i:s"));
	}
}
