/**
 * ownCloud - spreedwebrtc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright Leon 2015
 */

// This file is loaded both in ownCloud and in WebRTC context

(function (window) {

	var PostMessageAPI = function(config) {
		var IN_IFRAME = (function() {
			try {
				return window.self !== window.top;
			} catch (e) {
				return true;
			}
		})();

		this.parent = IN_IFRAME ? config.parent : null;
		this.iframe = config.iframe;
		this.popup = config.popup;
		this.opener = config.opener;
		this.allowedPartners = config.allowedPartners;
		this.partnerOrigin = null;
		this.listener = null;

		this.init();
	};
	PostMessageAPI.prototype.log = function(message) {
		var args = Array.prototype.slice.call(arguments);
		args.unshift("PostMessageAPI:");
		console.log.apply(console, args);
	};
	PostMessageAPI.prototype.init = function() {

	};
	PostMessageAPI.prototype.post = function(obj) {
		var pw = this.getPartnerWindow();
		pw.postMessage(obj, this.partnerOrigin || this.allowedPartners[0]);
	};
	PostMessageAPI.prototype.getPartnerWindow = function() {
		var pw = null;
		if (this.parent) {
			pw = this.parent;
		} else if (this.iframe) {
			pw = this.iframe.contentWindow;
		} else if (this.popup) {
			pw = this.popup;
		} else if (this.opener) {
			pw = this.opener;
		}
		if (pw === null) {
			this.log("Found no partner window");
		}
		return pw;
	};
	PostMessageAPI.prototype.gotValidEvent = function(event) {
		if (!this.partnerOrigin) {
			this.partnerOrigin = event.origin;
		}
		this.log("Got event", event);
	};
	PostMessageAPI.prototype.validateEvent = function(event) {
		var valid = true;
		if (this.partnerOrigin && event.origin !== this.partnerOrigin) {
			valid = false;
		} else if (!Array.isArray(this.allowedPartners) || this.allowedPartners.indexOf(event.origin) === -1) {
			valid = false;
		} else if (event.source !== this.getPartnerWindow()) {
			valid = false;
		}
		return valid;
	};
	PostMessageAPI.prototype.bind = function(fnct) {
		if (this.listener) {
			// Unbind first
			this.unbind();
		}
		var that = this;
		this.listener = function(event) {
			if (that.validateEvent(event)) {
				that.gotValidEvent(event);
				fnct(event);
			}
		};
		window.addEventListener("message", this.listener, false);
	};
	PostMessageAPI.prototype.unbind = function() {
		if (!this.listener) {
			return;
		}
		window.removeEventListener("message", this.listener, false);
		this.listener = null;
	};

	if (typeof define === "function" && define.amd) {
		define(function() {
			return PostMessageAPI;
		});
	} else {
		window.PostMessageAPI = PostMessageAPI;
	}

})(window);
