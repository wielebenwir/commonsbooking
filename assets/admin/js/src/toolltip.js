/* 
* Tooltips
*
* Enables Javascript tooltips via jQuery UI (loaded in the WP Backend by default) 
* 
* Usage: <span class="dashicons dashicons-editor-help" title="My text"></span>
*/

(function ($) {
	'use strict';
	$(function () {
		$( document ).tooltip();
	});
})(jQuery);
