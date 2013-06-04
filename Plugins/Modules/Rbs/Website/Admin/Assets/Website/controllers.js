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
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"],
			[i18n.trans('m.change.website.admin.js.website-list | ucf'), "Change/Website/Website"]
		]);

		MainMenu.loadModuleMenu('Change_Website');
	}

	ListController.$inject =
		['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Website_Website_ListController', ListController);

	/**
	 * Controller for forms.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"],
			[i18n.trans('m.change.website.admin.js.website-list | ucf'), "Change/Website/Website"]
		]);
		FormsManager.initResource($scope, 'Change_Website_Website');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Change_Website_Website_FormController', FormController);

})();