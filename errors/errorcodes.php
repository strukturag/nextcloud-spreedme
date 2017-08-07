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
	const TEMPORARY_PASSWORD_USERID_TOO_LONG = 50104;
	const DB_CONFIG_ERROR_CONFIG_PHP_EXISTS = 50201;
	const REMOTE_CONFIG_EMPTY = 50301;
	const REMOTE_CONFIG_INVALID_JSON = 50302;
	const FILETRANSFER_DISABLED = 50401;
	const FILETRANSFER_FAILED = 50402;

	private function __construct() {

	}

}
