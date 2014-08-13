(function() {
	"use strict";

	function rbsDocumentEditorRbsGeoAddressFieldsEdit() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl)  {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.fields)) {
						scope.document.fields = [];
					}
				};

				scope.canDeleteItem = function(field) {
					return field && !field.locked;
				}
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsGeoAddressFieldsEdit', rbsDocumentEditorRbsGeoAddressFieldsEdit);
})();