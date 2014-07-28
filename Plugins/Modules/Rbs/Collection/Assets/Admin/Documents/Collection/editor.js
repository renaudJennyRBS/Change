(function() {
	"use strict";

	function rbsDocumentEditorRbsCollectionCollectionEdit() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.items)) {
						scope.document.items = [];
					}
				};

				scope.canDeleteItem = function(item) {
					return item && !item.locked;
				}
			}
		};
	}
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCollectionCollectionEdit', rbsDocumentEditorRbsCollectionCollectionEdit);
})();