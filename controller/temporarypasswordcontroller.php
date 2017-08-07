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

use OCA\SpreedME\Security\TemporaryPasswordManager;
use OCA\SpreedME\User\User;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;

class TemporaryPasswordController extends Controller {

	private $user;
	private $temporaryPasswordManager;

	public function __construct($appName, IRequest $request, IDBConnection $db) {
		parent::__construct($appName, $request);

		if (!empty($userId)) {
			$this->user = new User($userId);
		} else {
			$this->user = new User();
		}

		$this->temporaryPasswordManager = new TemporaryPasswordManager($db);
	}

	/**
	 * @NoAdminRequired
	 */
	public function generateTemporaryPassword($userid, $expiration) {
		$_response = array('success' => false);
		if ($this->user->isSpreedMeAdmin() && $userid !== null && $expiration !== null) {
			try {
				$_response['tp'] = $this->temporaryPasswordManager->generateTemporaryPassword($userid, $expiration);
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
	public function getTokenWithTemporaryPassword($tp) {
		$_response = array('success' => false);
		if ($tp) {
			try {
				$token = $this->temporaryPasswordManager->getSignedComboFromTemporaryPassword($tp);
				$_response = array_merge($_response, $token);
				$_response['success'] = true;
			} catch (\Exception $e) {
				$_response['error'] = $e->getCode();
			}
		}

		return new DataResponse($_response);
	}

}
