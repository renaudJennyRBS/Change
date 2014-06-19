(function() {
	"use strict";

	function Editor(ArrayUtils, Navigation, UrlManager) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.modifiersManager = {
					moveTop: function(index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, 0);
					},

					moveUp: function(index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, index - 1);
					},

					moveBottom: function(index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, scope.document.modifiersOrder.length - 1);
					},

					moveDown: function(index) {
						ArrayUtils.move(scope.document.modifiersOrder, index, index + 1);
					},

					remove: function(index) {
						scope.document.modifiersOrder.splice(index, 1);
					},

					deleteItem: function(doc) {
						var index = null;
						angular.forEach(scope.document.modifiersOrder, function(field, i) {
							if (field.id === doc[0].id) {
								index = i;
							}
						});
						scope.fieldManager.remove(index);
					},

					cascadeEdit: editorCtrl.registerEditCascade('modifiersOrder')
				};
			}
		}
	}

	Editor.$inject = ['RbsChange.ArrayUtils', 'RbsChange.Navigation', 'RbsChange.UrlManager'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCommerceProcessEdit', Editor);

	/**
	 * Aside.
	 */
	function CreationLinksAside() {
		return {
			restrict: 'E',
			templateUrl : 'Rbs/Commerce/Documents/Process/fees-and-discounts-aside.twig'
		};
	}

	angular.module('RbsChange').directive('rbsAsideCommerceProcessFeesAndDiscounts', CreationLinksAside);
})();