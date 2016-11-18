<?php

use \OCA\SpreedME\Helper\Helper;

$iframe_url = Helper::getSpreedWebRtcUrl();

script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'webrtc');
style('spreedme', 'webrtc');

// Nextcloud 11 file upload
if (is_file(Helper::getAppPath('files') . 'js/file-upload.js')) {
	script('files', 'file-upload');
}

$sharedConfig = array(
	'is_guest' => $_['is_guest'] === true,
	'features' => array(
		'temporary_password' => Helper::getConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED') === true,
	),
);

?>

<script id="sharedconfig" type="application/json"><?php
// Not an issue to output this directly, json_encode by default has disabled JSON_UNESCAPED_SLASHES
echo json_encode($sharedConfig);
?></script>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
