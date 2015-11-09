/**
 * ownCloud - spreedwebrtc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

// This file is loaded in ownCloud context

(function ($, OC, OwnCloudConfig, PostMessageAPI) {
$(document).ready(function() {

	if (!window.opener) {
		return;
	}

	var sharedConfig = $.parseJSON($("script[data-shared-config]").attr("data-shared-config"));
	var ALLOWED_PARTNERS = sharedConfig.allowedPartners.split(",");

	var postMessageAPI = new PostMessageAPI({
		allowedPartners: ALLOWED_PARTNERS,
		opener: window.opener
	});

	var open = function(config) {
		OC.dialogs.filepicker(config.title, function(selectedFiles) {
			postMessageAPI.post({
				message: selectedFiles,
				type: "files"
			});
			window.close();
		}, config.allowMultiSelect, config.filterByMIME, null, config.withDetails);
	};

	postMessageAPI.bind(function(event) {
		switch (event.data.type) {
		case "open":
			open(event.data.message);
			break;
		default:
			console.log("Got unsupported message type", event.data.type);
		}
	});

	postMessageAPI.post({
		type: "init"
	});

});
})(jQuery, OC, OwnCloudConfig, PostMessageAPI);
