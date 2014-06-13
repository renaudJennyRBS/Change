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

})();