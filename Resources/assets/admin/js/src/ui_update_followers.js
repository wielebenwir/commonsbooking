/**
 * Following
 *
 * <span @class=cb2-follow-location-ID> text </span>
 * will follow a <select @class=cb2-leader
 * Options below:
 *
 * @author 		Annesley Newholm <annesley_newholm@yahoo.it>
 *
 * @since 2.0
 *
 */

function cb2_update_followers() {
	cb2_update_follower.call(this, 'location_ID');
	cb2_update_follower.call(this, 'period_status_type_ID');
	cb2_update_follower.call(this, 'datetime_part_period_start[date]');
}

function cb2_update_follower(form_ID_name) {
	var $ = jQuery;
	var jBody = $(this).closest('.cb2-popup');
	if (!jBody.length) jBody = $(document.body);
	var form_ID_name_escaped = form_ID_name.replace(/\]/, '\\]')
		.replace(/\[/, '\\[');
	var css_ID_name = form_ID_name.replace(/_/g, '-')
		.replace(/[^a-zA-Z]+/g, '-')
		.replace(/^-+|-+$/g, '');   // period-status-type-ID, location-ID, etc.
	var css_text_name = css_ID_name.replace(/[_-]ID$/g, ''); // period-status-type, location, etc.

	var jSelectOption = jBody.find('.cb2-leader select#' + form_ID_name).find('option:selected');
	var jRadio = jBody.find('.cb2-leader input:checked[type=radio][name=' + form_ID_name_escaped + ']');
	var jInput = jBody.find('.cb2-leader input[type!=radio][name=' + form_ID_name_escaped + ']');

	var sOptionID =
		(jSelectOption.length ? jSelectOption.attr('value') : '')
		+ (jRadio.length ? jRadio.val() : '')
		+ (jInput.length ? jInput.val() : '');
	var sOptionText =
		(jSelectOption.length ? jSelectOption.text() : '')
		+ (jRadio.length ? jRadio.parent().children('label').text() : '')
		+ (jInput.length ? jInput.attr('display-name') || jInput.val() : ''); // Use val() for date fields

	sOptionText = sOptionText.replace(/\(.*\)/, '');
	if (!sOptionText || sOptionText.match(/-- .* --/)) sOptionText = 'not selected';

	if (window.console) console.info('[.' + css_text_name + '] => [' + sOptionText + ']');

	jBody.find('.cb2-follow-' + css_ID_name).html(sOptionID);
	jBody.find('.cb2-follow-' + css_text_name).html(sOptionText);

	jBody.find('a.cb2-follow').each(function () {
		var href = $(this).attr('href');
		var regex = new RegExp(form_ID_name + '=[^&]*', 'g');
		$(this).attr('href', href.replace(regex, form_ID_name + '=' + sOptionID));
		// if (window.console) console.log($(this));
	});
}
