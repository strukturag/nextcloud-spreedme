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

	var requestTP = function(userid, expiration, cb) {
		if (userid.length < 1) {
			alert("Please enter a valid username to invite");
			return;
		}
		if (expiration.length < 1 || expiration < 1 || parseInt(expiration, 10) != expiration) {
			alert("Please enter a valid expiration date");
			return;
		}
		if (expiration > Math.round(new Date().getTime() / 1000) + (60 * 60 * 24)) {
			var response = confirm("Do you really want to generate a Temporary Passwords which is valid for more than 1 day?");
			if (!response) {
				return;
			}
		}

		var baseUrl = OC.generateUrl('/apps/spreedme');
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
				cb(response.tp);
			}
		}).fail(function (response, code) {
			console.log(response, code);
		});
	};

	var useridField = $("form input[name=userid]");
	var expirationField = $("form input[name=expiration]");
	$("form input[type=submit]").click(function(e) {
		e.preventDefault();

		requestTP(useridField.val(), (new Date(expirationField.datetimepicker("getDate")).getTime() / 1000), function(tp) {
			var tpField = $("<input>")
				.attr("value", tp)
				.attr("size", "80")
				.attr("readonly", "readonly")
				.click(function() {
					$(this).select();
				});
			$("#tp")
				.text("")
				.append("Temporary Password generated:<br />")
				.append(tpField)
				.append("<br /><br />");
		});
	});

	expirationField.datetimepicker({
		oneLine: true,
		minDate: new Date(),
		controlType: "select"
	});
	var date = new Date((new Date()).getTime() + (1000 * 60 * 60 * 2)); // Add 2 hours
	expirationField.datetimepicker("setDate", date);

});
})(jQuery, OC);
