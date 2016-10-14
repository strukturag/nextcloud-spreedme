/**
 * Nextcloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

// This file is loaded in WebRTC context

"use strict";

// TODO(leon): :(
(function() {
var modules = [
	'angular',
	'moment',
	'./PostMessageAPI.js',
];
// TODO(leon): Create helper script with this function, as we also need it in webrtc.js
var getQueryParam = function(param) {
	var query = window.location.search.substring(1);
	var vars = query.split("&");
	for (var i = 0; i < vars.length; i++) {
		var pair = vars[i].split("=");
		if (pair[0] === param) {
			return window.decodeURIComponent(pair[1]);
		}
	}
	return false;
};
if (getQueryParam('load_config_js') !== false) {
	modules.push('./config/OwnCloudConfig.js');
}
// Make sure OwnCloudConfig is always the last argument
define(modules, function(angular, moment, PostMessageAPI, OwnCloudConfig) {
	'use strict';

	if (typeof OwnCloudConfig === 'undefined') {
		OwnCloudConfig = {OWNCLOUD_ORIGIN: '',};
	}

	var HAS_PARENT = window !== parent;
	// TODO(leon): Create helper script with this function, as we also need it in webrtc.js
	var ALLOWED_PARTNERS = (function() {
		var allowed = [];
		var origin = OwnCloudConfig.OWNCLOUD_ORIGIN;
		var isPort = origin[0] === ':';

		if (origin && !isPort) {
			allowed.push(origin);
		} else {
			// Not set - allow own host
			var location = document.location;
			var protocol = location.protocol;
			var hostname = location.hostname;
			var port = (isPort ? origin.substring(1) : location.port);
			var isDefaultPort = (protocol === 'http:' && port === '80') || (protocol === 'https:' && port === '443');
			var optionalPort = (port && !isDefaultPort ? ':' + port : '');
			allowed.push(protocol + "//" + hostname + optionalPort);
		}

		return allowed;
	})();
	// Copied from directives/presentation.js
	var SUPPORTED_DOCUMENT_TYPES = {
		// rendered by pdfcanvas directive
		"application/pdf": "pdf",
		// rendered by odfcanvas directive
		// TODO(fancycode): check which formats really work, allow all odf for now
		"application/vnd.oasis.opendocument.text": "odf",
		"application/vnd.oasis.opendocument.spreadsheet": "odf",
		"application/vnd.oasis.opendocument.presentation": "odf",
		"application/vnd.oasis.opendocument.graphics": "odf",
		"application/vnd.oasis.opendocument.chart": "odf",
		"application/vnd.oasis.opendocument.formula": "odf",
		"application/vnd.oasis.opendocument.image": "odf",
		"application/vnd.oasis.opendocument.text-master": "odf"
	};

	var log = function() {
		var args = Array.prototype.slice.call(arguments);
		args.unshift("OC:");
		console.log.apply(console, args);
	};

	var postMessageAPI = new PostMessageAPI({
		allowedPartners: ALLOWED_PARTNERS,
		parent: parent
	});

	/*var storage = {
		userid: null,
		username: null
	};*/

	return {

		initialize: function(app, launcher) {

			app.run(["$rootScope", "$window", "$q", "$timeout", "ownCloud", "mediaStream", "appData", "userSettingsData", "rooms", "restURL", "alertify", "chromeExtension", function($rootScope, $window, $q, $timeout, ownCloud, mediaStream, appData, userSettingsData, rooms, restURL, alertify, chromeExtension) {

				var redirectToOwncloud = function() {
					// This redirects to the Nextcloud host. No base path is included if this page is not loaded via Iframe.
					// TODO(leon): Fix this somehow.
					var url = ALLOWED_PARTNERS[0];
					var baseURL = ownCloud.getConfig().baseURL;
					var fullURL = ownCloud.getConfig().fullURL;
					if (baseURL && fullURL) {
						var a = $window.document.createElement("a");
						a.href = fullURL;
						var redirectURL = a.pathname + a.search + a.hash;
						url = baseURL + "/../../login?redirect_url=" + $window.encodeURIComponent(redirectURL);
					}
					$window.parent.location.replace(url);
				};

				if (!HAS_PARENT) {
					alertify.dialog.error(
						"Access denied",
						"Please do not directly access this service. Open the Spreed.ME app in your Nextcloud installation instead.",
						redirectToOwncloud,
						redirectToOwncloud
					);
					// Workaround to prevent app from continuing
					appData.authorizing(true);
					return;
				}

				// Fix room location for social sharing
				(function() {
					// TODO(leon): Rely on something else than __proto__
					//var orig = _.bind(restURL.__proto__.room, restURL);
					var that = restURL.__proto__;
					that.room = function(room) {
						var makeRoomUrl = function(url, room) {
							var roomName = "";
							if (room) {
								roomName = "#" + room;
							}
							return url + that.encodeRoomURL(roomName).replace("%23", "#"); // Allow first hash to be unencoded
						};
						return makeRoomUrl(ownCloud.getConfig().baseURL, room);
					};
				})();

				// Chrome extension
				(function() {
					if ($window.webrtcDetectedBrowser === 'chrome') {
						var chromeStoreLink = "https://chrome.google.com/webstore/detail/labcnlicceloglidikcjbfglhnjibcbd";
						chromeExtension.registerAutoInstall(function() {
							var d = $q.defer();
							// TODO(leon): Don't write HTML in JS
							alertify.dialog.alert('Screen sharing requires a browser extension. Please add the Spreed.ME screen sharing extension to Chrome and try again. Open the URL<br /><a href="' + chromeStoreLink + '" target="_blank">' + chromeStoreLink + '</a><br />in your browser to install the extension.');
							//d.reject(); // This will cause an additional dialog. Uncomment if wanted.
							return d.promise;
						});
					}
				})();

				// TODO(leon): Remove is* values
				var online = ownCloud.deferreds.online;
				var isOnline = false;
				var admin = ownCloud.deferreds.admin;
				var isAdmin = false;
				var guest = ownCloud.deferreds.guest;
				var isGuest = false;
				var authorize = ownCloud.deferreds.authorize;
				var temporaryPassword = ownCloud.deferreds.features.temporaryPassword;
				var isTemporaryPasswordFeatureEnabled = false;

				appData.e.on("selfReceived", function(event, data) {
					log("selfReceived", data);

					if (!isOnline) {
						appData.authorizing(true);
					} else {
						// isOnline === true
						authorize.resolve();
					}

					online.resolve();

					// Make sure to apply everything which came later.
					$window.setTimeout(function() {
						$rootScope.$digest();
					});
				});

				online.promise.then(function() {
					isOnline = true;
				});
				admin.promise.then(function() {
					isAdmin = true;
				});
				guest.promise.then(function(guest) {
					isGuest = guest;
					angular.element("body").addClass((guest ? "is-guest" : "is-no-guest"));
				});
				temporaryPassword.promise.then(function(enabled) {
					isTemporaryPasswordFeatureEnabled = enabled;
				});
				$q.all([guest.promise, temporaryPassword.promise]).then(function() {
					if (isGuest) {
						if (isTemporaryPasswordFeatureEnabled) {
							askForTemporaryPassword();
						} else {
							alertify.dialog.error(
								"Nextcloud account required",
								"Please log in into your Nextcloud account to use use this service.",
								redirectToOwncloud,
								redirectToOwncloud
							);
						}
					}
				});

				var getUserSettings = function() {
					return userSettingsData.load() || {};
				};

				var saveSettings = function() {
					var settingsScope = angular.element($("[ng-form='settingsform']")).scope();
					// Force update
					settingsScope.settingsform.$dirty = true;
					settingsScope.saveSettings();
				};

				var tokenReceived = function(token) {
					postMessageAPI.requestResponse((new Date().getTime()) /* id */, {
						type: "guestLogin",
						guestLogin: token
					}, function(event) {
						var token = event.data;
						if (!token.success) {
							askForTemporaryPassword();
							return;
						}
						doLogin({
							useridcombo: token.useridcombo,
							secret: token.secret
						});
					});
				};

				var askForTemporaryPassword = (function() {
					var alreadyAsked = false;
					return function() {
						if (!alreadyAsked && ownCloud.dataStore.temporaryPassword) {
							// Try to get tp from query params. Only done once, then prompt appears again
							tokenReceived(ownCloud.dataStore.temporaryPassword);
						} else {
							alertify.dialog.prompt("Please enter a password to log in", function(token) {
								tokenReceived(token);
							}, function() {
								askForTemporaryPassword();
							});
							// TODO(leon): There is no better way?
							$timeout(function() {
								var modal = angular.element(".modal");
								modal.find(".modal-footer button[ng-click='cancel()']")
									.css('float', 'left')
									.text('Go to Nextcloud')
									.click(function() {
										redirectToOwncloud();
									});
							}, 100);
						}
						alreadyAsked = true;
					};
				})();

				var setConfig = function(config) {
					ownCloud.setConfig(config);

					if (typeof config.isGuest !== "undefined") {
						guest.resolve(config.isGuest);
					}
					if (typeof config.temporaryPassword !== "undefined") {
						ownCloud.dataStore.temporaryPassword = config.temporaryPassword;
					}
					if (typeof config.features.temporaryPassword !== "undefined") {
						temporaryPassword.resolve(!!config.features.temporaryPassword);
					}
				};

				var setGuestConfig = function(config) {
					if (config.display_name) {
						setUsername(config.display_name);
					}
				};

				var setUserConfig = function(config) {
					setUsername(config.display_name);

					if (config.is_spreedme_admin) {
						admin.resolve(true);
					}
				};

				var setUsername = function(displayName) {
					authorize.promise.then(function() {
						var userSettings = getUserSettings();
						if (userSettings.displayName !== displayName) {
							// Update
							appData.get().user.displayName = displayName;
							userSettings.displayName = displayName;
							userSettingsData.save(userSettings);
							saveSettings();
						}
					});
				};

				var setBuddyPicture = function(buddyPicture) {
					authorize.promise.then(function() {
						var userSettings = getUserSettings();
						if (userSettings.buddyPicture !== buddyPicture) {
							// Update
							appData.get().user.buddyPicture = buddyPicture;
							userSettings.buddyPicture = buddyPicture;
							userSettingsData.save(userSettings);
							saveSettings();
						}
					});
				};

				var doLogin = function(login) {
					online.promise.then(function() {
						mediaStream.users.authorize(login, function(data) {
							// TODO(leon): Next block should move to somewhere else
							if (isGuest) {
								authorize.promise.then(function() {
									// Guest userid example: ext/test/56d859b12aaa12.12345678
									var userid = data.userid;
									var displayName = userid;
									var parts = userid.split("/");
									if (parts.length === 3) {
										displayName = parts[1]; // This is the actual user name
									}
									setGuestConfig({
										display_name: displayName
									});
								});
							}

							log("Retrieved nonce - authenticating as user:", data.userid);
							mediaStream.api.requestAuthentication(data.userid, data.nonce);
							delete data.nonce;
						}, function(data, status) {
							log("Failed to authorize session", status, data);
							$timeout(function() {
								var modal = angular.element(".modal");
								var scope = modal.find(".modal-header").scope().$parent;
								scope.msg = "Could not authenticate. Please try again.";
								if (isGuest && isTemporaryPasswordFeatureEnabled) {
									//scope.close();
									askForTemporaryPassword();
								} else {
									modal.find("button").remove();
								}
							}, 100);
						});
					});
				};

				var changeRoom = function(room) {
					authorize.promise.then(function() {
						ownCloud.dataStore.currentRoom = room;
						rooms.joinByName(room, true);
					});
				};

				$rootScope.$on("room.updated", function(event, room) {
					var update = function(newRoom) {
						ownCloud.dataStore.currentRoom = newRoom;
						postMessageAPI.post({
							type: "roomChanged",
							roomChanged: newRoom
						});
					};

					update(room.Name);
				});

				postMessageAPI.bind(function(event) {
					var message = event.data[event.data.type];
					switch (event.data.type) {
					case "config":
						var config = message;
						setConfig(config);
						break;
					case "token":
						var token = message;
						doLogin(token);
						break;
					case "userConfig":
						var config = message;
						setUserConfig(config);
						break;
					case "userBuddyPicture":
						var buddyPicture = message;
						setBuddyPicture(buddyPicture);
						break;
					case "guestConfig":
						var config = message;
						setGuestConfig(config);
						break;
					case "changeRoom":
						var room = message;
						changeRoom(room);
						break;
					}
				});

				postMessageAPI.post({
					type: "init"
				});

			}]);

			// See User::getSignedCombo()
			app.filter("displayUserid", [function() {
				return function(id) {
					return id.replace(/\|/g, ":");
				};
			}]);

			app.directive("settingsAccount", [function() {
				return {
					scope: false,
					restrict: "E",
					link: function(scope, element) {
						// Hide some element in settings
						scope.withUsersForget = false;
						element.find(".profile-yourname").hide();
						element.find(".profile-yourpicture").hide();
					}
				};
			}]);

			app.directive("roomBar", ["$window", "$q", "$timeout", "ownCloud", "alertify", function($window, $q, $timeout, ownCloud, alertify) {
				var open = function() {
					/*var popup = $window.open(
						ownCloud.getConfig().baseURL + "/admin/tp",
						"Generate Temporary Password",
						"height=460px,width=620px,location=no,menubar=no,status=no,titlebar=no,toolbar=no"
					);*/
					var iframe = ownCloud.openModalWithIframe(
						"generate-tp",
						"/admin/tp",
						"Generate Temporary Password",
						' ', // Has to be non-empty, otherwise default title is used
						function() {},
						function() {}
					);
					var postMessageApiTP = new PostMessageAPI({
						allowedPartners: ALLOWED_PARTNERS,
						iframe: iframe.get(0)
					});
					iframe.get(0).onbeforeunload = function() {
						// Timeout to retrieve last-second postMessages
						setTimeout(postMessageApiTP.unbindAll, 100);
					};

					var init = function() {
						postMessageApiTP.post({
							message: {
								room: ownCloud.dataStore.currentRoom
							},
							type: "roomChanged"
						});
					};
					postMessageApiTP.bind(function(event) {
						switch (event.data.type) {
						case "init":
							init(event.data.message);
							break;
						default:
							log("Got unsupported message type", event.data.type);
						}
					});
				};
				var addGenerateTemporaryPasswordButton = function(element) {
					var $button = $('<a>')
					.attr('title', 'Generate Temporary Password')
					.addClass('btn btn-link btn-sm generate-temporary-password')
					.html('<i class="fa fa-key fa-lg"></i>')
					.on("click", open)
					.prependTo(element.find('.socialshare'));
				};

				return {
					scope: false,
					restrict: "E",
					link: function(scope, element) {
						// Hide roombar
						//element.hide();
						$q.all({
							"isAdmin": ownCloud.deferreds.admin.promise,
							"isTemporaryPasswordFeatureEnabled": ownCloud.deferreds.features.temporaryPassword.promise,
						}).then(function(args) {
							if (args.isAdmin && args.isTemporaryPasswordFeatureEnabled) {
								addGenerateTemporaryPasswordButton(element);
							}
						});
					}
				};
			}]);

			app.service('ownCloud', ["$window", "$http", "$q", "$timeout", "alertify", function($window, $http, $q, $timeout, alertify) {

				var deferreds = {
					authorize: $q.defer(),
					online: $q.defer(),
					admin: $q.defer(),
					guest: $q.defer(),
					features: {
						temporaryPassword: $q.defer()
					}
				};

				var dataStore = {
					currentRoom: ""
				};

				var config = {

				};

				var getConfig = function() {
					return config;
				};

				var setConfig = function(newConfig) {
					config.baseURL = newConfig.baseURL;
					config.fullURL = newConfig.fullURL;
				};

				var downloadFile = function(file) {
					var defer = $q.defer();
					postMessageAPI.requestResponse(file.path /* id */, {
						type: "downloadFile",
						downloadFile: file.path
					}, function(event) {
						var data = event.data.data;
						defer.resolve(data);
					});

					return defer.promise;
				};

				var uploadFile = function(file, filename) {
					var defer = $q.defer();
					postMessageAPI.requestResponse(filename /* id */, {
						type: "uploadBlob",
						// :( blob attributes are lost when sent via postMessage
						uploadBlob: {
							blob: file,
							name: filename
						}
					}, function(event) {
						var data = event.data.data;
						defer.resolve(data);
					});

					return defer.promise;
				};

				var openModalWithIframe = function(name, url, title, message, success_cb, error_cb) {
					// TODO(leon): This is extremely ugly.
					alertify.dialog.notify(
						title,
						message,
						success_cb,
						error_cb
					);
					var iframe = angular.element("<iframe>");
					$timeout(function() {
						var modal = angular.element(".modal");
						modal
							.addClass("owncloud-iframe-modal")
							.addClass(name);
						iframe
							.attr("src", config.baseURL + url)
							.attr("frameborder", "0")
							.attr("seamless", "seamless")
							.hide();
						var loader = angular.element("<div>")
							.addClass("loader")
							.css({
								"background-image": "url('" + config.baseURL.replace("/index.php/apps/spreedme", "/core/img/loading.gif") + "')"
							});
						iframe.get(0).onload = function() {
							loader.remove();
							iframe.show();
						};

						modal.find(".modal-footer")
							.hide();
						modal.find(".modal-body")
							.append(loader)
							.append(iframe);
					}, 100);

					return iframe;
				};

				var FileSelector = function(cb, config) {
					this.cb = cb;
					this.config = config;
					this.postMessageAPI = null;

					this.init();
				};
				FileSelector.prototype.log = function(message) {
					var args = Array.prototype.slice.call(arguments);
					args.unshift("FileSelector:");
					log.apply(log, args);
				};
				FileSelector.prototype.init = function() {
					/*var popup = $window.open(
						config.baseURL + "/file-selector",
						"FileSelector",
						"height=740px,width=770px,location=no,menubar=no,status=no,titlebar=no,toolbar=no"
					);*/
					var iframe = openModalWithIframe(
						"file-selector",
						"/file-selector",
						"Please select the file(s) you want to share",
						' ', // Has to be non-empty, otherwise default title is used
						function() {},
						function() {}
					);

					this.postMessageAPI = new PostMessageAPI({
						allowedPartners: ALLOWED_PARTNERS,
						iframe: iframe.get(0)
					});

					iframe.get(0).onbeforeunload = function() {
						// Timeout to retrieve last-second postMessages
						setTimeout(this.postMessageAPI.unbindAll, 100);
					};

					var that = this;
					this.postMessageAPI.bind(function(event) {
						that.log("Got message event", event);
						switch (event.data.type) {
						case "init":
							that.open(event.data.message);
							break;
						case "filesSelected":
							that.gotSelectedFiles(event.data.message);
							return;
						case "close":
							angular.element(iframe).scope().close();
							return;
						default:
							that.log("Got unsupported message type", event.data.type);
						}
					});
				};
				FileSelector.prototype.open = function() {
					this.postMessageAPI.post({
						message: {
							title: "Please select the file(s) you want to share",
							allowMultiSelect: true,
							filterByMIME: this.config.allowedFileTypes,
							withDetails: false, // TODO(leon): Set this to true at some point..
							inIframe: true
						},
						type: "open"
					});
				};
				FileSelector.prototype.gotSelectedFiles = function(files) {
					this.log("Files selected", files);
					files.forEach(_.bind(function(file) {
						/*
							date: "3. August 2015 um 16:20:35 MESZ"
							etag: "1aae35591c2a999c9d1055a24345a15f"
							icon: "/core/img/filetypes/x-office-document.svg"
							id: "15"
							mimetype: "application/vnd.oasis.opendocument.text"
							mtime: 1438611635000
							name: "Example.odt"
							parentId: "18"
							permissions: 27
							selectedPath: "/Photos/Example.odt"
							size: 36227
							type: "file"
						*/
						// This only works if oc-dialogs.js::filepicker comes with `withDetails` support
						var fileWithMeta = {
							name: file.name,
							path: file.selectedPath,
							mimetype: file.mimetype,
						};
						this.cb(fileWithMeta);
					}, this));
				};

				return {
					getConfig: getConfig,
					setConfig: setConfig,
					uploadFile: uploadFile,
					downloadFile: downloadFile,
					FileSelector: FileSelector,
					deferreds: deferreds,
					openModalWithIframe: openModalWithIframe,
					dataStore: dataStore
				};

			}]);

			app.directive('presentation', ['$compile', '$timeout', 'ownCloud', 'fileData', 'toastr', function($compile, $timeout, ownCloud, fileData, toastr) {

				var importFromOwnCloud = function() {
					var $scope = this;

					var allowedFileTypes = null;
					if (SUPPORTED_DOCUMENT_TYPES) {
						allowedFileTypes = [];
						for (var type in SUPPORTED_DOCUMENT_TYPES) {
							allowedFileTypes.push(type);
						}
					}

					var fromBlob = function(namespace, blobs, cb) {
						// Helper to allow later modifications.
						var binder = {
							namespace: function() {
								return namespace;
							},
							go: function() {
								var files = [];
								var i;
								for (i = 0; i < blobs.length; i++) {
									files.push(fileData.createFile(binder.namespace(), blobs[i]));
								}
								cb(files);
							}
						}
						// Return helper.
						return binder;
					};

					var ownCloudShare = function(file) {
						ownCloud.downloadFile(file)
						.then(function(blob) {
							blob.name = file.name;
							var namespace = "file_" + $scope.id;
							var fromBlobBinder = fromBlob(namespace, [blob], function(files) {
								$timeout(function() {
									$scope.$apply(function(scope) {
										_.each(files, function(f) {
											if (!f.info.hasOwnProperty("id")) {
												f.info.id = f.id;
											}
											scope.advertiseFile(f);
										});
									});
								});
							});
							fromBlobBinder.namespace = function() {
								// Inject own id into namespace.
								return namespace + "_" + $scope.myid;
							};
							fromBlobBinder.go();
						});
					};

					var fs = new ownCloud.FileSelector(ownCloudShare, {
						allowedFileTypes: allowedFileTypes
					});
				};

				var downloadPresentation = function(presentationToDownload) {
					var $scope = this;
					var $presentationToDownload = angular.element(presentationToDownload);
					return function(e) {
						// Prevent event propagation. This prevents switching the presentation if we want to download a presentation which is currently not active
						e.stopPropagation();

						var presentation = $presentationToDownload.scope().presentation;
						var file = presentation.file;
						var cb = function(file, filename) {
							var $downloadButton = $presentationToDownload.find('.download-to-owncloud');
							$downloadButton.addClass('hidden');
							ownCloud.uploadFile(file, filename)
							.then(function(info) {
								$downloadButton.removeClass('hidden');
								var escapeUnsafe = function(unsafe) {
									return unsafe
										.replace(/&/g, "&amp;")
										.replace(/</g, "&lt;")
										.replace(/>/g, "&gt;")
										.replace(/"/g, "&quot;")
										.replace(/'/g, "&#039;");
								};
								toastr.info(moment().format("lll"), info.savedFilename + " has been saved to your Nextcloud drive");
							});
						};

						if (!file && !(file instanceof "FileWriterFake")) {
							// We need file.file :)
							log("No file found. Not downloading", file);
							return;
						}

						if (typeof file.file === 'function') {
							file.file(function(file) {
								cb(file, presentation.info.name);
							});
						} else if (typeof file.file !== 'object') {
							var xhr = new XMLHttpRequest();
							xhr.onreadystatechange = function() {
								if (this.readyState == 4 && this.status == 200) {
									cb(this.response, presentation.info.name);
								}
							};
							xhr.responseType = 'blob';
							xhr.open('GET', file.toURL(), true);
							xhr.send();
						} else {
							cb(file.file, presentation.info.name);
						}
					};
				};

				return {
					restrict: 'C',
					scope: false,
					// TODO(leon): Wow. This function is __so__ shitty. Please don't continue reading, stranger :/
					compile: function(element) {
						// Compile
						return function(scope, element) {
							// Link
							var $button = $('<button>');
							$button
								.text('Nextcloud Import')
								.addClass('btn btn-primary owncloud-start-import');
							$button.on("click", importFromOwnCloud.bind(scope));

							element.find('.welcome button').parent().append(' ').append($button.clone(true).addClass('btn-lg'));

							var $thumb = element.find('.presentations .thumbnail').first().clone();
							$thumb.find('.fa').removeClass('fa-plus').addClass('fa-cloud-upload');
							$thumb.find('button').replaceWith($button.clone(true));
							$thumb.insertAfter(element.find('.presentations .thumbnail').first());

							var onmouseenter = function(event) {
								var $this = $(this);
								if ($this.find('.btn.download-to-owncloud').length === 0) {
									var $button = $this.find('.download');
									var $newButton = $button.clone()
										.removeClass('download ng-hide')
										.addClass('download-to-owncloud')
										.removeAttr('ng-show ng-click');
									$newButton.find('.fa').removeClass('fa-download').addClass('fa-cloud-download');
									$newButton.on('click', _.bind(downloadPresentation, scope)($this));
									$newButton.insertAfter($button);
								}
							};
							$(element).on('mouseenter', '.presentations .thumbnail.ng-scope', onmouseenter);

							ownCloud.deferreds.guest.promise.then(function(guest) {
								if (guest) {
									// Remove some features for guests
									element.find('.owncloud-start-import').remove();
									$thumb.remove();
									$(element).off('mouseenter', '.presentations .thumbnail.ng-scope', onmouseenter);
								}
							});
						};
					},
				};
			}]);

		}

	}

});
})();
