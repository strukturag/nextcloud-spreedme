<?php

use \OCA\SpreedME\Helper\Helper;

$iframe_url = Helper::getSpreedWebRtcUrl();

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'webrtc');
style('spreedme', 'webrtc');

$is_guest = $_['is_guest'];

?>

<script data-shared-config='{"isGuest": <?php echo $is_guest ? 'true' : 'false'; ?>}'></script>

<div id="debug"><b>Debug</b><br /></div>

<div id="container">
	<iframe src="<?php echo $iframe_url; ?>" allowfullscreen></iframe>
</div>
