<?php

script('spreedme', 'generateTP');
script('spreedme', 'jquery-timepicker');
style('spreedme', 'jquery-timepicker');

?>

<div>
	<p id="info">
		Here you can generate Temporary Passwords for users which do not have an ownCloud account in your installation.<br />
		Just enter:<br />
		  1. The username of the person you want to invite (this is the name which will be shown in the buddy list of spreed-webrtc)<br />
		  2. The expiration date at which the Temporary Password ("TP") should expire<br />
		<b>Please note</b>: Once you published a TP (to friends, family, ..) it can't be invalidated, until expiration date it reached. So carefully set the expiration date.<br /><br />
	</p>
	<div id="tp"></div>
</div>

<form action="#" method="POST">

	Username: <input type="text" name="userid" value="" /><br />
	Expiration: <input type="text" name="expiration" value="" /><br />

	<br /><input type="submit" />

</form>
