(function () {

	"use strict";

	function changeEditorWebsiteTopic (Breadcrumb) {

		return {
			restrict    : 'EA',
			templateUrl : 'Document/Rbs/Website/Topic/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl) {
				scope.onLoad = function () {
					if (!scope.document.website) {
						scope.document.website = Breadcrumb.getWebsite();
					}
				};

				editorCtrl.init('Rbs_Website_Topic');
			}
		};

	}

	changeEditorWebsiteTopic.$inject = ['RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteTopic', changeEditorWebsiteTopic);

})();