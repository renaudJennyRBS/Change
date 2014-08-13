(function() {
	"use strict";

	function rbsDocumentEditorRbsSimpleformFormEdit() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.fields)) {
						scope.document.fields = [];
					}
				};
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsSimpleformFormEdit', rbsDocumentEditorRbsSimpleformFormEdit);
})();