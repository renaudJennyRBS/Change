(function ($) {

	$.jstree._themes = 'Rbs/Admin/lib/jstree/themes/';

	// Convenient hack to reverse jQuery collections.
	$.fn.reverse = [].reverse;


	//-------------------------------------------------------------------------
	//
	// AngularJS modules
	//
	//-------------------------------------------------------------------------

	// Declares the main module and its dependencies.
	var app = angular.module('RbsChange', ['ngResource', 'ngSanitize', 'ngMobile', 'OAuthModule']);

	app.constant('RbsChange.Version', '4.0.0');

	app.constant('RbsChange.Device', {
		'isMultiTouch' : function () {
			return ('ontouchstart' in document.documentElement);
		}
	});

	app.directive('rbsChangeVersion', ['RbsChange.Version', function (version) {
		return {
			'restrict'   : 'A',
			link : function (scope, elm) {
				elm.text('RBS Change version ' + version);
			}
		};
	}]);

	// === Routing and navigation ===

	app.config(['$locationProvider', '$interpolateProvider', function ($locationProvider, $interpolateProvider) {
		$locationProvider.html5Mode(true);
		$interpolateProvider.startSymbol('(=').endSymbol('=)');
	}]);


	app.directive('todo', function () {
		return {
			'restrict'   : 'E',
			'transclude' : true,
			'template'   : '<div class="alert alert-danger">TODO <span ng-transclude="">en cours de développement... </span></div>'
		};
	});


	// UID generation
	// FIXME Check if this is still used.

	app.factory('RbsChange.UID', function () {

		var uid = 0;

		var getUID = function (prefix) {
			prefix = prefix || 'RCUID-';
			uid++;
			return prefix + uid;
		};

		$.fn.getUID = function (prefix) {
			prefix = prefix || 'RCUID-';
			if (!this.length) {
				return 0;
			}
			var fst = this.first(),
			    id = fst.attr('id');
			if (!id) {
				id = getUID();
				fst.attr('id', id);
			}
			return id;
		};

		return {
			getUID: getUID
		};
	});


	app.config(['OAuthServiceProvider', function (OAuth) {
		var oauthUrl = '/rest.php/OAuth/';
		OAuth.setBaseUrl(oauthUrl);
		OAuth.setRealm('rest');

		// Sign all the requests on our REST services...
		OAuth.setSignedUrlPatternInclude('/rest.php/');
		// ... but do NOT sign OAuth requests.
		OAuth.setSignedUrlPatternExclude(oauthUrl);
	}]);


	/**
	 * RootController
	 *
	 * This Controller is bound on the <body/> tag and is, thus, the "root Controller".
	 * Mostly, it deals with user authentication.
	 */
	app.controller('Change.RootController', ['$rootScope', '$filter', '$location', 'RbsChange.Settings', 'RbsChange.Utils', 'RbsChange.REST', 'OAuthService', function ($rootScope, $filter, $location, Settings, Utils, REST, OAuthService) {
		var redirectUrl = null,
		    alreadyGotError = false;

		$rootScope.setLanguage = function (lang) {
			// TODO Save settings on the server.
			$rootScope.language = Settings.language = lang;
		};

		$rootScope.logout = function () {
			OAuthService.logout();
			$location.path('/login');
		};

		$rootScope.$on('OAuth:AuthenticationFailure', function (event, rejection) {
			if (rejection.status === 401 || (rejection.status === 500 && rejection.data && Utils.startsWith(rejection.data.code, 'EXCEPTION-72'))) {
				if (alreadyGotError) {
					$location.path('/login');
				} else {
					alreadyGotError = true;
					redirectUrl = angular.copy($location.path());
					$location.path('/login');
				}
			}
		});

		$rootScope.$on('OAuth:UserLoginSuccess', function (event, userId) {
			alreadyGotError = false;
			if (!redirectUrl || Utils.startsWith(redirectUrl, '/login')) {
				redirectUrl = '/';
			}
			$rootScope.$apply(function () {
				$location.path(redirectUrl);
			});
		});

		REST.resource('Rbs_Users_User', OAuthService.getUserId()).then(function (user) {
			$rootScope.user = user;
		});

	}]);



	// === Global directives (custom HTML components) ===

	/**
	 * Directive that automatically gives the focus to an element when it is created/displayed.
	 */
	app.directive('autoFocus', function () {
		var timer = null;

		return function (scope, elm, attr) {
			if (timer) {
				clearTimeout(timer);
			}

			timer = setTimeout(function () {
				elm.focus();
				timer = null;
			});
		};
	});

	app.directive('focusOnShow', ['$timeout', function ($timeout) {

		return function (scope, element, attr) {
			if (attr.ngShow) {
				scope.$watch(attr.ngShow, function (value) {
					if (value) {
						$timeout(function () {
							jQuery(element).find(attr.focusOnShow).first().focus();
						});
					}
				});
			}
		};

	}]);



	var uid = 0;

    $.getUID = function (prefix) {
		prefix = prefix || 'RCUID-';
		uid++;
		return prefix + uid;
    };

	$.fn.getUID = function (prefix) {
		prefix = prefix || 'RCUID-';
		if (!this.length) {
			return 0;
		}
		var	fst = this.first(),
			id = fst.attr('id');
		if (!id) {
			id = $.getUID();
			fst.attr('id', id);
		}
		return id;
	};



	//=========================================================================


	$('body').on('click', '[data-role="close"][data-parent]', function () {
		var $this = $(this);
		var parent = $this.attr('data-parent');
		if (parent.substring(0, 9) === 'popover:#') {
			$(parent.substring(8, parent.length)).popover('hide');
		} else {
			$this.closest(parent).hide();
		}
	});

	$('body').on('click', '[data-toggle-class]', function () {
		var $this = $(this);
		var val = $this.data('toggleClass');
		var p = val.indexOf(' ');
		var cls = val.substring(0, p);
		var sel = val.substring(p + 1);
		$(sel).toggleClass(cls);
	});

	$('body').on('change', ':checkbox[data-toggle-class]', function () {
		var $this = $(this);
		var val = $this.data('toggleClass');
		var p = val.indexOf(' ');
		var cls = val.substring(0, p);
		var inverse = false;
		if (cls.charAt(0) === '!') {
			cls = cls.substring(1, cls.length);
			inverse = true;
		}
		var sel = val.substring(p + 1);
		$(sel).toggleClass(cls, inverse ? ! $this.prop('checked') : $this.prop('checked'));
	});

	// Fix for mobile devices (iPad)
	$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });


})( window.jQuery );