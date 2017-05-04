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

	var iframe = $("#container iframe").get(0);

	var sharedConfig = $.parseJSON($("#sharedconfig").html());
	var IS_GUEST = sharedConfig.is_guest;
	var IS_TEMPORARY_PASSWORD_FEATURE_ENABLED = sharedConfig.features.temporary_password;
	var ALLOWED_PARTNERS = (function() {
		var parser = document.createElement("a");
		parser.href = iframe.src;
		return [
			parser.origin
			//, "https://" + parser.host
			//, "http://" + parser.host
		];
	})();

	var getQueryParam = function(param) {
		var query = window.parent.location.search.substring(1);
		var vars = query.split("&");
		for (var i = 0; i < vars.length; i++) {
			var pair = vars[i].split("=");
			if (pair[0] === param) {
				return window.decodeURIComponent(pair[1]);
			}
		}
		return false;
	};

	var postMessageAPI = new PostMessageAPI({
		allowedPartners: ALLOWED_PARTNERS,
		iframe: iframe
	});
	var currentRoom = decodeURIComponent(window.location.hash.replace("#", "")) || "";

	var getConfig = function() {
		postMessageAPI.post({
			config: {
				baseURL: document.location.origin + OC.generateUrl("/apps/spreedme"),
				fullURL: document.location.href,
				isGuest: IS_GUEST,
				temporaryPassword: getQueryParam("tp"),
				features: {
					temporaryPassword: IS_TEMPORARY_PASSWORD_FEATURE_ENABLED
				}
			},
			type: "config"
		});
	};

	var getGuestConfig = function() {
		postMessageAPI.post({
			guestConfig: {

			},
			type: "guestConfig"
		});
	};

	var getUserConfig = function() {
		var url = OC.generateUrl("/apps/spreedme/api/v1/user/config");
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

	var getToken = function() {
		var url = OC.generateUrl("/apps/spreedme/api/v1/user/token");
		$.ajax({
			url: url,
			method: "GET",
		})
		.done(function(token) {
			postMessageAPI.post({
				token: token,
				type: "token"
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
		if (IS_GUEST) {
			getGuestConfig();
		} else {
			getToken();
			getUserConfig();
		}
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

	var guestLogin = function(tp, event) {
		var url = OC.generateUrl("/apps/spreedme/api/v1/token/withtp");
		$.ajax({
			url: url,
			type: 'POST',
			data: $.param({tp: tp})
		}).done(function (response) {
			postMessageAPI.answerRequest(event, {
				useridcombo: response.useridcombo,
				secret: response.secret,
				success: response.success,
				type: "guestLogin"
			});
		}).fail(function (response, code) {
			postMessageAPI.answerRequest(event, {
				success: false,
				type: "guestLogin"
			});
		});
	};

	var downloadFile = function(file, event) {
		var url = OC.generateUrl("/apps/spreedme/api/v1/file/download") + "?requesttoken=" + encodeURIComponent(oc_requesttoken) + "&file=" + encodeURIComponent(file);

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
		xhr.responseType = "blob";
		xhr.open("GET", url, true);
		xhr.send();
	};

	var uploadBlob = function(obj, event) {
		var uploader = makeBlobUploader('Spreed.ME Downloads');
		return uploader(obj, event).then(function(data) {
			postMessageAPI.answerRequest(event, {
				data: data,
				type: "uploadBlob"
			});
			return data; // So further thens get 'data' as well
		});
	};

	var uploadAndShareBlob = function(obj, event) {
		var cb = function(data) {
			if (data.success) {
				data.url = document.location.origin + OC.generateUrl("/s/" + data.token);
			}
			postMessageAPI.answerRequest(event, {
				data: data,
				type: "uploadAndShareBlob"
			});
		};

		var fd = new FormData();
		fd.append('target', currentRoom);
		fd.append('file', obj.blob, obj.name);
		//fd.append('requesttoken', oc_requesttoken);
		return $.ajax({
			type: 'POST',
			url: OC.generateUrl("/apps/spreedme/api/v1/filetransfers"),
			data: fd,
			processData: false,
			contentType: false
		}).then(cb, cb);
	};

	var makeBlobUploader = function(baseFolderName) {
	return function(obj, event) {
		var uploadFolderPath = (function() {
			var padNum = function(num, count) {
				var str = '' + num;
				while (str.length < count) {
					str = '0' + str;
				}
				return str;
			};
			var date = new Date();
			return '/' + baseFolderName + '/' + date.getFullYear() + '/' + padNum(date.getMonth() + 1, 2);
		})();
		// TODO(leon): Let backend do this job, as it might cause a lot of traffic for the client..
		var FileCounter = function(filename) {
			var counter = 1;
			return {
				next: function() {
					if (counter === 1) {
						counter++;
						return filename;
					}
					var parts = filename.split('.');
					var extension = parts.pop();
					var name = parts.pop();
					parts.push(name + ' ' + counter);
					parts.push(extension);
					counter++;
					return parts.join('.');
				},
			};
		};
		// NC 11 file uploader
		var doUploadNC11 = function(blob, filename) {
			var deferred = $.Deferred();
			var fileCounter = new FileCounter(filename);
			var uploader = new OC.Uploader();
			var upload = function(blob, filename) {
				return uploader.filesClient.putFileContents(uploadFolderPath + "/" + filename, blob, {
					contentType: "text/plain;charset=utf-8",
				});
			};
			var tryUpload = function() {
				var filename = fileCounter.next();
				upload(blob, filename).then(function() {
					deferred.resolve({
						response: null,
						savedFilename: filename,
						path: uploadFolderPath
					});
				}, function(status) {
					if (status === 412) {
						// File already exists, try to upload using a different filename
						tryUpload();
					}
				});
			};
			uploader.ensureFolderExists(uploadFolderPath).then(function() {
				tryUpload();
			});
			return deferred;
		};
		// NC-pre-11 file uploader
		var doUploadPreNC11 = function(blob, filename) {
			var deferred = $.Deferred();
			var fileCounter = new FileCounter(filename);
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
			};
			var tryUpload = function() {
				var filename = fileCounter.next();
				upload(blob, filename).done(function(response) {
					var json = $.parseJSON(response)[0];
					if (json.status === 'existserror') {
						tryUpload();
					} else {
						// No error
						deferred.resolve({
							response: response,
							savedFilename: filename,
							path: uploadFolderPath
						});
					}
				});
			};
			tryUpload();
			return deferred;
		};

		var doUpload = doUploadPreNC11;
		if (OC.Uploader) {
			doUpload = doUploadNC11;
		}
		return doUpload(obj.blob, obj.name);
	};
	}; // makeBlobUploader

	var shareFile = function(obj, event) {
		// TODO(leon): Check if this is still in NC11
		// Thanks for providing a JavaScript API! :(
		var url = OC.linkToOCS('apps/files_sharing/api/v1', 2);
		url += "shares?format=json"; // Why does one explicitly need to specify the format? :(
		var path = obj;
		// var date = new Date();
		// TODO(leon): Set this to 5 minutes or so?
		var expireDate;
		// var expireDate = date.getFullYear() + "-" + padNum(date.getMonth() + 1, 2) + "-" + padNum(date.getDate(), 2);
		// TODO(leon): Do we want to protect the share with a shared secret just to be on the safe side or does the random URL provide enough entropy to be "unguessable"?
		var password;
		return $.ajax({
			type: 'POST',
			url: url,
			data: {
				path: path,
				shareType: OC.Share.SHARE_TYPE_LINK,
				permissions: OC.PERMISSION_READ,
				expireDate: expireDate,
				password: password,
				passwordChanged: false,
			},
			dataType: 'json'
		}).then(function(data) {
			var url;
			// Thanks for creating such a good API :)
			if (typeof data.ocs.data !== "undefined" && data.ocs.data.url !== "") {
				url = data.ocs.data.url;
			}
			postMessageAPI.answerRequest(event, {
				data: url,
				type: "shareFile"
			});
			return data;
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
		case "guestLogin":
			guestLogin(message, event);
			break;
		case "downloadFile":
			downloadFile(message, event);
			break;
		case "uploadBlob":
			uploadBlob(message, event);
			break;
		case "uploadAndShareBlob":
			uploadAndShareBlob(message, event);
			break;
		case "shareFile":
			shareFile(message, event);
			break;
		default:
			console.log("Got unsupported message type", event.data.type);
		}
	});

});
})(jQuery, OC, PostMessageAPI);
