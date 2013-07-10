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
				Editor.initScope(scope, elm, function ()
				{
					// Categories.
					scope.List = {selectedCondition: null};
					Loading.start(i18n.trans('m.rbs.catalog.admin.js.condition-list-loading'));
					REST.collection('Rbs_Catalog_Condition').then(function (conditions)
					{
						scope.List.conditions = conditions.resources;
						/*for (var i = 5; i > 0; i--)
						 {
						 scope.List.conditions.unshift({id: i, label: 'toto' + i});
						 }*/
						scope.List.conditions.unshift({id: 0, label: i18n.trans('m.rbs.catalog.admin.js.no-condition')});
						if (scope.List.conditions.length == 1)
						{
							scope.List.selectedCondition = scope.List.conditions[0];
						}
						Loading.stop();
					});

					scope.$watch('List.selectedCondition', function (newValue, oldValue)
					{
						if (newValue === oldValue || scope.document.isNew())
						{
							return;
						}

						var url = '';
						if (newValue)
						{
							url = '/catalog/product/' + scope.document.id + '/categories/' + scope.List.selectedCondition.id + '/';
						}
						scope.categoryListUrl = url;
					});

					scope.addInCategories = function (docIds)
					{
						var conditionId = scope.List.selectedCondition.id;
						var url = REST.getBaseUrl('catalog/product/' + scope.document.id + '/categories/' + conditionId + '/');
						$http.put(url, {"addCategoryIds": docIds, "priorities": 0}, REST.getHttpConfig())
							.success(function (data)
							{
								// TODO use data
								scope.$broadcast('Change:DocumentList:DLRbsCatalogProductCategories:call', { 'method': 'reload' });
							})
							.error(function errorCallback(data, status)
							{
								data.httpStatus = status;
								scope.$broadcast('Change:DocumentList:DLRbsCatalogProductCategories:call', { 'method': 'reload' });
							});
					};
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