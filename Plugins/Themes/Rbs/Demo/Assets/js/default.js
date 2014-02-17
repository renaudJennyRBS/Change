jQuery(document).ready(function($) {
	/** Enable tooltips and popovers. */
	jQuery('[data-toggle="tooltip"]').tooltip();
	jQuery('[data-toggle="popover"]').popover();

	/** Open dropdown menus on hover. */
	/*$('.dropdown').hover(
		function () {
			$(this).addClass('open');
		},
		function () {
			$(this).removeClass('open');
		}
	);*/
});