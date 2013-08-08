(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for resume.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @param $http
	 * @param Utils
	 * @constructor
	 */
	function ResumeController($scope, Breadcrumb, MainMenu, i18n, REST, $http, Utils, $routeParams)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.timeline.admin.js.module-name | ucf'), "Rbs/Timeline"]
		]);

		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
		});

		MainMenu.loadModuleMenu('Rbs_Timeline');
	}

	ResumeController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n', 'RbsChange.REST', '$http', 'RbsChange.Utils', '$routeParams'];
	app.controller('Rbs_Timeline_Resume_Controller', ResumeController);

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @param $http
	 * @param Utils
	 * @constructor
	 */
	function ResumeListController($scope, Breadcrumb, MainMenu, i18n, REST, $http, Utils)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.timeline.admin.js.module-name | ucf'), "Rbs/Timeline"]
		]);

		//get users infos
		var url = Utils.makeUrl(REST.getBaseUrl('timeline/userOrGroupIdentifiers'), { 'autocomplete': '@' });
		$http.get(url).success(function (data){
			$scope.users = data;
		});

		//get groups infos
		url = Utils.makeUrl(REST.getBaseUrl('timeline/userOrGroupIdentifiers'), { 'autocomplete': '@+' });
		$http.get(url).success(function (data){
			$scope.groups = data;
		});

		MainMenu.loadModuleMenu('Rbs_Timeline');
	}

	ResumeListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n', 'RbsChange.REST', '$http', 'RbsChange.Utils'];
	app.controller('Rbs_Timeline_Resume_ListController', ResumeListController);
})();