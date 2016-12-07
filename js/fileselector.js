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

(function($, OC, PostMessageAPI) {
$(document).ready(function() {

	if (!window.parent) {
		return;
	}

	var sharedConfig = $.parseJSON($("#sharedconfig").html());
	var ALLOWED_PARTNERS = sharedConfig.allowed_partners.split(",");

	var postMessageAPI = new PostMessageAPI({
		allowedPartners: ALLOWED_PARTNERS,
		parent: window.parent
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
			var fileClient = window.OC.Files && window.OC.Files.getClient();
			if (fileClient && fileClient.getClient && fileClient.getFolderContents) {
				// Nextcloud 11
				var origFunction = _.bind(fileClient.getFolderContents, fileClient);
				fileClient.getFolderContents = function(dir) {
					var defer = origFunction(dir);
					$.when(defer)
					.then(function(status, files) {
						files.forEach(function(file) {
							fileDetails[file.path] = fileDetails[file.path] || [];
							fileDetails[file.path].push(file);
						});
					});
					return defer;
				};
			} else {
				// Everything else
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
		}

		OC.dialogs.filepicker((config.inIframe ? "" : config.title), function(selectedFiles) {
			selectedFiles = decorateSelectedFiles(selectedFiles);
			postMessageAPI.post({
				message: selectedFiles,
				type: "filesSelected"
			});
			if (!config.inIframe) {
				window.close();
			} else {
				postMessageAPI.post({
					message: "",
					type: "close"
				});
			}
		}, config.allowMultiSelect, config.filterByMIME, null, config.withDetails);

		setTimeout(function() {
			if (config.inIframe) {
				$(".oc-dialog")
					.addClass("in-iframe");
			}
		}, 100);
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
})(jQuery, OC, PostMessageAPI);
