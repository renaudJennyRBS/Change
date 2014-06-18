(function() {
	"use strict";

	function Editor() {
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

	angular.module('RbsChange').directive('rbsDocumentEditorRbsPaymentDeferredConnectorNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPaymentDeferredConnectorEdit', Editor);
})();