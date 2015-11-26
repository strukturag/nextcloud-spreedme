/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

// This file is loaded in ownCloud context

(function($, OC, OwnCloudConfig, PostMessageAPI) {
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
		var fileDetails = {};
		var decorateSelectedFiles = function(selectedFiles) {
			if (!config.withDetails) {
				/*
					Currently we need this structure (see owncloud.js, FileSelector.prototype.gotSelectedFiles):
					{
						name: file.name,
						path: file.selectedPath,
						mimetype: file.mimetype,
					}
				*/
				var ret = [];
				for (var i = 0; i < selectedFiles.length; i++) {
					var filePath = selectedFiles[i];
					var filePathSplit = filePath.split("/");
					var fileName = filePathSplit.pop();
					var fileDirectory = filePathSplit.join("/") || "/";
					var filesInSameDirectory = fileDetails[fileDirectory];
					for (var j = 0; j < filesInSameDirectory.length; j++) {
						var file = filesInSameDirectory[j];
						if (file.name === fileName) {
							/*
								file structure:
								date: "3. August 2015 um 16:20:35 MESZ"
								etag: "1aae35591c2a999c9d1055a24345a15f"
								icon: "/core/img/filetypes/x-office-document.svg"
								id: "15"
								mimetype: "application/vnd.oasis.opendocument.text"
								mtime: 1438611635000
								name: "Example.odt"
								parentId: "18"
								permissions: 27
								size: 36227
								type: "file"
							*/
							file.selectedPath = fileDirectory + "/" + file.name;
							ret.push(file);
							break;
						}
					}
				}
				return ret;
			}

			// Else return original list
			return selectedFiles;
		};

		if (!config.withDetails) {
			// Add our own logic to file retrival function to save details about selected files.
			var origFunction = window.OCdialogs._getFileList;
			window.OCdialogs._getFileList = function(dir, mimeType) {
				var defer = origFunction(dir, mimeType);
				$.when(defer)
				.then(function(response) {
					var data = response.data;
					// Save them to our list
					fileDetails[data.directory] = data.files;
				});
				return defer;
			};
		}

		OC.dialogs.filepicker(config.title, function(selectedFiles) {
			selectedFiles = decorateSelectedFiles(selectedFiles);
			postMessageAPI.post({
				message: selectedFiles,
				type: "filesSelected"
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
