<?php
namespace OCA\NdcVersionStatus\Controller;

use OCP\IURLGenerator;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Mail\IMailer;

class PageController extends Controller {

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IMailer */
	private $mailer;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	const RedirectUrl = "https://odf.nat.gov.tw/versionStatus/update.php";

	public function __construct($AppName,
								IConfig $config,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IMailer $mailer){
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->mailer = $mailer;

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
	 * @param srting $updateInfo Get version result from odf.nat.gov.tw ex: odfweb=0&ndcodfweb=1
	 */
	public function result($updateInfo) {
		if (!$updateInfo) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('ndcversionstatus.page.index'));
		}

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

	/**
	 * @return DataResponse
	 *
	 * @param array $data
	 */
	public function sendMail($content) {

		// odfweb name
		$ocDefaults = new \OC_Defaults;
		$odfwebName = $this->config->getAppValue('theming', 'name', $ocDefaults->getTitle());

		$groupId = 'admin';
		$groupUsers = $this->groupManager->get($groupId)->getUsers();

		foreach ($groupUsers as $u) {
			$uid = $u->getUid();
			$user = $this->userManager->get($uid); // IUser
			$email = $user->getEMailAddress();

			if (!empty($email)) {
				try { // send mail
					$displayName = $user->getDisplayName();

					$template = $this->mailer->createEMailTemplate('ndcversionstatus.resultMail', [
						'displayname' => $displayName,
					]);

					$template->setSubject("[$odfwebName] 版本檢查通知");
					$template->addHeader();
					$template->addHeading('版本檢查');
					$body = '<h4><u>' . $odfwebName . ' 檢查結果如下</u><h4>' . $content;
					$template->addBodyText($body, $body);
					$template->addFooter();

					$message = $this->mailer->createMessage();
					$message->setTo([$email => $displayName]);
					$message->useTemplate($template);
					$errors = $this->mailer->send($message);
					if (!empty($errors)) {
						throw new \RuntimeException('Email could not be sent. Check your mail server log');
					}

					$sendInfos[$uid]['result'] = true;
					$sendInfos[$uid]['message'] = 'Email sent.';

				} catch (\Exception $e) {
					$sendInfos[$uid]['result'] = false;
					$sendInfos[$uid]['message'] = $e->getMessage();
				}
			}
		}

		$sentCount = 0;
		foreach($sendInfos as $uid => $infos) {
			if($infos['result']) $sentCount ++;
		}

		if ($sentCount > 0) {
			return new DataResponse([
				'data' => [
					'message' => $sentCount . ' email sent.',
					'infos' => $sendInfos
				],
				'result' => true
			]);
		} else {
			return new DataResponse([
				'data' => [
					'message' => 'A problem occurred while sending the email. Please revise your settings',
					'infos' => $sendInfos
				],
				'result' => false
			]);
		}
	}
}
