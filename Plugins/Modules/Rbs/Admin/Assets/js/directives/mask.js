(function ($) {

	angular.module('RbsChange').directive('mask', ['$timeout', function ($timeout) {

		return {
			restrict : 'A',
			priority : 99,
			   scope : true,

			link : function (scope, elm, attrs) {

				/**
				 * Creates the element that aims to mask the element to which this directive is bound (elm).
				 */
				function createMaskElIfNeeded () {
					if (!scope.maskEl) {
						scope.maskEl = $('<div class="mask-over-element"></div>');
						$('body').append(scope.maskEl);
						scope.maskEl.css({
							position : 'absolute'
						});
					}
				}

				function mask () {
					if ($(elm).is(":visible")) {
						var offset = $(elm).offset();
						createMaskElIfNeeded();
						scope.maskEl.css({
							  left : offset.left + 'px',
							   top : offset.top + 'px',
							 width : $(elm).outerWidth(),
							height : $(elm).outerHeight()
						});
						scope.maskEl.show();
					}
				}

				function unmask () {
					if (scope.maskEl) {
						scope.maskEl.hide();
					}
				}

				scope.$on('$destroy', function () {
					if (scope.maskEl) {
						scope.maskEl.remove();
					}
				});

				attrs.$observe('mask', function (shouldMask) {
					if ((/^true$/i).test(shouldMask)) {
						$timeout(mask);
					} else {
						$timeout(unmask);
					}
				});
			}
		};
	}]);

})(window.jQuery);