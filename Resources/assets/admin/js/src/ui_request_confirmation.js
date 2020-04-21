/**
 * Request confirmation for destructive actions that cannot be undone
 *
 * Usage: <a class="admin-request-confirmation" data-confirmationstring="Please confirm">
 *
 * @author  Florian Egermann <florian@macht-medien.de>
 *
 * @since 2.0
 *
 */
(function ($) {
	'use strict';
	$(function () {
		$('.admin-request-confirmation').on('click', function () {
			return confirm($(this).data('confirmationstring'));
		});
	});
})(jQuery);
