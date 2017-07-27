<?php
/**
 * Nextcloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

namespace OCA\SpreedME\Controller;

use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Security\Security;
use OCA\SpreedME\User\User;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class ApiController extends Controller {

	private $user;
	private $urlGenerator;

	public function __construct($appName, IRequest $request, $userId, IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);

		if (!empty($userId)) {
			$this->user = new User($userId);
		} else {
			$this->user = new User();
		}

		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function getConfig() {
		$_response = array('success' => false);
		try {
			$_response['spreed_webrtc'] = array(
				'url' => Helper::getSpreedWebRtcUrl(false, false),
			);
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserConfig() {
		$_response = array('success' => false);
		try {
			$_response = array_merge($_response, $this->user->getInfo());
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getToken() {
		$_response = array('success' => false);
		try {
			$_response = array_merge($_response, $this->user->getSignedCombo());
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	public function saveConfig($config) {
		$allowedKeys = array(
			'SPREED_WEBRTC_ORIGIN',
			'SPREED_WEBRTC_BASEPATH',
			'OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED',
			'SPREED_WEBRTC_IS_SHARED_INSTANCE',
			'SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS',
			'SPREED_WEBRTC_ALLOW_ANONYMOUS_FILE_TRANSFERS',
		);

		$_response = array('success' => false);
		try {
			foreach ($allowedKeys as $key) {
				if (isset($config[$key])) {
					$value = $config[$key];
					Helper::setDatabaseConfigValueIfEnabled($key, $value);
					// Extra configuration for some of the keys
					switch ($key) {
						case 'SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS':
							if ($value === 'true') {
								Helper::createServiceUserUnlessExists();
							}
							break;
					}
				}
			}
			Helper::setDatabaseConfigValueIfEnabled('is_set_up', 'true');
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	public function regenerateSharedSecret() {
		$_response = array('success' => false);
		try {
			$key = Security::regenerateSharedSecret();
			$_response['sharedsecret'] = $key;
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	public function generateSpreedWebRTCConfig() {
		$_response = array('success' => false);
		try {
			$_response['config'] = Helper::generateSpreedWebRTCConfig();
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		return new DataResponse($_response);
	}

	/**
	 * @NoAdminRequired
	 */
	public function downloadFile() {
		$filePath = $_GET['file'];
		$filePathSplit = explode('/', $filePath);
		$fileName = array_pop($filePathSplit);
		$fileDir = implode('/', $filePathSplit);

		//$url = '/remote.php/webdav' . $filePath;
		$url = $this->urlGenerator->linkTo(
			'files',
			'ajax/download.php',
			array(
				'dir' => $fileDir,
				'files' => $fileName,
			)
		);

		return new \OCP\AppFramework\Http\RedirectResponse($url);
	}

}
