(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.service('RbsChange.MainMenu', ['$rootScope', '$location', '$timeout', '$compile', '$q', '$http', function ($rootScope, $location, $timeout, $compile, $q, $http) {

		var self = this,
		    $el = $('#mainMenu'),
		    currentUrl = null,
		    currentScope = null,
		    contentsStack = [],

		    buildMenuNgTemplate =
		       '<ul class="nav nav-list">' +
		          '<div ng-repeat="entry in _chgMenu" ng-switch="entry.type">' +
		             '<li ng-switch-when="group" class="nav-header">(=entry.label=)</li>' +
		             '<li ng-switch-when="section" ng-if="!entry.hideWhenCreate || !document.isNew()" ng-class="{\'invalid\': entry.invalid.length > 0}">' +
		                '<span ng-show="entry.fields.length > 0" class="pull-right badge" ng-class="{\'badge-success\': entry.corrected.length > 0}"><span class="badge-required-indicator" ng-show="entry.required.length > 0">*</span>(=entry.fields.length=)</span>' +
		                '<a ng-href="(=entry.url=)" ng-show="entry.url">(=entry.label=)</a>' +
		                '<a href="javascript:;" ng-hide="entry.url" data-menu-section="(=entry.id=)" ng-click="__mainMenuSetSection(entry.id)">(=entry.label=)</a>' +
		             '</li>' +
		          '</div>' +
		       '</ul>';

		this.init = function () {
			// Load main menu when the route changes

			$rootScope.$on('$routeUpdate', function () {
				self.updateLinks();
			});

			$rootScope.$on('$routeChangeSuccess', function () {
				self.updateLinks();
			});

		};


		/**
		 * Highlights the menu entry that corresponds to the current URL.
		 *
		 * @returns {String} The highlighted section's label.
		 */
		this.updateLinks = function () {
			var absUrl = $location.absUrl(),
			    currentUrl = $location.url(),
			    currentPath = $location.path(),
			    currentLabel = '',
			    sectionParam = $location.search()['section'];

			if (currentUrl.charAt(0) === '/') {
				currentUrl = currentUrl.substring(1);
			}
			if (currentPath.charAt(0) === '/') {
				currentPath = currentPath.substring(1);
			}

			$el.find(".nav a[href]:visible").each(function () {
				var href = $(this).attr('href'),
				    matches = href.match(/section=([a-z0-9\-]+)/i),
				    section = matches ? matches[1] : undefined;

				// TODO Optimize this... This should be possible ;)
				if (href === currentUrl || href === currentPath || href === absUrl || (sectionParam && sectionParam === section) || (currentScope && currentScope.section !== null && angular.isDefined($(this).data('menuSection')) && $(this).data('menuSection') === currentScope.section)) {
					$(this).parent().addClass("active");
					currentLabel = $(this).text();
				} else {
					$(this).parent().removeClass("active");
				}
			});

			$(".jstree a[href]").each(function () {
				var href = $(this).attr('href');
				if (href === currentUrl || href === currentPath || href === absUrl) {
					$(this).addClass("jstree-clicked");
				} else {
					$(this).removeClass("jstree-clicked");
				}
			});

			return currentLabel;
		};


		this.getCurrentSectionLabel = function () {
			var absUrl = $location.absUrl(),
			    currentUrl = $location.url(),
			    currentPath = $location.path(),
			    currentLabel = '';

			if (currentUrl.charAt(0) === '/') {
				currentUrl = currentUrl.substring(1);
			}
			if (currentPath.charAt(0) === '/') {
				currentPath = currentPath.substring(1);
			}

			$el.find("a[href]").each(function () {
				var href = $(this).attr('href');
				if (href === currentUrl || href === currentPath || href === absUrl) {
					$(this).parent().addClass("active");
					currentLabel = $(this).text();
				}
			});

			return currentLabel;
		};


		this.freeze = function () {
			this.frozen = true;
		};


		this.unfreeze = function () {
			this.frozen = false;
		};


		this.pushContents = function () {
			if (!this.frozen) {
				contentsStack.push(currentScope);
			}
		};


		this.popContents = function () {
			if (!this.frozen) {
				var scope = contentsStack.pop();
				this.build(scope._chgMenu, scope);
			}
		};


		/**
		 * Loads the main menu of the given module.
		 *
		 * @param module The module name.
		 * @param scope
		 *
		 * @returns Boolean true if the module has been loaded
		 */
		this.loadModuleMenu = function (module, scope) {
			if (!this.frozen) {
				return this.load(module.replace(/_/g, '/') + '/menu.twig', scope);
			}
			return false;
		};


		/**
		 * Loads the menu from the given URL, and compiles the contents in the given scope (or $rootScope).
		 *
		 * @param url Menu URL
		 * @param scope Scope
		 * @returns Promise resolved when the menu is loaded. Promise's result (transmitted to the success callbacks)
		 * is the label of the currently highlighted section in the menu.
		 */
		this.load = function (url, scope) {
			if (this.frozen) {
				return null;
			}

			var self = this,
			    deferred = $q.defer();

			if (currentUrl !== url) {
				if (self.request) {
					self.request.abort();
				}
				currentScope = scope || $rootScope;

				$http.get(url).success(function(data) {
					$compile(data)(currentScope, function (clone) {
						$timeout(function() {
							$el.html(clone);
							deferred.resolve(self.updateLinks());
							self.request = null;
						});
					});
				});

				currentUrl = url;
			} else {
				deferred.resolve(self.updateLinks());
			}

			return deferred.promise;
		};


		/**
		 * Builds the menu contents from the given entries.
		 *
		 * @param menuObject Menu object that defines the menu (see directives/editor.js).
		 * @param scope The scope into which the contents should be compiled.
		 */
		this.build = function (menuObject, scope) {
			if (this.frozen) {
				return null;
			}

			var self = this,
			    html;

			// Allow future loads of the same menu URL if the menu was built programmatically with this method.
			currentUrl = null;
			currentScope = scope;

			if (angular.isArray(menuObject)) {
				if ( ! angular.isFunction(scope.__mainMenuSetSection) ) {
					scope.__mainMenuSetSection = function (sec) {
						scope.section = sec;
					};
				}
				html = buildMenuNgTemplate;
			} else if (angular.isString(menuObject)) {
				html = menuObject;
			} else if (angular.isFunction(menuObject)) {
				html = menuObject();
			} else {
				console.error("Could not build MainMenu: scope.menu must be an Array, a String or a Function.");
			}

			$compile(html)(currentScope, function attachFn(clone) {
				$el.empty();
				$el.append(clone);

				$timeout(function () {
					self.updateLinks();
				});

				currentScope.$watch('section', function () {
					self.updateLinks();
				}, true);
			});
		};


		/**
		 * Hides the menu.
		 */
		this.hide = function () {
			$el.hide();
		};


		/**
		 * Shows the menu.
		 */
		this.show = function () {
			$el.show();
			$('#propertiesContainer').hide();
			$('#page-editor-block-properties-link').hide();
			$('#page-editor-block-properties-link-border').hide();
		};

	}]);


	app.run(['RbsChange.MainMenu', function (MainMenu) {
		MainMenu.init();
	}]);


})( window.jQuery );