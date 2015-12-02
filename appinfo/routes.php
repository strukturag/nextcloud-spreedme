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

return [
	'routes' => [
		// Page
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#webrtc', 'url' => '/webrtc', 'verb' => 'GET'],
		['name' => 'page#file_selector', 'url' => '/file-selector', 'verb' => 'GET'],
		['name' => 'page#display_changelog', 'url' => '/changelog', 'verb' => 'GET'],
		['name' => 'page#debug', 'url' => '/debug', 'verb' => 'GET'],

		// API
		['name' => 'api#get_user_config', 'url' => '/api/v1/user/config', 'verb' => 'GET'],
		['name' => 'api#get_login', 'url' => '/api/v1/user/login', 'verb' => 'GET'],
		['name' => 'api#download_file', 'url' => '/api/v1/file/download', 'verb' => 'GET'],
	],
];
