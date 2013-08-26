(function ()
{
	"use strict";

	function Editor()
	{
		return {
			restrict : 'EC',
			templateUrl : 'Rbs/Catalog/Attribute/editor.twig',
			replace : true,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				editorCtrl.init('Rbs_Catalog_Attribute');
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogAttribute', Editor);
})();