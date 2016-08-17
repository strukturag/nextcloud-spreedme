<?php

use \OCA\SpreedME\Helper\Helper;

style('spreedme', 'adminSettings');
if (Helper::doesJsConfigExist()) {
	script('spreedme', '../extra/static/config/OwnCloudConfig');
}
script('spreedme', 'adminSettings');

$classes = array();
$classes[] = (Helper::doesPhpConfigExist() ? 'php-config-file' : 'php-config-database');
$classes[] = (Helper::doesJsConfigExist() ? 'js-config-found' : 'js-config-missing');

?>

<div id="spreedme" class="section <?php echo implode(' ', $classes); ?>">
	<h2 class="app-name">Spreed.ME</h2>
	<a target="_blank" class="icon-info svg"
		title="Open documentation"
		href="https://github.com/strukturag/nextcloud-spreedme/blob/master/doc/admin-config.md"></a>
	<p class="hidden warning message"></p>
	<div class="show-if-php-config-file">
		<p><code>config/config.php</code> was found.</p>
		<p>If you want to change it, you need to edit the file by yourself.</p>
	</div>
	<div class="show-if-php-config-database">
		<p><code>config/config.php</code> was not found. We will use the Nextcloud database to read/write config values.</p>
		<p>You can change them here:</p>
		<form action="#" method="POST">
			<p>
				<label for="SPREED_WEBRTC_ORIGIN">SPREED_WEBRTC_ORIGIN:</label>
				<input type="text" id="SPREED_WEBRTC_ORIGIN" name="SPREED_WEBRTC_ORIGIN" placeholder=""
					value="<?php p(Helper::getDatabaseConfigValue('SPREED_WEBRTC_ORIGIN'));?>" />
			</p>
			<p>
				<label for="SPREED_WEBRTC_BASEPATH">SPREED_WEBRTC_BASEPATH:</label>
				<input type="text" id="SPREED_WEBRTC_BASEPATH" name="SPREED_WEBRTC_BASEPATH" placeholder="/webrtc/"
					value="<?php p(Helper::getDatabaseConfigValue('SPREED_WEBRTC_BASEPATH'));?>" />
			</p>
			<p class="hidden SPREED_WEBRTC_SHAREDSECRET warning">
				<!-- label for and input id removed intentionally. This makes it possible to copy&paste 'sharedsecret_secret' -->
				<label>A new SPREED_WEBRTC_SHAREDSECRET was generated.<br />Use it for <code>sharedsecret_secret</code> in Spreed WebRTC's configuration.<br />Restart Spreed WebRTC afterwards.</label>
				<input type="text" name="SPREED_WEBRTC_SHAREDSECRET" placeholder="" readonly="readonly"
					class="select-on-click" value="" />
			</p>
			<p>
				<label for="REGENERATE_SPREED_WEBRTC_SHAREDSECRET">SPREED_WEBRTC_SHAREDSECRET:</label>
				<input type="button" id="REGENERATE_SPREED_WEBRTC_SHAREDSECRET" name="REGENERATE_SPREED_WEBRTC_SHAREDSECRET"
					class="needs-confirmation" data-confirmation-message="Do you really want to generate a new shared secret?\nYou will need to change it in Spreed WebRTC's configuration, too." value="Generate new shared secret" />
			</p>
			<p>
				<label for="OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED">OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED:</label>
				<input type="checkbox" id="OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED" name="OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED"
					<?php echo (Helper::getDatabaseConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED') === true ? 'checked="checked"' : ''); ?> />
			</p>
			<p class="hidden OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY warning">
				A new OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY was generated.<br />Previously generated 'Temporary Passwords' are no longer valid.
			</p>
			<p>
				<label for="REGENERATE_OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY">OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY:</label>
				<input type="button" id="REGENERATE_OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY" name="REGENERATE_OWNCLOUD_TEMPORARY_PASSWORD_SIGNING_KEY"
					class="needs-confirmation" data-confirmation-message="Do you really want to generate a new signing key?\nAll previously generated 'Temporary Passwords' will be invalidated." value="Generate new signing key" />
			</p>
			<button type="submit" class="primary">Save settings</button>
		</form>
	</div>
	<div class="show-if-js-config-found">
		<p><code>extra/static/config/OwnCloudConfig.js</code> was found.</p>
		<p>If you want to change some of the options listed below, you need to edit the file by yourself.</p>
		<form action="#" method="POST">
			<p>
				<label for="OWNCLOUD_ORIGIN">OWNCLOUD_ORIGIN:</label>
				<input type="text" id="OWNCLOUD_ORIGIN" name="OWNCLOUD_ORIGIN" placeholder="" readonly="readonly"
					value="" />
			</p>
		</form>
	</div>
	<div class="show-if-js-config-missing">
		<p><code>extra/static/config/OwnCloudConfig.js</code> was not found.</p>
		<p>You should create it if you run Nextcloud on a different origin than Spreed WebRTC.</p>
	</div>
</div>
