(function() {
	"use strict";

	function rbsDocumentEditorRbsSimpleformField($compile) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.fieldTypeUpdated = function() {
					var directiveName = scope.document.fieldTypeCode;

					if (!directiveName) {
						elm.find('[data-role="fieldTypeConfig"]').empty();
					}
					else {
						var callback = function(element) {
							elm.find('[data-role="fieldTypeConfig"]').replaceWith(element);
						};
						directiveName = directiveName.replace(/_/g, '-').replace(/([a-z])([A-Z])/, '$1-$2').toLowerCase();
						var html = '<div data-role="fieldTypeConfig"><div ' + directiveName + '=""></div></div>';
						$compile(html)(scope, callback);
					}
				};

				scope.onLoad = function() {
					if (!angular.isObject(scope.document.parameters) || angular.isArray(scope.document.parameters)) {
						scope.document.parameters = {};
					}
				};

				scope.onReady = function() {
					scope.fieldTypeUpdated();
				};
			}
		};
	}

	rbsDocumentEditorRbsSimpleformField.$inject = ['$compile'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSimpleformFieldNew', rbsDocumentEditorRbsSimpleformField);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSimpleformFieldEdit', rbsDocumentEditorRbsSimpleformField);
})();