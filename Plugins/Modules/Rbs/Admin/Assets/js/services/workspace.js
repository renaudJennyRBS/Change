(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.service('RbsChange.Workspace', [ function () {

		var	timers = {};

		this.addResizeHandler = function (uniqueId, callback) {
			$(window).resize(function () {
				var ms = 500;
				if (!uniqueId) {
					throw new Error("A 'uniqueId' is required.");
				}
				if (timers[uniqueId]) {
					clearTimeout (timers[uniqueId]);
				}
				timers[uniqueId] = setTimeout(callback, ms);

			});
		};


		this.removeResizeHandler = function (uniqueId) {
			clearTimeout(timers[uniqueId]);
		};

	}]);


	//
	// Workspace Directives
	//


	/**
	 * Directive: rbsActiveRoute
	 * Usage    : as attribute: <a href="..." rbs-active-route>...</a>
	 *
	 * Toggles the 'active' CSS class depending on the current URL.
	 */
	app.directive('rbsActiveRoute', ['$rootScope', '$location', function ($rootScope, $location)
	{
		return {
			restrict : 'A',
			priority : -100,

			link : function (scope, iElement, iAttrs)
			{
				// Get parent element on which the 'active' class should be set
				var activeEl = iAttrs.rbsActiveRoute ? iElement.closest(iAttrs.rbsActiveRoute) : iElement,
					href = iAttrs.href;

				if (href.substr(0, 5) !== 'http:' && href.substr(0, 6) !== 'https:' && href.charAt(0) !== '/')
				{
					href = '/' + href;
				}

				function isSameURL () {
					return href === $location.absUrl() || href === $location.path();
				}

				function updateStyle () {
					activeEl[isSameURL() ? 'addClass' : 'removeClass']('active');
				}

				// React to every route change and add/remove 'active' on parent element
				$rootScope.$on('$routeChangeSuccess', updateStyle);
				$rootScope.$on('$routeUpdate', updateStyle);
				updateStyle();
			}
		};
	}]);


	/**
	 * Directive: rbsAsideColumn
	 * Usage : <div rbs-aside-column>...</div>
	 *
	 * Shortcut to set the default CSS class name for a standard left column.
	 */
	app.directive('rbsAsideColumn', function ()
	{
		return {
			restrict : 'A',
			link : function (scope, iElement)
			{
				iElement.addClass('col-md-3');
			}
		};
	});


	/**
	 * Directive: rbsAsideColumn
	 * Usage : <div rbs-main-column>...</div>
	 *
	 * Shortcut to set the default CSS class name for a standard main view with a left column.
	 */
	app.directive('rbsMainColumn', function ()
	{
		return {
			restrict : 'A',
			link : function (scope, iElement)
			{
				iElement.addClass('col-md-9');
			}
		};
	});


	/**
	 * Directive: rbsFullWidth
	 * Usage : <div rbs-full-width>...</div>
	 *
	 * Shortcut to set the default CSS class name for a full width view.
	 */
	app.directive('rbsFullWidth', function ()
	{
		return {
			restrict : 'A',
			link : function (scope, iElement)
			{
				iElement.addClass('col-md-12');
			}
		};
	});


	/**
	 * Directive: rbsAsidePluginMenu
	 * Usage    : as element: <rbs-aside-plugin-menu[ plugin="Vendor_Plugin"]></rbs-aside-plugin-menu>
	 *
	 * Displays the menu of the given plugin.
	 * If `plugin` attribute is not set, this Directive will look for the current plugin based on the current route.
	 */
	app.directive('rbsAsidePluginMenu', ['$rootScope', function ($rootScope)
	{
		return {
			restrict : 'E',
			replace : true,
			template : '<div ng-include="menuUrl"></div>',
			scope : true,

			link : function (scope, iElement, iAttrs)
			{
				var plugin = iAttrs['plugin'] || $rootScope.rbsCurrentPluginName;
				scope.menuUrl = plugin.replace(/_/, '/') + '/menu.twig';
			}
		};
	}]);


	/**
	 * Default Asides for a list view.
	 */
	app.directive('rbsDefaultAsidesForList', function ()
	{
		return {
			restrict : 'E',
			template :
				'<rbs-aside-select-session></rbs-aside-select-session>' +
				'<rbs-aside-plugin-menu></rbs-aside-plugin-menu>' +
				'<rbs-aside-tag-filter></rbs-aside-tag-filter>'
		};
	});


	/**
	 * Default Asides for an editor view.
	 */
	app.directive('rbsDefaultAsidesForEditor', function ()
	{
		return {
			restrict : 'E',
			template :
				'<rbs-aside-editor-menu></rbs-aside-editor-menu>' +
				'<rbs-aside-translation ng-hide="document.isNew()" document="document"></rbs-aside-translation>' +
				'<rbs-aside-timeline ng-hide="document.isNew()"></rbs-aside-timeline>' +
				'<rbs-aside-seo ng-hide="document.isNew()" document="document"></rbs-aside-seo>'
		};
	});


	/**
	 * Directive: rbsAsideEditorMenu
	 * Usage    : as element: <rbs-aside-editor-menu></rbs-aside-editor-menu>
	 *
	 * This Directive displays the menu with the sections of an Editor.
	 * It updates on the 'Change:UpdateEditorMenu' event.
	 */
	app.directive('rbsAsideEditorMenu', ['$compile', '$rootScope', function ($compile, $rootScope)
	{
		return {
			restrict : 'E',

			link : function (scope, iElement)
			{
				$rootScope.$on('Change:UpdateEditorMenu', function (event, menuData)
				{
					var html,
						menuObject, menuScope;

					if (menuData.hasOwnProperty('scope') && menuData.hasOwnProperty('entries'))
					{
						menuObject = menuData.entries;
						menuScope = menuData.scope;
					}
					else {
						menuObject = menuData;
						menuScope = scope;
					}

					if (angular.isArray(menuObject)) {
						if ( ! angular.isFunction(scope.__mainMenuSetSection) ) {
							scope.__mainMenuSetSection = function (sec) {
								scope.section = sec;
							};
						}
						html = buildMenuHtml(menuObject);
					}

					$compile(html)(menuScope, function attachFn(clone) {
						iElement.append(clone);
					});
				});


				function buildMenuHtml (menu)
				{
					var	html = '<div class="panel panel-default">',
						firstGroup = true;

					angular.forEach(menu, function (entry)
					{
						if (entry.type === 'group')
						{
							if (! firstGroup) {
								html += '</div>';
							}
							html += '<div class="panel-heading"><h3 class="panel-title">' + entry.label + '</h3></div>';
							html += '<div class="list-group">';
							firstGroup = false;
						}
						else if (entry.type === 'section')
						{
							html += '<a class="list-group-item"';
							if (entry.hideWhenCreate) {
								html += ' ng-if="! document.isNew()"';
							}
							if (entry.url) {
								html += ' href="' + entry.url + '" rbs-active-route>';
							}
							else {
								html += ' href="javascript:;" data-menu-section="' + entry.id + '" ng-click="__mainMenuSetSection(' + entry.id + ')">';
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
			}
		};
	}]);


	/**
	 * Directive: rbsAside
	 * Usage    : as element: <rbs-aside template="template/url/of/aside/element.twig"></rbs-aside>
	 *
	 * Displays an Aside whose template is located at the URL provided in the 'template' attribute.
	 */
	app.directive('rbsAside', function ()
	{
		return {
			restrict : 'E',
			replace : true,
			template : '<div ng-include="asideUrl"></div>',
			scope : true,

			link : function (scope, iElement, iAttrs)
			{
				scope.asideUrl = iAttrs['template'];
			}
		};
	});


	/**
	 * Directive: rbsAsideTranslation
	 * Usage    : as element: <rbs-aside-translation document="document"></rbs-aside-translation>
	 *
	 * Displays the languages list to translate the given document.
	 */
	app.directive('rbsAsideTranslation', ['RbsChange.Utils', 'RbsChange.REST', 'RbsChange.i18n', '$q', function (Utils, REST, i18n, $q)
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/tpl/i18n-aside.twig',
			scope : {
				document : '='
			},

			link : function (scope, iElement)
			{
				iElement.hide();
				var docPromise = $q.defer();

				scope.$watch('document', function (doc)
				{
					if (Utils.isDocument(doc) && doc.refLCID) {
						docPromise.resolve(doc);
					}
				});

				$q.all([docPromise.promise, REST.getAvailableLanguages()]).then(function (results)
				{
					var doc = results[0],
						langs = results[1],
						contents = [];

					angular.forEach(langs.items, function (item, lcid) {
						if (lcid === doc.refLCID) {
							contents.push({
								'url' : doc.refUrl(),
								'text' : item.label + ' (<abbr title="' + i18n.trans('m.rbs.admin.adminjs.reference_language | ucf') + '">' + i18n.trans('m.rbs.admin.adminjs.ref_lang_abbr') + '</abbr>)',
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

					iElement.show();

					if (contents.length > 1) {
						scope.rbsI18nItems = contents;
					}
				});
			}
		};
	}]);


	/**
	 * Directive: rbsAsideSeo
	 * Usage    : as element: <rbs-aside-seo document="document"></rbs-aside-seo>
	 *
	 * Displays the link to edit the SEO information of the given document.
	 */
	app.directive('rbsAsideSeo', ['RbsChange.Utils', 'RbsChange.REST', function (Utils, REST)
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Seo/aside.twig',
			scope : {
				document : '='
			},

			link : function (scope, iElement)
			{
				iElement.hide();
				scope.$watch('document', function (doc)
				{
					if (Utils.isDocument(doc))
					{
						var seoLink = doc.getLink('seo');
						if (seoLink)
						{
							iElement.show();
							REST.call(seoLink, null, REST.resourceTransformer()).then(function (seoDocument)
							{
								scope.seoDocument = seoDocument;
							});
						}
					}
				});
			}
		};
	}]);


	/**
	 * Directive: rbsAsideTimeline
	 * Usage    : as element: <rbs-aside-timeline></rbs-aside-timeline>
	 *
	 * Displays the link that points to the given document's timeline.
	 */
	app.directive('rbsAsideTimeline', [ function ()
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Timeline/aside.twig'
		};
	}]);


	/**
	 * Directive: rbsAsideSelectSession
	 * Usage    : as element: <rbs-aside-select-session></rbs-aside-select-session>
	 */
	app.directive('rbsAsideSelectSession', ['$rootScope', 'RbsChange.SelectSession', function ($rootScope, SelectSession)
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/tpl/select-session-aside.twig',
			scope : {},

			link : function (scope, iElement)
			{
				iElement.hide();

				// Select session
				scope.selectSession = {
					info : SelectSession.info(),
					end : SelectSession.end,
					cancel : SelectSession.rollback,
					clear : SelectSession.clear,
					append : SelectSession.append,

					appendSelected : function () {
						SelectSession.append(angular.element($('#workspace rbs-document-list').first()).isolateScope().selectedDocuments);
						// FIXME deselectAll();
						return this;
					},

					use : function (doc) {
						SelectSession.append(doc).end();
					}
				};

				function addSelectSessionAside() {
					scope.selectSession.info = SelectSession.info();
					if (scope.selectSession.info === null) {
						iElement.hide();
					} else {
						iElement.show();
					}
				}

				// Update SelectSession information everytime it changes.
				$rootScope.$on('Change:SelectSessionUpdate', function () {
					addSelectSessionAside();
				});
				addSelectSessionAside();
			}
		};
	}]);


})( window.jQuery );