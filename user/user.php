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

namespace OCA\SpreedWebRTC\User;

use OCA\SpreedWebRTC\Security\Security;

class User {

	private $userId;
	private $user;

	public function __construct($userId = null) {
		$this->userId = $userId;
	}

	public function loginAndSetCookie($username, $password) {
		$success = \OC_User::login($username, $password);
		if ($success) {
			$this->login($username, $password);
			return true;
		}
		throw new \Exception('Invalid login', 50002);
	}

	private function login($username, $password) {
		$user = new \OCP\User();
		if ($user->checkPassword($username, $password) !== false) {
			$this->user = $user;
			$this->userId = $this->getUserId();
			return true;
		}
		throw new \Exception('Invalid login', 50002);
	}

	public function requireLogin() {
		if (!$this->userId) {
			throw new \Exception('Not logged in', 50001);
		}
		if (!$this->user) {
			$this->user = new \OCP\User($this->userId);
		}
	}

	public function getInfo() {
		$this->requireLogin();

		return array(
			'id' => $this->getUserId(),
			'display_name' => $this->getDisplayName(),
			'is_admin' => $this->isAdmin(),
		);
	}

	private function getUserId() {
		return $this->user->getUser();
	}

	private function getDisplayName() {
		return $this->user->getDisplayName();
	}

	private function isAdmin() {
		$groups = \OC_Group::getUserGroups($this->userId);

		return in_array('admin', $groups, true);
	}

	public function getSignedCombo() {
		$info = $this->getInfo();

		return Security::getSignedCombo($info['id']);
	}

}
