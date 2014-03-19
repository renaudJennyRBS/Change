/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		/**
		 * @ngdoc service
		 * @name RbsChange.service:UrlManager
		 *
		 * @description Provides methods to get different URLs for the Backoffice UI.
		 */
		$provide.provider('RbsChange.UrlManager', ['RbsChange.Utils', '$routeProvider', function (Utils, $routeProvider)
		{
			this.$get = ['$location', function ($location) {
				var urls = {};

				var register = function (key, name, path) {
					if (!urls.hasOwnProperty(key))
					{
						urls[key] = {};
					}
					urls[key][name] = path;
				};

				var defaultRule = {
					// Ensure that the user's settings are loaded before executing each route.
					resolve : {
						user : ['RbsChange.Settings', function (Settings) {
							return Settings.ready();
						}]
					},
					reloadOnSearch : false
				};

				var applyConfig = function(routes) {
					var rule, key, name;
					angular.forEach(routes, function(route, path) {
						if (route.hasOwnProperty('rule')) {
							rule = route.rule;
							key = route.model || route.module;
							name = route.name;
							if (key && name) {
								register(key, name, path);
							}

							if (name) {
								rule.ruleName =  name;
							}
							if (key) {
								rule.relatedModelName =  key;
							}

							if (route.hasOwnProperty('options')) {
								rule.options = route.options;
							}
							if (!rule.hasOwnProperty('redirectTo')) {
								rule = angular.extend({}, defaultRule, rule);
								if (rule.templateUrl) {
									//TODO Compatibility check
									rule.resolve.rbsPlugin = ['$rootScope', '$location', function ($rootScope, $location) {
										var tokens = $location.path().split('/');
										if (tokens.length > 2) {
											$rootScope.rbsCurrentPluginName = tokens[1] + '_' + tokens[2];
										} else {
											$rootScope.rbsCurrentPluginName = 'Rbs_Admin';
										}
										return $rootScope.rbsCurrentPluginName
									}];
								}
							}
							$routeProvider.when(path, rule);
						}
					});
				};

				var getUrl = function (doc, name) {
					var	key, namedPaths;
					if (angular.isObject(doc) && angular.isDefined(doc.model)) {
						key = doc.model;
					} else if (angular.isString(doc)) {
						key = doc;
					} else {
						throw new Error("Could not determine the Model of the given parameter: " + doc + ". Please provide a Model name (String) or a Document object.");
					}

					if (urls.hasOwnProperty(key)) {
						namedPaths = urls[key];
						if (name && namedPaths.hasOwnProperty(name)) {
							return namedPaths[name];
						} else if (name === 'i18n' && namedPaths.hasOwnProperty('form')) {
							return namedPaths['form'];
						}
					}
					return null;
				};


				var replaceParams = function (urlTpl, routeParams, queryStringParams) {
					queryStringParams = angular.extend({}, queryStringParams);
					var tplParamRegexp = /:([a-z]+)/gi, tplParams = [], result;
					while (result = tplParamRegexp.exec(urlTpl)) {
						tplParams.push(result[1]);
					}

					angular.forEach(tplParams, function(paramName){
						var v = '';
						if (routeParams.hasOwnProperty(paramName)){
							v = routeParams[paramName];
							if (angular.isObject(v) && v.id) {
								v = v.id;
							}
							// Do NOT set a parameter in the query string if it has already been used in the route.
							if (queryStringParams.hasOwnProperty(paramName)) {
								delete queryStringParams[paramName];
							}

						} else {
							console.error(paramName + ' can not be replaced in URL tpl ' + urlTpl);
						}
						urlTpl = urlTpl.replace(new RegExp(':'+paramName, 'g'), v);
					});

					urlTpl = urlTpl.replace(/\/+/g, '/');

					return Utils.makeUrl(urlTpl, queryStringParams);
				};


				var fixUrl = function (url) {
					// Remove starting slash
					if (url.charAt(0) === '/') {
						url = url.slice(1);
					}

					var search = $location.search(), params = {};

					if (search.hasOwnProperty('np')) {
						params['np'] = search['np'];
					}

					if (search.hasOwnProperty('nf')) {
						params['nf'] = search['nf'];
					}

					return Utils.makeUrl(url, params);
				};


				/**
				 * Returns the URL of the given Document or Model name.
				 *
				 * @description
				 * If a Document is provided, the parameters in the URL templates are replaced to return
				 * the full URL ready to be used.
				 * If a Model name is provided, the returned String is the URL template, with parameters,
				 * as defined in the Module's configuration.
				 * @returns {String} The URL of the provided element.
				 */
				var getNamedUrl = function (doc, name, params) {
					var url;

					if (!angular.isObject(params)) {
						params = {};
					}

					url = getUrl(doc, name || 'form');

					if (url === null) {
						return "javascript:;";
					}

					// If `doc` is a Document object, we try to replace the parameters in the `url` with
					// the corresponding properties of the Document.
					if (angular.isString(doc)) {
						url = replaceParams(url, params, params);
					} else {
						url = replaceParams(url, angular.extend({}, doc, params), params);
					}

					return fixUrl(url);
				};


				// Public API
				return {
					'applyConfig' : applyConfig,

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getListUrl
					 *
					 * @description Returns the URL of the view that lists the Documents.
					 *
					 * @param {Document|String} doc Document or Model name.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getListUrl' : function (doc, params) {
						return getNamedUrl(doc, 'list', params);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getSelectorUrl
					 *
					 * @description Returns the URL of the view that lists the Documents for a selection from a picker.
					 *
					 * @param {Document|String} doc Document or Model name.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getSelectorUrl' : function (doc, params) {
						var result = getNamedUrl(doc, 'selector', params);
						if (result != "javascript:;")
							return result;
						return getNamedUrl(doc, 'list', params);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getEditUrl
					 *
					 * @description Returns the URL of the view that displays the editor of the given `doc`.
					 *
					 * @param {Document} doc Document.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getEditUrl' : function (doc, params) {
						return getNamedUrl(doc, 'edit', params);
					},

					'getFormUrl' : function (doc, params) {
						var result = getNamedUrl(doc, 'edit', params);
						if (result != "javascript:;")
							return result;
						return getNamedUrl(doc, 'form', params);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getNewUrl
					 *
					 * @description Returns the URL of the view that displays the editor to create a new Document.
					 *
					 * @param {String} doc Document Model name.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getNewUrl' : function (doc, params) {
						return getNamedUrl(doc, 'new', params);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getTranslateUrl
					 *
					 * @description Returns the URL of the view that displays the editor to translate the given `doc`.
					 *
					 * @param {Document} doc Document.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getTranslateUrl' : function (doc, LCID) {
						return getNamedUrl(doc, 'translate', {'LCID': LCID || doc.LCID});
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:UrlManager
					 * @name RbsChange.service:UrlManager#getUrl
					 *
					 * @description Returns the URL of the view that displays the editor of the given `doc`.
					 *
					 * Same as {@link RbsChange.service:UrlManager#getEditUrl `getEditUrl`}.
					 *
					 * @param {Document} doc Document.
					 * @param {Object=} params Parameters to append in the URL.
					 */
					'getUrl' : function (doc, params, name) {
						//TODO fallback, delete this condition after form.twig refactoring
						if (!name || name == 'form') {
							var result = getNamedUrl(doc, 'edit', params);
							if (result != "javascript:;")
								return result;
						}
						return getNamedUrl(doc, name, params);
					}
				};
			}];
		}]);
	}]);

	// Filters

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsURL
	 * @function
	 *
	 * @description
	 * Returns the URL for the input Document, Model name or Plugin name.
	 *
	 * Routes are defined in the `Assets/Admin/routes.json` file in each Plugin. Commonly used route names are:
	 *
	 * - `new` (with input = Model name)
	 * - `edit` (with input = Document)
	 * - `list` (with input = Model name)
	 *
	 * @param {Document|String} input Document, Model name or Plugin name.
	 * @param {String=} routeName Route name (defaults to <em>edit</em>).
	 *
	 * @example
	 * <pre>
	 *   <a ng-href="(= 'Rbs_Catalog_Product' | rbsURL:'new' =)">Create new product</a>
	 *   <a ng-href="(= product | rbsURL =)">Edit (= product.label =)</a>
	 * </pre>
	 */
	var urlFilter = ['RbsChange.Breadcrumb', 'RbsChange.Utils', 'RbsChange.UrlManager', function (Breadcrumb, Utils, UrlManager)
	{
		return function (doc, routeName, params)
		{
			var	url, nodeId, qs;

			if (params === 'tree') {
				nodeId = Breadcrumb.getCurrentNodeId();
				if (nodeId) {
					params = { 'tn' : nodeId };
				} else {
					params = null;
				}
			}

			if (Utils.isDocument(doc)) {
				if (routeName === 'createFrom') {
					routeName = 'new';
					qs = 'from=' + doc.id;
				}
				if (! routeName && doc.refLCID && doc.LCID !== doc.refLCID) {
					routeName = 'translate';
				}
				url = UrlManager.getUrl(doc, params, routeName);
				if (qs) {
					url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
				}
			} else if (Utils.isModelName(doc) || Utils.isModuleName(doc)) {
				url = UrlManager.getUrl(doc, params || null, routeName);
			} else {
				return 'javascript:;';
			}
			return url;
		};
	}];

	app.filter('rbsURL', urlFilter);

	app.filter('rbsAdminTemplateURL', function () {
		return function (doc, tplName) {
			return doc && doc.model ? doc.model.replace(/_/g, '/') + '/' + tplName + '.twig' : '';
		};
	});
})();
