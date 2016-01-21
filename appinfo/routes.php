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

return [
	'routes' => [
		// Page
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#webrtc', 'url' => '/webrtc', 'verb' => 'GET'],
		['name' => 'page#file_selector', 'url' => '/file-selector', 'verb' => 'GET'],
		['name' => 'page#display_changelog', 'url' => '/admin/changelog', 'verb' => 'GET'],
		['name' => 'page#debug', 'url' => '/admin/debug', 'verb' => 'GET'],
		['name' => 'page#generate_temporary_password', 'url' => '/admin/tp', 'verb' => 'GET'],

		// API
		['name' => 'api#get_config', 'url' => '/api/v1/config', 'verb' => 'GET'],
		['name' => 'api#get_user_config', 'url' => '/api/v1/user/config', 'verb' => 'GET'],
		['name' => 'api#get_token', 'url' => '/api/v1/user/token', 'verb' => 'GET'],
		['name' => 'api#get_token_with_temporary_password', 'url' => '/api/v1/token/withtp', 'verb' => 'POST'],
		['name' => 'api#generate_temporary_password', 'url' => '/api/v1/admin/tp', 'verb' => 'POST'],
		['name' => 'api#download_file', 'url' => '/api/v1/file/download', 'verb' => 'GET'],
	],
];
