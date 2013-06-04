(function (jq) {

	/**
	 *
	 */
	angular.module('RbsChange').directive('clipboardWidget', ['RbsChange.Clipboard', '$location', function (Clipboard, $location) {

		return {
			restrict : 'E',

			templateUrl: 'Rbs/Admin/js/directives/clipboard-widget.html',

			replace: true,

			scope: true,

			link : function (scope, elm, attrs) {
				scope.values = Clipboard.values;
				scope.selection = Clipboard;

				scope.clear = function () {
					Clipboard.clear();
				};

				scope.$watch('selection.status', function () {
					jq(elm).hide().fadeIn('fast');
				});

				scope.display = function () {
					$location.path('/clipboard');
				};

			}
		};
	}]);

})(window.jQuery);