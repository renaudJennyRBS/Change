(function ()
{
	"use strict";

	function Editor(ProductListService)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/ProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if (scope.document.id > 0)
					{
						ProductListService.addListContent(scope);
					}
				};

				editorCtrl.init('Rbs_Catalog_ProductList');
			}
		};
	}

	Editor.$inject = ['RbsChange.ProductListService'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductList', Editor);
})();
