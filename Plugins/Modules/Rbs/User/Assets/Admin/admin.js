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
				.route('groupUsers', 'Rbs/User/Group/:id/GroupUsers', 'Document/Rbs/User/Group/group-users.twig')
			;

			$delegate.model('Rbs_User').route('home', 'Rbs/User', { 'redirectTo': 'Rbs/User/User/'});
			$delegate.model(null).route('userProfile', 'Rbs/User/Profile', 'Document/Rbs/User/Profile/profile.twig');

			$delegate.routesForModels([
				'Rbs_User_User',
				'Rbs_User_Group'
			]);

			return $delegate;
		}]);
	}]);


	function ChangeUserUserLoginController($scope, OAuthService, NotificationCenter, ErrorFormatter)
	{
		$scope.login = function () {
			OAuthService.authenticate($scope.username, $scope.password).then(

				// Success
				function () {
					// Nothing to do here for the moment.
				},

				// Failure
				function (failure) {
					NotificationCenter.error("Unable to authenticate", ErrorFormatter.format(failure));
				}
			);
		};
	}


	ChangeUserUserLoginController.$inject = [
		'$scope',
		'OAuthService',
		'RbsChange.NotificationCenter',
		'RbsChange.ErrorFormatter'
	];
	app.controller('Rbs_User_User_LoginController', ChangeUserUserLoginController);


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_User_User');
	__change.createEditorForModel('Rbs_User_Group');

})();