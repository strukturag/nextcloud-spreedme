<?php

use \OCA\SpreedWebRTC\Config\Config;

$origin = Config::SPREED_WEBRTC_ORIGIN;
$basepath = Config::SPREED_WEBRTC_BASEPATH;

if (empty($origin) || empty($basepath)) {
	die('Please edit the config/config.php and set all required constants.');
}

$iframe_url = $origin . $basepath;
if (isset($_GET['debug'])) {
	$iframe_url .= '?debug';
}

script('spreedwebrtc', '../extra/static/config/OwnCloudConfig');
script('spreedwebrtc', '../extra/static/PostMessageAPI');
script('spreedwebrtc', 'webrtc');
style('spreedwebrtc', 'webrtc');

?>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
