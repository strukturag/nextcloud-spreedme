<?php

use \OCA\SpreedME\Helper\Helper;

script('spreedme', '../extra/static/PostMessageAPI');
script('spreedme', 'generateTP');
script('spreedme', 'jquery-timepicker');
style('spreedme', 'generateTP');
style('spreedme', 'jquery-timepicker');

// TODO(leon): Check if this could result in a potential XSS vulnerability
$sharedConfig = array(
	'allowed_partners' => Helper::getSpreedWebRtcOrigin(),
);

?>

<script id="sharedconfig" type="application/json"><?php echo json_encode($sharedConfig); ?></script>

<div>
	<div id="infotext">
		<p>Enter the following information to generate a Temporary Password ("TP"), which non-ownCloud users can use to log in:</p>
		<ol>
			<li>The name of the person you want to invite.</li>
			<li>The expiration date at which the TP should expire.</li>
		</ol>
		<p><b>Please note</b>: Once you published a TP (to a friend, ..) it can't be invalidated, until expiration date has passed. So carefully set the expiration date.<br /><br /></p>
	</div>
	<div id="tp" class="hidden">
		<div id="error" class="hidden">
			<p><b>Error!</b></p>
			<p id="errorcode"></p>
		</div>
		<div id="generated" class="hidden">
			<p class="info"><b>Temporary Password generated!</b><br /></p>
			Copy and send this URL to your partner:<br />
			<input type="text" readonly="readonly" name="temporarypasswordurl" /><br />
			Alternatively, send the password:<br />
			<input type="text" readonly="readonly" name="temporarypassword" />
		</div>
	</div>
</div>

<form action="#" method="POST">
	<p>
		<label for="userid">Name:</label>
		<input type="text" id="userid" name="userid" placeholder="John Doe" value="" />
	</p>
	<p>
		<label for="expiration">Expiration:</label>
		<input type="text" id="expiration" name="expiration" value="" />
	</p>
	<button type="submit" class="primary">Generate</button>
</form>
