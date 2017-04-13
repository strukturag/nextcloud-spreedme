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

namespace OCA\SpreedME\Helper;

use OCA\SpreedME\Errors\ErrorCodes;
use OCA\SpreedME\Security\Security;
use OCA\SpreedME\Settings\Settings;

class Helper {

	private static $defaultConfig = array(
		'SPREED_WEBRTC_ORIGIN' => '',
		'SPREED_WEBRTC_BASEPATH' => '/webrtc/',
		'SPREED_WEBRTC_IS_SHARED_INSTANCE' => false,
		'SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS' => false,
		'SPREED_WEBRTC_ALLOW_ANONYMOUS_FILE_TRANSFERS' => false,
		'OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED' => false,
	);

	private function __construct() {

	}

	public static function getOwnHost($port = null) {
		$request = \OC::$server->getRequest();

		$protocol = $request->getServerProtocol();
		$hostname = $request->getInsecureServerHost();
		// The host has already been checked against the trusted_domains in
		// lib/base.php (function init).

		if (!empty($port)) {
			// Strip existing port (if any) from hostname.
			$pos = strrpos($hostname, ':');
			if ($pos !== false) {
				$hostport = substr($hostname, $pos + 1);
				if (is_numeric($hostport)) {
					$hostname = substr($hostname, 0, $pos);
				}
			}

			// Append new port (but only if it isn't the default port for the current protocol).
			$is_http = ($protocol === 'http');
			$is_https = ($protocol === 'https');
			if (($is_http && $port !== '80') || ($is_https && $port !== '443')) {
				$hostname = $hostname . ':' . $port;
			}
		}

		return $protocol . '://' . $hostname;
	}

	private static function getDefaultValue($key) {
		if (isset(self::$defaultConfig[$key])) {
			return self::$defaultConfig[$key];
		}
		return null;
	}

	private static function getFileConfigValue($key) {
		$constantName = '\OCA\SpreedME\Config\Config::' . $key;
		if (defined($constantName)) {
			return constant($constantName);
		}
		return null;
	}

	public static function getDatabaseConfigValue($key) {
		$value = \OC::$server->getConfig()->getAppValue(Settings::APP_ID, $key);
		if ($value === 'true' || $value === 'false') {
			// TODO(leon): How can we improve this?
			$value = ($value === 'true');
		}
		return $value;
	}

	public static function getDatabaseConfigValueOrDefault($key) {
		if (self::getDatabaseConfigValue('is_set_up') === true) {
			return self::getDatabaseConfigValue($key);
		}
		$default = self::getDefaultValue($key);
		if ($default !== null) {
			return $default;
		}
		return '';
	}

	public static function getConfigValue($key) {
		if (self::doesPhpConfigExist()) {
			return self::getFileConfigValue($key);
		}
		return self::getDatabaseConfigValue($key);
	}

	private static function setDatabaseConfigValue($key, $value) {
		\OC::$server->getConfig()->setAppValue(Settings::APP_ID, $key, $value);
	}

	public static function setDatabaseConfigValueIfEnabled($key, $value) {
		if (self::doesPhpConfigExist()) {
			throw new \Exception('config/config.php exists. Can\'t modify DB config values', ErrorCodes::DB_CONFIG_ERROR_CONFIG_PHP_EXISTS);
		}
		self::setDatabaseConfigValue($key, $value);
	}

	public static function getOwnAppVersion() {
		return \OCP\App::getAppVersion(Settings::APP_ID);
	}

	public static function getOwnAppPath() {
		return realpath(__DIR__ . '/..') . '/';
	}

	public static function getAppPath($app) {
		return sprintf('%s/../%s/', realpath(__DIR__ . '/..'), $app);
	}

	public static function notifyIfAppNotSetUp() {
		if (!self::doesPhpConfigExist() && self::getDatabaseConfigValue('is_set_up') !== true) {
			die('You didn\'t set up this Nextcloud app. Please open the Nextcloud admin settings page and configure this app.');
		}
	}

	public static function doesPhpConfigExist() {
		return class_exists('\OCA\SpreedME\Config\Config', true);
	}

	public static function doesJsConfigExist() {
		return is_file(self::getOwnAppPath() . 'extra/static/config/OwnCloudConfig.js');
	}

	public static function getSpreedWebRtcOrigin() {
		$origin = self::getConfigValue('SPREED_WEBRTC_ORIGIN');
		$is_port = !empty($origin) && $origin[0] === ':';
		$port = null;
		if ($is_port) {
			$port = str_replace(':', '', $origin);
		}
		if (empty($origin) || $is_port) {
			$origin = self::getOwnHost($port);
		}
		return $origin;
	}

	public static function getSpreedWebRtcUrl($debug = null, $includeQueryParams = true) {
		$origin = self::getSpreedWebRtcOrigin();
		$basepath = self::getConfigValue('SPREED_WEBRTC_BASEPATH');
		$url = $origin . $basepath;
		$params = array();

		if ($debug !== false) {
			if ($debug === true || isset($_GET['debug'])) {
				$params['debug'] = true;
			}
		}
		if ($includeQueryParams) {
			if (self::doesJsConfigExist()) {
				$params['load_config_js'] = true;
			}
			$query = http_build_query($params);
			if (!empty($query)) {
				$url .= '?' . $query;
			}
		}

		return $url;
	}

