/**
 * Nextcloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

// This file is loaded in Nextcloud context

(function($, OC) {
$(document).ready(function() {

	if (typeof OwnCloudConfig === 'undefined') {
		OwnCloudConfig = {OWNCLOUD_ORIGIN: '',};
	}

	var $c = $('#spreedme');
	var baseUrl = OC.generateUrl('/apps/spreedme');
	var showMessage = function(message) {
		$c.find('.message')
			.removeClass('hidden')
			.text(message);
	};
	var removeMessage = function() {
		$c.find('.message')
			.addClass('hidden');
	};
	var showError = function(message) {
		showMessage('Error! ' + message);
	};
	var saveConfig = function(config, cb_success, cb_error) {
		$.ajax({
			url: baseUrl + '/api/v1/admin/config',
			type: 'PATCH',
			data: {config: config},
		}).done(function (response) {
			if (response.success === true) {
				removeMessage();
				cb_success(response);
			} else {
				showError(response.error);
				cb_error(response.error);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};
	var regenerateSharedSecret = function(cb_success, cb_error) {
		$.ajax({
			url: baseUrl + '/api/v1/admin/config/regenerate/sharedsecret',
			type: 'POST',
			data: {},
		}).done(function (response) {
			if (response.success === true) {
				removeMessage();
				cb_success(response.sharedsecret);
			} else {
				cb_error(response.error);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};
	var regenerateTemporaryPasswordSigningKey = function(cb_success, cb_error) {
		$.ajax({
			url: baseUrl + '/api/v1/admin/config/regenerate/tp-key',
			type: 'POST',
			data: {},
		}).done(function (response) {
			if (response.success === true) {
				removeMessage();
				cb_success(response);
			} else {
				cb_error(response.error);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};
	var generateSpreedWebRTCConfig = function(cb_success, cb_error) {
		$.ajax({
			url: baseUrl + '/api/v1/admin/config/generate/spreed-webrtc-config',
			type: 'POST',
			data: {},
		}).done(function (response) {
			if (response.success === true) {
				removeMessage();
				cb_success(response.config);
			} else {
				cb_error(response.error);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};

	$c.find('[name="OWNCLOUD_ORIGIN"]').val(OwnCloudConfig.OWNCLOUD_ORIGIN);
	$c.find('.needs-confirmation').click(function(e) {
		var message = $(this).data('confirmation-message').replace('\\n', '\n') || 'Are you sure?';
		if (!window.confirm(message)) {
			e.stopImmediatePropagation();
			e.stopPropagation();
		}
	});
	$c.find('.select-on-click').click(function(e) {
		$(this).select();
	});
	$c.find('.do-show-advanced-settings').click(function(e) {
		$c.addClass('show-advanced-settings');
		$(this).remove();
	});

	$c.find('[name="REGENERATE_SPREED_WEBRTC_SHAREDSECRET"]').click(function(e) {
		regenerateSharedSecret(function(sharedSecret) {
			$c.find('.SPREED_WEBRTC_SHAREDSECRET')
				.removeClass('hidden')
				.find('input[type="text"]')
				.val(sharedSecret);
		}, function(error) {

		});
	});
	$c.find('[name="GENERATE_SPREED_WEBRTC_CONFIG"]').click(function(e) {
		generateSpreedWebRTCConfig(function(config) {
			$c.find('.SPREED_WEBRTC_CONFIG')
				.removeClass('hidden')
				.find('textarea')
				.val(config);
		}, function(error) {

		});
	});

	$c.find('form').submit(function(e) {
		e.preventDefault();

		// TODO(leon): Can this be improved? $.serializeArray / $.serialize removes the checkbox if it's not checked :/
		var config = {
			SPREED_WEBRTC_ORIGIN: $c.find('[name="SPREED_WEBRTC_ORIGIN"]').val(),
			SPREED_WEBRTC_BASEPATH: $c.find('[name="SPREED_WEBRTC_BASEPATH"]').val(),
			SPREED_WEBRTC_IS_SHARED_INSTANCE: $c.find('[name="SPREED_WEBRTC_IS_SHARED_INSTANCE"]').is(':checked'),
			SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS: $c.find('[name="SPREED_WEBRTC_UPLOAD_FILE_TRANSFERS"]').is(':checked'),
			SPREED_WEBRTC_ALLOW_ANONYMOUS_FILE_TRANSFERS: $c.find('[name="SPREED_WEBRTC_ALLOW_ANONYMOUS_FILE_TRANSFERS"]').is(':checked'),
			OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED: $c.find('[name="OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED"]').is(':checked'),
		};

		saveConfig(config, function() {
			// TODO(leon): Make the box green
			showMessage('The settings were saved');
		}, function(error) {

		});
	});

});
})(jQuery, OC);
