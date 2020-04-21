/**
 * Calendar row + map script
 *
 * Used by [cb2_calendar_map listen-to-fields=""] shortcode
 *
 * @author    Annesley Newholm <annesley_newholm@yahoo.it>
 *
 * @since 2.0.0
 *
 */
(function ($) {
	'use strict';
	$(function () {
		// Write in console log the PHP value passed in enqueue_js_vars in public/class-plugin-name.php
		// if (window.console) console.log(pn_js_vars.alert);

		$(document).ready(function () {
			// ------------------------------------- selectors and listeners
			// these classes can refresh other ajax listening shortcodes
			$('.cb2-selector').click(function () {
				// Something selected, lets get its values and check for listeners
				// e.g. a map which needs to re-adjust its view
				var jsObject = {};
				var jParameters = $(this).find('.cb2-selector-value');
				if ($(this).hasClass('cb2-selector-value')) jParameters = jParameters.andSelf();

				if (jParameters.length) {
					// They have serialized their parameters in their immediate <input>s
					jParameters.each(function () {
						// We include blank parameters
						// in case it is a reset request
						jsObject[$(this).attr('name')] = $(this).val();
					});

					// Mark as selected
					$(this).closest('.cb2-selection-container').find('.cb2-selected').removeClass('cb2-selected');
					$(this).addClass('cb2-selected');

					// Inform any listeners of the selection
					if (window.console) console.log(jsObject);
					$(this).trigger('cb2-selected', jsObject);
				}
			});

			$(document).on('cb2-selected', function (event, paramNewValues) {
				$('.cb2-listen-on').each(function () {
					// These are the WP_Query's that need refreshing
					var self = this;
					var jsObject = {};
					var sListenToField, paramNewValuesFiltered = {};
					var sListenToFields = $(this).find(':input[name=listen-to-fields]').val();
					var aListenToFields = (sListenToFields ? sListenToFields.split(',') : {});

					// Filter requested fields
					if (sListenToFields && sListenToFields != 'all' && aListenToFields.length) {
						for (var i = 0; i < aListenToFields.length; i++) {
							sListenToField = aListenToFields[i];
							if (paramNewValues[sListenToField])
								paramNewValuesFiltered[sListenToField] = paramNewValues[sListenToField];
						}
						paramNewValues = paramNewValuesFiltered;
						if (window.console) console.log(paramNewValues);
					}

					// They have serialized their parameters in their immediate <input>s
					// we still refresh even if there are no values
					// because it could be a de-select
					$(this).children(':input').each(function () {
						jsObject[$(this).attr('name')] = $(this).val();
					});
					jsObject = jQuery.extend(jsObject, paramNewValues);
					// Remove blank values
					for (var key in jsObject) {
						if (!jsObject[key]) delete jsObject[key];
					}

					if (window.console) console.log(this, jsObject, paramNewValues);
					if (!jsObject['action']) jsObject['action'] = 'cb2_ajax_shortcode';
					var ajax_url = jsObject['ajax-url'];

					// CB2_Shortcodes Class is listening on the cb2_ajax_shortcode action
					if (ajax_url) {
						$(this).addClass('cb2-refreshing');
						$.post(ajax_url, jsObject, function (response) {
							var jNewContent = $(response);
							var jNewContentChildren = jNewContent.children();
							$(self).replaceWith(jNewContentChildren);
							geo_hcard_map_init();
						});
					} else if (window.console) console.error('jsObject has no ajax URL for CB2 container [' + shortcodeId + ']');
				});
			});

			if ($('li.cb2-template-popup-item').length > 0) {
				$('li.cb2-template-popup-item').each(function () {
					console.log("bound");
					console.log($(this));
					$(this).on("click", function () {
						console.log($(this));
						var url = $(this).find('a').attr('href');

					});
				});

			}

			window.cb2 = {}; // global commons booking object

			cb2.calendarStyles = function () { // manage style of calendar by calendar size, not window width

				if ($('.cb2-calendar-grouped').length < 1) {
					return;
				}

				if ($('.cb2-calendar-grouped').outerWidth() >= 450) {
					$('.cb2-calendar-grouped').addClass('cb2-calendar-grouped-large');
				} else {
					$('.cb2-calendar-grouped').removeClass('cb2-calendar-grouped-large');
				}

			};

			cb2.calendarTooltips = function () {

				if ($('.cb2-calendar-grouped').length < 1) {
					return;
				}

				$('.cb2-slot[data-state="allow-booking"] ').parents('li.cb2-date').each(function (i, elem) {
					var template = document.createElement('div');
					template.id = $(elem).attr('id');
					var html = '<div><ul>';

					$(elem).find('[data-state="allow-booking"]').each(function (j, slot) {
						html += '<li>';
						if ($(slot).attr('data-item-thumbnail')) {
							html += '<img src="' + $(slot).attr('data-item-thumbnail') + '">';
						}
						html += '<a href="' + $(slot).attr('data-item-thumbnail') + '">';
						html += $(slot).attr('data-item-title');
						html += '</a></li>';
					});

					html += '</ul></div>';

					template.innerHTML = html;

					tippy('#' + template.id, {
						appendTo: document.querySelector('.cb2-calendar-grouped'),
						arrow: true,
						html: template,
						interactive: true,
						theme: 'cb2-calendar',
						trigger: 'click'
					}); // need to polyfill MutationObserver for IE10 if planning to use dynamicTitle

				});

			};

			cb2.init = function () {
				cb2.calendarStyles();
				cb2.calendarTooltips();
			};

			cb2.resize = function () {
				cb2.calendarStyles();
			};

			cb2.init();

			$(window).on('resize', cb2.resize);

		});
	});
})(jQuery);
