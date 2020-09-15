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

		$this->versionParams = null;
		$this->getOdfwebVersion();
		$this->getNdcodfwebVersion();
	}

	private function getOdfwebVersion() {
		$version_odfweb = file_get_contents(\OC::$SERVERROOT.'/version-odfweb.txt');
		if ($version_odfweb) {
			$this->versionParams['odfweb'] = preg_replace('/\r|\n/', '', $version_odfweb);
		}
	}

	private function getNdcodfwebVersion() {
		$wopi_url = $this->config->getAppValue('richdocuments', 'wopi_url');
		if ($wopi_url) {
			$response = file_get_contents($wopi_url . "/hosting/version");
			if ($response) {
				$obj = json_decode($response);
				if ($versionStr = $obj->loolserver->Version ?? $obj->OxOOL) {
					// remove '-x' in version string
					$pieces = explode("-", $versionStr);
					$this->versionParams['ndcodfweb'] = $pieces[0];
				}
			}
		}
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param srting $result Get version result from odf.nat.gov.tw
	 */
	public function index() {

		// Prepare parameters for TemplateResponse
		if (!is_null($this->versionParams)) {
			foreach($this->versionParams as $key => $val) {
				$parameters[$key] = $val;
			}
			$parameters['showButton'] = true;
		}

		$parameters['redirectUrl'] = self::RedirectUrl;
		$parameters['odfwebReferrer'] = $this->urlGenerator->getAbsoluteURL('index.php/apps/ndcversionstatus/result/');

		$lastCheckTime = $this->config->getAppValue($this->appName, 'lastCheckTime');
		if ($lastCheckTime && !empty($lastCheckTime)) {
			$parameters['lastCheckTime'] = $lastCheckTime;
		}

		return new TemplateResponse('ndcversionstatus', 'index', $parameters);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param srting $updateInfo Get version result from odf.nat.gov.tw
	 */
	public function result($updateInfo) {

		$pieces = explode("&", $updateInfo);
		foreach($pieces as $piece) {
			$val = explode("=", $piece);
			$name = $val[0];
			if($name) {
				$needUpdate = $val[1] === '1' ? true : false;
				$parameters[$name] = $needUpdate;
			}
		}

		return new TemplateResponse('ndcversionstatus', 'result', $parameters);
	}


	/**
	 * Set appconfig lastCheckTime
	 */
	public function setTimeConfig() {
		date_default_timezone_set('Asia/Taipei');
		$this->config->setAppValue($this->appName, 'lastCheckTime', date("Y-m-d H:i:s"));
	}
}
