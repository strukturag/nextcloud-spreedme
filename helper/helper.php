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

namespace OCA\SpreedME\Helper;

use OCA\SpreedME\Config\Config;
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
