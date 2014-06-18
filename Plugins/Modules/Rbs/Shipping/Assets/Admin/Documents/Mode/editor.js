(function() {
	"use strict";

	function rbsDocumentEditorRbsShippingMode() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onLoad = function() {
					if (!angular.isObject(scope.document.cartFilterData) || angular.isArray(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};
			}
		}
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsShippingModeNew', rbsDocumentEditorRbsShippingMode);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsShippingModeEdit', rbsDocumentEditorRbsShippingMode);
})();