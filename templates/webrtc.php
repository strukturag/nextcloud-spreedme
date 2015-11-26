<?php

use \OCA\SpreedME\Config\Config;

$origin = Config::SPREED_WEBRTC_ORIGIN;
$basepath = Config::SPREED_WEBRTC_BASEPATH;

if (empty($origin)) {
	die('Please edit the config/config.php file and set all required constants.');
}

$iframe_url = $origin . $basepath;
if (isset($_GET['debug'])) {
	$iframe_url .= '?debug';
}

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'webrtc');
style('spreedme', 'webrtc');

?>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
