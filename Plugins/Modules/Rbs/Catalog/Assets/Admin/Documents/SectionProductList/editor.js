(function() {
	"use strict";

	function rbsDocumentEditorRbsCatalogSectionProductListNew(REST, $q, $location) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',
			link: function(scope, elm, attrs, editorCtrl) {
				scope.onLoad = function() {
					var defer = $q.defer(), sectionId, params = $location.search();
					if (params.hasOwnProperty('sectionId')) {
						sectionId = parseInt(params.sectionId, 10);
						if (!isNaN(sectionId)) {
							REST.resource(sectionId).then(function(section) {
								scope.document.synchronizedSection = section;
								defer.resolve();
							});
							return defer.promise;
						}
					}
					return null;
				};
			}
		};
	}
	rbsDocumentEditorRbsCatalogSectionProductListNew.$inject = ['RbsChange.REST', '$q', '$location'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogSectionProductListNew', rbsDocumentEditorRbsCatalogSectionProductListNew);
})();