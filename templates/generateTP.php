<?php

script('spreedme', 'generateTP');
script('spreedme', 'jquery-timepicker');
style('spreedme', 'generateTP');
style('spreedme', 'jquery-timepicker');

?>

<div>
	<p>Here you can generate Temporary Passwords for users which do not have an ownCloud account in your installation. Just enter:</p>
	<ol>
		<li>The username of the person you want to invite, e.g. "John Doe". This is the name which will be shown in the buddy list of spreed-webrtc.</li>
		<li>The expiration date at which the Temporary Password ("TP") should expire.</li>
	</ol>
	<p><b>Please note</b>: Once you published a TP (to a friend, ..) it can't be invalidated, until expiration date has passed. So carefully set the expiration date.<br /><br /></p>
	<div id="tp">
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

	Username: <input type="text" name="userid" placeholder="John Doe" value="" /><br />
	Expiration: <input type="text" name="expiration" value="" /><br />

	<br /><input type="submit" value="Generate" />

</form>
