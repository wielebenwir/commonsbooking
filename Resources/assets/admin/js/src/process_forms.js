
// -------------------------------------------- document.ready and content changes
/* global cb2_settings, cb2_dictionary */
function cb2_process(){
	var $     = jQuery;
	var jRoot = $(this); // Usually document or cb2-popup
	var WP_DEBUG = $('body.cb2-WP_DEBUG-on').length;

	jRoot.find('label p, label span').click(function(e){
		// Browsers do not like HTML in labels
		var jTarget, idTarget, jLabel = $(this).parent('label');
		if (jLabel.length) {
			idTarget = jLabel.attr('for');
			jTarget = jLabel.parent().find('#' + idTarget + ':visible');
			jTarget.click();
		}
	});

	jRoot.find('form').on('submit', function(){
		// Show that form is being submitted
		var jSubmit = $(this).find('.cb2-submit');
		jSubmit.val(jSubmit.val() + ' ...');
		$(window).off( 'beforeunload.edit-post' );
		setTimeout(function(){
			jSubmit.addClass('disabled');
		});
	});


	jRoot.find('form').submit(function(){
		// Disable any non selected LI sub-option inputs
		$(this).find('.cb2-form-disable').attr('disabled', '1');
		$(this).find('li.cb2-selected').each(function(){
			$(this).closest('ul').find('li:not(.cb2-selected) input').attr('disabled', '1');
		});
	});

	jRoot.find('a.thickbox').each(function(){
		// Hijax all thickbox hrefs so that the PHP knows it is in a popup
		var href = $(this).attr('href');
		if (href.indexOf('cb2_load_normal_page') == -1 && href.indexOf('cb2_load_template') == -1) {
			href += (href.indexOf('?') == -1 ? '?' : '&' ) + 'cb2_load_normal_page=1';
			$(this).attr('href', href);
		}
	});

	jRoot.find('.cmb2-id-period-status-type-ID input').click(function(){
		// Add status-{ID} to the body CSS class according to selection
		var jPopup    = $(this).closest('body, .cb2-popup');
		var jPeriodStatusType = $(this).parent().find('label');
		var css_class = jPopup.attr('class');
		var type      = jPeriodStatusType.text().toLowerCase().replace(/[^a-z]+/g, '-').replace(/^-+|-+$/g, '');
		var id        = $(this).val();
		css_class = ' ' + css_class + ' ';
		css_class = css_class.replace(/ +cb2-status-[^ ]+/g, ' ');
		css_class = css_class + 'cb2-status-' + type + ' cb2-status-' + id;
		css_class = css_class.replace(/ +/, ' ').trim();
		jPopup.attr('class', css_class);
		jPopup.css('background-color', '');
	});

	jRoot.find('.cb2-form').on('submit', function(){
		// Admin Form submission progress bar
		var self = this;
		setTimeout(function(){
			var jSubmit = $(self).find(':input[type=submit]');
			jSubmit.val(jSubmit.val() + ' ...');
			jSubmit.after(' <progress></progress> ');
			$(self).find(':input').addClass('disabled');
		}, 0);
	});

	jRoot.find('.cb2-javascript-form input[type=button]').click(function(){
		var sExisting;
		var sRedirect = document.location;
		var sQuery    = unescape(document.location.search.replace(/^\?/, ''));
		var aQuery    = sQuery.split('&');
		var jForm     = $(this).closest('.cb2-javascript-form');
		var jInputs   = jForm.find(":input");

		jInputs.each(function(){
			// Attribute switching so that the form inputs can exist inside the outer form
			var sJSName =  $(this).attr('js-name');
			if (sJSName)   $(this).attr('name', sJSName);
			else sJSName = $(this).attr('name');

			// Remove existing parameters
			// so that double submits do not aggregate
			if (sJSName) {
				sJSName = sJSName.replace(/\[\d+\]/, '[]');
				var i = aQuery.length;
				while (i > 0) {
					i--;
					sExisting = aQuery[i].replace(/=.*/, '').replace(/\[[0-9]+\]/, '[]');
					if (sExisting == sJSName)
						aQuery.splice(i, 1);
				}
			}
		});

		sQuery  = aQuery.join('&');
		sQuery += '&';
		sQuery += jInputs.serialize()
		sQuery += '&redirect=' + escape(sRedirect);

		document.location = document.location.pathname + '?' + sQuery;
	});

	jRoot.find('.cb2-template-type-available > .cb2-details').click(function(e){
		// TODO: NOT_USED: multiple period-inst selector for admin bulk actions on calendars
		var container      = $(this).parent();
		var checkbox       = $(this).children('.cb2-periodinst-selector');
		var cssClass       = $(this).attr('class').trim();
		var target         = $(e.target);
		var clicked_input  = (target.is(checkbox));
		var is_checked     = checkbox.attr('checked');

		// The default checkbox event will check the checkbox
		// AFTER this action
		if (clicked_input) is_checked = !is_checked;

		if (is_checked) {
			if (!clicked_input) checkbox.removeAttr('checked');
			container.attr( 'class', cssClass.replace(/cb2-selected/, '') );
		} else {
			if (!clicked_input) checkbox.attr('checked', '1');
			container.attr( 'class', cssClass + ' cb2-selected' );
		}

		// Prevent any container clicks from bubbling
		//e.stopPropagation();

		// Prevent any default container <a> working
		if (!clicked_input) e.preventDefault();
	});

	// -------------------------------------------------- start / end dates interaction
	var changed_datetime_part_period_start_date = false,
			changed_datetime_part_period_end_date   = false;
	var previous_datetime_part_period_start_date = $('#datetime_part_period_start_date').val(),
			previous_datetime_part_period_end_date   = $('#datetime_part_period_end_date').val();

	jRoot.find('#datetime_part_period_start_date').change(function(){
		// Add the difference in the start date to the end date
		var start_date      = new Date($('#datetime_part_period_start_date').val());
		var end_date        = new Date($('#datetime_part_period_end_date').val());
		var prev_start_date = new Date(previous_datetime_part_period_start_date);
		var diff_year       = start_date.getFullYear()  - prev_start_date.getFullYear();
		var diff_month      = start_date.getMonth()     - prev_start_date.getMonth();
		var diff_day        = start_date.getDate()      - prev_start_date.getDate();
		var jSubmit         = $(this).closest('body,.cb2-popup').find('.cb2-popup-form-save');

		jSubmit.removeAttr('disabled');
		if (isNaN(start_date.getTime())) {
			$(this).addClass('cb2-error');
			jSubmit.attr('disabled', '1');
		} else {
			if (!$('#datetime_part_period_start_date, #datetime_part_period_end_date').hasClass('cb2-error')) {
				end_date.setFullYear( end_date.getFullYear()  + diff_year);
				end_date.setMonth(    end_date.getMonth()     + diff_month);
				end_date.setDate(     end_date.getDate()      + diff_day);
				$('#datetime_part_period_end_date').val(cb2_iso_date(end_date));
				if (window.console) console.info(diff_year + '-' + diff_month + '-' + diff_day);
			}

			// Error checking
			$('#datetime_part_period_start_date, #datetime_part_period_end_date').removeClass('cb2-error');
			if (start_date > end_date) {
				$('#datetime_part_period_end_date').addClass('cb2-error');
				jSubmit.attr('disabled', '1');
			}

			// Maintain previous knowledge
			previous_datetime_part_period_start_date = $(this).val();
			previous_datetime_part_period_end_date   = $('#datetime_part_period_end_date').val();
		}
		changed_datetime_part_period_start_date  = true;
	});

	jRoot.find('#datetime_part_period_end_date').change(function(){
		var start_date      = new Date($('#datetime_part_period_start_date').val());
		var end_date        = new Date($('#datetime_part_period_end_date').val());
		var jSubmit         = $(this).closest('body,.cb2-popup').find('.cb2-popup-form-save');

		jSubmit.removeAttr('disabled');
		if (isNaN(end_date.getTime())) {
			$(this).addClass('cb2-error');
			jSubmit.attr('disabled', '1');
		} else {
			// Error checking
			$('#datetime_part_period_start_date, #datetime_part_period_end_date').removeClass('cb2-error');
			if (start_date > end_date) {
				$('#datetime_part_period_end_date').addClass('cb2-error');
				jSubmit.attr('disabled', '1');
			}

			// Maintain previous knowledge
			previous_datetime_part_period_end_date = $(this).val();
		}
		changed_datetime_part_period_end_date  = true;
	});

	// -------------------------------------------- Save type and recurrence
	var original_recurrence_type;
	jRoot.find('#cb2-SOT').click(function(){ // Save Instance Only
		// Cannot repeat
		original_recurrence_type = $('.cmb2-id-recurrence-type :input[checked]');
		$('#recurrence_type1').click();
		$('.cmb2-id-recurrence-type').slideUp();

		$('#datetime_part_period_start_date').val(
			$('#datetime_period_inst_start_date').val()
		);
		$('#datetime_part_period_end_date').val(
			$('#datetime_period_inst_end_date').val()
		);
	});

	jRoot.find('#cb2-SFH, #cb2-SAI').click(function(){ // Save From Here, Save All Instances
		// Can repeat
		$('.cmb2-id-recurrence-type').slideDown();
		if (original_recurrence_type) original_recurrence_type.click();
	});

	jRoot.find('#cb2-save-types input').click(function(){
		var updatestring = $('label[for="' + this.id + '"]').html();
		$('#cb2-follow-cb2-save-types').html(updatestring);
	});

	// -------------------------------------------- nexts
	jRoot.find('.cb2-nexts').each(function(){
		var nexts  = $(this).find('li');
		var ids    = '';
		var hrefs  = nexts.find('a').each(function() {
			ids += (ids?',':'') + $(this).attr('href');
		});
		var panels = nexts.closest('.cb2-popup,#post-body').find(ids);
		panels.css('overflow-y', 'hidden');
		panels.hide();

		$(this).closest('.cb2-popup,body').addClass('cb2-with-nexts');
		$(this).closest('.cb2-popup,#post-body').removeClass('columns-2');

		nexts.click(function(e) {
			// Next button also comes here by clicking the .next() LI
			var next     = $(this);
			var href     = next.find('a').attr( 'href' );
			var newPanel = next.closest('.cb2-popup,body').find(href);
			var oldPanel = panels.filter(':visible');

			if (!next.hasClass('cb2-selected')) {
				// Select next href
				nexts.removeClass('cb2-selected');
				nexts.addClass('cb2-unselected');
				next.addClass('cb2-selected');
				next.removeClass('cb2-unselected');

				// Select panel
				// TODO: prevent clicks during existing sliding (causes panel disappearance)
				// Fix heights at normal display
				newPanel.css('width', '100%');
				oldPanel.css('width', '100%');
				oldPanel.css('height', oldPanel.height());
				newPanel.css('height', newPanel.height());
				// Setup initial states
				newPanel.css('width', '0%');
				newPanel.show();
				// Animate
				oldPanel.animate({"width": '0%'},   500, function(){
					oldPanel.hide();
					oldPanel.css('height', 'auto');
				});
				newPanel.animate({"width": '100%'}, 500, function(){
					newPanel.css('height', 'auto');
					newPanel.focus();
				});

				// Button states
				if (next.hasClass('cb2-last')) {
					$('.cb2-popup-form-next').hide();
					$('.cb2-popup-form-save').show();
				}
				else {
					$('.cb2-popup-form-next').show();
					$('.cb2-popup-form-save').hide();
				}
			}

			e.preventDefault();
		});

		// Open first next
		if (nexts.length) {
			var next  = nexts.filter('.cb2-selected');
			if (!next.length) next = nexts.eq(0);
			var href  = next.find('a').attr( 'href' );
			var panel = next.closest('.cb2-popup,body').find(href);
			next.addClass('cb2-selected');
			next.removeClass('cb2-unselected');
			panel.show();
		}
	});

	$('.cb2-popup a.thickbox').click(function(e){
		// Thickbox showing from thickbox concatenates the styles and classes
		// so we clear them here
		var sClass      = $('#TB_window').attr('class');
		var aPopupTypes = sClass.match(/cb2-popup-[^ ]+/g);
		var sPopupTypes = aPopupTypes.join(' ');
		$('#TB_window').attr('class', 'cb2-popup ' + sPopupTypes);
	});

	jRoot.find('.cb2-popup-form-next').click(function(){
		// Button must be 2 parents local to the cb2-nexts container element
		var jCurrent = $(this).closest('.cb2-popup,body').find('.cb2-nexts li.cb2-selected');
		var jNext    = jCurrent.next();
		jNext.click();
	});

	// auto-next panels
	$('.cb2-popup-add #cb2-tab-type li').click(function(){
		$('.cb2-popup-form-next').click();
	});

	function cb2_next_if_selects_complete(){
		var allComplete = true;
		var jSelects    = $(this).find('select:visible');
		jSelects.each(function(){
			var value = $(this).val();
			if (!value || value == '__Null__') allComplete = false;
			if (!allComplete && window.console) console.info($(this).attr('name') + ' <select> not complete');
			return allComplete;
		});

		if (allComplete) $('.cb2-popup-form-next').click();
		if (allComplete && window.console) {
			console.info('all complete' + (jSelects.length ? '' : ' (none found)'));
			if (jSelects.length) console.log(jSelects);
		}
	}
	jRoot.find('#cb2-tab-objects').change(      cb2_next_if_selects_complete);
	jRoot.find('#cb2-tab-objects:visible').each(cb2_next_if_selects_complete);

	// ------------------------------------------------------- Misc
	$(document).find('.toplevel_page_cb2-menu > .wp-submenu > li').each(function(){
		// Give our WP menu LIs some classes
		var href, matches;
		var jA = $(this).children('a');
		if (href = jA.attr('href')) {
			if (matches = href.match(/[?&]page=([^&]+)/)) {
				if ( matches.length ) {
					$(this).addClass(matches[1]);
				}
			} else if (href.match(/[a-zA-Z_0-9]+/)) {
				$(this).addClass(href);
			}
		}
	});

	jRoot.find('.cmb2-radio-list > li').each(function(){
		// Annotate all LIs with their child <input @name
		// so that they can be styled by CSS and selected by JS
		var jLI      = $(this);
		var jInput   = $(this).children('input');
		var jLabel   = $(this).children('label');
		var name     = jInput.attr('name');
		if (name == 'period_status_type_ID') name = 'status'; // Backward compat
		var css_name = name.toLowerCase().replace(/[^a-z]+/g, '-').replace(/^-+|-+$/g, '');
		var id       = jInput.val();
		var label    = jLabel.text();
		var short_label = label.replace(/ .*$/, ''); // Some labels are very verbose
		var type     = short_label.toLowerCase().replace(/[^a-z]+/g, '-').replace(/^-+|-+$/g, '');
		var stub     = 'cb2-' + css_name + '-';

		jLI.addClass(stub + type);
		jLI.addClass(stub + id);
	});

	// Give parent LI of radio inputs a cb2-selected class for formatting reasons
	jRoot.find('input[type=radio]').click(function(){
		$(this).closest('ul').children('li').removeClass('cb2-selected');
		$(this).closest('li').addClass('cb2-selected');
	});
	jRoot.find('input[type=radio]:checked').each(function(){
		// And on startup
		$(this).closest('ul').children('li').removeClass('cb2-selected');
		$(this).closest('li').addClass('cb2-selected');
	});

	jRoot.find('.cb2-add-class-advanced').click(function(){
		// advanced links that show hidden elements through a body class
		$(this).closest('body,.cb2-popup,.cb2-panel').addClass('cb2-advanced');
	});

	jRoot.find('.cb2-set-href-querystring').change(function(){

		// Allow a > input form elements to change their parent a@href on change
		var name      = $(this).attr('name');
		var value     = $(this).val().trim();
		var text      = $(this).find('option:selected').html().trim();
		var jHref     = $(this).closest('a');
		var title     = jHref.attr('title');
		var href      = jHref.attr('href');
		var parts     = href.split('?');
		var url       = parts[0].trim();
		var qs        = (parts.length > 1 ? parts[1].trim() : '');
		var qsParts   = qs.split('&');
		var css_name  = name.replace(/_/g, '-')
									.replace(/[_-]ID$/g, '')
									.replace(/[^a-z]+/g, '-')
									.replace(/^-+|-+$/g, '');   // period-status-type
		var css_class = 'cb2-follow cb2-follow-' + css_name;

		// Change any existing value
		var found = false;
		for (var i in qsParts) {
			var pair = qsParts[i];
			var pairParts = pair.split('=');
			if (pairParts[0] == name) {
				qsParts[i] = name + '=' + value;
				found = true;
				break;
			}
		}
		// Add if not found
		if (!found) qsParts[qsParts.length] = name + '=' + value;
		title = title.replace(/: .*/, '');

		qs = qsParts.join('&');
		jHref.attr('href',  url   + '?'  + qs);
		jHref.attr('title', title + ': <span class="' + css_class + '">' + text + '</span>');

		// Let it bubble and click the link
	});

	jRoot.find('.cb2-tabs').each(function(){
		var tabs   = $(this).find('li');
		var ids    = '';
		var hrefs  = tabs.find('a').each(function() {
			ids += (ids?',':'') + $(this).attr('href');
		});
		var panels = tabs.closest('.cb2-popup,body').find(ids);
		panels.hide();

		$(this).closest('.cb2-popup,body').addClass('cb2-with-tabs');
		$(this).closest('.cb2-popup,#post-body').removeClass('columns-2');
		$(this).addClass('cb2-processed');

		tabs.click(function(e) {
			var tab   = $(this);
			var href  = tab.find('a').attr( 'href' );
			var panel = tab.closest('.cb2-popup,body').find(href);

			// Close other tabs
			tabs.removeClass('cb2-selected');
			tabs.addClass('cb2-unselected');
			panels.hide();
			tabs.each(function(){
				var other_href = $(this).find('a').attr( 'href' );
				other_href = other_href.replace(/^#/, '');
				$(this).closest('.cb2-popup,body').removeClass('cb2-tabs-' + other_href + '-selected');
			});
			href = href.replace(/^#/, '');
			tab.closest('.cb2-popup,body').addClass('cb2-tabs-' + href + '-selected');

			// Open target tabcb2_update_followers
			tab.addClass('cb2-selected');
			tab.removeClass('cb2-unselected');
			panel.focus();
			panel.show();

			e.preventDefault();
		});

		// Open first tab
		if (tabs.length) {
			var tab   = tabs.filter('.cb2-selected');
			if (!tab.length) tab = tabs.eq(0);
			var href  = tab.find('a').attr( 'href' );
			var panel = tab.closest('.cb2-popup,body').find(href);
			tab.addClass('cb2-selected');
			tab.removeClass('cb2-unselected');
			href = href.replace(/^#/, '');
			tab.closest('.cb2-popup,body').addClass('cb2-tabs-' + href + '-selected');
			panel.show();
		}
	});

	jRoot.find('.cb2-popup-form-trash').click(function() {
		var self   = this;
		var form   = $(self).closest('.cb2-ajax-edit-form');
		var data   = form.find(':input').serialize();
		var action = form.attr('action-trash');

		$(self).attr('disabled', '1');
		$(self).parents('.cb2-popup, body').addClass('cb2-saving');
		$.post({
			url: action,
			data: data,
			success: function(){
				$(self).removeAttr('disabled');
				$(self).parents('.cb2-popup, body').removeClass('cb2-saving');
				// TODO: callback based refresh => calendar ajax refresh
				if (!$(document.body).hasClass('cb2-CB2_DEBUG-on'))
					document.location = document.location;
				$(self).parents('.cb2-popup, body').addClass('cb2-refreshing');
			},
			error: function(data) {
				var responseXML, message;
				$(self).parents('.cb2-popup, body').removeClass('cb2-saving');
				$(self).parents('.cb2-popup, body').addClass('cb2-ajax-failed');
				$(self).removeAttr('disabled');
				console.log(data);

				if (responseXML = $(data.responseText)) {
					message = responseXML.filter('result').attr('message');
					if (!message) message = 'Unknown response';
					alert(message);
				}
			}
		});
	});

	jRoot.find('.cb2-popup-form-save').click(function() {
		// TODO: Save all the forms, or just the visible one?
		var self   = this;
		var form   = $(self).closest('.cb2-ajax-edit-form');
		var data   = form.find(':input').serialize();
		var action = form.attr('action');

		$(self).attr('disabled', '1');
		$(self).parents('.cb2-popup, body').addClass('cb2-saving');
		$.post({
			url: action,
			data: data,
			success: function(){
				$(self).removeAttr('disabled');
				$(self).parents('.cb2-popup, body').removeClass('cb2-saving');
				// TODO: callback based refresh => calendar ajax refresh
				if (!$(document.body).hasClass('cb2-CB2_DEBUG-on'))
					document.location = document.location;
				$(self).parents('.cb2-popup, body').addClass('cb2-refreshing');
			},
			error: function(data) {
				var responseXML, message;
				$(self).parents('.cb2-popup, body').removeClass('cb2-saving');
				$(self).parents('.cb2-popup, body').addClass('cb2-ajax-failed');
				$(self).removeAttr('disabled');
				console.log(data);

				if (responseXML = $(data.responseText)) {
					message = responseXML.filter('result').attr('message');
					if (!message) message = 'Unknown response';
					alert(message);
				}
			}
		});
	});

	jRoot.find('.cb2-calendar-krumo-show').click(function(){
		$(this).parent().find('.cb2-calendar-krumo').show();
	});

	jRoot.find('#TB_window #cb2-fullscreen').click(function() {
		$('#TB_ajaxContent')
			.css('max-width', 'none')
			.css('width',     'auto')
			.css('height',    'auto');
		$("#TB_window")
			.css('overflow-y', 'scroll')
			.addClass('cb2-fullscreen')
			.animate({
				width:  '100%',
				height: '100%',
				top: '0%',
				left: '0%',
				marginTop: '0px',
				marginLeft: '0px',
			});
	});

	var jCMB2_select_properties = $('.cb2-with-properties[name=location_ID]:visible, input[type=hidden].cb2-with-properties[name=location_ID]');
	if (jCMB2_select_properties.length) {
		console.info('attaching cb2_object_selected to:');
		console.log(jCMB2_select_properties);
	} else {
		console.info('no cb2-with-properties found');
	}
	jCMB2_select_properties.on('cb2_object_selected', function(e, object, element){
		// TODO: this currently fires twice because of double script inclusion in the popup
		var has_opening_hours = object && object.last_opening_hours;
		var jNoOpeningHours   = $('.cb2-period-group-id-OPH, .cb2-period-entity-create-OPH, .cb2-period-group-id-HRY, .cb2-period-entity-create-HRY, .cb2-ignore-location-restrictions' );
		var jNoOHFirstOption  = $('.cb2-period-group-id-CUS, .cb2-period-entity-create-CUS');
		var jOHFirstOption    = $('.cb2-period-group-id-OPH, .cb2-period-entity-create-OPH');
		var jClassHolder      = $(this).closest('body,.cb2-popup');

		jClassHolder.removeClass('cb2-has-opening-hours').removeClass('cb2-no-opening-hours');
		jClassHolder.addClass(has_opening_hours ? 'cb2-has-opening-hours' : 'cb2-no-opening-hours');

		if (has_opening_hours) {
			$('.cb2-no-opening-hours-show').slideUp();
			$('.cb2-no-opening-hours-hide').slideDown();
			jNoOpeningHours.css('opacity','1');
			jOHFirstOption.children('input').click();
		} else {
			$('.cb2-no-opening-hours-show').slideDown();
			$('.cb2-no-opening-hours-hide').slideUp();
			jNoOpeningHours.css('opacity','0.5');
			jNoOHFirstOption.children('input').click();
		}

		if (window.console) console.info('last_opening_hours object:' + object);
	});

	$(document).on('cmb_init_pickers', function(e, pickers) {
		if (pickers) {
			for (picker in pickers) {
				pickers[picker].attr('autocomplete', 'off');
			}
		}
	});

	jRoot.find('form').submit(function(){
		var datepickers = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
		// datepickers.show();
	});

	jRoot.find('.cmb-type-text-datetime-timestamp').change(function(){
		var datepicker = $(this).find('.cmb2-datepicker');
		var timepicker = $(this).find('.cmb2-timepicker');
		if (datepicker.length && !datepicker.val()) {
			timepicker.val('');
		}
	});

	$('#cb2-set-full-day').click(function() {
		var field_list = $(this).parents('.cmb-field-list');
		field_list.find('#datetime_part_period_start_time').val("00:00");
		field_list.find('#datetime_part_period_end_time').val("23:59");
		return false;
	});

	// @TODO Maybe reintroduce different dates function
	// $('.cb2-same-dates #cb2-different-dates').click(function() {
	// 	$(this).closest('form, #cb2-ajax-edit-form').removeClass('cb2-same-dates');
	// 	return false;
	// });

	jRoot.find(':input').change(cb2_update_followers);
	jRoot.find('input[type=radio]').click(cb2_update_followers);
	cb2_update_followers();

	var datepickers       = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
	var recurrence_boxes  = $('.cmb2-id-recurrence-sequence, .cmb2-id-datetime-from, .cmb2-id-datetime-to, .cmb2-id-period-explanation-selection');
	var sequence          = $('.cmb2-id-recurrence-sequence');
	var sequence_checks   = sequence.find('.cmb2-checkbox-list');
	var daily_html        = sequence.find('.cmb2-checkbox-list').html();
	var recurrence_inputs = recurrence_boxes.find('input');
	var start_date_input  = $('#datetime_part_period_start_date');
	var end_date_input    = $('#datetime_part_period_end_date');

	recurrence_boxes.hide();

	jRoot.find('.cmb2-id-recurrence-type input').click(function(){
		var repeat_setting = $(this).val();
		var checked        = 'checked="1"';
		var start_date     = new Date( start_date_input.val() );
		var end_date       = ( end_date_input.val() ? new Date( end_date_input.val() ) : undefined );
		//datepickers.hide(); // Hide so that values still work!
		recurrence_inputs.removeAttr('disabled');
		recurrence_boxes.removeClass('cb2-disabled');

		// Indicate new repeat type
		$(this).closest('form, #cb2-ajax-edit-form')
			.removeClass('cb2-repeat-D cb2-repeat-W cb2-repeat-M cb2-repeat-Y');
		if (repeat_setting == '__Null__') {
			recurrence_boxes.slideUp();
		} else {
			recurrence_boxes.slideDown();
			$(this).closest('form, #cb2-ajax-edit-form').addClass('cb2-repeat-' + repeat_setting);
		}

		// Show / Hide interface elements
		switch (repeat_setting) {
			case '__Null__': {
				// datepickers.show();
				recurrence_boxes.addClass('cb2-disabled');
				recurrence_inputs.attr('disabled', '1');
				break;
			}
			case 'D': {
				var options = '';
				// TODO: translations, start_of_week and settings of checkboxes: load from PHP
				/*
				var day, days  = ["Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday", "Monday"];
				for (var h = 0; h < 7; h++) {
					day = days[h].substr(0,3);
					options += '<li><input type="checkbox" ' + checked + ' class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence' + h + '" value="' + h + '">';
					options += '<label for="recurrence_sequence' + h + '">' + day + '</label></li>';
				}
				sequence_checks.html(options);
				*/
				$('.cmb2-id-period-explanation-selection .cb2-description p').html(cb2_dictionary.period_explanation_selection );
				sequence_checks.html(daily_html);
				sequence_checks.slideDown();
				break;
			}
			case 'W': {
				var start_date_day = cb2_dayofweek_string(start_date);
				$('.cmb2-id-period-explanation-selection .cb2-description p').html(cb2_dictionary.period_repeats_weekly_on + start_date_day);
				// datepickers.show();
				sequence_checks.slideUp(undefined, function(){
					sequence_checks.html('');
				});
				break;
			}
			case 'M': {
				var options = '';
				// TODO: translations and load settings: load from PHP
				/*
				var month, months  = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
				for (var h = 0; h < 12; h++) {
					month = months[h].substr(0,3);
					options += '<li><input type="checkbox" ' + checked + ' class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence' + h + '" value="' + h + '">';
					options += '<label for="recurrence_sequence' + h + '">' + month + '</label></li>';
				}
				sequence_checks.html(options);
				sequence_checks.slideDown();
				*/
				var start_date_day_of_month = start_date.getDate();
				var modulus = start_date_day_of_month % 10;
				var suffix  = 'th';
				switch (modulus) {
					case 1: suffix = 'st'; break;
					case 2: suffix = 'nd'; break;
					case 3: suffix = 'rd'; break;
				}
				var day_advice = (start_date_day_of_month == 31
					? cb2_dictionary.period_repeats_monthly
					: cb2_dictionary.period_repeats_monthly_on + start_date_day_of_month + suffix + cb2_dictionary.period_repeats_monthly_on_day
				);
				$('.cmb2-id-period-explanation-selection .cb2-description p').html(day_advice);
				// datepickers.show();
				sequence_checks.slideUp(undefined, function(){
					sequence_checks.html('');
				});
				break;
			}
			case 'Y': {
				// TODO: replace with month-day picker
				// datepickers.show();
				sequence_checks.slideUp(undefined, function(){
					sequence_checks.html('');
				});
				var start_date_in_year = start_date.getDate() + ' of ' + cb2_month_string(start_date);
				$('.cmb2-id-period-explanation-selection .cb2-description p').html(cb2_dictionary.period_repeats_yearly_on + start_date_in_year + '.');
				break;
			}
		}
	});
	jRoot.find('.cmb2-id-recurrence-type input[checked]').click();
}

function cb2_init_popup(){
	var $ = window.jQuery;
	var adopt_classes_from_content = $('#TB_window .TB_window_classes');
	if (adopt_classes_from_content.length) {
		$('#TB_window').addClass(adopt_classes_from_content.text());
		adopt_classes_from_content.remove();
	}

	// set actions (below title)
	var adopt_header = $('#TB_window .TB_title_actions');
	if (adopt_header.length) {
		$('#TB_title').after(adopt_header);
	}
	// set title .TB_title_html
	var adopt_title = $('#TB_window .TB_title_html').text();
	if ((adopt_title).length) {
		$('#TB_window #TB_ajaxWindowTitle').prepend(adopt_title);
	}


	cb2_process.apply(this);

	// TODO: is this CMB2::init() working? NO
	if (window.CMB2) {
		delete window.CMB2.$metabox;
		window.CMB2.init();
	}
}

(function($) {
  'use strict';
  $(document).ready(cb2_process);
	$(document).on('cb2-popup-appeared', function(){
		// Run in the cb2-popup context
		var jPopup = $('#TB_window');
		if (jPopup.length) {
			if (window.console) console.info('received event cb2-popup-appeared');
			cb2_init_popup.apply(jPopup.get(0)); // => cb2_process()
		}
	});
})(jQuery);
