(function ()
{
	"use strict";

	/**
	 * @param Models
	 * @constructor
	 */
	function Editor(Models)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Catalog/Attribute/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function() {

					if (scope.document.documentType)
					{
						scope.documentTypeLabel = Models.getModelLabel(scope.document.documentType);
					}

				};

				editorCtrl.init('Rbs_Catalog_Attribute');
			}
		};
	}

	Editor.$inject = ['RbsChange.Models'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogAttribute', Editor);
})();