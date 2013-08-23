(function () {

	"use strict";

	function changeEditorWebsiteWebsite () {

		return {
			restrict : 'EC',
			templateUrl : 'Rbs/Website/Website/editor.twig',
			replace : true,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl) {
				editorCtrl.init('Rbs_Website_Website');
			}
		};

	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteWebsite', changeEditorWebsiteWebsite);

})();