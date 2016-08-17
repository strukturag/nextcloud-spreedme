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

use OCP\AppFramework\App;

$app = new App(Settings::APP_ID);
$container = $app->getContainer();

return $container->query('OCA\SpreedME\Controller\AdminPageController')->index()->render();
