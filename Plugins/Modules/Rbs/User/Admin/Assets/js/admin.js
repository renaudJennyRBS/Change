(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// User

		. when(
			'/Rbs/User',
			{
				templateUrl : 'Rbs/User/User/list.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/User',
			{
				templateUrl : 'Rbs/User/User/list.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/User/:id',
			{
				templateUrl : 'Rbs/User/User/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/Login',
			{
				templateUrl : 'Rbs/User/login.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/User/:id/Applications/',
			{
				templateUrl: 'Rbs/User/User/applications.twig',
				reloadOnSearch: false
			})

		. when(
			'/Rbs/User/Group',
			{
				templateUrl : 'Rbs/User/Group/list.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/Group/:id',
			{
				templateUrl : 'Rbs/User/Group/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/User/User/:id/Permission/',
			{
				templateUrl : 'Rbs/User/User/permission.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/User/Group/:id/Permission/',
			{
				templateUrl : 'Rbs/User/User/permission.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// User
			$delegate.register('Rbs_User_User', {
				'form'  : '/Rbs/User/User/:id',
				'list'  : '/Rbs/User/User',
				'applications' : '/Rbs/User/User/:id/Applications/',
				'permission' : '/Rbs/User/User/:id/Permission/'
			});
			// Group
			$delegate.register('Rbs_User_Group', {
				'form'  : '/Rbs/User/Group/:id',
				'list'  : '/Rbs/User/Group',
				'permission' : '/Rbs/User/Group/:id/Permission/'
			});

			return $delegate;

		}]);
	}]);

	function ChangeUserUserLoginController($scope, Workspace, OAuthService, NotificationCenter) {
		Workspace.hideMenus();

		$scope.login = function () {
			OAuthService.authenticate($scope.username, $scope.password).then(

				// Success
				function () {
					Workspace.restore();
				},

				// Failure
				function (failure) {
					NotificationCenter.error("Unable to authenticate", failure);
				}
			);
		};
	}


	ChangeUserUserLoginController.$inject = [
		'$scope',
		'RbsChange.Workspace',
		'OAuthService',
		'RbsChange.NotificationCenter'
	];
	app.controller('Rbs_User_User_LoginController', ChangeUserUserLoginController);

})();