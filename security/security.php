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

namespace OCA\SpreedWebRTC\Security;

use OCA\SpreedWebRTC\Config\Config;
use OCA\SpreedWebRTC\Helper\Helper;
use OCA\SpreedWebRTC\Settings\Settings;

class Security {

	private function __construct() {

	}

	public static function getSignedCombo($userid) {
		$key = Config::SPREED_WEBRTC_SHAREDSECRET;
		$max_usercombo_age = Settings::SPREED_WEBRTC_MAX_USERCOMBO_AGE;

		$useridcombo = (time() + $max_usercombo_age) . ':' . $userid;
		$secret = base64_encode(hash_hmac('sha256', $useridcombo, $key, true));

		return array(
			'useridcombo' => $useridcombo,
			'secret' => $secret,
		);
	}

	public static function getAllowedIframeDomains() {
		$origin = Config::SPREED_WEBRTC_ORIGIN;
		if (empty($origin)) {
			$origin = Helper::getOwnHost();
		}
		return array(
			$origin,
		);
	}

}
