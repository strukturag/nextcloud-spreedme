<?php
/**
 * ownCloud - spreedwebrtc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

namespace OCA\SpreedWebRTC\Controller;

use OCA\SpreedWebRTC\Config\Config;
use OCA\SpreedWebRTC\Helper\Helper;
use OCA\SpreedWebRTC\User\User;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SpreedWebRTCApiController extends Controller {

	private $user;

	public function __construct($appName, IRequest $request, $userId) {
		parent::__construct($appName, $request);
		if (!empty($userId)) {
			$this->user = new User($userId);
		} else {
			$this->user = new User();
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function downloadFile() {
		// TODO(leon): Make this RESTy
		$url = '/remote.php/webdav/' . urldecode($_GET['file']);

		return new \OCP\AppFramework\Http\RedirectResponse($url);
	}

	/**
	 * @UseSession
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function login() {
		$_response = array('success' => false);
		if (
			isset($_POST['username']) && isset($_POST['password'])
			&& !empty($_POST['username']) && !empty($_POST['password'])
		) {
			$username = $_POST['username'];
			$password = $_POST['password'];
			try {
				$this->user->loginAndSetCookie($username, $password);
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
	public function getShares() {
		$_response = array('success' => false);
		try {
			$_response['shares'] = \OCP\Share::getItemShared('file', null);
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
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function demo() {

		$_response = array('success' => false);

		try {
			$path = Helper::getOwnHost() . Config::SPREED_WEBRTC_BASEPATH . 'api/v1/config';
			$contents = file_get_contents($path);
			$json = json_decode($contents, true);
			$error = json_last_error();
			if ($error !== JSON_ERROR_NONE) {
				// Error
				throw new \Exception('Failed to parse api response', 50003);
			}

			// As config might contain sensible information only return values of importance
			$_response['config'] = array(
				'version' => $json['Version'],
			);
			$_response['success'] = true;
		} catch (\Exception $e) {
			$_response['error'] = $e->getCode();
		}

		/*try {
		$contents = file_get_contents('http://owncloud/webrtc');
		$dom = new \DOMDocument();
		$dom->loadHTML($contents);
		$config_element = $dom->getElementById('globalcontext');
		$config_string = $config_element->nodeValue;
		if (!empty($config_string)) {
		$json = json_decode($config_string, true);
		$error = json_last_error();
		if ($error !== JSON_ERROR_NONE) {
		// Error
		throw new \Exception('Failed to parse config string', 50003);
		}

		$config = $json['Cfg'];
		// As config might contain sensible information only return values of importance
		$_response['config'] = array(
		'version' => $config['Version']
		);
		}
		$_response['success'] = true;
		} catch (\Exception $e) {
		$_response['error'] = $e->getCode();
		}*/

		return new DataResponse($_response);
	}

}
