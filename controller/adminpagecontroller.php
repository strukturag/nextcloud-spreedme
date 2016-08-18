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

namespace OCA\SpreedME\Controller;

use OCA\SpreedME\Settings\Settings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class AdminPageController extends Controller {

	private $userid;

	public function __construct($appName, IRequest $request, $userId) {
		parent::__construct($appName, $request);

		$this->userid = $userId;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index() {
		$params = [];
		$response = new TemplateResponse(Settings::APP_ID, 'settings-admin', $params, 'blank');

		return $response;
	}

}
