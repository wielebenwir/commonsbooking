/**
 * Switch toggle "Allow bookings"
 *
 *
 * @author  Florian Egermann <florian@macht-medien.de>
 *
 * @since 2.0
 *
 */
(function ($) {
	'use strict';
	$(function () {
		var click_target = $('#confirmed_user_ID');
		click_target.on('click', function () {
			$('#post').submit();
		});
	});
})(jQuery);
