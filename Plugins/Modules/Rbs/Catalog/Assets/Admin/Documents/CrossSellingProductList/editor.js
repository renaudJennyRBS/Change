(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function Editor(REST, $routeParams) {
		return {
			restrict: 'A',
			require : '^rbsDocumentEditorBase',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function ()
				{
					if ($routeParams.productId) {
						//Creation : get Product
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product) {
							scope.document.product = product;
						});
					}
				};
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams'];
	app.directive('rbsDocumentEditorRbsCatalogCrossSellingProductList', Editor);

})();
