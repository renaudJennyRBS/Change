(function() {
	"use strict";

	/**
	 * ProductSet editor.
	 */
	function Editor($routeParams, REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.onReady = function () {
					if ($routeParams.rootProductId) {
						//Creation : get Product
						REST.resource('Rbs_Catalog_Product', $routeParams.rootProductId).then(function (product) {
							scope.document.rootProduct = product;
						});
					}
				};
			}
		};
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductSetNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductSetEdit', Editor);
})();