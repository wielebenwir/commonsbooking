/**
 * Following
 *
 * <span @class=cb-follow-location-ID> text </span>
 * will follow a <select @class=cb-leader
 * Options below:
 *
 * @author 		Annesley Newholm <annesley_newholm@yahoo.it>
 *
 * @since 2.0
 *
 */

function cb_update_followers() {
	cb_update_follower.call(this, 'location_ID');
	cb_update_follower.call(this, 'period_status_type_ID');
	cb_update_follower.call(this, 'datetime_part_period_start[date]');
}

function cb_update_follower(form_ID_name) {
	var $ = jQuery;
	var jBody = $(this).closest('.cb-popup');
	if (!jBody.length) jBody = $(document.body);
	var form_ID_name_escaped = form_ID_name.replace(/\]/, '\\]')
		.replace(/\[/, '\\[');
	var css_ID_name = form_ID_name.replace(/_/g, '-')
		.replace(/[^a-zA-Z]+/g, '-')
		.replace(/^-+|-+$/g, '');   // period-status-type-ID, location-ID, etc.
	var css_text_name = css_ID_name.replace(/[_-]ID$/g, ''); // period-status-type, location, etc.

	var jSelectOption = jBody.find('.cb-leader select#' + form_ID_name).find('option:selected');
	var jRadio = jBody.find('.cb-leader input:checked[type=radio][name=' + form_ID_name_escaped + ']');
	var jInput = jBody.find('.cb-leader input[type!=radio][name=' + form_ID_name_escaped + ']');

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

	jBody.find('.cb-follow-' + css_ID_name).html(sOptionID);
	jBody.find('.cb-follow-' + css_text_name).html(sOptionText);

	jBody.find('a.cb-follow').each(function () {
		var href = $(this).attr('href');
		var regex = new RegExp(form_ID_name + '=[^&]*', 'g');
		$(this).attr('href', href.replace(regex, form_ID_name + '=' + sOptionID));
		// if (window.console) console.log($(this));
	});
}
