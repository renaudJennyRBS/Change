(function ($) {

	var app = angular.module('RbsChange');

	/**
	 * @name help
	 *
	 */
	app.directive('help', ['$http', '$compile', '$timeout', function ($http, $compile, $timeout) {

		function helpLoaded (scope, $target, contents, section, style) {
			style = style || 'info';
			contents = '<div class="alert alert-block alert-' + style + ' fade in"><button title="Fermer l\'aide" type="button" class="close" data-dismiss="alert">&times;</button>' + contents + '</div>';
			$compile(contents)(scope, function (clone) {
				$timeout(function() {
					$target.html(clone);
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
				});
			});
		}

		return {
			restrict : 'A',

			link : function (scope, elm, attrs) {

				var $target = $(attrs.help);
				$target.hide();


				$(elm).click(function (event) {

					var section = null,
						src = attrs.href,
						p;

					event.stopPropagation();
					event.preventDefault();

					// Is 'src' a URL or an element id?
					if (src.charAt(0) !== '#') {
						if ( (p = src.indexOf('#')) !== -1 ) {
							section = src.substring(p+1);
							src = src.substring(0, p);
						}
						$http.get(src + '.twig')
						.success(function (data) {
							helpLoaded(scope, $target, data, section);
						})
						.error(function (data) {
							helpLoaded(scope, $target, "L'aide n'a pas pu être chargée.", section, 'warning');
						});
						scope.$apply();
					} else {
						if ( (p = src.indexOf('.')) !== -1 ) {
							section = src.substring(p+1);
							src = src.substring(0, p);
						}
						helpLoaded(scope, $target, $(src).html(), section);
					}

				});
			}
		};
	}]);

})(window.jQuery);