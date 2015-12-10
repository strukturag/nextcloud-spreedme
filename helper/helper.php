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

namespace OCA\SpreedME\Helper;

use OCA\SpreedME\Config\Config;
use OCA\SpreedME\Settings\Settings;

class Helper {

	private function __construct() {

	}

	public static function getOwnHost($port = null) {
		$is_http = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off');
		$protocol = ($is_http ? 'http' : 'https');
		$hostname = $_SERVER['SERVER_NAME'];
		if ($port === null) {
			$port = $_SERVER['SERVER_PORT'];
		}
		$is_default_port = ($is_http && $port === '80') || (!$is_http && $port === '443');
		$optional_port = (!empty($port) && !$is_default_port ? ':' . $port : '');

		return $protocol . '://' . $hostname . $optional_port;
	}

	public static function getOwnAppVersion() {
		return \OCP\App::getAppVersion(Settings::APP_ID);
	}

	public static function getOwnAppPath() {
		return getcwd() . '/apps/' . Settings::APP_ID . '/';
	}

	public static function notifyIfAppNotSetUp() {
		if (!class_exists('\OCA\SpreedME\Config\Config', true) || !is_file(self::getOwnAppPath() . 'extra/static/config/OwnCloudConfig.js')) {
			die('You didn\'t set up this ownCloud app. Please follow the instructions in the README.md file in the apps/' . Settings::APP_ID . ' folder.');
		}
	}

	public static function getSpreedWebRtcOrigin() {
		$origin = Config::SPREED_WEBRTC_ORIGIN;
		$is_port = $origin[0] === ':';
		$port = null;
		if ($is_port) {
			$port = str_replace(':', '', $origin);
		}
		if (empty($origin) || $is_port) {
			$origin = self::getOwnHost($port);
		}
		return $origin;
	}

	public static function getSpreedWebRtcUrl($debug = null) {
		$origin = self::getSpreedWebRtcOrigin();
		$basepath = Config::SPREED_WEBRTC_BASEPATH;

		$url = $origin . $basepath;
		if ($debug !== false) {
			if ($debug === true || isset($_GET['debug'])) {
				$url .= '?debug';
			}
		}

		return $url;
	}

}
