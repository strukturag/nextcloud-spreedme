/*
 * Spreed WebRTC.
 * Copyright (C) 2013-2014 struktur AG
 *
 * This file is part of Spreed WebRTC.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// This file is loaded in WebRTC context

"use strict";
define(['angular', '../../../../../extra/static/PostMessageAPI', '../../../../../extra/static/config/OwnCloudConfig'], function(angular, PostMessageAPI, OwnCloudConfig) {

	var ALLOWED_PARTNERS = (function() {
		var OWNCLOUD_ORIGIN = OwnCloudConfig.OWNCLOUD_ORIGIN;
		if (OWNCLOUD_ORIGIN && OWNCLOUD_ORIGIN !== "please-change-me") {
			return [
				OWNCLOUD_ORIGIN
			];
		}

		console.error("Please make sure to edit your OwnCloudConfig.js file in extra/static/config");

		// Boo! Not set - Only allow own host
		var location = document.location;
		var protocol = location.protocol;
		var host = location.host;
		// First element is default
		// TODO(leon): When used with postMessage API, we have to iterate over this list
		return [
			protocol + "//" + host
			//, "https://" + host
			//, "http://" + host
		];
	})();
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

	/*var storage = {
		userid: null,
		username: null
	};*/

	return {

		initialize: function(app, launcher) {

			app.run(["$rootScope", "$window", "$q", "$timeout", "ownCloud", "mediaStream", "appData", "userSettingsData", "rooms", "alertify", function($rootScope, $window, $q, $timeout, ownCloud, mediaStream, appData, userSettingsData, rooms, alertify) {

				//$window.mediaStream = mediaStream;

				var postMessageAPI = new PostMessageAPI({
					allowedPartners: ALLOWED_PARTNERS,
					parent: parent
				});

				var currentRoom;
				var online = $q.defer();
				var isOnline = false;
				var authorize = $q.defer();

				appData.e.on("selfReceived", function(event, data) {
					log("selfReceived", data);

					if (!isOnline) {
						appData.authorizing(true);
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

				var getUserSettings = function() {
					return userSettingsData.load() || {};
				};

				var saveSettings = function() {
					var settingsScope = angular.element($("[ng-form='settingsform']")).scope();
					// Force update
					settingsScope.settingsform.$dirty = true;
					settingsScope.saveSettings();
				};

				var setConfig = function(config) {
					ownCloud.setConfig(config);
				};

				var setUserConfig = function(config) {
					setUsername(config.display_name);
				}

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
							log("Retrieved nonce - authenticating as user:", data.userid);
							mediaStream.api.requestAuthentication(data.userid, data.nonce);
							delete data.nonce;
							authorize.resolve();
						}, function(data, status) {
							log("Failed to authorize session", status, data);
							$timeout(function() {
								var modal = angular.element(".modal");
								var scope = modal.find(".modal-header").scope().$parent;
								modal.find("button").remove();
								scope.msg = "Could not authenticate. Incorrect shared secret?";
							}, 100);
						});
					});
				};

				var changeRoom = function(room) {
					authorize.promise.then(function() {
						currentRoom = room;
						rooms.joinByName(room, true);
					})
				};

				$rootScope.$on('$routeChangeSuccess', function(e, current, previous) {
					var newRoom = decodeURIComponent(current.params.room);
					var oldRoom = "";
					if (previous) {
						oldRoom = decodeURIComponent(previous.params.room);
					}
					if (newRoom !== oldRoom && newRoom !== currentRoom) {
						postMessageAPI.post({
							type: "roomChanged",
							roomChanged: newRoom
						});
					}
				});

				postMessageAPI.bind(function(event) {
					var message = event.data[event.data.type];
					switch (event.data.type) {
					case "config":
						var config = message;
						setConfig(config);
						break;
					case "login":
						var login = message;
						doLogin(login);
						break;
					case "userConfig":
						var config = message;
						setUserConfig(config);
						break;
					case "userBuddyPicture":
						var buddyPicture = message;
						setBuddyPicture(buddyPicture);
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

				var config = {

				};

				var setConfig = function(newConfig) {
					config.baseURL = newConfig.baseURL;
				};

				var api = (function(config) {
					// Private
					var basePath = "/api/v1/";
					var $config = {
						token: null,
						parentConfig: config
					};
					var _request = function(method, url, config) {
						config = angular.extend({
							method: method,
							url: $config.parentConfig.baseURL + basePath + url
						}, config);
						return $http(config);
					};
					var get = function(url, config) {
						return _request("GET", url, config);
					};
					var post = function(url, data, config) {
						config = angular.extend({
							data: angular.element.param(data)
						}, config);
						// Workaround as the angular version we currently use doesn't support .merge
						config.headers["Content-Type"] = "application/x-www-form-urlencoded";
						return _request("POST", url, config);
					};
					var requestToken = function() {
						var deferred = $q.defer();
						if ($config.token) {
							// Cached.
							deferred.resolve($config.token);
						} else {
							get("tokenize")
							.then(function(response) {
								var token = response.data.token;
								$config.token = token;
								deferred.resolve(token);
							});
						}
						return deferred.promise;
					};
					var getCSRFSafe = function(url, config) {
						return requestToken()
						.then(function(token) {
							config = angular.extend({
								headers: {
									requesttoken: token
								}
							}, config);
							return get(url, config);
						});
					};
					var postCSRFSafe = function(url, data, config) {
						return requestToken()
						.then(function(token) {
							config = angular.extend({
								headers: {
									requesttoken: token
								}
							}, config);
							return post(url, data, config);
						});
					};

					// Expose public components
					return {
						get: get,
						post: post,
						requestToken: requestToken,
						getCSRFSafe: getCSRFSafe,
						postCSRFSafe: postCSRFSafe
					};
				})(config);

				var download = function(file) {
					var url = "file/download?file=" + encodeURIComponent(file.path);
					return api.getCSRFSafe(url, {responseType: "blob"});
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
						setTimeout(this.postMessageAPI.unbind, 100);
					};

					var that = this;
					this.postMessageAPI.bind(function(event) {
						that.log("Got message event", event);
						switch (event.data.type) {
						case "init":
							that.open(event.data.message);
							break;
						case "files":
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
							withDetails: true
						},
						type: "open"
					});
				};
				FileSelector.prototype.gotSelectedFiles = function(files) {
					this.log("Files selected", files);
					files.forEach(_.bind(function(file) {
						var fileWithMeta = {
							name: file.name,
							path: file.selectedPath,
							mimetype: file.mimetype,
						};
						this.cb(fileWithMeta);
					}, this));
				};

				return {
					setConfig: setConfig,
					download: download,
					FileSelector: FileSelector,
				};

			}]);

			app.directive('presentation', ['$compile', '$timeout', 'ownCloud', 'fileData', function($compile, $timeout, ownCloud, fileData) {

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
						ownCloud.download(file)
						.then(function(response) {
							var namespace = "file_" + $scope.id;
							var blob = response.data;
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

				return {
					restrict: 'C',
					scope: false,
					compile: function(element) {
						// Compile
						return function(scope, element) {
							// Link
							var $button = $('<button>');
							$button
								.text('ownCloud Import')
								.addClass('btn btn-lg btn-primary owncloud-start-import');
							$button.on("click", importFromOwnCloud.bind(scope));
							element.find('.welcome button').parent().append(' ').append($button);
						};
					},
				};
			}]);

		}

	}

});
