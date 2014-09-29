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

	function rbsUserShortAccount($rootScope, AjaxAPI, window) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserShortAccount.tpl',

			link: function(scope, elem, attrs) {
				scope.parameters = AjaxAPI.getBlockParameters(attrs.cacheKey);
				scope.accessorId = scope.parameters.accessorId;
				scope.accessorName = scope.parameters.accessorName;

				$rootScope.$on('rbsUserConnected', function(event, params) {
					scope.accessorId = params.accessorId;
					scope.accessorName = params.accessorName;
				});

				$rootScope.$on('rbsUserProfileUpdated', function(event, params) {
					if (params.profile.fullName !== null && params.profile.fullName != '') {
						scope.accessorId = params.userId;
						scope.accessorName = params.profile.fullName;
					}
				});

				scope.logout = function() {
					var v = AjaxAPI.getData('Rbs/User/Logout');
					v.success(function(data, status, headers, config) {
						scope.parameters.accessorId = null;
						scope.parameters.accessorName = null;
						window.location.reload(true);
					}).
						error(function(data, status, headers, config) {
							scope.error = data.message;
							console.log('logout error', data, status);
						});
				}
			}
		}
	}
	rbsUserShortAccount.$inject = ['$rootScope', 'RbsChange.AjaxAPI', '$window'];
	app.directive('rbsUserShortAccount', rbsUserShortAccount);

	function rbsManageAutoLogin($http) {
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


	function rbsEditAccount($http, $rootScope) {
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

	function rbsUserLoginController(scope, elem, AjaxAPI, window, $rootScope) {
		var key = elem.attr('data-cache-key');
		scope.parameters = AjaxAPI.getBlockParameters(key);
		scope.error = null;

		function buildDevice() {
			var userAgent = window.navigator.userAgent;
			var system =
				userAgent.match(/windows/i) ? 'Windows' :
					userAgent.match(/kindle/i) ? 'Kindle' :
						userAgent.match(/android/i) ? 'Android' :
							userAgent.match(/ipad/i) ? 'iPad' :
								userAgent.match(/iphone/i) ? 'iPhone' :
									userAgent.match(/ipod/i) ? 'iPod' :
										userAgent.match(/mac/i) ? 'OS X' :
											userAgent.match(/(linux|x11)/i) ? 'Linux' :
												'unknown system';

			var webBrowser =
				userAgent.match(/firefox/i) && !userAgent.match(/seamonkey/i) ? 'Firefox' :
					userAgent.match(/seamonkey/i) ? 'Seamonkey' :
						userAgent.match(/chrome/i) && !userAgent.match(/chromium/i) ? 'Chrome' :
							userAgent.match(/chromium/i) ? 'Chromium' :
								userAgent.match(/safari/i) && !userAgent.match(/chrome/i) && !userAgent.match(/chromium/i) ? 'Safari' :
									userAgent.match(/msie/i) ? 'Internet Explorer' :
										'unknown web browser';
			return webBrowser + ' - ' + system;
		}

		scope.parameters.device = buildDevice();

		scope.submit = function() {
			scope.error = null;
			var v = AjaxAPI.putData('Rbs/User/Login', {login: scope.parameters.login, 'password': scope.parameters.password,
				realm: scope.parameters.realm, rememberMe: scope.parameters.rememberMe,
				device: scope.parameters.device});

			v.success(function(data, status, headers, config) {
				var user = data.dataSets.user;
				scope.parameters.accessorId = user.accessorId;
				scope.parameters.accessorName = user.name;
				if (scope.parameters.reloadOnSuccess) {
					window.location.reload(true)
				} else {
					var params = {'accessorId': user.accessorId, 'accessorName': user.name};
					$rootScope.$broadcast('rbsUserConnected', params);
				}
			}).
			error(function(data, status, headers, config) {
					scope.error = data.message;
					scope.parameters.password = scope.parameters.login = null;
				console.log('login error', data, status);
			});
		};

		scope.logout = function() {
			var v = AjaxAPI.getData('Rbs/User/Logout');
			v.success(function(data, status, headers, config) {
				scope.parameters.accessorId = null;
				scope.parameters.accessorName = null;
				if (scope.parameters.reloadOnSuccess) {
					window.location.reload(true);
				} else {
					var params = {'accessorId': null, 'accessorName': null};
					$rootScope.$broadcast('rbsUserConnected', params);
				}
			}).
				error(function(data, status, headers, config) {
					scope.error = data.message;
					console.log('logout error', data, status);
				});
		}
	}
	rbsUserLoginController.$inject = ['$scope', '$element', 'RbsChange.AjaxAPI', '$window', '$rootScope'];
	app.controller('rbsUserLoginController', rbsUserLoginController)
})();