(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @constructor
	 */
	function ListController($scope, $routeParams, DocumentList, Breadcrumb, MainMenu, i18n, REST)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.change.theme.admin.js.module-name | ucf'), "Change/Theme"]
		]);

		var DL = DocumentList.initScope($scope);
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': i18n.trans('m.change.theme.admin.js.pagetemplate | ucf'), 'url': 'Change/Theme/PageTemplate/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.change.admin.admin.js.modification-date | ucf'), sortable: true });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.change.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		if ($routeParams.theme)
		{
			REST.resource($routeParams.theme).then(function (theme)
			{
				Breadcrumb.setPath([theme]);
				DL.query = {"model": "Change_Theme_PageTemplate", "where": {"and": [
					{"op": "eq", "lexp": {"property": "theme"}, "rexp": {"value": theme.id}}
				]}};
				DL.reload();
			});
		}
		else
		{
			DL.setResourceUrl('Change_Theme_PageTemplate');
		}

		MainMenu.loadModuleMenu('Change_Theme');
	}

	ListController.$inject =
		['$scope', '$routeParams', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n',
			'RbsChange.REST'];
	app.controller('Change_Theme_PageTemplate_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @param REST
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager, i18n, REST)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.change.theme.admin.js.module-name | ucf'), "Change/Theme"]
		]);
		FormsManager.initResource($scope, 'Change_Theme_PageTemplate').then(function (pageTemplate)
		{
			REST.resource(pageTemplate.theme).then(function (theme)
			{
				Breadcrumb.setPath([theme]);
			});
		});
	}

	FormController.$inject =
		['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n', 'RbsChange.i18n', 'RbsChange.REST'];
	app.controller('Change_Theme_PageTemplate_FormController', FormController);
})();