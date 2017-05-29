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

namespace OCA\SpreedME\User;

use OCA\SpreedME\Errors\ErrorCodes;
use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Security\Security;

class User {

	private $user;

	public function __construct() {
		$this->user = \OC::$server->getUserSession()->getUser();
	}

	public function requireLogin() {
		if ($this->user === null) {
			throw new \Exception('Not logged in', ErrorCodes::NOT_LOGGED_IN);
		}
	}

	public function getInfo() {
		return array(
			'id' => $this->getUserId(),
			'display_name' => $this->getDisplayName(),
			'is_admin' => $this->isAdmin(),
			'is_spreedme_admin' => $this->isSpreedMeAdmin(),
		);
	}

	private function getUserId() {
		$this->requireLogin();

		if (Helper::getConfigValue('SPREED_WEBRTC_IS_SHARED_INSTANCE')) {
			// Return cloud id instead
			return $this->getCloudId();
		}
		return $this->user->getUID();
	}

	private function getUID() {
		$this->requireLogin();

		return $this->user->getUID();
	}

	private function getCloudId() {
		$this->requireLogin();

		if (!method_exists($this->user, 'getCloudId')) {
			$uid = \OC::$server->getUserSession()->getUser()->getUID();
			$server = \OC::$server->getURLGenerator()->getAbsoluteURL('/');
			return $uid . '@' . rtrim(\OCA\Files_Sharing\Helper::removeProtocolFromUrl($server), '/');
		}
		// Nextcloud 9
		return $this->user->getCloudId();
	}

	private function getDisplayName() {
		$this->requireLogin();

		return $this->user->getDisplayName();
	}

	private function getGroups() {
		$this->requireLogin();

		if (class_exists('\OC_Group', true)) {
			// Nextcloud <= 11, ownCloud
			return \OC_Group::getUserGroups($this->getUserId());
		}
		// Nextcloud >= 12
		$groups = \OC::$server->getGroupManager()->getUserGroups(\OC::$server->getUserSession()->getUser());
		return array_map(function ($group) {
			return $group->getGID();
		}, $groups);
	}

	private function getAdministeredGroups() {
		$this->requireLogin();

		if (class_exists('\OC_SubAdmin', true)) {
			return \OC_SubAdmin::getSubAdminsGroups($this->getUserId());
		}
		// Nextcloud 9
		$groups = (new \OC\SubAdmin(
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getDatabaseConnection()
		))->getSubAdminsGroups($this->user);
		return array_map(function ($group) {
			return $group->getGID();
		}, $groups);
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
		$id = $this->getUserId();
		// Spreed WebRTC uses colons as a delimiter for the useridcombo.
		// As the user id might contain colons (if it's a cloud id), we need to
		// replace it with a non-valid URL character, e.g. a pipe (|).
		// The reverse happens in the 'displayUserid' filter of owncloud.js
		$id = str_replace(':', '|', $id);
		return Security::getSignedCombo($id);
	}

}
