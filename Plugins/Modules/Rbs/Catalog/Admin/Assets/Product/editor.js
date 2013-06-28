(function ()
{
	"use strict";

	/**
	 * @param Editor
	 * @param DocumentList
	 * @param Loading
	 * @param REST
	 * @param i18n
	 * @param Breadcrumb
	 * @param Utils
	 * @constructor
	 */
	function Editor(Editor, DocumentList, Loading, REST, i18n, Breadcrumb, Utils)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Product/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function () {
					if (scope.document.isNew() && Utils.isTreeNode(Breadcrumb.getCurrentNode())) {
						scope.document.category = [Breadcrumb.getCurrentNode()];
					}
				});

				scope.createActions = [
					{ 'label': i18n.trans('m.rbs.catalog.admin.js.price | ucf'), 'url': 'Rbs/Catalog/Price/new', 'icon': 'file' }
				];
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.DocumentList', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Breadcrumb', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('editorRbsCatalogProduct', Editor);
})();