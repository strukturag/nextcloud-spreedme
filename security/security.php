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

namespace OCA\SpreedME\Security;

use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Settings\Settings;

class Security {

	// TODO(leon): Rename all of the methods and variables.
	// This got so confusing.

	// Increase this with every major update of the signing process to invalidate old signatures
	const COMBO_VERSION = 2;

	private function __construct() {

	}

	private static function getSignedUsercomboArray($version, $userid, $key, $max_age, $expiration = 0) {
		if ($expiration > 0) {
			$useridcombo = sprintf('%s:%s:%s', intval($expiration), $userid, $version);
		} else {
			$useridcombo = sprintf('%s:%s:%s', (time() + $max_age), $userid, $version);
		}
		$secret = base64_encode(hash_hmac('sha256', $useridcombo, $key, true));

		return array(
			'useridcombo' => $useridcombo,
			'secret' => $secret,
		);
	}

	public static function getSignedCombo($userid, $expiration = 0) {
		$version = self::COMBO_VERSION;
		$key = Helper::getConfigValue('SPREED_WEBRTC_SHAREDSECRET');
		$max_age = Settings::SPREED_WEBRTC_USERCOMBO_MAX_AGE;

		if ($expiration > 0) {
			return self::getSignedUsercomboArray(
				$version,
				$userid,
				$key,
				false,
				$expiration + $max_age // So we have time to authenticate
			);
		} else {
			return self::getSignedUsercomboArray(
				$version,
				$userid,
				$key,
				$max_age
			);
		}
	}

	public static function getRandomString($length, $charset = '0123456789abcdef') {
		return \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate($length, $charset);
	}

	public static function regenerateSharedSecret() {
		$key = Security::getRandomString(256 / 4); // 256 bit
		Helper::setDatabaseConfigValueIfEnabled('SPREED_WEBRTC_SHAREDSECRET', $key);
		return $key;
	}

	public static function getAllowedIframeDomains() {
		$origin = Helper::getSpreedWebRtcOrigin();

		return array(
			$origin,
			'mailto:', // For social sharing via email
		);
	}

}
