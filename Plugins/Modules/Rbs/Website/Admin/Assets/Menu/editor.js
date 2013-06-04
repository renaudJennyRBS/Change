(function ()
{
	function changeEditorWebsiteMenu(Editor)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Change/Website/Menu/editor.twig',
			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link: function (scope, elm, attrs)
			{
				Editor.initScope(scope, elm);
			}
		};
	}

	changeEditorWebsiteMenu.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeWebsiteMenu', changeEditorWebsiteMenu);
})();