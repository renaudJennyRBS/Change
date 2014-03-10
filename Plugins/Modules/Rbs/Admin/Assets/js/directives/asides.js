(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


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

			link : function (scope, iElement, iAttrs)
			{
				var activeEl,
					href;

				function setHref (h)
				{
					href = h;
					if (href.substr(0, 5) !== 'http:' && href.substr(0, 6) !== 'https:' && href.charAt(0) !== '/')
					{
						href = '/' + href;
					}
				}

				function isSameURL ()
				{
					return href === $location.absUrl() || href === $location.path();
				}

				function updateStyle ()
				{
					activeEl[isSameURL() ? 'addClass' : 'removeClass']('active');
				}

				// Get parent element on which the 'active' class should be set
				activeEl = iAttrs.rbsActiveRoute ? iElement.closest(iAttrs.rbsActiveRoute) : iElement;
				href = setHref(iAttrs.href);

				// React to every route change and add/remove 'active' on parent element
				$rootScope.$on('$routeChangeSuccess', updateStyle);
				$rootScope.$on('$routeUpdate', updateStyle);
				updateStyle();

				iAttrs.$observe('href', function (h)
				{
					setHref(h);
					updateStyle();
				});
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
				'<rbs-aside-plugin-menu></rbs-aside-plugin-menu>' +
				'<rbs-aside-tag-filter></rbs-aside-tag-filter>'
		};
	});


	/**
	 * Default Asides for an editor view.
	 */
	app.directive('rbsDefaultAsidesForEditor', ['$timeout', function ($timeout)
	{
		return {
			restrict : 'E',

			//!\ This template is NOT in a separate file!
			// It is included in 'Rbs/Admin/Http/Actions/Assets/home.twig' because we need it to be included
			// before other Directives execute.
			templateUrl : 'Rbs/Admin/js/directives/aside-defaults-editor.twig',

			link : function (scope, iElement)
			{
				$timeout(function ()
				{
					var otherLinks = iElement.siblings('[rbs-aside-other-link]'),
						container = iElement.find('.rbs-aside-other-links');
					if (otherLinks.length > 0) {
						container.append('<hr/>');
					}
					container.append(otherLinks);
				});
			}
		};
	}]);


	/**
	 * Directive: rbsAsideEditorMenu
	 * Usage    : as element: <rbs-aside-editor-menu></rbs-aside-editor-menu>
	 *
	 * This Directive displays the menu with the sections of an Editor.
	 * It updates on the 'Change:UpdateEditorMenu' event.
	 */
	app.directive('rbsAsideEditorMenu', ['$rootScope', function ($rootScope)
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/aside-editor-sections.twig',
			require : '?^rbsDocumentEditorBase',

			link : function (scope, iElement, iAttrs, ctrl)
			{
				$rootScope.$on('Change:UpdateEditorMenu', function (event, menuEntries)
				{
					scope.entries = menuEntries;
				});

				if (ctrl) {
					scope.entries = ctrl.getMenuEntries();
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
			templateUrl : 'Rbs/Admin/js/directives/aside-translations.twig',
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

					scope.currentLCID = doc.LCID;
					scope.missingTranslations = false;

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
							if (! translated) {
								scope.missingTranslations = true;
							}
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
	app.directive('rbsAsideSeo', ['RbsChange.Utils', 'RbsChange.REST', '$location', function (Utils, REST, $location)
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
						iElement.show();
						var seoLink = doc.getLink('seo');
						if (seoLink)
						{
							REST.call(seoLink, null, REST.resourceTransformer()).then(function (seoDocument)
							{
								scope.seoDocument = seoDocument;
							});
						}
					}
				});

				scope.seoCreate = function ()
				{
					scope.seoCreating = true;
					REST.call(scope.document.getActionUrl('addSeo'), null, REST.resourceTransformer()).then(function (seoDocument)
					{
						scope.seoCreating = false;
						scope.seoDocument = seoDocument;
						$location.path(seoDocument.url());
					});
				};

			}
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
			templateUrl : 'Rbs/Admin/js/directives/aside-select-session.twig',
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