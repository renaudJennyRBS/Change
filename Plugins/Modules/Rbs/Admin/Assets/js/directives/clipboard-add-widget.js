(function () {

	/**
	 *
	 */
	angular.module('RbsChange').directive('rbsClipboardAddWidget', ['RbsChange.Clipboard', function (Clipboard) {

		return {
			restrict : 'E',

			templateUrl: 'Rbs/Admin/js/directives/clipboard-add-widget.twig',

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