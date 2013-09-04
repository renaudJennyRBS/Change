(function () {

	"use strict";

	function editorRbsThemePageTemplate(Breadcrumb)
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Theme/PageTemplate/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.onLoad = function () {
					var currentNode = Breadcrumb.getCurrentNode();
					if (currentNode.model === 'Rbs_Theme_Theme') {
						scope.document.theme = currentNode;
					}
				};

				editorCtrl.init('Rbs_Theme_PageTemplate');
			}
		};
	}

	editorRbsThemePageTemplate.$inject = ['RbsChange.Breadcrumb'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsThemePageTemplate', editorRbsThemePageTemplate);

})();