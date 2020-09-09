<?php
namespace OCA\NdcVersionStatus\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;
use OCP\IConfig;

class PageController extends Controller {

	/** @var IConfig */
	private $config;

	const RedirectUrl = "https://odf.nat.gov.tw/versionStatus/update.php";

	public function __construct($AppName, IConfig $config, IRequest $request){
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->config = $config;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index() {
		// prepare odfweb&online version
		$version_odfweb = file_get_contents(\OC::$SERVERROOT.'/version-odfweb.txt');
		$wopi_url = $this->config->getAppValue('richdocuments', 'wopi_url');
		$response = file_get_contents($wopi_url . "/hosting/version");
		if ($response) {
			$obj = json_decode($response);
			$version_online = $obj->OxOOL;
		}

		$redirectUrl = self::RedirectUrl."?online=$version_online&odfweb=$version_odfweb";

		$headers = @get_headers($redirectUrl);
		if($headers[0] == 'HTTP/1.1 200 OK') {
			$this->setTimeConfig();
			return new RedirectResponse($redirectUrl);
		} else {
			// if redirect not work
			$parameters = array(
				'redirect_url'   => $redirectUrl,
				'version_online' => $version_online,
				'version_odfweb' => $version_odfweb,
			);

			$lastCheckTime = $this->config->getAppValue($this->appName, 'lastCheckTime');
			if ($lastCheckTime && !empty($lastCheckTime)) {
				$parameters['lastCheckTime'] = $lastCheckTime;
			}

			return new TemplateResponse('ndcversionstatus', 'index', $parameters);  // templates/index.php
		}
	}

	/**
	 * Set appconfig lastCheckTime
	 */
	public function setTimeConfig() {
		date_default_timezone_set('Asia/Taipei');
		$this->config->setAppValue($this->appName, 'lastCheckTime', date("Y-m-d H:i:s"));
	}
}
