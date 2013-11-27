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

	/**
	 * @param $scope
	 * @param User
	 * @param REST
	 * @param $http
	 * @constructor
	 */
	function RbsUserConnectionInfoController($scope, User, REST, $http)
	{
		var originalUser;
		$scope.currentPassword = '';

		function initUser() {
			REST.call(REST.getBaseUrl('admin/connectionInfo')).then(function (user) {
					originalUser = user;
					$scope.user = angular.copy(user);
				}
			);
			$scope.wrongPassword = false;
			$scope.invalidLogin = false;
			$scope.form.$setPristine();
		}

		User.ready().then(function () {
			initUser();
		});

		$scope.saveConnectionInfo = function () {
			$http.put(REST.getBaseUrl('admin/connectionInfo'), {user: $scope.user, password: $scope.currentPassword}).success(function (){
				initUser();
			}).error(function (dataError){
					if (dataError.message == 'wrong password given') {
						$scope.wrongPassword = true;
					}
					else if (dataError.message == 'Invalid document properties. (login)') {
						$scope.invalidLogin = true;
					}
					console.error(dataError);
				});
			$scope.currentPassword = '';
		};

		$scope.revert = function () {
			initUser();
		};

		$scope.isUnchanged = function () {
			return angular.equals(originalUser, $scope.user);
		};

	}

	RbsUserConnectionInfoController.$inject = ['$scope', 'RbsChange.User', 'RbsChange.REST', '$http'];
	app.controller('Rbs_User_ConnectionInfo_Controller', RbsUserConnectionInfoController);

	/**
	 * @param $scope
	 * @param User
	 * @param REST
	 * @param $http
	 * @constructor
	 */
	function RbsUserChangePasswordController($scope, User, REST, $http)
	{
		$scope.currentPassword = '';

		function initUser() {
			REST.call(REST.getBaseUrl('admin/connectionInfo')).then(function (user) {
					$scope.user = angular.copy(user);
				}
			);
			$scope.wrongPassword = false;
			$scope.confirmPassword = '';
			$scope.form.$setPristine();
		}

		User.ready().then(function () {
			initUser();
		});

		$scope.saveConnectionInfo = function () {
			$http.put(REST.getBaseUrl('admin/connectionInfo'), {user: $scope.user, password: $scope.currentPassword}).success(function (){
				initUser();
			}).error(function (dataError){
					if (dataError.message == 'wrong password given') {
						$scope.wrongPassword = true;
					}
					console.error(dataError);
				});
			$scope.currentPassword = '';
		};

		$scope.revert = function () {
			initUser();
		};

		$scope.$watch('user.password', function (){
			$scope.form.newpassword.$setValidity('samePassword', $scope.user.password === $scope.confirmPassword);
			$scope.form.confirmpassword.$setValidity('samePassword', $scope.user.password === $scope.confirmPassword);
		});

		$scope.$watch('confirmPassword', function (){
			$scope.form.confirmpassword.$setValidity('samePassword', $scope.user.password === $scope.confirmPassword);
			$scope.form.newpassword.$setValidity('samePassword', $scope.user.password === $scope.confirmPassword);
		});

		$scope.isUnchanged = function () {
			return $scope.user.password !== $scope.confirmPassword;
		};

	}

	RbsUserChangePasswordController.$inject = ['$scope', 'RbsChange.User', 'RbsChange.REST', '$http'];
	app.controller('Rbs_User_ChangePassword_Controller', RbsUserChangePasswordController);

})();