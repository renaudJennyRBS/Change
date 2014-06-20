(function() {
	"use strict";

	function Editor($routeParams, REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onLoad = function() {
					if (scope.document.isNew() && $routeParams.hasOwnProperty('orderProcessId') && !scope.document.orderProcess) {
						REST.resource('Rbs_Commerce_Process', $routeParams['orderProcessId']).then(function(process) {
							scope.document.orderProcess = process;
							scope.document.orderProcessId = process.id;
						})
					}
				};

				scope.onReady = function() {
					if (angular.isArray(scope.document.cartFilterData) || !angular.isObject(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};
			}
		}
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCommerceFeeNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCommerceFeeEdit', Editor);
})();