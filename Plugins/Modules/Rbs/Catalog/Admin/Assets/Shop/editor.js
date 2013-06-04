(function ()
{
	"use strict";

	function Editor(Editor)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Change/Catalog/Shop/editor.twig',
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
	angular.module('RbsChange').directive('editorChangeCatalogShop', Editor);
})();