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

namespace OCA\SpreedME\Settings;

class Settings {

	const APP_ID = 'spreedme';
	const APP_TITLE = 'Spreed.ME';
	const APP_ICON = 'app.svg';

	const SPREED_WEBRTC_USERCOMBO_MAX_AGE = 20;

	const SPREEDME_SERVICEUSER_USERNAME = 'spreedme_service';
	const SPREEDME_SERVICEUSER_UPLOADFOLDER = 'spreed-webrtc-uploads';
	const SPREEDME_SERVICEUSER_MAX_UPLOAD_SIZE_ANONYMOUS = 1024 * 1024 * 10; // 10 MB
	const SPREEDME_SERVICEUSER_MAX_UPLOAD_SIZE_LOGGEDIN = 1024 * 1024 * 20; // 20 MB

	private function __construct() {

	}

}
