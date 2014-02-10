(function () {

	"use strict";

	function changeEditorWebsiteTopic ($routeParams, Breadcrumb, REST) {
		return {
			restrict    : 'EA',
			templateUrl : 'Document/Rbs/Website/Topic/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl) {
				scope.onLoad = function () {
					if (!scope.document.section){
						var nodeId =  Breadcrumb.getCurrentNodeId();
						if (nodeId) {
							REST.resource(nodeId).then(function (doc){ scope.document.section = doc})
						}
					}

					if (scope.document.isNew() && $routeParams.website && !scope.document.website) {
						scope.document.website = $routeParams.website;
						REST.resource($routeParams.website).then(function (doc){ scope.document.website = doc});
					}
				};

				editorCtrl.init('Rbs_Website_Topic');
			}
		};
	}
	changeEditorWebsiteTopic.$inject = ['$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteTopic', changeEditorWebsiteTopic);
})();