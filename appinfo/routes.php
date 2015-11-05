<?php
/**
 * ownCloud - spreedwebrtc
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
		//['name' => 'page#install', 'url' => '/install', 'verb' => 'GET'],

		// API
		['name' => 'spreedwebrtcapi#get_csrf_token', 'url' => '/api/v1/tokenize', 'verb' => 'GET'],
		['name' => 'spreedwebrtcapi#get_config', 'url' => '/api/v1/config', 'verb' => 'GET'],
		['name' => 'spreedwebrtcapi#download_file', 'url' => '/api/v1/file/download', 'verb' => 'GET'],
		//['name' => 'spreedwebrtcapi#login', 'url' => '/api/v1/user/login', 'verb' => 'POST'],
		['name' => 'spreedwebrtcapi#get_user_config', 'url' => '/api/v1/user/config', 'verb' => 'GET'],
		['name' => 'spreedwebrtcapi#get_login', 'url' => '/api/v1/user/login', 'verb' => 'GET'],
		//['name' => 'spreedwebrtcapi#get_shares', 'url' => '/api/v1/user/shares', 'verb' => 'GET'],

		['name' => 'spreedwebrtcapi#demo', 'url' => '/api/v1/demo', 'verb' => 'GET'],
	],
];
