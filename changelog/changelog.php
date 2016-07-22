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

namespace OCA\SpreedME\Changelog;

use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Settings\Settings;

class Changelog {

	const CHANGELOG_FILE = 'CHANGELOG.md';

	private function __construct() {

	}

	public static function getChangesSinceVersion($since) {
		$changes_by_version = self::getAllChangesByRelease();
		$changes_since_version = array();

		foreach ($changes_by_version as $version => $changes) {
			if (version_compare($since, $version) !== -1) {
				continue;
			}

			$changes_since_version[$version] = $changes;
		}

		return $changes_since_version;
	}

	private static function getAllChangesByRelease() {
		$version_regex = '/^owncloud-' . Settings::APP_ID . ' \((.*)\)/';
		$contents = file_get_contents(Helper::getOwnAppPath() . self::CHANGELOG_FILE);
		$releases = explode("\n\n", $contents);
		$changes_by_version = array();

		foreach ($releases as $release) {
			list($version_line) = explode("\n", $release);
			$changes = str_replace($version_line . "\n", '', $release);
			$matches = array();
			preg_match($version_regex, $release, $matches);
			list($_tmp, $version) = $matches;
			if ($version && !isset($changes_by_version[$version])) {
				$changes = explode('*', $changes);
				$changes_by_line = array();
				foreach ($changes as $change) {
					if (!empty($change)) {
						// Replace multiple whitespaces, newlines, ..
						$change = preg_replace('/\s+/', ' ', trim(str_replace("\n", '', $change)));
						$changes_by_line[] = $change;
					}
				}
				$changes_by_version[$version] = $changes_by_line;
			}
		}

		return $changes_by_version;
	}

}
