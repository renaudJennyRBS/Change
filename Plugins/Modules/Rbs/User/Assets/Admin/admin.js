(function () {

	"use strict";

	var app = angular.module('RbsChange');

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
})();