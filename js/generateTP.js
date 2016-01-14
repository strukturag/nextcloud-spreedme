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

(function($, OC) {
$(document).ready(function() {

	if (window.parent) {
		var sharedConfig = $.parseJSON($("#sharedconfig").html());
		var ALLOWED_PARTNERS = sharedConfig.allowed_partners.split(",");

		var postMessageAPI = new PostMessageAPI({
			allowedPartners: ALLOWED_PARTNERS,
			parent: window.parent
		});
	}

	var currentRoom = "";
	var baseUrl = OC.generateUrl('/apps/spreedme');
	var roomUpdated = function(room) {
		currentRoom = room;
	};
	var requestTP = function(userid, expiration, cb_success, cb_error) {
		if (userid.length < 1) {
			alert("Please enter a valid name to invite");
			return;
		}
		if (expiration.length < 1 || expiration < 1 || parseInt(expiration, 10) != expiration) {
			alert("Please enter a valid expiration date");
			return;
		}
		if (expiration > (new Date().getTime() / 1000) + (60 * 60 * 24)) {
			var response = confirm("Do you really want to generate a Temporary Passwords which is valid for more than 1 day?");
			if (!response) {
				return;
			}
		}

		var data = {
			userid: userid,
			expiration: expiration
		};
		$.ajax({
			url: baseUrl + '/api/v1/admin/tp',
			type: 'POST',
			data: $.param(data)
		}).done(function (response) {
			if (response.success === true) {
				cb_success(response.tp);
			} else {
				cb_error(response.error);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};

	var useridField = $("form input[name=userid]");
	var expirationField = $("form input[name=expiration]");
	$("form input[type=submit]").click(function(e) {
		e.preventDefault();

		requestTP(useridField.val(), Math.round(new Date(expirationField.datetimepicker("getDate")).getTime() / 1000), function(tp) {
			$("[name=temporarypassword]")
				.attr("value", tp)
				.click(function() {
					$(this).select();
				});
			$("[name=temporarypasswordurl]")
				.attr("value", document.location.origin + baseUrl + "?tp=" + window.encodeURIComponent(tp) + (currentRoom === "" ? "" : "#" + window.encodeURIComponent(currentRoom)))
				.click(function() {
					$(this).select();
				});

			$("body")
				.removeClass("failure")
				.addClass("success");
		}, function(error) {
			$("#errorcode").text(error);

			$("body")
				.removeClass("success")
				.addClass("failure");
		});
	});

	expirationField.datetimepicker({
		oneLine: true,
		minDate: new Date(),
		controlType: "select"
	});
	var date = new Date((new Date()).getTime() + (1000 * 60 * 60 * 2)); // Add 2 hours
	expirationField.datetimepicker("setDate", date);

	if (postMessageAPI) {
		postMessageAPI.bind(function(event) {
			switch (event.data.type) {
			case "roomChanged":
				roomUpdated(event.data.message.room);
				break;
			default:
				console.log("Got unsupported message type", event.data.type);
			}
		});

		postMessageAPI.post({
			type: "init"
		});
	}

});
})(jQuery, OC);
