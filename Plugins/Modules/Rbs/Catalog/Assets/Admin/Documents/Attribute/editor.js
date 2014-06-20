(function() {
	"use strict";

	function Editor(Models) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.onReady = function() {
					if (scope.document.documentType) {
						scope.documentTypeLabel = Models.getModelLabel(scope.document.documentType);
					}
				};
			}
		};
	}

	Editor.$inject = ['RbsChange.Models'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogAttributeNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogAttributeEdit', Editor);
})();