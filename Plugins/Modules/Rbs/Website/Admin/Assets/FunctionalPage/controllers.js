(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @constructor
	 */
	function ListController($scope, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.functionalpage-list | ucf'), "Rbs/Website/FunctionalPage"]
		]);

		MainMenu.loadModuleMenu('Rbs_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];

	app.controller('Rbs_Website_FunctionalPage_ListController', ListController);

})();