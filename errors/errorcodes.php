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

namespace OCA\SpreedME\Errors;

class ErrorCodes {

	const NOT_LOGGED_IN = 50001;
	const TEMPORARY_PASSWORD_NOT_ENABLED = 50101;
	const TEMPORARY_PASSWORD_INVALID = 50102;
	const TEMPORARY_PASSWORD_INVALID_USERID = 50103;

	private function __construct() {

	}

}
