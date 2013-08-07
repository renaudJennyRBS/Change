(function ()
{
	"use strict";

	function editorRbsThemePageTemplate(Editor, Breadcrumb)
	{
		return {
			restrict: 'EC',

			templateUrl: 'Rbs/Theme/PageTemplate/editor.twig',

			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function ()
				{
					var currentNode = Breadcrumb.getCurrentNode();
					if (currentNode.model === 'Rbs_Theme_Theme') {
						scope.document.theme = currentNode;
					}
				});
			}
		};
	}

	editorRbsThemePageTemplate.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('editorRbsThemePageTemplate', editorRbsThemePageTemplate);
})();