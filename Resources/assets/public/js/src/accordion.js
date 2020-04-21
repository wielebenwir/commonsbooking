(function ($) {

	var allPanels = $('.accordion > div.content').hide();

	$('.accordion > dt > a').click(function () {
		allPanels.slideUp();
		$(this).parent().next().slideDown();
		return false;
	});

})(jQuery);
