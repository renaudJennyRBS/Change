(function ()
{
	"use strict";

	function Editor () {
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Stock/Sku/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.data = {lengthUnit:'m'};
				editorCtrl.init('Rbs_Stock_Sku');
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockSku', Editor);

})();