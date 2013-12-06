(function ()
{
	"use strict";

	function Editor()
	{
		return {
			restrict: 'EC',
			templateUrl: 'Document/Rbs/Catalog/ProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				editorCtrl.init('Rbs_Catalog_ProductList');
			}
		};
	}

	Editor.$inject = [];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductList', Editor);
})();
