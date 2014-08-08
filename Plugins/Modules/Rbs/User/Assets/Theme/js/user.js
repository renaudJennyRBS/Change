(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsUserForgotPassword($http) {
		return {
			restrict: 'AE',
			templateUrl: 'Theme/Rbs/Base/Rbs_User/forgot-password.twig',
			replace: true,

			link: function(scope) {
				scope.diplayResetBox = false;
				scope.sending = false;
				scope.successSending = false;
				scope.resetPasswordEmail = null;

				scope.openBox = function() {
					jQuery('#reset-password-modal-main-content').modal({});
				};

				scope.askReset = function() {
					scope.sending = true;
					$http.post('Action/Rbs/User/ResetPasswordRequest', {email: scope.resetPasswordEmail})
						.success(function() {
							scope.sending = false;
							scope.successSending = true;
						})
						.error(function() {
							scope.sending = false;
							scope.successSending = true;
						}
					);
				};
			}
		}
	}

	rbsUserForgotPassword.$inject = ['$http'];
	app.directive('rbsUserForgotPassword', rbsUserForgotPassword);

	function rbsUserShortAccount($rootScope) {
		return {
			restrict: 'A',
			templateUrl: '/accountShort.tpl',
			replace: false,
			transclude: true,

			link: function(scope) {
				scope.userNowConnected = false;

				$rootScope.$on('rbsUserConnected', function(event, params) {
					scope.userNowConnected = true;
					scope.accessorId = params.accessorId;
					scope.accessorName = params.accessorName;
				});

				$rootScope.$on('rbsUserProfileUpdated', function(event, params) {
					if (params.profile.fullName !== null && params.profile.fullName != '')
					{
						scope.userNowConnected = true;
						scope.accessorId = params.userId;
						scope.accessorName = params.profile.fullName;
					}
				});
			}
		}
	}

	rbsUserShortAccount.$inject = ['$rootScope'];
	app.directive('rbsUserShortAccount', rbsUserShortAccount);

	function rbsManageAutoLogin($http)
	{
		return {
			restrict: 'A',
			templateUrl: '/manageToken.tpl',
			replace: false,

			link: function(scope, elm, attrs) {
				scope.tokens = angular.fromJson(attrs.tokens);
				scope.errors = null;

				scope.deleteToken = function (index) {
					var params = {
						tokenId : scope.tokens[index].id
					};
					$http.post('Action/Rbs/User/RevokeToken', params)
						.success(function(data) {
							scope.tokens.splice(index, 1);
							scope.errors = null;
							if (scope.tokens.length == 0)
							{
								scope.tokens = null;
							}
						})
						.error(function(data) {
							scope.errors = data.errors;
						});
				}
			}
		};
	}
	rbsManageAutoLogin.$inject = ['$http'];
	app.directive('rbsManageAutoLogin', rbsManageAutoLogin);


	function rbsEditAccount($http, $rootScope)
	{
		return {
			restrict: 'A',
			templateUrl: '/editAccount.tpl',
			replace: false,

			link: function(scope, elm, attrs) {
				scope.success = false;
				scope.readonly = true;

				scope.userId = attrs.userId;
				scope.profile = angular.fromJson(attrs.profile);
				scope.items = angular.fromJson(attrs.titles);

				scope.openEdit = function() {
					scope.readonly = false;
					scope.profileBackup = angular.copy(scope.profile);
				};

				scope.saveAccount = function() {
					$http.post('Action/Rbs/User/EditAccount', scope.profile)
						.success(function(data) {
							scope.errors = null;
							scope.readonly = true;
							scope.profile = data;
							var params = {'profile': scope.profile, 'userId': scope.userId};
							$rootScope.$broadcast('rbsUserProfileUpdated', params);
						})
						.error(function(data) {
							scope.errors = data.errors;
						});
				};

				scope.cancelEdit = function() {
					scope.readonly = true;
					scope.profile = angular.copy(scope.profileBackup);
				};

			}
		}
	}
	rbsEditAccount.$inject = ['$http', '$rootScope'];
	app.directive('rbsEditAccount', rbsEditAccount);

})();