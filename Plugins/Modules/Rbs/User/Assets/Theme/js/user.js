(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsUserForgotPassword(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserForgotPassword.tpl',
			link: function(scope) {

				function createResetPasswordRequest(data) {
					var request = AjaxAPI.postData('Rbs/User/User/ResetPasswordRequest', data);
					scope.sending = true;
					request.success(function(data) {
						scope.sending = false;
						scope.successSending = true;
					})
					.error(function(data, status) {
						scope.sending = false;
						scope.successSending = false;
						console.error('rbsUserCreateAccount', data, status);
					});
				}

				scope.diplayResetBox = false;
				scope.sending = false;
				scope.successSending = false;
				scope.resetPasswordEmail = null;

				scope.openBox = function() {
					jQuery('#reset-password-modal-main-content').modal({});
				};

				scope.invalidMail = function() {
					return 	!scope.resetPasswordEmail || scope.resetPasswordEmail == '';
				};

				scope.askReset = function() {
					createResetPasswordRequest({email: scope.resetPasswordEmail});
				};
			}
		}
	}
	rbsUserForgotPassword.$inject = ['RbsChange.AjaxAPI'];
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

	function rbsUserManageAutoLogin(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserManageAutoLogin.tpl',
			controller : ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.tokens = AjaxAPI.globalVar(cacheKey);
			}],
			link: function(scope, elm, attrs) {
				scope.errors = null;

				scope.deleteToken = function (index) {
					var data = {
						tokenId : scope.tokens[index].id
					};
					AjaxAPI.openWaitingModal();

					scope.errors = null;
					AjaxAPI.deleteData('Rbs/User/RevokeToken', data)
						.success(function(data) {
							AjaxAPI.closeWaitingModal();
							scope.tokens.splice(index, 1);
							scope.errors = null;
							if (scope.tokens.length == 0)
							{
								scope.tokens = null;
							}
						})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							console.log('deleteToken error', data, status);
						});
				}
			}
		};
	}
	rbsUserManageAutoLogin.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsUserManageAutoLogin', rbsUserManageAutoLogin);

	function rbsUserLogin(AjaxAPI, $rootScope, window) {

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

		return {
			restrict: 'A',
			templateUrl: '/rbsUserLogin.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				scope.error = null;
				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.data = {login:null, password:null, realm: scope.parameters.realm,
					rememberMe: true, device: buildDevice()};

				this.getData = function () {
					return scope.data;
				};

				this.login = function(loginData) {
					scope.error = null;
					var v = AjaxAPI.putData('Rbs/User/Login', loginData);
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

				this.logout = function() {
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
			}],
			link: function(scope, elm, attrs, controller) {
				scope.login = function() {
					controller.login(scope.data);
				};

				scope.logout = function() {
					controller.logout();
				}
			}
		}
	}
	rbsUserLogin.$inject = ['RbsChange.AjaxAPI', '$rootScope', '$window'];
	app.directive('rbsUserLogin', rbsUserLogin);

	function rbsUserCreateAccount(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserCreateAccount.tpl',
			scope: {
				fixedEmail:'@',
				confirmationPage:'@'
			},
			controller : ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');

				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.data = {
					email: scope.fixedEmail ? scope.fixedEmail : null,
					password: null,
					confirmationPage: scope.confirmationPage ? parseInt(scope.confirmationPage) : 0
				};

				this.getData = function() {
					return scope.data;
				};

				this.createAccountRequest = function(data) {
					var request = AjaxAPI.postData('Rbs/User/User/AccountRequest', data);
					AjaxAPI.openWaitingModal();
					scope.error = null;
					request.success(function(data) {
						AjaxAPI.closeWaitingModal();
						scope.requestAccountCreated = true;
						})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							if (data && data.message) {
								scope.error = data.message;
							}
							console.error('rbsUserCreateAccount', data, status);
					});
				};

				this.confirmAccountRequest = function(data) {
					var request = AjaxAPI.putData('Rbs/User/User/AccountRequest', data);
					AjaxAPI.openWaitingModal();
					scope.error = null;
					request.success(function(data) {
						AjaxAPI.closeWaitingModal();
						scope.accountConfirmed = true;
					})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							scope.accountConfirmed = true;
							if (data && data.message) {
								scope.error = data.message;
							}
							console.error('rbsUserConfirmAccount', data, status);
						});
				};

				if (scope.parameters.requestId && scope.parameters.email) {
					this.confirmAccountRequest({requestId: scope.parameters.requestId, email: scope.parameters.email})
				}
			}],

			link: function(scope, elm, attrs, controller) {
				scope.showForm = function() {
					return !scope.requestAccountCreated && !scope.parameters.requestId;
				};

				scope.submit = function() {
					controller.createAccountRequest(scope.data);
				}
			}
		}
	}
	rbsUserCreateAccount.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsUserCreateAccount', rbsUserCreateAccount);


	function rbsUserResetPassword(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserResetPassword.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');

				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.data = {
					token: scope.parameters.token ? scope.parameters.token : null,
					password: null
				};

				this.getData = function() {
					return scope.data;
				};

				this.confirmResetPassword = function(data) {
					var request = AjaxAPI.putData('Rbs/User/User/ResetPasswordRequest', data);
					AjaxAPI.openWaitingModal();
					scope.error = null;
					request.success(function(data) {
						AjaxAPI.closeWaitingModal();
						scope.passwordConfirmed = true;
					})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							scope.passwordConfirmed = false;
							if (data && data.message) {
								scope.error = data.message;
							}
							console.error('rbsUserResetPassword', data, status);
						});
				};
			}],

			link: function(scope, elm, attrs, controller) {
				scope.showForm = function() {
					return scope.data.token && !scope.passwordConfirmed;
				};

				scope.submit = function() {
					controller.confirmResetPassword(scope.data);
				}
			}
		}
	}
	rbsUserResetPassword.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsUserResetPassword', rbsUserResetPassword);


	function rbsUserChangePassword(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserChangePassword.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');

				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);

				scope.data = {
					currentPassword: null,
					password: null
				};

				this.getData = function() {
					return scope.data;
				};

				this.changePassword = function(data) {
					var request = AjaxAPI.putData('Rbs/User/User/ChangePassword', data);
					AjaxAPI.openWaitingModal();
					scope.error = null;
					request.success(function(data) {
						AjaxAPI.closeWaitingModal();
						scope.passwordConfirmed = true;
						scope.data = {
							currentPassword: null,
							password: null
						};
						scope.confirmPassword = null;
					})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							scope.passwordConfirmed = false;
							if (data && data.message) {
								scope.error = data.message;
							}
							console.error('changePassword', data, status);
						});
				};
			}],

			link: function(scope, elm, attrs, controller) {
				scope.showForm = function() {
					return scope.parameters.authenticated;
				};

				scope.submit = function() {
					controller.changePassword(scope.data);
				}
			}
		}
	}
	rbsUserChangePassword.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsUserChangePassword', rbsUserChangePassword);

	function rbsUserAccount(AjaxAPI, $rootScope) {
		return {
			restrict: 'A',
			templateUrl: '/rbsUserAccount.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.data = AjaxAPI.globalVar(cacheKey);

				this.saveProfiles = function(data) {
					var request = AjaxAPI.putData('Rbs/User/User/Profiles', data);
					AjaxAPI.openWaitingModal();
					scope.error = null;
					request.success(function(data) {
						AjaxAPI.closeWaitingModal();
						scope.readonly = true;
						scope.data = data.dataSets;
						var params = {'profile': scope.data, 'userId': scope.data.common.id};
						$rootScope.$broadcast('rbsUserProfileUpdated', params);
					})
						.error(function(data, status) {
							AjaxAPI.closeWaitingModal();
							if (data && data.message) {
								scope.error = data.message;
							}
							console.error('changePassword', data, status);
						});
				};
			}],
			link: function(scope, elm, attrs, controller) {
				scope.success = false;
				scope.readonly = true;

				scope.openEdit = function() {
					scope.readonly = false;
					scope.dataBackup = angular.copy(scope.data);
				};

				scope.saveAccount = function() {
					controller.saveProfiles(scope.data);
				};

				scope.cancelEdit = function() {
					scope.readonly = true;
					scope.data = scope.dataBackup;
				};
			}
		}
	}
	rbsUserAccount.$inject = ['RbsChange.AjaxAPI', '$rootScope'];
	app.directive('rbsUserAccount', rbsUserAccount);
})();