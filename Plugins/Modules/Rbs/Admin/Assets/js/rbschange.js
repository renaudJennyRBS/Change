(function ($) {

	$.jstree._themes = 'Rbs/Admin/lib/jstree/themes/';

	// Convenient hack to reverse jQuery collections.
	$.fn.reverse = [].reverse;

	// Declares the main module and its dependencies.
	var app = angular.module('RbsChange', ['ngResource', 'ngSanitize', 'ngMobile', 'ngCookies', 'OAuthModule']);


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

		// Raised from the <form-button-bar/> directive to build the contents displayed before the buttons.
		// Single argument is a hash object with:
		// - document: the document being edited in the Editor
		// - contents: array of HTML Strings (Angular code is allowed as it will be compiled :))
		'EditorFormButtonBarContents'    : 'Change:Editor.FormButtonBarContents',

		// The following events are less useful for you...
		'EditorDocumentUpdated'          : 'Change:Editor.DocumentUpdated',
		'EditorCorrectionChanged'        : 'Change:CorrectionChanged',
		'EditorCorrectionRemoved'        : 'Change:CorrectionRemoved',
		'EditorUpdateDocumentProperties' : 'Change:UpdateDocumentProperties',

		// Raised from the <rbs-document-list/> directive when a filter parameter is present in the URL.
		// Listeners should fill in the 'predicates' recieved in the args.
		'DocumentListApplyFilter'        : 'Change:DocumentList.ApplyFilter',

		// Raised from the <rbs-document-list/> directive when a converter has been requested on a column.
		// {
		//    "converter" : "...",
		//    "params"    : "...",
		//    "promises"  : [],
		//    "values"    : {}
		// }
		// Listeners should fill in the "promises" array and the "values" hash object.
		'DocumentListConverterGetValues' : 'Change:DocumentList.ConverterGetValues',

		'DocumentListPreview'            : 'Change:DocumentList.Preview'
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
		OAuth.setRealm(__change.OAuth.realm);

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


	/**
	 * Usages:
	 * `<div rbs-time-ago="my.date.object"></div>`
	 * `<div rbs-time-ago="my.date.object">Last update: {time}</div>`
	 * `<div rbs-time-ago="my.date.object" interval="60"></div>` (interval is in seconds)
	 */
	app.directive('rbsTimeAgo', ['$timeout', function ($timeout) {

		var DEFAULT_INTERVAL = 60;

		return {
			'restrict' : 'A',
			'scope' : {
				'date' : '=rbsTimeAgo'
			},

			'link' : function (scope, element, attrs) {
				var stop,
					interval = DEFAULT_INTERVAL,
					content = element.html();

				if (attrs.interval) {
					interval = parseInt(attrs.interval, 10);
					if (isNaN(interval) || interval <= 0) {
						console.warn("Attribute 'interval' should be a valid positive integer.");
						interval = DEFAULT_INTERVAL;
					}
				}

				function update () {
					var timeAgo = moment(scope.date).fromNow();
					if (content) {
						element.html(content.replace(/\{time\}/, timeAgo));
					}
					else {
						element.html(timeAgo);
					}
					// Re-launch timer for next update
					stop = $timeout(update, interval*1000);
				}

				scope.$watch('date', function (value) {
					if (value) {
						if (stop) {
							$timeout.cancel(stop);
						}
						update();
					}
				}, true);

				scope.$on('$destroy', function () {
					$timeout.cancel(stop);
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
	app.controller('Change.RootController', ['$rootScope', 'RbsChange.User', '$location', function ($rootScope, User, $location) {

		if ($location.path() !== '/authenticate') {
			User.init();
		}

		$rootScope.$on('OAuth:AuthenticationSuccess', function () {
			console.log("User:AuthenticationSuccess");
			User.load().then(function () {
				$location.url($location.search()['route']);

			});
		});

	}]);

})( window.jQuery );