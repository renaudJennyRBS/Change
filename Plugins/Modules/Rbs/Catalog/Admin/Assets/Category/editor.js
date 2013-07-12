(function ()
{
	"use strict";

	function Editor(Editor, REST, i18n, $http)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Category/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm);
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.REST', 'RbsChange.i18n', '$http'];
	angular.module('RbsChange').directive('editorRbsCatalogCategory', Editor);
})();