<?php
/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2016
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
				'url' => Helper::getSpreedWebRtcUrl(),
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

	/**
	 * @NoCSRFRequired
	 */
	public function generateTemporaryPassword() {
		$userid = isset($_POST['userid']) ? $_POST['userid'] : null;
		$expiration = isset($_POST['expiration']) ? $_POST['expiration'] : null;

		$_response = array('success' => false);
		if ($userid !== null && $expiration !== null) {
			try {
				$_response['tp'] = Security::generateTemporaryPassword($userid, $expiration);
				$_response['success'] = true;
			} catch (\Exception $e) {
				$_response['error'] = $e->getCode();
			}
		}

		return new DataResponse($_response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function getTokenWithTemporaryPassword() {
		$tp = isset($_POST['tp']) ? $_POST['tp'] : null;

		$_response = array('success' => false);
		if ($tp !== null) {
			try {
				$token = Security::getSignedComboFromTemporaryPassword($tp);
				$_response = array_merge($_response, $token);
				$_response['success'] = true;
			} catch (\Exception $e) {
				$_response['error'] = $e->getCode();
			}
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
