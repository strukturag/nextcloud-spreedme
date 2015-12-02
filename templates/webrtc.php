<?php

use \OCA\SpreedME\Helper\Helper;

$iframe_url = Helper::getSpreedWebRtcUrl();

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'webrtc');
style('spreedme', 'webrtc');

?>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
