(function () {
	var app = angular.module('RbsChange');

	app.directive('applyCorrectionOptions', ['$filter', function ($filter) {
		return {

			restrict    : 'C',
			templateUrl: 'Rbs/Admin/js/directives/apply-correction-options.twig',

			link : function (scope, element, attrs) {

				scope.submitPlanned = function () {
					scope.dialogEmbedQ.resolve(scope.plannedCorrectionDate);
					scope.closeEmbeddedModal();
				};

				scope.submitNow = function () {
					scope.dialogEmbedQ.resolve('now');
					scope.closeEmbeddedModal();
				};

			}

		};
	}]);

})();