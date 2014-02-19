(function () {
	"use strict";

	function Editor(REST, ArrayUtils) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Commerce/Process/editor.twig',
			require: 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {
				scope.onLoad = function () {
				};

				scope.modifiersManager = {
					moveTop: function (index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, 0);
					},

					moveUp: function (index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, index - 1);
					},

					moveBottom: function (index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, scope.document.modifiersOrder.length - 1);
					},

					moveDown: function (index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, index + 1);
					},

					remove: function (index) {
						scope.document.modifiersOrder.splice(index, 1);
					},

					deleteItem: function (doc) {
						var index = null;
						angular.forEach(scope.document.modifiersOrder, function (field, i) {
							if (field.id === doc[0].id) {
								index = i;
							}

						});

						scope.fieldManager.remove(index);

					}
				};

				editorCtrl.init('Rbs_Commerce_Process');
			}
		}
	}

	Editor.$inject = ['RbsChange.REST', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCommerceProcess', Editor);
})();