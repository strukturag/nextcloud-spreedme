/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

// This file is loaded in WebRTC context

"use strict";
define([
	'angular',
	'moment',
	'../../../../../extra/static/PostMessageAPI',
	'../../../../../extra/static/config/OwnCloudConfig'
], function(angular, moment, PostMessageAPI, OwnCloudConfig) {
	'use strict';

	var HAS_PARENT = window !== parent;
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
			var port = (isPort ? origin : ':' + location.port);
			allowed.push(protocol + "//" + hostname + port);
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

				if (!HAS_PARENT) {
					var redirect = function() {
						// This only redirects to the ownCloud host. No base path included!
						// TODO(leon): Fix this somehow.
						$window.location.replace(ALLOWED_PARTNERS[0]);
					};
					alertify.dialog.exec("error", "Error", "Please do not directly access this service. Open the Spreed.ME app in your ownCloud installation instead.", redirect, redirect);
					// Workaround to prevent app from continuing
					appData.authorizing(true);
					return;
				}

				// Fix room location for social sharing
				(function() {
					// TODO(leon): Rely on something else than __proto__
					//var orig = _.bind(restURL.__proto__.room, restURL);
					var parentUrl = document.referrer; // This is the URL of the site which loads this script in an Iframe
					//var ownCloudAppPath = "/index.php/apps/spreedme/";
					var that = restURL.__proto__;
					that.room = function(room) {
						var makeRoomUrl = function(url, room) {
							var parser = document.createElement("a");
							parser.href = url;
							var roomName = "";
							if (room) {
								roomName = "#" + room;
							}
							//return parser.protocol + "//" + parser.host + ownCloudAppPath + roomName;
							return ownCloud.getConfig().baseURL + that.encodeRoomURL(roomName).replace("%23", "#"); // Allow first hash to be unencoded
						};

						//return orig(name).replace($window.location.protocol + '//' + $window.location.host, makeRoomUrl(parentUrl, room));
						return makeRoomUrl(parentUrl, room);
					};
				})();

				// Chrome extension
				(function() {
					if ($window.webrtcDetectedBrowser === 'chrome') {
						var chromeStoreElem = $window.document.head.querySelector('link[rel=chrome-webstore-item]');
						if (!chromeStoreElem) {
							return;
						}
						var chromeStoreLink = chromeStoreElem.href;
						chromeExtension.registerAutoInstall(function() {
							var d = $q.defer();
							alertify.dialog.alert('Screen sharing requires a browser extension. Please add the Spreed WebRTC screen sharing extension to Chrome and try again. Copy the url ' + chromeStoreLink + ' open it in your browser, and install the extension.');
							//d.reject(); // This will cause an additional dialog. Uncomment if wanted.
							return d.promise;
						});
					}
				})();

				var currentRoom;
				var online = ownCloud.deferreds.online;
				var isOnline = false;
				var guest = ownCloud.deferreds.guest;
				var isGuest = false;
				var authorize = $q.defer(); // Too risky to expose

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
				guest.promise.then(function() {
					isGuest = true;
					askForGuestToken();
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

				var askForGuestToken = function(previouslyFailed) {
					previouslyFailed = !!previouslyFailed;
					alertify.dialog.prompt("Please enter a token to log in", function(token) {
						postMessageAPI.requestResponse((new Date().getTime()) /* id */, {
							type: "guestLogin",
							guestLogin: token
						}, function(event) {
							var token = event.data;
							if (!token.success) {
								askForGuestToken(true);
								return;
							}
							doLogin({
								useridcombo: token.useridcombo,
								secret: token.secret
							});
						});
					}, function() {
						askForGuestToken(true);
					});
				};

				var setConfig = function(config) {
					ownCloud.setConfig(config);

					if (config.isGuest) {
						guest.resolve(true);
					}
				};

				var setGuestConfig = function(config) {
					if (config.display_name) {
						setUsername(config.display_name);
					}
				};

				var setUserConfig = function(config) {
					setUsername(config.display_name);
				};

				var setUsername = function(displayName) {
					var userSettings = getUserSettings();
					if (true || userSettings.displayName !== displayName) {
						// Update
						appData.get().user.displayName = displayName;
						userSettings.displayName = displayName;
						userSettingsData.save(userSettings);
						saveSettings();
					}
				};

				var setBuddyPicture = function(buddyPicture) {
					var userSettings = getUserSettings();
					if (true || userSettings.buddyPicture !== buddyPicture) {
						// Update
						appData.get().user.buddyPicture = buddyPicture;
						userSettings.buddyPicture = buddyPicture;
						userSettingsData.save(userSettings);
						saveSettings();
					}
				};

				var doLogin = function(login) {
					online.promise.then(function() {
						mediaStream.users.authorize(login, function(data) {
							if (isGuest) {
								authorize.promise.then(function() {
									setGuestConfig({
										display_name: data.userid
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
								if (isGuest) {
									//scope.close();
									askForGuestToken(true);
								} else {
									modal.find("button").remove();
								}
							}, 100);
						});
					});
				};

				var changeRoom = function(room) {
					authorize.promise.then(function() {
						currentRoom = room;
						rooms.joinByName(room, true);
					});
				};

				$rootScope.$on('$routeChangeSuccess', function(e, current, previous) {
					var inform = function(newRoom) {
						postMessageAPI.post({
							type: "roomChanged",
							roomChanged: newRoom
						});
					};

					if (!current || !current.params) {
						inform("");
						return;
					}

					var newRoom = decodeURIComponent(current.params.room);
					var oldRoom = "";
					if (previous) {
						oldRoom = decodeURIComponent(previous.params.room);
					}

					if (newRoom !== oldRoom && newRoom !== currentRoom) {
						inform(newRoom);
					}
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
						// TODO(leon): This is only a temporary workaround
						authorize.promise.then(function() {
							setUserConfig(config);
						});
						break;
					case "userBuddyPicture":
						var buddyPicture = message;
						// TODO(leon): This is only a temporary workaround
						authorize.promise.then(function() {
							setBuddyPicture(buddyPicture);
						});
						break;
					case "guestConfig":
						var config = message;
						// TODO(leon): This is only a temporary workaround
						authorize.promise.then(function() {
							setGuestConfig(config);
						});
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

			app.directive("roomBar", [function() {
				return {
					scope: false,
					restrict: "E",
					link: function(scope, element) {
						// Hide roombar
						//element.hide();
					}
				};
			}]);

			app.service('ownCloud', ["$window", "$http", "$q", function($window, $http, $q) {

				var deferreds = {
					online: $q.defer(),
					guest: $q.defer()
				};

				var config = {

				};

				var getConfig = function() {
					return config;
				};

				var setConfig = function(newConfig) {
					config.baseURL = newConfig.baseURL;
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

				var FileSelector = function(cb, config) {
					this.cb = cb;
					this.config = config;
					this.postMessageAPI = null;

					this.init();
				};
				FileSelector.prototype.log = function(message) {
					var args = Array.prototype.slice.call(arguments);
					args.unshift("FileSelector:");
					console.log.apply(console, args);
				};
				FileSelector.prototype.init = function() {
					var popup = $window.open(
						config.baseURL + "/file-selector",
						"FileSelector",
						"height=740px,width=770px,location=no,menubar=no,status=no,titlebar=no,toolbar=no"
					);
					this.postMessageAPI = new PostMessageAPI({
						allowedPartners: ALLOWED_PARTNERS,
						popup: popup
					});

					popup.onbeforeunload = function() {
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
							withDetails: false // TODO(leon): Set this to true at some point..
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
					deferreds: deferreds
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
								toastr.info(moment().format("lll"), info.savedFilename + " has been saved to your ownCloud drive");
							});
						};

						if (!file && !(file instanceof "FileWriterFake")) {
							// We need file.file :)
							console.log("No file found. Not downloading", file);
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
								.text('ownCloud Import')
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

							ownCloud.deferreds.guest.promise.then(function() {
								// Remove some features for guests
								element.find('.owncloud-start-import').remove();
								$thumb.remove();
								$(element).off('mouseenter', '.presentations .thumbnail.ng-scope', onmouseenter);
							});
						};
					},
				};
			}]);

		}

	}

});
