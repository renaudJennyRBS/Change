(function ($) {

	$.jstree._themes = 'Rbs/Admin/lib/jstree/themes/';

	// Convenient hack to reverse jQuery collections.
	$.fn.reverse = [].reverse;

	// Declares the main module and its dependencies.
	var app = angular.module('RbsChange', ['ngResource', 'ngSanitize', 'ngMobile', 'OAuthModule']);


	//-------------------------------------------------------------------------
	//
	// Constants.
	//
	//-------------------------------------------------------------------------


	app.constant('RbsChange.Version', '4.0.0');


	app.constant('RbsChange.Device', {
		'isMultiTouch' : function () {
			return ('ontouchstart' in document.documentElement);
		}
	});


	/**
	 * Events used by Change, where you can attach your own handlers.
	 */
	app.constant('RbsChange.Events', {

		// Raised when an Editor is ready (its document and the Breadcrumb are loaded).
		// Single argument is the edited document.
		'EditorReady'                    : 'Change:Editor.Ready',

		// Raised when an Editor is about to save a Document.
		// Single argument is a hash object with:
		// - document: the edited document that is about to be saved
		// - promises: array of promises that should be resolved before the save process is called.
		'EditorPreSave'                  : 'Change:Editor.RegisterPreSavePromises',

		// Raised when an Editor has just saved a Document.
		// Single argument is a hash object with:
		// - document: the edited document that has been saved
		// - promises: array of promises that should be resolved before the edit process is terminated.
		'EditorPostSave'                 : 'Change:Editor.RegisterPostSavePromises',

		// The following events are less useful for you...
		'EditorDocumentUpdated'          : 'Change:Editor.DocumentUpdated',
		'EditorCorrectionChanged'        : 'Change:CorrectionChanged',
		'EditorCorrectionRemoved'        : 'Change:CorrectionRemoved',
		'EditorUpdateDocumentProperties' : 'Change:UpdateDocumentProperties'
	});


	//-------------------------------------------------------------------------
	//
	// Configuration.
	//
	//-------------------------------------------------------------------------


	app.config(['$locationProvider', '$interpolateProvider', function ($locationProvider, $interpolateProvider) {
		$locationProvider.html5Mode(true);
		$interpolateProvider.startSymbol('(=').endSymbol('=)');
	}]);


	app.config(['OAuthServiceProvider', function (OAuth) {
		var oauthUrl = '/rest.php/OAuth/';
		OAuth.setBaseUrl(oauthUrl);
		OAuth.setRealm('rest');

		// Sign all the requests on our REST services...
		OAuth.setSignedUrlPatternInclude('/rest.php/');
		// ... but do NOT sign OAuth requests.
		OAuth.setSignedUrlPatternExclude(oauthUrl);
	}]);


	//-------------------------------------------------------------------------
	//
	// Directives.
	//
	//-------------------------------------------------------------------------


	app.directive('rbsChangeVersion', ['RbsChange.Version', function (version) {
		return {
			'restrict'   : 'A',
			link : function (scope, elm) {
				elm.text('RBS Change version ' + version);
			}
		};
	}]);


	app.directive('rbsTodo', function () {
		return {
			'restrict'   : 'E',
			'transclude' : true,
			'template'   : '<div class="alert alert-danger">TODO <span ng-transclude="">en cours de développement... </span></div>'
		};
	});


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


	//-------------------------------------------------------------------------
	//
	// Controllers.
	//
	//-------------------------------------------------------------------------


	/**
	 * RootController
	 *
	 * This Controller is bound to the <body/> tag and is, thus, the "root Controller".
	 * Mostly, it deals with user authentication and settings.
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

		REST.resource('Rbs_User_User', OAuthService.getUserId()).then(function (user) {
			$rootScope.user = user;
		});

	}]);


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