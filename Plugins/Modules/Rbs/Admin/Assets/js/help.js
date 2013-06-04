(function ($) {


	function showHelp ($source) {
		var section = null;
		var href = $source.attr('href'), p;
		var $target = $($source.attr('data-help-target'));

		$target.hide();

		// Is 'href' a URL or an element id?
		if (href.charAt(0) !== '#') {
			if ( (p = href.indexOf('#')) !== -1 ) {
				section = href.substring(p+1);
				href = href.substring(0, p);
			}
			$.get(href)
			.done(function(data) {
				helpLoaded($target, data, section);
			})
			.fail(function(data) {
				helpLoaded($target, "L'aide n'a pas pu être chargée.", section, 'warning');
			});
		} else {
			if ( (p = href.indexOf('.')) !== -1 ) {
				section = href.substring(p+1);
				href = href.substring(0, p);
			}
			helpLoaded($target, $(href).html(), section);
		}
	}


	function helpLoaded($target, contents, section, style) {
		style = style || 'info';
		$target.html('<div class="alert alert-block alert-' + style + ' fade in"><button type="button" class="close" data-dismiss="alert">&times;</button>' + contents + '</div>');
		$target.fadeIn('fast');

		// Give focus to the close button so that the user can close the
		// help by pressing the ESC key.
		$target.find('[data-dismiss="alert"]').keypress(function(e) {
			if (e.keyCode === 27) {
				$(this).click();
			}
		}).focus();

		// Highlight help section, if any.
		if (section) {
			$target.find('[data-help-section="' + section + '"]').addClass('highlight');
		}
	}


	$("body").on('click.rbschange.data-help-target', "[data-help-target]", function(event) {
		showHelp($(this));
		event.preventDefault();
	});


})( window.jQuery );