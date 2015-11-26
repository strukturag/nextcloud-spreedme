<?php
/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

namespace OCA\SpreedME\Controller;

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
	 */
	public function getLogin() {
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
				'files' => $fileName
			)
		);

		return new \OCP\AppFramework\Http\RedirectResponse($url);
	}

}
