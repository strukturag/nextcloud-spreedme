<?php

use \OCA\SpreedWebRTC\Config\Config;

$origin = Config::SPREED_WEBRTC_ORIGIN;

script('spreedwebrtc', '../extra/static/config/OwnCloudConfig');
script('spreedwebrtc', '../extra/static/PostMessageAPI');
script('spreedwebrtc', 'fileselector');
style('spreedwebrtc', 'fileselector');

?>

<script data-shared-config='{"allowedPartners": "<?php echo $origin; ?>"}'></script>

<div id="debug"><b>Debug</b><br /></div>
