(function () {

	"use strict";

	var app = angular.module('RbsChange');



	app.service('RbsChange.Settings', ['RbsChange.User', function (User) {

		var user = User.get();

		return {
			'set' : function (key, value, save) {
				user.profile[key] = value;
				if (save !== false) {
					return User.saveProfile(user.profile);
				}
				return null;
			},

			'get' : function (key, defaultValue) {
				console.log("Settings.get(", key, ") : ", user.profile[key]);
				return user.profile[key] || defaultValue;
			},

			'ready' : function () {
				return User.ready();
			}
		};

	}]);



	app.service('RbsChange.User', ['RbsChange.REST', 'OAuthService', '$location', '$rootScope', '$cookies', '$http', '$q', function (REST, OAuthService, $location, $rootScope, $cookies, $http, $q)
	{
		var	user = {'profile':{}},
			loaded = false,
			loading = false,
			self = this,
			readyQ = $q.defer();

		/**
		 * Starts the OAuth authentication process.
		 */
		this.startAuthentication = function () {
			var callbackUrl = document.getElementsByTagName('base')[0].href + 'authenticate?route=' + encodeURIComponent($location.url());
			OAuthService.startAuthentication(callbackUrl);
		};


		/**
		 * Load current user from the server.
		 */
		this.load = function () {
			if (loading) {
				return;
			}

			loading = true;
			var promise = REST.call(REST.getBaseUrl('admin/currentUser'));
			promise.then(

				// Success
				function (result) {
					loading = false;
					loaded = true;
					angular.extend(user, result.properties);
					REST.setLanguage(user.profile.LCID);
					$cookies.LCID = user.profile.LCID;
					readyQ.resolve(user);
				},

				// Error
				function (error) {
					loading = false;
					loaded = false;
					if (error.status == 401 || error.status == 403)
					{
						OAuthService.logout();
						self.startAuthentication();
					}
					else
					{
						console.error(error);
					}
				}
			);

			return promise;
		};


		/**
		 * @returns {Promise}
		 */
		this.ready = function () {
			return readyQ.promise;
		};


		/**
		 * @param profile
		 */
		this.saveProfile = function (profile) {
			var p = $http.put(REST.getBaseUrl('admin/currentUser'), profile);
			p.then(function (result) {
				angular.extend(user, result.data.properties);
			});
			return p;
		};


		/**
		 * @returns User
		 */
		this.get = function () {
			return user;
		};


		/**
		 *
		 */
		this.logout = function () {
			// Remove all properties of our 'user' object, keeping the same reference.
			if (user) {
				var p, props = [];
				for (p in user) {
					if (user.hasOwnProperty(p)) {
						props.push(p);
					}
				}
				for (p=0 ; p<props.length ; p++) {
					delete user[props[p]];
				}
			}

			OAuthService.logout();
			this.load();
		};


		this.init = function () {
			$rootScope.user = this.get();
			if (OAuthService.hasOAuthData()) {
				this.load();
			}
			else {
				this.startAuthentication();
			}
		};

	}]);


})();