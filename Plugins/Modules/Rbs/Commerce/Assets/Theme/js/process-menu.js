(function () {
	function resizeSidebar() {
		jQuery('.process-sidebar').width(function () { return jQuery(this).parent().width(); });
	}

	jQuery(window).resize(function(){
		resizeSidebar();
	});
	resizeSidebar();

	jQuery('.process-sidebar').affix({
		offset: {
			top: function () { return jQuery('.process-sidebar-container').offset().top - 15; },
			bottom: function () {
				var node = jQuery('.process-sidebar-container').parent();
				return jQuery(document).outerHeight() - node.offset().top - node.outerHeight();
			}
		}
	});

	jQuery('body').scrollspy({ target: '.process-sidebar' });
})();