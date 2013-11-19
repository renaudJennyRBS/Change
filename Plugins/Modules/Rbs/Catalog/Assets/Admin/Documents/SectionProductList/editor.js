(function ()
{
	"use strict";

	function Editor(ProductListService)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Document/Rbs/Catalog/SectionProductList/editor.twig',
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

				editorCtrl.init('Rbs_Catalog_SectionProductList');
			}
		};
	}

	Editor.$inject = ['RbsChange.ProductListService'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogSectionProductList', Editor);
})();
