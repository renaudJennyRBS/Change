(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Users

		. when(
			'/Rbs/Users/User',
			{
				templateUrl : 'Rbs/Users/User/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Users/User/:id',
			{
				templateUrl : 'Rbs/Users/User/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/Users/Login',
			{
				templateUrl : 'Rbs/Users/login.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Users
			$delegate.register('Rbs_Users_User', {
				'form'  : '/Rbs/Users/User/:id',
				'list'  : '/Rbs/Users/User'
			});

			return $delegate;

		}]);
	}]);

	function ChangeUsersUserLoginController($scope, Workspace, OAuthService, NotificationCenter) {
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


	ChangeUsersUserLoginController.$inject = [
		'$scope',
		'RbsChange.Workspace',
		'OAuthService',
		'RbsChange.NotificationCenter'
	];
	app.controller('Rbs_Users_User_LoginController', ChangeUsersUserLoginController);

})();