/**
 * Calendar selection and booking bar script
 *
 * Used by [booking_calendar] shortcode
 *
 * @author  Florian Egermann <florian@macht-medien.de>
 *
 * @since 2.0
 *
 */
(function ($) {
	'use strict';
	/* global cb2_settings */
	$(function () {

		if ( ! $( '#cb2-actionbar' ).length ) { // booking bar el not in page
			return;
		}

		var selection_candidates = [];
		var time_start, time_end, day_start, day_end;

		// elements
		var bb_el = $( '#cb2-actionbar' );
		var cal_el = $( '.cb2-calendar' );
		var placer_el = $( '#cb2-actionbar-placer' );

		// Update calendar period classes on selection
		$.fn.updateClass = function (classname) {
			$(this).closest("li").addClass(classname);
			$(this).attr('checked', true);
		};

		// updateText in the booking bar
		function updateBookingbar() {
			if (time_start) {
				$('#bb-intro').css('opacity', 0);
				$('#bb-selection').animate({
					opacity: 1
				}, 400);
				$('#bb-pickup-date').text( day_start );
				$('#bb-pickup-time').text(time_start);
				$('#bb-return-date').text(day_end);
				$('#bb-return-time').text(time_end);
			} else {
				$('#bb-intro').animate({
					opacity: 1
				}, 400);
				$('#bb-selection').css('opacity', 0);
			}
		}

		// show a notice on error
		function showNotice(notice) {
			var args = Array.prototype.slice.call(arguments);
			args.shift(); // Remove the notice from {0}
			$('<li>' + notice.format.apply(notice, args) + '</li>')
				.appendTo('#bb-error')
				.addClass('animated shake')
				.delay(4000).queue( function () {
					$(this).remove();
				}
			);
		}

		// submit the form
		$("#bb-submit-button").click(function (e) {
			$("#cb2-booking-form").submit();
			if (window.console) console.log('submitted');
			e.preventDefault();
		});

		// reset all period selections
		function resetAll() {
			$('.cb2-periodinst-selector').removeAttr('checked');
			$('.cb2_prdinst-tf ').removeClass('cb2-selected cb2-range-selected selection-first selection-middle selection-last ');
		}

		// set bookingbar dynamic width, y position
		$(window).on( 'load resize scroll', function (e) {   // assign scroll, resize event listener

			var placer_bottom = placer_el.offset().top;
			var placer_left = placer_el.offset().left;
			var placer_width = placer_el.width();
			var viewportTop = $(window).scrollTop();
			var viewportBottom = viewportTop + $(window).height();
			var breakpoint = placer_bottom + bb_el.height() + 50;

			bb_el.css({                      // scroll to that element or below it
				width: placer_width,
				opacity: 1
			});


			if ( viewportBottom >= breakpoint ) { // position the element in the dom
				bb_el.css({
					position: 'relative',
					left: 0
				});
			} else { // set fixed to screen bottom
				bb_el.css({
					position: 'fixed',
					bottom: 5,
					left: placer_left
				});
			}
		});


		resetAll();

		$('.cb2-selectable > .cb2-details').click(function (e) {
			var selection_container = $(this).closest('.cb2-selection-container');
			var checkbox = $(this).children('.cb2-periodinst-selector');
			var target = $(e.target);
			var clicked_input = (target.is(checkbox));
			var clicked_id = target.attr('id');

			var bcontinue = true;
			var c_els_between = [];

			if (clicked_input) {

				// Set selection candidates
				if ($.inArray(clicked_id, selection_candidates) !== -1) {
					selection_candidates.splice($.inArray(clicked_id, selection_candidates), 1); // remove period id
				} else {
					selection_candidates.push(clicked_id); // add period id
				}

				// Set selection candidates elements
				var c_els = $('#' + selection_candidates.join(',#'));

				// there is a selection
				if (c_els.length > 1) {
					var containers = selection_container.find('.cb2-selectable, .cb2-not-includable');
					var c_el_first = c_els.first();
					var c_el_last = c_els.last();
					var noninclude_errors;
					var c_el_first_object, c_el_last_object;
					var max_period_usage = cb2_settings.bookingoptions_max_period_usage;
					var min_period_usage = cb2_settings.bookingoptions_min_period_usage;

					c_els = $.merge(c_el_first, c_el_last); // prevent selection of more than 2
					c_els_between = containers.slice(containers.index(c_el_first.closest('li')) + 1, containers.index(c_el_last.closest('li')));

					// Check for same timeframe, and max settings
					if (c_el_first.attr('properties') && c_el_last.attr('properties')) {
						c_el_first_object = JSON.parse(c_el_first.attr('properties'));
						c_el_last_object  = JSON.parse(c_el_last.attr( 'properties'));
						if (window.console) console.log(c_el_first_object, c_el_last_object);
						if (c_el_first_object && c_el_last_object) {
							if (c_el_first_object.period_entity.ID != c_el_last_object.period_entity.ID) {
								showNotice(cb2_settings.bookingbartemplates_notice_across_timeframes
									? cb2_settings.bookingbartemplates_notice_across_timeframes
									: 'across timeframes:' + c_el_first_object.period_entity.ID + ' =&gt; ' + c_el_last_object.period_entity.ID
								);
								bcontinue = false;
							} else {
								max_period_usage = c_el_first_object.period_entity.max_period_usage;
								min_period_usage = c_el_first_object.period_entity.min_period_usage;
							}
						}
					}

					// Validate not-includable
					if (bcontinue) c_els_between.each(function (index, el) {
						if ($(el).hasClass('cb2-not-includable')) {

							bcontinue = false;
							noninclude_errors = 1;
							// remove failed candidate
							for (var i = 0; i < c_els.length; i++) {
								var obj = c_els[i];
								if (clicked_id.indexOf(obj.id) !== -1) {
									c_els.splice(i, 1);
								}
							}
							e.preventDefault();
						}
					});
					if (noninclude_errors) {
						showNotice(cb2_settings.bookingbartemplates_notice_non_includable
							? cb2_settings.bookingbartemplates_notice_non_includable
							: 'Cannot book across these slots'
						);
					}
				}

				// validate max slots
				if (bcontinue && c_els.length + c_els_between.length > max_period_usage) {

					if (window.console) console.info('exceeded max period usage of [' + max_period_usage + ']');
					var notice = cb2_settings.bookingbartemplates_notice_max_slots.replace(/{{max-slots}}/g, max_period_usage);
					showNotice(cb2_settings.bookingbartemplates_notice_max_slots
						? notice
						: 'Maximum bookable slots: ' + max_period_usage,
					max_period_usage);
					bcontinue = false;

					// remove failed candidate
					for (var i = 0; i < c_els.length; i++) {
						var obj = c_els[i];
						if (clicked_id.indexOf(obj.id) !== -1) {
							c_els.splice(i, 1);
						}
					}
					e.preventDefault();
				}

				// validate min slots
				if (bcontinue && c_els.length + c_els_between.length < min_period_usage) {

					if (window.console) console.info('exceeded min period usage of [' + min_period_usage + ']');
					showNotice(cb2_settings.bookingbartemplates_notice_min_slots
						? cb2_settings.bookingbartemplates_notice_min_slots
						: 'Minimum bookable slots: ' + min_period_usage,
					min_period_usage);
					bcontinue = false;

					// remove failed candidate
					for (var i = 0; i < c_els.length; i++) {
						var obj = c_els[i];
						if (clicked_id.indexOf(obj.id) !== -1) {
							c_els.splice(i, 1);
						}
					}
					e.preventDefault();
				}

				// We have a valid selection
				if (bcontinue === true) {

					// reset all
					resetAll();

					// set the checkbox
					c_els.attr('checked', true);

					// set the classes
					var els_to_update = c_els.closest("li");

					// set selected
					els_to_update.addClass('cb2-selected');

					// set beginning/end
					if (els_to_update.length === 1) {
						els_to_update.addClass('selection-single');
					} else {
						els_to_update.first().addClass('selection-first');
						els_to_update.last().addClass('selection-last');
					}
					// set the in-betweens
					if (c_els_between.length) {
						c_els_between.addClass('cb2-range-selected selection-middle');
					}

					time_start = c_els.first().parents('.cb2-details').find('.cb2-period-start').text();
					time_end = c_els.last().parents('.cb2-details').find('.cb2-period-end').text();
					day_start = c_els.first().parents('li.cb2_day').children('.cb2-day-title').text();
					day_end = c_els.last().parents('li.cb2_day').children('.cb2-day-title').text();


				} else { // no valid selection
					e.preventDefault();
				}

				updateBookingbar();

				// set selection candidates
				selection_candidates = [];
				c_els.each(function () {
					selection_candidates.push(this.id);
				});

			} // end if clicked input

			// Prevent any container clicks from bubbling
			e.stopPropagation();

		});
	});
})(jQuery);

