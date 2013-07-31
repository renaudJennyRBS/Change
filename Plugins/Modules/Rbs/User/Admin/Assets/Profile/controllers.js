(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @param $scope
	 * @param $rootScope
	 * @param Breadcrumb
	 * @param REST
	 * @param MainMenu
	 * @param $http
	 * @param i18n
	 * @constructor
	 */
	function RbsUserProfileController($scope, Breadcrumb, REST, MainMenu, $http, i18n, User, PaginationPageSizes)
	{
		Breadcrumb.resetLocation([]);
		Breadcrumb.setResource(i18n.trans('m.rbs.user.admin.js.profile | ucf'));

		MainMenu.loadModuleMenu('Rbs_User_Profile');

		function initUser() {
			$scope.user = angular.copy(User.get());
			$scope.form.$setPristine();
		}

		User.ready().then(function () {
			initUser();
		});

		$scope.PaginationPageSizes = PaginationPageSizes;

		$scope.saveProfile = function () {
			User.saveProfile($scope.user.profile).then(function () {
				initUser();
			});
		};

		$scope.revert = function () {
			initUser();
		};

		$scope.isUnchanged = function () {
			return angular.equals(User.get(), $scope.user);
		};
	}

	RbsUserProfileController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.REST', 'RbsChange.MainMenu', '$http', 'RbsChange.i18n', 'RbsChange.User', 'RbsChange.PaginationPageSizes'];
	app.controller('Rbs_User_Profile_Controller', RbsUserProfileController);

})();