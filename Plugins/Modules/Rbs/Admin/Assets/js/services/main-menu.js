(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.service('RbsChange.MainMenu', ['$rootScope', '$location', '$timeout', '$compile', '$q', '$http', 'RbsChange.REST', 'RbsChange.i18n', function ($rootScope, $location, $timeout, $compile, $q, $http, REST, i18n) {

		var self = this,
		    $el = $('#rbs-left-menu'),
		    currentUrl = null,
		    currentScope = null,
		    contentsStack = [];

		this.init = function ()
		{
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

			$el.find(".aside.main a.list-group-item[href]").each(function () {
				var href = $(this).attr('href'),
				    matches = href.match(/section=([a-z0-9\-]+)/i),
				    section = matches ? matches[1] : undefined;

				// TODO Optimize this... This should be possible ;)
				if (href === currentUrl || href === currentPath || href === absUrl || (sectionParam && sectionParam === section) || (currentScope && currentScope.section !== null && angular.isDefined($(this).data('menuSection')) && $(this).data('menuSection') === currentScope.section)) {
					$(this).addClass("active");
					currentLabel = $(this).text();
				} else {
					$(this).removeClass("active");
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

			$el.find(".aside.main a[href]").each(function () {
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
					if (angular.isString(data)) {

						if (data.indexOf('<rbs-tag-filter') === -1 && data.indexOf('<rbs-tag-filter-panel') === -1)
						{
							data += '<rbs-tag-filter-panel></rbs-tag-filter-panel>';
						}

						data = '<div class="aside main">' + data + '</div>';
					}
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

			this.hide().clear();

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
				html = buildMenuHtml(menuObject);
			} else if (angular.isString(menuObject)) {
				html = menuObject;
			} else if (angular.isFunction(menuObject)) {
				html = menuObject();
			} else {
				console.error("Could not build MainMenu: scope.menu must be an Array, a String or a Function.");
			}

			$compile(html)(currentScope, function attachFn(clone) {
				$el.find('.aside.main').first().remove();
				$el.prepend(clone);

				$timeout(function () {
					$el.show();
					self.updateLinks();
				});

				currentScope.$watch('section', function () {
					self.updateLinks();
				}, true);
			});
		};


		function buildMenuHtml (menu)
		{
			var	html = '<div class="aside main panel panel-default">',
				firstGroup = true;

			angular.forEach(menu, function (entry)
			{
				if (entry.type === 'group')
				{
					//html += '<h4 class="list-group-item">' + entry.label + '</h4>';
					if (! firstGroup) {
						html += '</div>';
					}
					html += '<div class="panel-heading"><h3 class="panel-title">' + entry.label + '</h3></div>';
					html += '<div class="list-group">';
					firstGroup = false;
				}
				else if (entry.type === 'section')
				{
					if (entry.url) {
						html += '<a class="list-group-item" href="' + entry.url + '">';
					}
					else {
						html += '<a class="list-group-item" href="javascript:;" data-menu-section="' + entry.id + '" ng-click="__mainMenuSetSection(' + entry.id + ')">';
					}
					if (entry.fields && entry.fields.length) {
						html += '<span class="badge"';
						if (entry.corrected && entry.corrected.length) {
							html += ' class="badge-success"';
						}
						html += '>';
						if (entry.required && entry.required.length) {
							html += '<span class="badge-required-indicator">*</span>';
						}
						html += entry.fields.length + '</span>';
					}

					html += entry.label + '</a>';
				}
			});

			html += '</div></div>';

			return html;
		}


		/**
		 * Adds an aside box identified by `key` with the given `content`.
		 * Content is compiled in the given `scope`.
		 *
		 * @param key
		 * @param tpl
		 * @param scope
		 */
		this.addAside = function (key, content, scope)
		{
			if (scope) {
				$compile(content)(scope, function (clone) {
					clone.attr('data-aside-key', key);
					addAsideContents(key, clone);
				});
			}
			else {
				content = $(content);
				content.attr('data-aside-key', key);
				addAsideContents(key, content);
			}

			return this;
		};


		/**
		 * Adds an aside box identified by `key` with the given template `tpl` as content.
		 * Template content is compiled in the given `scope`.
		 *
		 * @param key
		 * @param tpl
		 * @param scope
		 */
		this.addAsideTpl = function (key, tpl, scope)
		{
			var self = this;
			$http.get(tpl).success(function (html) {
				self.addAside(key, html, scope);
			});
		};


		this.addTranslationsAside = function (doc, scope) {
			var self = this;
			REST.getAvailableLanguages().then(function (langs) {
				var contents = [];
				angular.forEach(langs.items, function (item, lcid) {
					if (lcid === doc.refLCID) {
						contents.push({
							'url' : doc.refUrl(),
							'text' : item.label + ' (<abbr title="' + i18n.trans('m.rbs.admin.admin.js.reference-language | ucf') + '">' + i18n.trans('m.rbs.admin.admin.js.ref-lang-abbr') + '</abbr>)',
							'icon' : 'icon-book'
						});
					}
					else {
						var translated = doc.isTranslatedIn(lcid);
						contents.push({
							'url' : doc.translateUrl(lcid),
							'text' : item.label,
							'cssClass' : translated ? 'translated' : 'untranslated',
							'icon' : translated ? 'icon-ok' : 'icon-warning-sign'
						});
					}
				});

				if (contents.length > 1) {
					scope._i18nItems = contents;
					self.addAsideTpl('i18n', 'Rbs/Admin/tpl/i18n-aside.twig', scope);
				}
			});
			return this;
		};


		/**
		 * Clears the menu.
		 */
		this.clear = function () {
			$el.empty();
			return this;
		};


		/**
		 * Hides the menu.
		 */
		this.hide = function () {
			$el.hide();
			return this;
		};


		/**
		 * Shows the menu.
		 */
		this.show = function () {
			$el.show();
			$('#propertiesContainer').hide();
			$('#page-editor-block-properties-link').hide();
			$('#page-editor-block-properties-link-border').hide();
			return this;
		};


		function addAsideContents (key, contents) {
			var	box = $el.find('[data-aside-key="' + key + '"]'),
				index = box.index();
			if (index !== -1) {
				box.after(contents);
				box.remove();
			}
			else {
				$el.append(contents);
			}
		}

	}]);


	app.run(['RbsChange.MainMenu', function (MainMenu)
	{
		MainMenu.init();
	}]);



	app.directive('rbsPanel', function ()
	{
		return {
			restrict : 'E',
			transclude : true,
			replace : true,
			template :
				'<div class="panel panel-default">' +
					'<div class="panel-heading">' +
						'<h3 class="panel-title"><span ng-if="_panel.icon"><i class="(=_panel.icon=)"></i> </span><span ng-bind="_panel.title"></span></h3>' +
					'</div>' +
					'<div class="panel-body" ng-transclude=""></div>' +
				'</div>',
			scope : true,
			link : function (scope, iElement, iAttrs)
			{
				scope._panel = {
					title : iAttrs.title,
					icon : iAttrs.icon
				};
			}
		};
	});



	app.directive('rbsLinksPanel', function ()
	{
		return {
			restrict : 'E',
			transclude : true,
			replace : true,
			template :
				'<div class="panel panel-default">' +
					'<div class="panel-heading">' +
						'<h3 class="panel-title"><span ng-if="_panel.icon"><i class="(=_panel.icon=)"></i> </span><span ng-bind="_panel.title"></span></h3>' +
					'</div>' +
					'<div class="list-group" ng-transclude=""></div>' +
				'</div>',
			scope : true,
			link : function (scope, iElement, iAttrs)
			{
				scope._panel = {
					title : iAttrs.title,
					icon : iAttrs.icon
				};
			}
		};
	});


	app.directive('rbsPanelLink', function ()
	{
		return {
			restrict : 'C',
			link : function (scope, iElement, iAttrs)
			{
				iElement.addClass('list-group-item');
				if (iAttrs.icon) {
					iElement.prepend('<i class="' + iAttrs.icon + '"></i> ');
				}
			}
		};
	});


})( window.jQuery );