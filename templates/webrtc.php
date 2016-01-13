<?php

use \OCA\SpreedME\Config\Config;
use \OCA\SpreedME\Helper\Helper;

$iframe_url = Helper::getSpreedWebRtcUrl();

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'webrtc');
style('spreedme', 'webrtc');

// Be careful with this!
// Only use boolean values, else this could result in a potential XSS vulnerability
$sharedConfig = array(
	'is_guest' => $_['is_guest'] === true,
	'features' => array(
		'temporary_password' => Config::OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED === true,
	),
);

?>

<script id="sharedconfig" type="application/json"><?php echo json_encode($sharedConfig); ?></script>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
