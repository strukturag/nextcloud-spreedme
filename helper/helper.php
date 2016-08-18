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
use OCA\SpreedME\Settings\Settings;

class Helper {

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

	private static function getFileConfigValue($key) {
		return constant('\OCA\SpreedME\Config\Config::' . $key);
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
		$defaultConfig = array(
			'SPREED_WEBRTC_ORIGIN' => '',
			'SPREED_WEBRTC_BASEPATH' => '/webrtc/',
			'SPREED_WEBRTC_IS_SHARED_INSTANCE' => false,
			'OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED' => false,
		);
		if (self::getDatabaseConfigValue('is_set_up') === true) {
			return self::getDatabaseConfigValue($key);
		}
		if (isset($defaultConfig[$key])) {
			return $defaultConfig[$key];
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

}
