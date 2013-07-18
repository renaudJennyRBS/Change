(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param Query
	 * @param $routeParams
	 * @param REST
	 * @constructor
	 */
	function ListController($scope, Breadcrumb, MainMenu, i18n, Query, $routeParams, $location, REST, Utils)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.menu-list | ucf'), "Rbs/Website/Menu"]
		]);

		$scope.selectedWebsite = null;

		REST.collection('Rbs_Website_Website').then(function (result) {
			$scope.websites = result.resources;

			// Only one website? Select it.
			if ($scope.websites.length === 1 && ! $routeParams.websiteId) {
				$location.path($scope.websites[0].url('menus'));
			} else if ($routeParams.websiteId) {
				var websiteId = parseInt($routeParams.websiteId, 10);
				if (isNaN(websiteId)) {
					throw new Error("Parameter 'websiteId' should be an integer.");
				}
				loadMenuList(websiteId);
				$scope.selectedWebsite = Utils.getById($scope.websites, websiteId);
				Breadcrumb.setPath([$scope.selectedWebsite]);
				registerWatches();
			}
		});

		function loadMenuList (websiteId) {
			$scope.listLoadQuery = Query.simpleQuery('Rbs_Website_Menu', 'website', websiteId);
		}

		function registerWatches () {
			$scope.$watch('selectedWebsite', function (website, oldValue) {
				if (website !== oldValue) {
					$location.path(website.url('menus'));
				}
			}, true);
		}

		MainMenu.loadModuleMenu('Rbs_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n', 'RbsChange.Query', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.Utils'];
	app.controller('Rbs_Website_Menu_ListController', ListController);

	//-------------------------------------------------------------------------

	/**
	 * @param $scope
	 * @param FormsManager
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.menu-list | ucf'), "Rbs/Website/Menu"]
		]);
		FormsManager.initResource($scope, 'Rbs_Website_Menu');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Website_Menu_FormController', FormController);

})();