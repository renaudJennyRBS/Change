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
			[i18n.trans('m.rbs.geo.admin.js.module-name | ucf'), "Rbs/Geo"],
			[i18n.trans('m.rbs.geo.admin.js.zone-list | ucf'), "Rbs/Geo/Zone"]
		]);
		MainMenu.loadModuleMenu('Rbs_Geo');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Geo_Zone_ListController', ListController);

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
			[i18n.trans('m.rbs.geo.admin.js.module-name | ucf'), "Rbs/Geo"],
			[i18n.trans('m.rbs.geo.admin.js.zone-list | ucf'), "Rbs/Geo/Zone"]
		]);
		FormsManager.initResource($scope, 'Rbs_Geo_Zone');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Geo_Zone_FormController', FormController);
})();