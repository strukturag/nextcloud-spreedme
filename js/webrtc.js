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

(function($, OC, OwnCloudConfig, PostMessageAPI) {
$(document).ready(function() {

	var iframe = $("#container iframe").get(0);

	var ALLOWED_PARTNERS = (function() {
		var parser = document.createElement("a");
		parser.href = iframe.src;
		return [
			parser.origin
			//, "https://" + parser.host
			//, "http://" + parser.host
		];
	})();

	var postMessageAPI = new PostMessageAPI({
		allowedPartners: ALLOWED_PARTNERS,
		iframe: iframe
	});
	var currentRoom = decodeURIComponent(window.location.hash.replace("#", "")) || "";

	var getConfig = function() {
		postMessageAPI.post({
			config: {
				// Use own origin, as this is possibly used by a different context
				baseURL: OwnCloudConfig.OWNCLOUD_ORIGIN + OC.generateUrl("/apps/spreedwebrtc")
			},
			type: "config"
		});
	};

	var getUserConfig = function() {
		var url = OC.generateUrl("/apps/spreedwebrtc/api/v1/user/config");
		$.ajax({
			url: url,
			method: "GET",
		})
		.done(function(userConfig) {
			postMessageAPI.post({
				userConfig: userConfig,
				type: "userConfig"
			});
			getUserBuddyPicture();
		});
	};

	var getLogin = function() {
		var url = OC.generateUrl("/apps/spreedwebrtc/api/v1/user/login");
		$.ajax({
			url: url,
			method: "GET",
		})
		.done(function(login) {
			postMessageAPI.post({
				login: login,
				type: "login"
			});
		});
	};

	var convertImgToBase64URL = function(url, callback, outputFormat) {
		var img = new Image();
		img.onload = function() {
			var canvas = document.createElement("canvas");
			var ctx = canvas.getContext("2d");
			canvas.height = this.height;
			canvas.width = this.width;
			ctx.drawImage(this, 0, 0);
			var dataURL = canvas.toDataURL(outputFormat);
			callback(dataURL);
			canvas = null;
		};
		img.src = url;
	};

	var getUserBuddyPicture = function() {
		var userid = OC.currentUser;
		var size = 256;
		var url = OC.generateUrl("/avatar/") + userid + "/" + size;

		convertImgToBase64URL(url + "?requesttoken=" + encodeURIComponent(oc_requesttoken), function(userBuddyPicture) {
			postMessageAPI.post({
				userBuddyPicture: userBuddyPicture,
				type: "userBuddyPicture"
			});
		});
	};

	var onInit = function() {
		if (currentRoom !== "") {
			postMessageAPI.post({
				type: "changeRoom",
				changeRoom: currentRoom
			});
		}
		getConfig();
		getLogin();
		getUserConfig();
	};

	var roomUpdated = function(room) {
		currentRoom = room;
		window.location.hash = room;
	};

	var openFilePicker = function(config) {
		OC.dialogs.filepicker(config.title, function(selectedFiles) {
			postMessageAPI.post({
				selectedFiles: selectedFiles,
				type: "selectedFiles"
			});
		}, config.allowMultiSelect, config.filterByMIME, null, config.withDetails);
	};

	var downloadFile = function(file, event) {
		var url = OC.generateUrl("/apps/spreedwebrtc/api/v1/file/download") + "?requesttoken=" + encodeURIComponent(oc_requesttoken) + "&file=" + encodeURIComponent(file);

		// jQuery doesn't support blob responses..
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				postMessageAPI.answerRequest(event, {
					data: this.response,
					file: file,
					type: "downloadFile"
				});
			}
		};
		xhr.open("GET", url);
		xhr.responseType = "blob";
		xhr.send();
	};

	var createFolder = function(path, name) {
		var fd = new FormData();
		fd.append('dir', path);
		fd.append('foldername', name);
		//fd.append('requesttoken', oc_requesttoken);
		return $.ajax({
			type: 'POST',
			url: OC.generateUrl('/apps/files/ajax/newfolder.php'),
			data: fd,
			processData: false,
			contentType: false
		});
	};

	var uploadBlob = function(obj, event) {
		var doUpload = function(blob, filename) {
			var uploadFolderPath = (function() {
				var date = new Date();
				return 'Spreed WebRTC Downloads/' + date.getFullYear() + '/' + (date.getMonth() + 1);
			})();
			var upload = function(blob, filename) {
				var fd = new FormData();
				fd.append('dir', '/');
				fd.append('file_directory', uploadFolderPath);
				fd.append('files[]', blob, filename);
				//fd.append('requesttoken', oc_requesttoken);
				return $.ajax({
					type: 'POST',
					url: OC.generateUrl('/apps/files/ajax/upload.php'),
					data: fd,
					processData: false,
					contentType: false
				});
			}
			var uploadNonOverwrite = (function() {
				var origFilename = '';
				var counter = 2;
				var deferred = $.Deferred();
				var getFilenameWithCounter = function(filename) {
					var parts = filename.split('.');
					var extension = parts.pop();
					var name = parts.pop();
					parts.push(name + ' ' + counter);
					parts.push(extension);
					counter++;
					return parts.join('.');
				};
				return function(blob, filename) {
					if (!origFilename) {
						origFilename = filename;
					}
					upload(blob, filename)
					.done(function(response) {
						var json = $.parseJSON(response)[0];
						if (json.status === 'existserror') {
							return uploadNonOverwrite(blob, getFilenameWithCounter(origFilename));
						} else {
							// No error
							deferred.resolve({
								response: response,
								savedFilename: filename,
								path: uploadFolderPath
							});
						}
					});
					return deferred;
				};
			})();

			return uploadNonOverwrite(blob, filename);
		};

		doUpload(obj.blob, obj.name)
		.then(function(data) {
			postMessageAPI.answerRequest(event, {
				data: data,
				type: "uploadBlob"
			});
		});
	};

	postMessageAPI.bind(function(event) {
		var message = event.data[event.data.type];
		switch (event.data.type) {
		case "init":
			onInit();
			break;
		case "roomChanged":
			var room = message;
			roomUpdated(room);
			break;
		case "openFilePicker":
			openFilePicker(message);
			break;
		case "downloadFile":
			downloadFile(message, event);
			break;
		case "uploadBlob":
			uploadBlob(message, event);
			break;
		default:
			console.log("Got unsupported message type", event.data.type);
		}
	});

});
})(jQuery, OC, OwnCloudConfig, PostMessageAPI);
