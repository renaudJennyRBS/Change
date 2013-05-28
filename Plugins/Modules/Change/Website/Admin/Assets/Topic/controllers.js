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
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"]
		]);

		var DL = DocumentList.initScopeForTree($scope);

		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;
		DL.addActions(['reorder']);

		$scope.createActions = [
			{ 'label': i18n.trans('m.change.website.admin.js.staticpage | ucf'), 'url': 'Change/Website/StaticPage/new', 'icon': 'file' },
			{ 'label': i18n.trans('m.change.website.admin.js.topic | ucf'), 'url': 'Change/Website/Topic/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.splice(1, 0, { id: 'type', label: "", width: "24px", align: "center" });
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.change.admin.admin.js.modification-date | ucf'), sortable: true });
		DL.columns.push({ id: 'nodeOrder', label: i18n.trans('m.change.admin.admin.js.order | ucf'), align: "right" });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.change.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Website_TopicListController', ListController);

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
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"]
		]);
		FormsManager.initResource($scope, 'Change_Website_Topic');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Change_Website_TopicFormController', FormController);
})();