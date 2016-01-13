<?php

use \OCA\SpreedME\Helper\Helper;

script('spreedme', '../extra/static/config/OwnCloudConfig');
script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'fileselector');
style('spreedme', 'fileselector');

// TODO(leon): Check if this could result in a potential XSS vulnerability
$sharedConfig = array(
	'allowed_partners' => Helper::getSpreedWebRtcOrigin(),
);

?>

<script id="sharedconfig" type="application/json"><?php echo json_encode($sharedConfig); ?></script>

<div id="debug"><b>Debug</b><br /></div>
