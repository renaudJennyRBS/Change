(function () {

	angular.module('RbsChange').directive('goUpInTree', ['$location', 'RbsChange.Utils', function ($location, Utils) {

		return {
			restrict : 'A',

			scope : true,

			template : '<a ng-show="parent != null" style="vertical-align: bottom" ng-href="{{parent.treeUrl()}}" class="btn" type="button" title="Retourner à {{parent.label}}"><i class="icon-arrow-up"></i></a>',

			replace : true,

			link : function (scope, elm, attrs) {
				scope.$on('Change:TreePathChanged', function (event, bcData) {
					if (!bcData.resource && bcData.path.length > 1) {
						scope.parent = bcData.path[bcData.path.length-2];
					} else {
						scope.parent = null;
					}
				});
			}

		};

	}]);

})();