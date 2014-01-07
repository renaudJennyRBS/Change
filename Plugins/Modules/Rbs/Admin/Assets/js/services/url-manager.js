(function () {

	"use strict";

	var app = angular.module('RbsChange');


	app.config(['$provide', function ($provide) {
		$provide.provider('RbsChange.UrlManager', ['RbsChange.Utils', '$routeProvider', function (Utils, $routeProvider) {

			this.$get = function ($location) {
				var urls = {};

				var register = function (modelName, url) {
					if (angular.isString(url)) {
						url = { 'form': url };
					}
					urls[modelName] = angular.extend(urls[modelName] || {}, url);
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

				var currentModelName = null;
				var routeDefFn = function (name, route, rule) {
					var urls, p;

					if (route.charAt(0) !== '/') {
						route = '/' + route;
					}

					if (currentModelName) {
						urls = {};
						urls[name] = route;
						register(currentModelName, urls);
					}

					if (angular.isString(rule)) {
						rule = { templateUrl : rule };
					}

					if (! rule.hasOwnProperty('redirectTo')) {
						rule = angular.extend({}, defaultRule, rule);
					}
					if ((p = route.indexOf('?')) !== -1) {
						route = route.substring(0, p);
					}

					if (rule.templateUrl && rule.resolve) {
						rule.resolve.rbsPlugin = ['$rootScope', '$location', function ($rootScope, $location) {
							var tokens = $location.path().split('/');
							$rootScope.rbsCurrentPluginName = tokens[1] + '_' + tokens[2];
							return $rootScope.rbsCurrentPluginName;
						}];
					}

					$routeProvider.when(route, rule);
				};


				var getUrl = function (doc, name) {
					var	model, out;

					if (angular.isObject(doc) && angular.isDefined(doc.model)) {
						model = doc.model;
					} else if (angular.isString(doc)) {
						model = doc;
					} else {
						throw new Error("Could not determine the Model of the given parameter: " + doc + ". Please provide a Model name (String) or a Document object.");
					}

					if (urls.hasOwnProperty(model)) {
						out = urls[model];
						if (name && out.hasOwnProperty(name)) {
							return out[name];
						} else if (name === 'i18n' && out.hasOwnProperty('form')) {
							return out['form'];
						} else if (angular.isString(out)) {
							return out;
						}
						return null;
					}
					return null;
				};


				var replaceParams = function (urlTpl, routeParams, queryStringParams)
				{
					queryStringParams = angular.extend({}, queryStringParams);

					angular.forEach(routeParams, function (v, k)
					{
						if (angular.isObject(v) && v.id) {
							v = v.id;
						}
						// Is this a parameter in the route?
						if (urlTpl.indexOf(':' + k) !== -1) {
							urlTpl = urlTpl.replace(new RegExp(':'+k, 'g'), v);
							// Do NOT set a parameter in the query string if it has already been used in the route.
							if (queryStringParams.hasOwnProperty(k)) {
								delete queryStringParams[k];
							}
						}
					});

					urlTpl = urlTpl.replace(/\/+/g, '/');

					return Utils.makeUrl(urlTpl, queryStringParams);
				};


				var fixUrl = function (url) {
					// Remove starting slash
					if (url.charAt(0) === '/') {
						url = url.slice(1);
					}

					var search = $location.search(),
						params = {};
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
				 *
				 * @param {Object|String} The Document object or a Model name.
				 * Can be one of:
				 * - `String`: Model name such as 'Rbs_Website_Page'.
				 * - `Object`: Document object with the required own property 'model'.
				 *
				 * @param {Object} Optional parameters object that will take precedence over the Document's properties.
				 *
				 * @param {String} Name of the URL to get.
				 *
				 * @returns {String} The URL of the provided element.
				 */
				var getNamedUrl = function (doc, params, name) {
					var url;

					// Allows second parameter to be the name of the rule to use if there are no parameters.
					if (angular.isDefined(params) && ! angular.isObject(params) && angular.isUndefined(name)) {
						name = params;
						params = {};
					}

					if (! angular.isObject(params)) {
						params = {};
					}

					if (! params.LCID && doc.LCID) {
						params.LCID = doc.LCID;
					}

					if ((! name || name === 'form') && doc.refLCID && doc.refLCID !== params.LCID) {
						name = 'translate';
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
					'register'   : register,

					'route' : function (name, route, rule) {
						routeDefFn(name, route, rule);
						return this;
					},

					'model' : function (modelName) {
						currentModelName = modelName;
						return this;
					},

					'routesForModels' : function (modelNames) {
						var self = this;
						angular.forEach(modelNames, function (model) {
							var baseRouteTpl = model.replace(/_/g, '/');
							self.model(model)
								.route('list', baseRouteTpl + '/', 'Document/' + baseRouteTpl + '/list.twig')
								.route('form', baseRouteTpl + '/:id', 'Document/' + baseRouteTpl + '/form.twig')
								.route('new' , baseRouteTpl + '/new', 'Document/' + baseRouteTpl + '/form.twig')
								.route('workflow', baseRouteTpl + '/:id/workflow', { 'templateUrl': 'Rbs/Admin/workflow/workflow.twig?model='+model, 'controller': 'RbsChangeWorkflowController' })
								.route('timeline', baseRouteTpl + '/:id/timeline', { 'templateUrl': 'Rbs/Timeline/timeline.twig?model='+model, 'controller': 'RbsChangeTimelineController' })
								.route('urls', baseRouteTpl + '/:id/url', { 'templateUrl': 'Rbs/Seo/url-manager.twig' })
							;
						});
						return this;
					},

					'routesForLocalizedModels' : function (modelNames) {
						var self = this;
						angular.forEach(modelNames, function (model) {
							var baseRouteTpl = model.replace(/_/g, '/');
							self.model(model)
								.route('list', baseRouteTpl + '/', 'Document/' + baseRouteTpl + '/list.twig')
								.route('form', baseRouteTpl + '/:id/:LCID', 'Document/' + baseRouteTpl + '/form.twig')
								.route('new' , baseRouteTpl + '/new', 'Document/' + baseRouteTpl + '/form.twig')
								.route('translate', baseRouteTpl + '/:id/:LCID/translate', { 'templateUrl': 'Document/' + baseRouteTpl+'/form.twig', 'controller': 'RbsChangeTranslateEditorController' })
								.route('workflow', baseRouteTpl + '/:id/:LCID/workflow', { 'templateUrl': 'Rbs/Admin/workflow/workflow.twig?model='+model, 'controller': 'RbsChangeWorkflowController' })
								.route('timeline', baseRouteTpl + '/:id/:LCID/timeline', { 'templateUrl': 'Rbs/Timeline/timeline.twig?model='+model, 'controller': 'RbsChangeTimelineController' })
								.route('urls', baseRouteTpl + '/:id/:LCID/url', { 'templateUrl': 'Rbs/Seo/url-manager.twig' })
							;
						});
						return this;
					},

					'getListUrl' : function (doc, params) {
						return getNamedUrl(doc, params, 'list');
					},

					'getTreeUrl' : function (doc, params) {
						return getNamedUrl(doc, params, 'tree');
					},

					'getFormUrl' : function (doc, params) {
						return getNamedUrl(doc, params, 'form');
					},

					'getTranslateUrl' : function (doc, LCID) {
						return getNamedUrl(doc, { 'LCID': LCID || doc.LCID }, 'translate');
					},

					'getUrl'     : function (doc, params, name) {
						// Special case for 'form' routes that can have a section to go directly to the
						// corresponding section of the editor.
						if (name && name.substr(0, 5) === 'form.') {
							return getNamedUrl(doc, params, 'form') + '?section=' + name.substring(5);
						}
						return getNamedUrl(doc, params, name);
					}
				};
			};
		}]);

	}]);



	// Filters

	var urlFilter = ['RbsChange.Breadcrumb', 'RbsChange.Utils', 'RbsChange.UrlManager', function (Breadcrumb, Utils, UrlManager) {

		return function (doc, urlName, params) {
			var	url, node;

			if (params === 'tree') {
				node = Breadcrumb.getCurrentNode();
				if (node) {
					params = { 'tn' : node.id };
				} else {
					params = null;
				}
			}

			if (Utils.isDocument(doc)) {
				if (! urlName && doc.refLCID && doc.LCID !== doc.refLCID) {
					urlName = 'translate';
				}
				url = UrlManager.getUrl(doc, angular.extend({ 'LCID': doc.LCID }, params), urlName);
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
