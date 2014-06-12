(function() {
	"use strict";

	function rbsDocumentEditorRbsSimpleformFormEdit(ArrayUtils) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.fields)) {
						scope.document.fields = [];
					}
				};

				scope.moveTop = function(index) {
					if (angular.isArray(scope.document.fields)) {
						ArrayUtils.move(scope.document.fields, index, 0);
					}
				};

				scope.moveUp = function(index) {
					if (angular.isArray(scope.document.fields)) {
						ArrayUtils.move(scope.document.fields, index, index - 1);
					}
				};

				scope.moveBottom = function(index) {
					if (angular.isArray(scope.document.fields)) {
						ArrayUtils.move(scope.document.fields, index, scope.document.fields.length - 1);
					}
				};

				scope.moveDown = function(index) {
					if (angular.isArray(scope.document.fields)) {
						ArrayUtils.move(scope.document.fields, index, index + 1);
					}
				};

				scope.remove = function(index) {
					if (angular.isArray(scope.document.fields)) {
						scope.document.fields.splice(index, 1);
					}
				};

				scope.deleteField = function(fieldToBeDeleted) {
					var index = null;
					angular.forEach(scope.document.fields, function(field, i) {
						if (field.id === fieldToBeDeleted.id) {
							index = i;
						}
					});
					scope.remove(index);
				};

				scope.cascadeCreate = editorCtrl.registerCreateCascade('fields', 'Rbs_Simpleform_Field');
				scope.cascadeEdit = editorCtrl.registerEditCascade('fields');
			}
		};
	}

	rbsDocumentEditorRbsSimpleformFormEdit.$inject = ['RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSimpleformFormEdit', rbsDocumentEditorRbsSimpleformFormEdit);
})();