(function ($) {

	"use strict";

	angular.module('RbsChange').directive('rbsClipboardIndicator', ['RbsChange.Clipboard', '$location', function (Clipboard, $location)
	{
		return {
			restrict : 'A',
			templateUrl: 'Rbs/Admin/js/directives/clipboard-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope, iElement)
			{
				scope.values = Clipboard.values;
				scope.selection = Clipboard;

				scope.clear = function () {
					Clipboard.clear();
				};

				scope.$watch('selection.status', function () {
					$(iElement).hide().fadeIn('fast');
				});

				scope.display = function () {
					$location.path('/clipboard');
				};
			}
		};
	}]);

})(window.jQuery);