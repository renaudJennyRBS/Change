(function() {
	"use strict";

	function Editor(REST, $routeParams, $q) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.initDocument = function() {
					// Edition ('id' is present): let the Editor does his job and load the Document!
					if ($routeParams.hasOwnProperty('id')) {
						return null;
					}

					// Creation: we need to load the 'parent' Website to init the new Menu with it.
					var defer = $q.defer(),
						menu = REST.newResource('Rbs_Website_Menu');

					if ($routeParams.hasOwnProperty('website')) {
						REST.resource('Rbs_Website_Website', $routeParams.website).then(function(website) {
							menu.website = website;
							defer.resolve(menu);
						});
					}
					else {
						defer.resolve(menu);
					}
					return defer.promise;
				};
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', '$q'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuEdit', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenuTranslate', Editor);
})();