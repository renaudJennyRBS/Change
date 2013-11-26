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


	app.directive('rbsDefaultAsidesForList', function ()
	{
		return {
			restrict : 'E',
			template :
				'<rbs-aside-plugin-menu></rbs-aside-plugin-menu>' +
				'<rbs-aside-tag-filter></rbs-aside-tag-filter>'
		};
	});


	app.directive('rbsDefaultAsidesForEditor', function ()
	{
		return {
			restrict : 'E',
			template :
				'<rbs-aside-editor-menu></rbs-aside-editor-menu>' +
				'<rbs-aside-translation ng-if="document.refLCID" document="document"></rbs-aside-translation>'
		};
	});


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



	app.directive('rbsAsideTranslation', ['RbsChange.REST', 'RbsChange.i18n', '$q', function (REST, i18n, $q)
	{
		return {
			restrict : 'E',
			replace : true,
			template : '<div ng-include="\'Rbs/Admin/tpl/i18n-aside.twig\'"></div>',
			scope : {
				document : '='
			},

			link : function (scope)
			{
				var docPromise = $q.defer();

				scope.$watch('document', function (doc)
				{
					if (doc && doc.id) {
						docPromise.resolve(doc);
					}
				}, true);

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

					if (contents.length > 1) {
						scope.rbsI18nItems = contents;
					}
				});
			}
		};
	}]);


})( window.jQuery );