<?php
namespace OCA\NdcVersionStatus\Controller;

use OCP\ISession;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;
use OCP\IConfig;

class PageController extends Controller {

	/** @var ISession */
	private $session;
	/** @var IConfig */
	private $config;

	private $userId;

	public function __construct($AppName, IConfig $config, ISession $session,IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		date_default_timezone_set('Asia/Taipei');
		$this->session = $session;
		$this->config = $config;
		$this->userId = $UserId;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoCSRFRequired
	 * @UseSession
	 */
	public function index() {
		if($this->session->exists('ndcversionstatus_lastCheck')) {
			$lastCheckTime = $this->session->get('ndcversionstatus_lastCheck');
		}

		// prepare odfweb version
		$version_odfweb = file_get_contents(\OC::$SERVERROOT.'/version-odfweb.txt');

		// prepare online version
		$wopi_url = $this->config->getAppValue('richdocuments', 'wopi_url');
		$response = file_get_contents($wopi_url . "/hosting/version");
		if ($response) {
			$obj = json_decode($response);
			$version_online = $obj->OxOOL;
		}

		$redirectUrl = "http://192.168.3.194/Odf.Nat/update.php"; // https://odf.nat.gov.tw/update.html
		$redirectUrl .= "?online=$version_online&odfweb=$version_odfweb";

		$headers = "" ;//  @get_headers($redirectUrl);
		if($headers[0] == 'HTTP/1.1 200 OK') {
			$this->session->set('ndcversionstatus_lastCheck', date("Y-m-d H:i:s"));
			return new RedirectResponse($redirectUrl);
		} else {
			// RedirectResponse not work
			$parameters = array(
				'lastCheckTime'  => $lastCheckTime,
				'redirect_url'   => $redirectUrl,
				'version_online' => $version_online,
				'version_odfweb' => $version_odfweb,
			);
			return new TemplateResponse('ndcversionstatus', 'index', $parameters);  // templates/index.php
		}
	}
}
