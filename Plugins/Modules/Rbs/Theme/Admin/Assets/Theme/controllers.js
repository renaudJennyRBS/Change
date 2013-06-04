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
	 * @constructor
	 */
	function ListController($scope, DocumentList, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.theme.admin.js.module-name | ucf'), "Rbs/Theme"]
		]);

		var DL = DocumentList.initScope($scope, 'Rbs_Theme_Theme');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': i18n.trans('m.rbs.theme.admin.js.theme | ucf'), 'url': 'Rbs/Theme/Theme/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.rbs.admin.admin.js.modification-date | ucf') });
		DL.columns.push({ id: 'templates', label: i18n.trans('m.rbs.theme.admin.js.pagetemplate-list | ucf'), width: "90px", align: "center" });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.rbs.admin.admin.js.activated | ucf'), width: "90px", align: "center" });

		MainMenu.loadModuleMenu('Rbs_Theme');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Theme_Theme_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.theme.admin.js.module-name | ucf'), "Rbs/Theme"]
		]);
		FormsManager.initResource($scope, 'Rbs_Theme_Theme');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Theme_Theme_FormController', FormController);

	/**
	 * Controller for menu.
	 *
	 * @param $scope
	 * @param REST
	 * @constructor
	 */
	function MenuController($scope, REST)
	{
		REST.collection('Rbs_Theme_Theme').then(function (themes)
		{
			$scope.themes = themes.resources;
		});
	}

	MenuController.$inject = ['$scope', 'RbsChange.REST'];
	app.controller('Rbs_Theme_MenuController', MenuController);
})();