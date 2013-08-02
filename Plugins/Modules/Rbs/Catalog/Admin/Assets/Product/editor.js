(function ()
{
	"use strict";

	/**
	 * @param Editor
	 * @param DocumentList
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @param Loading
	 * @constructor
	 */
	function Editor(Editor, DocumentList, REST, i18n, $http, Loading)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Product/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function() {
					if (!angular.isArray(scope.document.attributeValues))
					{
						scope.document.attributeValues = [];
					}
				});

				scope.$watch('document.attribute', function(newValue, oldValue){
					if (!angular.isUndefined(newValue))
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.document.attribute = newValue.id;
						}
						else if (newValue == '')
						{
							scope.document.attribute = null;
						}
					}
				});

				// Prices.
				scope.createActions = [
					{ 'label': i18n.trans('m.rbs.catalog.admin.js.price | ucf'), 'url': 'Rbs/Catalog/Price/new', 'icon': 'file' }
				];
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.DocumentList', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.Loading'];
	angular.module('RbsChange').directive('editorRbsCatalogProduct', Editor);
})();