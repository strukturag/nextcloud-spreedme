<?php

use \OCA\SpreedME\Helper\Helper;

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'fileselector');
style('spreedme', 'fileselector');

$sharedConfig = array(
	'allowed_partners' => Helper::getSpreedWebRtcOrigin(),
);

?>

<script id="sharedconfig" type="application/json"><?php
// Not an issue to output this directly, json_encode by default has disabled JSON_UNESCAPED_SLASHES
echo json_encode($sharedConfig);
?></script>

<div id="debug"><b>Debug</b><br /></div>
