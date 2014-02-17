(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @param $scope
	 * @param User
	 * @param PaginationPageSizes
	 * @constructor
	 */
	function RbsUserProfileController($scope, User, PaginationPageSizes)
	{
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

		//find notification mail mode
		if ($scope.user.profile.notificationMailInterval)
		{
			$scope.notificationMailMode = 'timeInterval';
		}
		else
		{
			$scope.notificationMailMode = $scope.user.profile.sendNotificationMailImmediately ? 'immediately' : 'no';
		}

		$scope.setNotificationMailMode = function (mode){
			switch (mode)
			{
				case 'no':
					$scope.user.profile.notificationMailInterval = '';
					$scope.notificationMailMode = 'no';
					$scope.user.profile.sendNotificationMailImmediately = false;
					break;
				case 'immediately':
					$scope.user.profile.notificationMailInterval = '';
					$scope.notificationMailMode = 'immediately';
					$scope.user.profile.sendNotificationMailImmediately = true;
					break;
				case 'timeInterval':
					if(!$scope.user.profile.notificationMailInterval)
					{
						$scope.user.profile.notificationMailInterval = 'P0Y0M0W1DT0H0M0S';
						$scope.user.profile.notificationMailAt = '12:00';
					}
					$scope.notificationMailMode = 'timeInterval';
					$scope.user.profile.sendNotificationMailImmediately = false;
					break;
				default:
					console.error('Error: undefined mode for Notification mail mode');
					break;
			}
		};
	}

	RbsUserProfileController.$inject = ['$scope', 'RbsChange.User', 'RbsChange.PaginationPageSizes'];
	app.controller('Rbs_User_Profile_Controller', RbsUserProfileController);

})();