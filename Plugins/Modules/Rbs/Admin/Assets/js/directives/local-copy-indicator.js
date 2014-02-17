(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsLocalCopyIndicator', ['RbsChange.EditorManager', function (EditorManager)
	{
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Admin/js/directives/local-copy-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope)
			{
				scope.rbsLocalCopies = EditorManager.getLocalCopies();

				scope.$watchCollection('rbsLocalCopies', function (copies) {
					scope.localCopies = [];
					angular.forEach(copies, function (copy) {
						scope.localCopies.push(copy);
					});
				});

				scope.clearLocalCopies = function () {
					EditorManager.removeAllLocalCopies();
				};

				scope.localizedId = function (doc) {
					return doc.id + '-' + doc.LCID;
				};
			}
		};
	}]);

})();