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
use OCP\IL10N;
use OCP\Mail\IMailer;

class PageController extends Controller {

	// 新版號 API
	private const NEW_VERSION_API = "https://odf.nat.gov.tw/versionStatus/api.php";

	// 目前使用的元件版號來源
	private const VERSION_PATH_ODFWEB = '/version-odfweb.txt'; // <odfweb>/version-odfweb.txt
	private const VERSION_PATH_ONLINE = '/hosting/version'; // <wopi_url>/hosting/version

	private $versionParams = array(
		'odfweb' => '',
		'modaodfweb' => ''
	);

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

	/** @var IL10N */
	private $l10n;

	public function __construct($AppName,
								IConfig $config,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IL10N $l10n,
								IMailer $mailer){
		parent::__construct($AppName, $request);
		$this->appName = $AppName;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->mailer = $mailer;
		$this->l10n = $l10n;

		$this->getOdfwebVersion();
		$this->getOnlineVersion();
	}

	/**
	 * 取得目前使用的 odfweb 版號
	 */
	private function getOdfwebVersion() {
		$version_odfweb = @file_get_contents(\OC::$SERVERROOT . self::VERSION_PATH_ODFWEB);
		if ($version_odfweb) {
			$this->versionParams['odfweb'] = preg_replace('/\r|\n/', '', $version_odfweb);
		} else {
			$this->versionParams['odfweb'] = "";
		}
	}

	/**
	 * 取得目前使用的 modaodfweb 版號
	 */
	private function getOnlineVersion() {
		$wopi_url = $this->config->getAppValue('richdocuments', 'wopi_url');
		$wopi_url = rtrim($wopi_url, "/");
		if (!preg_match("~^(?:f|ht)tps?://~i", $wopi_url)) {
			$wopi_url = "http://" . $wopi_url;
		}

		if ($wopi_url) {
			$response = @file_get_contents($wopi_url . self::VERSION_PATH_ONLINE);
			if ($response) {
				$obj = json_decode($response);
				if ($versionStr = $obj->loolserver->Version ?? $obj->OxOOL) {
					$pieces = explode("-", $versionStr);
					$this->versionParams['modaodfweb'] = $pieces[0];
					return;
				}
			}
		}
		$this->versionParams['modaodfweb'] = "";
	}

	/**
	 * @NoCSRFRequired
	 *
	 * 版號資訊頁面
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		foreach($this->versionParams as $item => $val) {
			$parameters[$item] = (!$val || $val == '') ? null : $val;
		}
		$parameters['resultPage'] = $this->urlGenerator->linkToRoute('ndcversionstatus.page.result');
		$parameters['lastCheckTime'] = $this->config->getAppValue($this->appName, 'lastCheckTime', '');
		return new TemplateResponse('ndcversionstatus', 'index', $parameters);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * 檢查結果頁面
	 *
	 * @return RedirectResponse|TemplateResponse
	 */
	public function result() {
		$releasedVersions = $this->getNewVersion();
		foreach($this->versionParams as $item => $val) {
			$parameters[$item]['msg']    = "";
			$parameters[$item]['result'] = "";
			$parameters[$item]['color']  = "gray";

			// 版本說明
			$usingVersion = (!$val || $val == "") ? null : $val;
			if (!isset($releasedVersions[$item]) || $releasedVersions[$item] === 'false') {
				$latestVersion = null;
			} else {
				$latestVersion = $releasedVersions[$item];
			}
			$stmt = $this->l10n->t(
				'Current version [ %1$s ], the latest version [ %2$s ].', [
				$usingVersion ?? $this->l10n->t('Unavailable'),
				$latestVersion ?? $this->l10n->t('Unavailable')
			]);
			$parameters[$item]['msg'] = $stmt;

			// 比較版號
			if ($usingVersion && $latestVersion) {
				$needUpdate = version_compare($usingVersion, $latestVersion, '<');
				$resStmt = $needUpdate ? 'New version available, please update.' : 'Using latest version';
				$parameters[$item]['result'] = $this->l10n->t($resStmt);
				$parameters[$item]['color'] = $needUpdate ? "red": 'green';
			}
		}

		// 更新日期
		$this->setTimeConfig();
		$time = $this->config->getAppValue($this->appName, 'lastCheckTime', null);
		if ($time) $parameters['lastCheckTime'] = $time;

		return new TemplateResponse('ndcversionstatus', 'result', $parameters);
	}

	/**
	 * 取得新版本資訊
	 * @return array 各元件的最新版號
	 */
	private function getNewVersion() {
		$opts = array("ssl" => array(
				"verify_peer" => false,
				"verify_peer_name" => false,
			),
		);
		$versions = @file_get_contents(self::NEW_VERSION_API, false, stream_context_create($opts));
		return json_decode($versions, true) ?? [];
	}

	/**
	 * Set appconfig lastCheckTime
	 */
	public function setTimeConfig() {
		date_default_timezone_set('Asia/Taipei');
		$this->config->setAppValue($this->appName, 'lastCheckTime', date("Y-m-d H:i:s"));
	}

	/**
	 *
	 * 將檢查結果寄給管理員
	 *
	 * @param array $data
	 * @return DataRespons
	 * @throws \RuntimeException
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

			// send mail
			try {
				if (empty($email)) {
					throw new \RuntimeException($this->l10n->t('Email unset.'));
				}

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
					throw new \RuntimeException($this->l10n->t('Email could not be sent. Check your mail server log.'));
				}

				$sendInfos[$uid]['result'] = true;
				$sendInfos[$uid]['message'] = $this->l10n->t('Email sent.');

			} catch (\Exception $e) {
				$sendInfos[$uid]['result'] = false;
				$sendInfos[$uid]['message'] = $e->getMessage();
			}
		}

		$sentCount = 0;
		foreach($sendInfos as $uid => $infos) {
			if($infos['result']) $sentCount ++;
		}

		if ($sentCount > 0) {
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('%s email sent.', [$sentCount]),
					'infos' => $sendInfos
				],
				'result' => true
			]);
		} else {
			return new DataResponse([
				'data' => [
					'message' => $this->l10n->t('A problem occurred while sending the email. Please revise your settings.'),
					'infos' => $sendInfos
				],
				'result' => false
			]);
		}
	}

}
