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

namespace OCA\SpreedME\Debug;

use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Settings\Settings;

class Debug {

	private function __construct() {

	}

	public static function runAllTests() {
		$tests = array();
		$methods = get_class_methods(get_class());
		foreach ($methods as $method) {
			if (strpos($method, 'test') === 0) {
				$tests[] = $method;
			}
		}

		printf('<b>%s Version %s</b>%s', Settings::APP_TITLE, Helper::getOwnAppVersion(), '<br /><br />' . PHP_EOL);

		$found_errors = false;
		foreach ($tests as $number => $test) {
			$error = self::$test();
			$message = 'Passed without an error';
			if (!empty($error)) {
				$found_errors = true;
				$message = sprintf('Error: %s', $error);
			}
			printf('Ran test #%s (%s):<br />&nbsp;&nbsp;&nbsp;%s<br /><br />' . PHP_EOL, ($number + 1), $test, $message);
		}

		if (!$found_errors) {
			echo 'Passed all tests. Everything seems to be set up correctly! :)';
		} else {
			echo 'Some tests failed. :(';
		}
	}

	private static function testOwncloudPhpConfigFile() {
		if (strlen(Helper::getConfigValue('SPREED_WEBRTC_SHAREDSECRET')) !== 64) {
			return 'SPREED_WEBRTC_SHAREDSECRET in config/config.php must be a 64 character hexadecimal string.';
		}

		if (!ctype_xdigit(Helper::getConfigValue('SPREED_WEBRTC_SHAREDSECRET'))) {
			return 'Invalid SPREED_WEBRTC_SHAREDSECRET in config/config.php. Secret may only contain hexadecimal characters.';
		}

		if (Helper::getConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED') === true) {
			if (strlen(Helper::getConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY')) !== 64) {
				return 'OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY in config/config.php must be a 64 character hexadecimal string.';
			}

			if (!ctype_xdigit(Helper::getConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY'))) {
				return 'Invalid OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY in config/config.php. Key may only contain hexadecimal characters.';
			}
		}
	}

	private static function testOwncloudJavascriptConfigFile() {
		$url = Helper::getOwnAppPath() . '/extra/static/config/OwnCloudConfig.js';
		$response = file_get_contents($url);

		if (!Helper::doesJsConfigExist()) {
			return;
		}

		if (strpos($response, 'OWNCLOUD_ORIGIN') === false) {
			return 'Did not find OwnCloudConfig.js at ' . $url;
		}
	}

	private static function testSpreedWebRTCAPI() {
		// Force ?debug & other query params removal
		$url = Helper::getSpreedWebRtcUrl(false, false);
		$config_url = $url . 'api/v1/config';

		// TODO(leon): Switch to curl as this is shitty?
		$response = file_get_contents($config_url, false, stream_context_create(
			array(
				'http' => array(
					'timeout' => 5,
				),
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
				),
			)
		));

		if (empty($response)) {
			return 'Unable to connect to WebRTC at ' . $url . '. Did you set a correct SPREED_WEBRTC_ORIGIN and SPREED_WEBRTC_BASEPATH in config/config.php?';
		}

		$json = json_decode($response, true);
		$error = json_last_error();

		if ($error !== JSON_ERROR_NONE) {
			return 'WebRTC API config endpoint returned incorrect json response: <pre>' . htmlspecialchars($response) . '</pre>';
		}

		if (!isset($json['Plugin']) || empty($json['Plugin'])) {
			return 'WebRTC API config endpoint does not include a plugin';
		}
	}

}
