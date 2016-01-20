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

namespace OCA\SpreedME\User;

use OCA\SpreedME\Security\Security;

class User {

	private $userId;
	private $user;

	public function __construct($userId = null) {
		$this->userId = $userId;
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
		$this->requireLogin();

		return $this->user->getUser();
	}

	private function getDisplayName() {
		$this->requireLogin();

		return $this->user->getDisplayName();
	}

	private function isAdmin() {
		$this->requireLogin();
		$groups = \OC_Group::getUserGroups($this->userId);

		return in_array('admin', $groups, true);
	}

	public function getSignedCombo() {
		$info = $this->getInfo();

		return Security::getSignedCombo($info['id']);
	}

}
