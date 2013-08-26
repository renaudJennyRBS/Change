(function () {

	"use strict";

	function editorRbsCatalogProductCategorization() {

		return {
			restrict : 'EC',
			templateUrl : 'Rbs/Catalog/ProductCategorization/editor.twig',
			replace: true,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl) {
				editorCtrl.init('Rbs_Catalog_ProductCategorization');
			}
		};

	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductCategorization', editorRbsCatalogProductCategorization);

})();