	public static function getRemoteSpreedWebRTCConfig() {
		// Force ?debug & other query params removal
		$url = self::getSpreedWebRtcUrl(false, false);
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
			throw new \Exception('Unable to connect to WebRTC at ' . $url . '. Did you set a correct SPREED_WEBRTC_ORIGIN and SPREED_WEBRTC_BASEPATH in config/config.php?', ErrorCodes::REMOTE_CONFIG_EMPTY);
		}

		$json = json_decode($response, true);
		$error = json_last_error();

		if ($error !== JSON_ERROR_NONE) {
			throw new \Exception('WebRTC API config endpoint returned incorrect json response: <pre>' . htmlspecialchars($response) . '</pre>', ErrorCodes::REMOTE_CONFIG_INVALID_JSON);
		}

		return $json;
	}

	public static function generateSpreedWebRTCConfig() {
		$configPath = self::getOwnAppPath() . 'doc/spreed-webrtc-minimal-config.txt';
		// Race condition if file is removed after this check, but we don't care :)
		if (!is_file($configPath)) {
			return;
		}
		$config = file_get_contents($configPath);
		if (self::getDatabaseConfigValue('SPREED_WEBRTC_SHAREDSECRET') === '') {
			Security::regenerateSharedSecret();
		}
		$replace = array(
			'/webrtc/' => self::getDatabaseConfigValueOrDefault('SPREED_WEBRTC_BASEPATH'),
			'the-default-secret-do-not-keep-me' => Security::getRandomHexString(256 / 4), // 256 bit
			'the-default-encryption-block-key' => Security::getRandomHexString(256 / 4), // 256 bit
			'i-did-not-change-the-public-token-boo' => Security::getRandomHexString(256 / 4), // 256 bit
			'/absolute/path/to/nextcloud/apps/spreedme/extra' => self::getOwnAppPath() . 'extra',
			'some-secret-do-not-keep' => self::getDatabaseConfigValue('SPREED_WEBRTC_SHAREDSECRET'),
		);
		try {
			$remoteConfig = self::getRemoteSpreedWebRTCConfig();
			if (strpos($remoteConfig['Version'], 'unreleased') !== false) {
				// Uncomment www root directive
				$replace[';root'] = 'root';
			}
		} catch (\Exception $e) {
			// TODO(leon): Handle error
		}
		return strtr($config, $replace);
	}

	public static function doesServiceUserExist() {
		$users = \OC::$server->getUserManager();
		return $users->userExists(Settings::SPREEDME_SERVICEUSER_USERNAME);
	}

	public static function areFileTransferUploadsAllowed() {
		return self::getConfigValue('SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS') === true;
	}

	public static function areAnonymousFileTransfersAllowed() {
		return self::getConfigValue('SPREED_WEBRTC_ALLOW_ANONYMOUS_FILE_TRANSFERS') === true;
	}

	public static function createServiceUserUnlessExists() {
		$users = \OC::$server->getUserManager();
		if (!self::doesServiceUserExist()) {
			$pass = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(50);
			try {
				if ($users->createUser(Settings::SPREEDME_SERVICEUSER_USERNAME, $pass) === false) {
					throw new \Exception('Backend does not implement CREATE_USER action');
				}
			} catch (\Exception $e) {
				// User already exists or couldn't be created. Thanks for being so specific.
				// Pass error along
				throw $e;
			}
		}
		// User already exists
		return true;
	}

	public static function getServiceUserMaxUploadSize() {
		if (\OC_User::getUser() === false) {
			// Anonymous user
			return Settings::SPREEDME_SERVICEUSER_MAX_UPLOAD_SIZE_ANONYMOUS;
		}
		// Logged in user
		return Settings::SPREEDME_SERVICEUSER_MAX_UPLOAD_SIZE_LOGGEDIN;
	}

	public static function runAsServiceUser($func) {
		if (!self::areFileTransferUploadsAllowed() || !self::doesServiceUserExist()) {
			throw new \Exception('Service user not usable');
		}
		if (\OC_User::getUser() === false && !self::areAnonymousFileTransfersAllowed()) {
			throw new \Exception('Anonymous uploads are not allowed');
		}

		$serviceUserRootPath = sprintf('/%s/files', Settings::SPREEDME_SERVICEUSER_USERNAME);

		// TODO(leon): Maybe replace order: First set user id, then chroot
		$oldUser = \OC_User::getUser();
		if ($oldUser === false) {
			// We're an anonymous user, init filesystem for service user
			\OC\Files\Filesystem::init(Settings::SPREEDME_SERVICEUSER_USERNAME, $serviceUserRootPath);
		} else {
			// User is logged in, chroot into service user's files folder
			$fsView = \OC\Files\Filesystem::getView();
			$oldRoot = $fsView->getRoot();
			$fsView->chroot($serviceUserRootPath);
		}

		// Switch user ID
		\OC_User::setUserId(Settings::SPREEDME_SERVICEUSER_USERNAME);

		$ret = $func();

		// Switch user ID back again
		\OC_User::setUserId($oldUser);

		if ($oldUser === false) {
			// Tear down if we're an anonymous user
			\OC\Files\Filesystem::tearDown();
		} else {
			// Switch back to old user
			// chroot back to previous root folder
			$fsView->chroot($oldRoot);
		}

		return $ret;
	}

}
