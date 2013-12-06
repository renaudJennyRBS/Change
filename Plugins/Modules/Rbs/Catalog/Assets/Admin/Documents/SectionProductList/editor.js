(function ()
{
	"use strict";

	function Editor()
	{
		return {
			restrict: 'EC',
			templateUrl: 'Document/Rbs/Catalog/SectionProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				editorCtrl.init('Rbs_Catalog_SectionProductList');
			}
		};
	}

	Editor.$inject = [];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogSectionProductList', Editor);
})();
