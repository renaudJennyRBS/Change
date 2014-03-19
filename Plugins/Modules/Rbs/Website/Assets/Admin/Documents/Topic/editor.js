(function() {
	"use strict";

	function Editor($routeParams, Breadcrumb, REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.onLoad = function() {
					if (!scope.document.section) {
						var nodeId = Breadcrumb.getCurrentNodeId();
						if (nodeId) {
							REST.resource(nodeId).then(function(doc) { scope.document.section = doc; });
						}
					}

					if (scope.document.isNew() && $routeParams.website && !scope.document.website) {
						scope.document.website = $routeParams.website;
						REST.resource($routeParams.website).then(function(doc) { scope.document.website = doc; });
					}
				};
			}
		};
	}

	Editor.$inject = ['$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteTopicNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteTopicEdit', Editor);
})();