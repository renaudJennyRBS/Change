(function ()
{
	"use strict";

	function Editor(Editor)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Brand/Brand/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm);
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor'];
	angular.module('RbsChange').directive('editorRbsBrandBrand', Editor);
})();