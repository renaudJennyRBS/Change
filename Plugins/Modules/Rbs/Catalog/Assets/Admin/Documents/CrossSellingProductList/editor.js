(function ()
{
	"use strict";

	function Editor(REST, $routeParams)
	{
		return {
			restrict: 'EA',
			templateUrl: 'Document/Rbs/Catalog/CrossSellingProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if ($routeParams.productId)
					{
						//Creation : get Product
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product){
							scope.document.product = product;
						});
					}
				};

				editorCtrl.init('Rbs_Catalog_CrossSellingProductList');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogCrossSellingProductList', Editor);
})();
