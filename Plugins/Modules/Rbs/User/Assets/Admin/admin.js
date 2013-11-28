(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_User_User')
				.route('applications'  , 'Rbs/User/User/:id/Applications/', 'Document/Rbs/User/User/applications.twig')
				.route('permission'    , 'Rbs/User/User/:id/Permissions/' , 'Document/Rbs/User/User/permission.twig')
				.route('public-profile', 'Rbs/User/User/:id/PublicProfile', 'Document/Rbs/User/User/public-profile.twig')
			;

			$delegate.model('Rbs_User_Group')
				.route('permission'  , 'Rbs/User/Group/:id/Permissions/', 'Document/Rbs/User/User/permission.twig')
				.route('public-profile', 'Rbs/User/Group/:id/PublicProfile', 'Document/Rbs/User/Group/public-profile.twig')
			;

			$delegate.model('Rbs_User').route('home', 'Rbs/User', { 'redirectTo': 'Rbs/User/User/'});
			$delegate.model(null).route('userProfile', 'Rbs/User/Profile', 'Document/Rbs/User/Profile/profile.twig');
			$delegate.model(null).route('userConnectionInfo', 'Rbs/User/ConnectionInfo', 'Document/Rbs/User/Profile/connection-info.twig');
			$delegate.model(null).route('userChangePassword', 'Rbs/User/ChangePassword', 'Document/Rbs/User/Profile/change-password.twig');

			$delegate.routesForModels([
				'Rbs_User_User',
				'Rbs_User_Group'
			]);

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


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_User_User');
	__change.createEditorForModel('Rbs_User_Group');

})();