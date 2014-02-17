(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
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
								rule.ruleName =  name;

								//TODO Compatibility check
								rule.relatedModelName =  key;

								if (route.hasOwnProperty('options')) {
									rule.options = route.options;
								}
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

					'getListUrl' : function (doc, params) {
						return getNamedUrl(doc, 'list', params);
					},

					'getSelectorUrl' : function (doc, params) {
						var result = getNamedUrl(doc, 'selector', params);
						if (result != "javascript:;")
							return result;
						return getNamedUrl(doc, 'list', params);
					},

					'getFormUrl' : function (doc, params) {
						return getNamedUrl(doc, 'form', params);
					},

					'getNewUrl' : function (doc, params) {
						return getNamedUrl(doc, 'new', params);
					},

					'getTranslateUrl' : function (doc, LCID) {
						return getNamedUrl(doc, 'translate', {'LCID': LCID || doc.LCID});
					},

					'getUrl' : function (doc, params, name) {
						return getNamedUrl(doc, name, params);
					}
				};
			}];
		}]);
	}]);

	// Filters

	var urlFilter = ['RbsChange.Breadcrumb', 'RbsChange.Utils', 'RbsChange.UrlManager', function (Breadcrumb, Utils, UrlManager) {

		return function (doc, urlName, params) {
			var	url, nodeId;

			if (params === 'tree') {
				nodeId = Breadcrumb.getCurrentNodeId();
				if (nodeId) {
					params = { 'tn' : nodeId };
				} else {
					params = null;
				}
			}

			if (Utils.isDocument(doc)) {
				if (! urlName && doc.refLCID && doc.LCID !== doc.refLCID) {
					urlName = 'translate';
				}
				url = UrlManager.getUrl(doc, params, urlName);
			} else if (Utils.isModelName(doc) || Utils.isModuleName(doc)) {
				url = UrlManager.getUrl(doc, params || null, urlName);
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
