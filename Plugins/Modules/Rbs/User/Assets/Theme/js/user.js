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

				scope.openBox = function (){
					jQuery('#reset-password-modal-main-content').modal({});
				}

				scope.askReset = function (){
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
				}
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

				$rootScope.$on('rbsUserConnected', function (event, params){
					scope.userNowConnected = true;
					scope.accessorId = params.accessorId;
					scope.accessorName = params.accessorName;
				});
			}
		}


	}
	rbsUserShortAccount.$inject = ['$rootScope'];
	app.directive('rbsUserShortAccount', rbsUserShortAccount);

})();