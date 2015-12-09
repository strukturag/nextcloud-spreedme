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

namespace OCA\SpreedME\Security;

use OCA\SpreedME\Config\Config;
use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Settings\Settings;

class Security {

	private function __construct() {

	}

	private static function getSignedUsercomboArray($userid, $key, $max_age, $expiration = null) {
		if ($expiration !== null) {
			$useridcombo = intval($expiration) . ':' . $userid;
		} else {
			$useridcombo = (time() + $max_age) . ':' . $userid;
		}
		$secret = base64_encode(hash_hmac('sha256', $useridcombo, $key, true));

		return array(
			'useridcombo' => $useridcombo,
			'secret' => $secret,
		);
	}

	public static function getSignedCombo($userid, $expiration = 0) {
		$key = Config::SPREED_WEBRTC_SHAREDSECRET;
		$max_age = Settings::SPREED_WEBRTC_USERCOMBO_MAX_AGE;

		if ($expiration > 0) {
			return self::getSignedUsercomboArray(
				$userid,
				$key,
				false,
				$expiration + $max_age // So we have time to authenticate
			);
		} else {
			return self::getSignedUsercomboArray(
				$userid,
				$key,
				$max_age
			);
		}
	}

	private static function requireEnabledTemporaryPassword() {
		if (Config::OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED !== true) {
			throw new \Exception('Temporary Passwords not enabled in config/config.php', 50101);
		}
	}

	public static function generateTemporaryPassword($userid, $expiration = 0) {
		self::requireEnabledTemporaryPassword();

		$key = Config::OWNCLOUD_TEMPORARY_PASSWORD_SHAREDSECRET;
		$max_age = Config::OWNCLOUD_TEMPORARY_PASSWORD_DEFAULT_MAX_AGE;

		if ($expiration > 0) {
			// Use a fixed expiration date
			$signed_combo_array = self::getSignedUsercomboArray(
				$userid,
				$key,
				false,
				$expiration
			);
		} else {
			// Dynamically expire after x seconds
			$signed_combo_array = self::getSignedUsercomboArray(
				$userid,
				$key,
				$max_age
			);
		}

		return $signed_combo_array['useridcombo'] . ':' . $signed_combo_array['secret'];
	}

	private static function validateTemporaryPassword($tp) {
		self::requireEnabledTemporaryPassword();

		$split = explode(':', $tp);

		if (count($split) !== 3) {
			// Invalid tp part length
			return false;
		}

		list($expiration, $userid, $hmac) = $split;

		if (time() > $expiration) {
			// Expired
			return false;
		}

		$calctp = self::generateTemporaryPassword($userid, $expiration);
		if (self::constantTimeEquals($tp, $calctp) !== true) {
			// Incorrect hmac
			return false;
		}

		return true;
	}

	public static function getSignedComboFromTemporaryPassword($tp) {
		self::requireEnabledTemporaryPassword();

		if (self::validateTemporaryPassword($tp) !== true) {
			throw new \Exception('Invalid Temporary Password', 50102);
		}

		// TODO(leon): Do we need to split again?
		$split = explode(':', $tp);
		list($expiration, $userid, $hmac) = $split;

		return self::getSignedCombo($userid, $expiration);
	}

	public static function constantTimeEquals($a, $b) {
		$alen = strlen($a);
		$blen = strlen($b);

		if ($alen !== $blen) {
			return false;
		}

		$result = 0;
		for ($i = 0; $i < $alen; $i++) {
			$result |= (ord($a[$i]) ^ ord($b[$i]));
		}

		return $result === 0;
	}

	public static function getAllowedIframeDomains() {
		$origin = Helper::getSpreedWebRtcOrigin();

		return array(
			$origin,
			'mailto:', // For social sharing via email
		);
	}

}
