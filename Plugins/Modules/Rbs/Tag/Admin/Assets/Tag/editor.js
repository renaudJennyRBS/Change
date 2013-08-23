(function () {

	"use strict";

	function editorFn () {

		return {
			restrict : 'C',
			templateUrl : 'Rbs/Tag/Tag/editor.twig',
			replace : true,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				editorCtrl.init('Rbs_Tag_Tag');
			}
		};

	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsTagTag', editorFn);

})();