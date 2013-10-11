(function ()
{
	"use strict";

	function Editor(REST, $routeParams, Settings)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/CrossSellingProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if (!scope.document.product && $routeParams.productId)
					{
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product){
							scope.document.product = product;
						});
					}
				};

				editorCtrl.init('Rbs_Catalog_CrossSellingProductList');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.Settings'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogCrossSellingProductList', Editor);
})();