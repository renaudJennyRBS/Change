(function ()
{
	"use strict";

	function Editor()
	{
		return {
			restrict : 'EC',
			templateUrl : 'Rbs/Catalog/Category/editor.twig',
			replace : true,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				editorCtrl.init('Rbs_Catalog_Category');
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogCategory', Editor);
})();