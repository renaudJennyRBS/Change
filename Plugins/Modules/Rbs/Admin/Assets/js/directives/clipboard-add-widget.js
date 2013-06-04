(function () {

	/**
	 *
	 */
	angular.module('RbsChange').directive('clipboardAddWidget', ['RbsChange.Clipboard', function (Clipboard) {

		return {
			restrict : 'E',

			templateUrl: 'Rbs/Admin/js/directives/clipboard-add-widget.html',

			replace: true,

			scope: {
				selected: '='
			},

			link : function (scope, elm, attrs) {

				scope.selection = Clipboard.values;

				scope.append = function () {
					Clipboard.append(scope.selected);
				};

				scope.replace = function () {
					Clipboard.replace(scope.selected);
				};


			}
		};
	}]);

})();