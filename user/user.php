<?php
/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
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
			'is_spreedme_admin' => $this->isSpreedMeAdmin(),
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

	private function getGroups() {
		$this->requireLogin();

		// TODO(leon): This looks like a private API.
		return \OC_Group::getUserGroups($this->userId);
	}

	private function getAdministeredGroups() {
		$this->requireLogin();

		// TODO(leon): This looks like a private API.
		if (class_exists('\OC_SubAdmin', true)) {
			return \OC_SubAdmin::getSubAdminsGroups($this->userId);
		}
		// ownCloud 9
		$subadmin = new \OC\SubAdmin(
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getDatabaseConnection()
		);
		$user = \OC::$server->getUserSession()->getUser();
		$ocgroups = $subadmin->getSubAdminsGroups($user);
		$groups = array();
		foreach ($ocgroups as $ocgroup) {
			$groups[] = $ocgroup->getGID();
		}
		return $groups;
	}

	private function isAdmin() {
		$groups = $this->getGroups();

		return in_array('admin', $groups, true);
	}

	private function isSpreedMeGroupAdmin() {
		$groups = $this->getAdministeredGroups();

		return in_array('Spreed.ME', $groups, true);
	}

	public function isSpreedMeAdmin() {
		return $this->isAdmin() || $this->isSpreedMeGroupAdmin();
	}

	public function getSignedCombo() {
		$info = $this->getInfo();

		return Security::getSignedCombo($info['id']);
	}

}
