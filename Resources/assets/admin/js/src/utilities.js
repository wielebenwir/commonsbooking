/**
 * Date utilities for the admin
 *
 * @author 		Annesley Newholm <annesley_newholm@yahoo.it>
 *
 * @since 2.0
 *
 */
function cb_dayofweek_string(d) {
	var weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
	return (d instanceof Date ? weekdays[d.getDay()] : undefined);
}

function cb_month_string(d) {
	var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
	return (d instanceof Date ? months[d.getMonth()] : undefined);
}

function cb_iso_date(date) {
	return date.getFullYear() + '-'
		+ (date.getMonth() + 1).toString().padStart(2, '0') + '-'
		+ date.getDate().toString().padStart(2, '0');
}
