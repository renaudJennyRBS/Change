(function() {
	"use strict";

	function rbsDocumentEditorRbsCollectionCollectionEdit(ArrayUtils) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.items)) {
						scope.document.items = [];
					}
				};

				scope.moveTop = function(index) {
					if (angular.isArray(scope.document.items)) {
						ArrayUtils.move(scope.document.items, index, 0);
					}
				};

				scope.moveUp = function(index) {
					if (angular.isArray(scope.document.items)) {
						ArrayUtils.move(scope.document.items, index, index - 1);
					}
				};

				scope.moveBottom = function(index) {
					if (angular.isArray(scope.document.items)) {
						ArrayUtils.move(scope.document.items, index, scope.document.items.length - 1);
					}
				};

				scope.moveDown = function(index) {
					if (angular.isArray(scope.document.items)) {
						ArrayUtils.move(scope.document.items, index, index + 1);
					}
				};

				scope.remove = function(index) {
					if (angular.isArray(scope.document.items)) {
						scope.document.items.splice(index, 1);
					}
				};

				scope.deleteItem = function(itemToBeDeleted) {
					var index = null;
					angular.forEach(scope.document.fields, function(item, i) {
						if (item.id === itemToBeDeleted.id) {
							index = i;
						}
					});
					scope.remove(index);
				};

				scope.cascadeCreate = editorCtrl.registerCreateCascade('items', 'Rbs_Collection_Item');
				scope.cascadeEdit = editorCtrl.registerEditCascade('items');
			}
		};
	}

	rbsDocumentEditorRbsCollectionCollectionEdit.$inject = ['RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCollectionCollectionEdit', rbsDocumentEditorRbsCollectionCollectionEdit);
})();