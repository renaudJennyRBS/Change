(function() {
	"use strict";

	function Editor(REST, $routeParams, $q) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {

				scope.onLoad = function() {
					if (scope.document.isNew() && $routeParams.website && !scope.document.website) {
						scope.document.website = $routeParams.website;
						REST.resource($routeParams.website).then(function(doc) { scope.document.website = doc});
					}
				};
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', '$q'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuEdit', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuTranslate', Editor);
})();