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

	app.factory('RbsChange.DocumentCache', ['$cacheFactory', function($cacheFactory) {
		return $cacheFactory('RbsChange.DocumentCache', {capacity: 50});
	} ]);

	/**
	 * @ngdoc service
	 * @name RbsChange.service:REST
	 *
	 * @description Provides methods to deal with REST services:
	 *
	 * - load and save Documents
	 * - load Collections
	 * - call other REST services
	 */
	app.provider('RbsChange.REST', function RbsChangeRESTProvider ()
	{
		var forEach = angular.forEach,
			REST_BASE_URL,
			HTTP_STATUS_CREATED = 201,
			temporaryId;

		this.setBaseUrl = function (url) {
			REST_BASE_URL = url;
		};

		this.$get = [
			'$http', '$location', '$q', '$timeout', '$rootScope',
			'RbsChange.Utils',
			'RbsChange.ArrayUtils',
			'RbsChange.UrlManager',
			'localStorageService',
			'RbsChange.DocumentCache',

			function ($http, $location, $q, $timeout, $rootScope, Utils, ArrayUtils, UrlManager, localStorageService, DocumentCache)
			{
				var absoluteUrl,
				    language = 'fr_FR',
				    lastCreatedDocument = null,
				    REST;

				temporaryId = localStorageService.get("temporaryId");
				if (temporaryId === null) {
					temporaryId = 0;
				} else {
					temporaryId = parseInt(temporaryId, 10);
				}

				if ( ! REST_BASE_URL ) {
					absoluteUrl = $location.absUrl();
					absoluteUrl = absoluteUrl.replace(/admin\.php.*/, 'rest.php/');
					REST_BASE_URL = absoluteUrl;
				}


				function ChangeDocument () {
					this.META$ = {
						'links'      : {},
						'actions'    : {},
						'locales'    : [],
						'correction' : null,
						'treeNode'   : null,
						'tags'       : null
					};
				}

				ChangeDocument.prototype.meta = function (string) {
					var splat = string.split(/\./),
						obj, i;
					obj = this.META$;
					for (i=0 ; i<splat.length && obj ; i++) {
						obj = obj[splat[i]];
					}
					return obj;
				};

				ChangeDocument.prototype.is = function (modelName) {
					return Utils.isModel(this, modelName);
				};

				ChangeDocument.prototype.isNew = function () {
					return Utils.isNew(this);
				};

				ChangeDocument.prototype.url = function (name, params) {
					return UrlManager.getUrl(this, params || {}, name || 'form');
				};

				ChangeDocument.prototype.refUrl = function (name) {
					return UrlManager.getUrl(this, { LCID: this.refLCID }, name || 'form');
				};

				ChangeDocument.prototype.translateUrl = function (LCID) {
					return UrlManager.getTranslateUrl(this, LCID);
				};

				ChangeDocument.prototype.hasUrl = function (name) {
					return this.url(name) !== 'javascript:;';
				};

				ChangeDocument.prototype.nodeChildrenCount = function () {
					return this.META$.treeNode ? this.META$.treeNode.childrenCount : 0;
				};

				ChangeDocument.prototype.nodeHasChildren = function () {
					return this.nodeChildrenCount() > 0;
				};

				ChangeDocument.prototype.nodeIsEmpty = function () {
					return this.nodeChildrenCount() === 0;
				};

				ChangeDocument.prototype.isRefLang = function () {
					return this.refLCID === this.LCID;
				};

				ChangeDocument.prototype.isLocalized = function () {
					return angular.isDefined(this.refLCID);
				};

				ChangeDocument.prototype.isTranslatedIn = function (lcid) {
					if (! this.META$.locales) {
						return false;
					}
					var i, translated = false;
					for (i=0 ; i<this.META$.locales.length && ! translated ; i++) {
						translated = (this.META$.locales[i].id === lcid);
					}
					return translated;
				};

				ChangeDocument.prototype.hasCorrection = function () {
					return Utils.hasCorrection(this);
				};

				ChangeDocument.prototype.isActionAvailable = function (actionName) {
					return angular.isObject(this.META$.actions) && this.META$.actions.hasOwnProperty(actionName);
				};

				ChangeDocument.prototype.getActionUrl = function (actionName) {
					if (angular.isObject(this.META$.actions) && this.META$.actions.hasOwnProperty(actionName)) {
						return this.META$.actions[actionName].href;
					}
					return null;
				};

				ChangeDocument.prototype.getLink = function (rel) {
					if (! rel) {
						throw new Error("Argument 'rel' should not be empty.");
					}
					if (angular.isObject(this.META$.links) && this.META$.links.hasOwnProperty(rel)) {
						return this.META$.links[rel].href;
					}
					return null;
				};

				ChangeDocument.prototype.getTagsUrl = function () {
					return this.META$.links['self'] ? this.META$.links['self'].href + '/tags/' : null;
				};

				ChangeDocument.prototype.loadTags = function () {
					var q = $q.defer(),
						doc = this,
						p;

					if (! this.META$.tags) {
						this.META$.tags = [];

						if (doc.getTagsUrl() !== null) {
							p = $http.get(doc.getTagsUrl(), getHttpConfig(transformResponseCollectionFn));
							p.success(function (result) {
								doc.META$.tags.length = 0;
								angular.forEach(result.resources, function (r) {
									doc.META$.tags.push(r);
								});
								q.resolve(result.resources);
							});
						}
					}
					else {
						q.resolve(doc.META$.tags);
					}

					return q.promise;
				};

				ChangeDocument.prototype.getTags = function () {
					this.loadTags();
					return this.META$.tags;
				};



				/**
				 * Builds a 'Resource' object with meta information, such as locales and links.
				 * Each Resource has a 'META$' property that holds these information.
				 */
				function buildChangeDocument (data, baseDocument) {

					var chgDoc = baseDocument || new ChangeDocument(),
						properties;

					// TODO FB 2013-03-21: I think this can be optimized :)

					// Response format differs between the 'Collection', 'Document' and 'Tree' resources.

					// Search for the properties of the resource:
					if (angular.isDefined(data.properties)) {
						if (angular.isDefined(data.properties.nodeOrder) && angular.isDefined(data.properties.document)) {
							properties = data.properties.document;
							chgDoc.META$.treeNode = angular.copy(data.properties);
							chgDoc.META$.url = properties.link.href;
							delete chgDoc.META$.treeNode.document;
							forEach(data.links, function (link) {
								if (link.rel === 'self') {
									chgDoc.META$.treeNode.url = link.href;
								}
							});
						} else {
							properties = data.properties;
						}
					} else if (angular.isDefined(data.model)) {
						properties = data;
					} else if (angular.isDefined(data.nodeOrder) && angular.isDefined(data.document)) {
						properties = data.document;
						data.actions = data.document.actions;
						delete properties.actions;

						chgDoc.META$.treeNode = angular.copy(data);
						delete chgDoc.META$.treeNode.document;
						chgDoc.META$.treeNode.url = data.link.href;
					}

					// Parse the 'links' section:
					forEach(data.links, function (link) {
						chgDoc.META$.links[link.rel] = link;
						if (link.rel === 'self' && !chgDoc.META$.url) {
							chgDoc.META$.url = link.href;
						} else if (link.rel === 'node') {
							chgDoc.META$.treeNode = angular.extend(
								chgDoc.META$.treeNode || {},
								{
									'url': link.href
								}
							);
						} else if (link.rel === 'parent') {
							chgDoc.META$.treeNode = angular.extend(
								chgDoc.META$.treeNode || {},
								{
									'parentUrl': link.href
								}
							);
						} else if (link.rel === 'children') {
							chgDoc.META$.treeNode = angular.extend(
								chgDoc.META$.treeNode || {},
								{
									'childrenUrl': link.href
								}
							);
						}
					});

					if (angular.isObject(data.link)) {
						chgDoc.META$.links['self'] = data.link;
					}

					// Parse the 'actions' section:
					forEach(data.actions, function (action) {
						chgDoc.META$.actions[action.rel] = action;
						if (action.rel === 'correction') {
							chgDoc.META$.correction = {};
						}
					});

					// Parse the 'i18n' sections:
					if (data.i18n) {
						chgDoc.META$.links.i18n = data.i18n;
						forEach(data.i18n, function (url, lcid) {
							chgDoc.META$.locales.push({
								'id': lcid,
								'label': lcid,
								'isReference': data.properties.refLCID === lcid
							});
						});
					}

					// Transform sub-documents into ChangeDocument instances.
					angular.forEach(properties, function (value, name) {
						if (Utils.isDocument(value)) {
							properties[name] = buildChangeDocument(value);
						} else if (angular.isArray(value)) {
							angular.forEach(value, function (v, i) {
								if (Utils.isDocument(value[i])) {
									value[i] = buildChangeDocument(value[i]);
								}
							});
						}
					});

					angular.extend(chgDoc, properties);
					return chgDoc;
				}


				function transformResponseCollectionFn (response) {
					var data = null;
					try {
						data = JSON.parse(response);
						if (angular.isDefined(data.resources)) {
							forEach(data.resources, function (rsc, key) {
								data.resources[key] = buildChangeDocument(rsc);
							});
						}
					} catch (e) {
						data = {
							"error"   : true,
							"code"    : "InvalidResponse",
							"message" : "Got error when parsing response: " + response
						};
					}
					return data;
				}


				function transformResponseResourceFn (response) {
					var data = null;
					try {
						data = JSON.parse(response);
						if (angular.isDefined(data.properties)) {
							data = buildChangeDocument(data);
						}
					} catch (e) {
						data = {
							"error"   : true,
							"code"    : "InvalidResponse",
							"message" : "Got error when parsing response: " + response
						};
					}
					return data;
				}


				/**
				 * Returns the HTTP Config that should be used for every REST call.
				 * Special headers, such as Accept-Language, and authentication stuff go here :)
				 *
				 * @returns {{headers: {Accept-Language: string}}}
				 */
				function getHttpConfig (transformResponseFn) {
					var config = {
						'headers': {
							'Accept-Language': angular.lowercase(language).replace('_', '-')
						}
					};

					if (angular.isFunction(transformResponseFn)) {
						config.transformResponse = transformResponseFn;
					}

					return config;
				}


				/**
				 * Returns the HTTP Config that should be used for every REST call.
				 * Special headers, such as Accept-Language, and authentication stuff go here :)
				 *
				 * @returns {{headers: {Accept-Language: string}}}
				 */
				function getHttpConfigWithCache (transformResponseFn) {
					var config = getHttpConfig(transformResponseFn);
					config.cache = true;
					return config;
				}


				/**
				 * Resolves the given `q` with the given `data`.
				 * This function ensures that the Q is resolved within the Angular life-cycle, without the need to attach
				 * the promise to a Scope.
				 *
				 * @param q
				 * @param data
				 */
				function resolveQ (q, data) {
					if (data === null || (data.code && data.message && !data.id)) {
						q.reject(data);
					} else {
						q.resolve(data);
					}
				}


				/**
				 * Rejects the given `q` with the given `reason`.
				 * This function ensures that the Q is rejected within the Angular life-cycle, without the need to attach
				 * the promise to a Scope.
				 *
				 * @param q
				 * @param reason
				 */
				function rejectQ (q, reason) {
					q.reject(reason);
				}


				function digest () {
					// In some (rare) cases, Angular does not fire the AJAX request above. This is really
					// strange, and I have to say that I don't know why.
					// Calling a digest cycle on the $rootScope solves the problem...
					if (!$rootScope.$$phase) {
						$rootScope.$apply();
					}
				}


				function _ToSlash (string) {
					return string.replace(/_/g, '/');
				}

				function _toDocumentRef(model, id, lcid) {
					if (/[0-9]+/.test(model)) {
						id = model;
						model = undefined;
						lcid = undefined;
					} else {
						if (Utils.isDocument(model)) {
							id = model.id;
							lcid = model.LCID;
							model = model.model;
						}
					}
					return {id: id, lcid: lcid, model: model};
				}

				$rootScope.$on('$routeChangeStart', function() {
					DocumentCache.removeAll();
				});


				// Public API of the REST service.

				REST = {
					'getHttpConfig' : function (transformer) {
						return getHttpConfig(transformer);
					},

					'transformObjectToChangeDocument' : function (object) {
						return angular.extend(new ChangeDocument(), object);
					},

					'collectionTransformer' : function () {
						return transformResponseCollectionFn;
					},

					'resourceTransformer' : function () {
						return transformResponseResourceFn;
					},

					'isLastCreated' : function (doc) {
						return lastCreatedDocument && doc && (doc.id === lastCreatedDocument.id && doc.model === lastCreatedDocument.model);
					},


					'getLastCreated' : function () {
						return lastCreatedDocument;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#getBaseUrl
					 *
					 * @description
					 * Returns the full URL of an action based on its `relativePath`, suitable to use with `$http`.
					 *
					 * @param {String} relativePath Relative path.
					 * @returns {String} Full path to use with `$http`.
					 */
					'getBaseUrl' : function (relativePath) {
						return REST_BASE_URL + relativePath;
					},

					/**
					 * @param lang
					 */
					'setLanguage' : function (lang) {
						language = lang;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#getAvailableLanguages
					 *
					 * @description
					 * Returns the list of all available languages.
					 *
					 * @returns {Promise} Promise resolved when the list of languages is loaded.
					 */
					'getAvailableLanguages' : function () {
						return this.action(
							'collectionItems',
							{'code' : 'Rbs_Generic_Collection_Languages'},
							true // cache
						);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#getResourceUrl
					 *
					 * @description
					 * Returns the URL of the resource identified by its `model`, `id` and optional `lcid`.
					 *
					 * @param {String} model The Document Model name.
					 * @param {Integer} id The Document's ID.
					 * @param {String=} lcid The Document's locale ID.
					 *
					 * @return {String} The resource's URL.
					 */
					'getResourceUrl' : function (model, id, lcid) {
						var url;

						if (/[0-9]+/.test(model)) {
							url = REST_BASE_URL + 'resources/' + model;
						} else {
							if (Utils.isDocument(model)) {
								id    = model.id;
								lcid  = model.LCID;
								model = model.model;
							}

							// Resulting URL will end with a slash.
							url = this.getCollectionUrl(model, null);

							if (id) {
								url += id;
								if (lcid) {
									url += '/' + lcid;
								}
							}
						}

						return url;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#getCollectionUrl
					 *
					 * @description
					 * Returns the URL of the collection for the given `model` and optional `params`.
					 *
					 * @param {String} model Model name.
					 * @param {Object} params Parameters (limit, offset, sort, ...)
					 *
					 * @return {String} The collection's URL.
					 */
					'getCollectionUrl' : function (model, params) {
						model = Utils.modelInfo(model);
						return Utils.makeUrl(
							REST_BASE_URL + 'resources/' + model.vendor + '/' + model.module + '/' + model.document + '/',
							params
						);
					},


					/**
					 * Returns unique ID for newly created resources.
					 * These IDs are negative integers.
					 */
					'getTemporaryId' : function () {
						temporaryId--;
						localStorageService.add("temporaryId", temporaryId);
						return temporaryId;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#newResource
					 *
					 * @description
					 * Creates a new, unsaved resource of the given `model` in the given locale (`lcid`).
					 *
					 * @param {string} model The Document Model name.
					 * @param {string=} lcid Locale ID (5 chars).
					 *
					 * @return {Document} The new unsaved Document.
					 */
					'newResource' : function (model, lcid) {
						var props = {
							'id'    : REST.getTemporaryId(),
							'model' : model,
							'publicationStatus' : 'DRAFT'
						};
						if (Utils.isValidLCID(lcid)) {
							props.refLCID = lcid;
							props.LCID = lcid;
						}

						return buildChangeDocument({
							'properties' : props
						});
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#resource
					 *
					 * @description
					 * Loads the Resource identified by its `model`, `id` and optional `lcid`.
					 *
					 * @param {String|Document} model Document Model name, or Document object.
					 * @param {Integer=} id Resource's ID.
					 * @param {String=} lcid (Optional) Locale ID.
					 *
					 * @return {Object} Promise that will be resolved with the Document when the Resource is loaded.
					 */
					'resource' : function (model, id, lcid) {
						var q = $q.defer(), self = this, httpConfig = getHttpConfig(transformResponseResourceFn);
						httpConfig.cache = DocumentCache;

						$http.get(this.getResourceUrl(model, id, lcid), httpConfig)
							.success(function restResourceSuccessCallback (data) {
								if (Utils.hasCorrection(data)) {
									self.loadCorrection(data).then(function (doc) {
										doc.META$.loaded = true;
										resolveQ(q, doc);
									});
								} else {
									data.META$.loaded = true;
									resolveQ(q, data);
								}
							})
							.error(function restResourceErrorCallback (data, status) {
								if (status === 303 && Utils.isDocument(data)) {
									resolveQ(q, data);
								} else {
									if (data) {
										data.httpStatus = status;
									}
									rejectQ(q, data);
								}
							});

						digest();
						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#resources
					 *
					 * @description
					 * Loads the Resources identified by the given `ids`.
					 *
					 * @param {Array} ids Array of Document IDs.
					 *
					 * @return {Object} Promise that will be resolved with the Documents when the Resources are loaded.
					 */
					'resources' : function (ids) {
						var q = $q.defer(),
							url = Utils.makeUrl(this.getBaseUrl('admin/documentList'), {'ids' : ids});

						$http.get(
								url,
								getHttpConfig(transformResponseCollectionFn)
							).success(function (data) {
								resolveQ(q, data);
							})
							.error(function (data) {
								rejectQ(q, data);
							});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#getResources
					 *
					 * @description
					 * Returns an Array with empty Document objects, and loads the Resources identified by the given `ids`.
					 * When the Resources are loaded, the returned Array is populated with the loaded Documents.
					 *
					 * @param {Array} ids Array of Document IDs.
					 *
					 * @return {Array} Array of empty Document objects, populated when the Documents are loaded.
					 */
					'getResources' : function (ids)
					{
						var docs = [], i;
						for (i=0 ; i<ids.length ; i++) {
							docs.push({id: ids[i], model: ''});
						}
						this.resources(ids).then(function (collection) {
							var i;
							for (i=0 ; i<collection.resources.length ; i++) {
								angular.extend(docs[i], collection.resources[i]);
							}
							if (collection.resources.length < docs.length) {
								docs.splice(collection.resources.length);
							}
						});
						digest();

						return docs;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#ensureLoaded
					 *
					 * @description
					 * Ensures that the given Document has been fully loaded.
					 *
					 * @param {String|Document} model Document Model name, or Document object.
					 * @param {Integer=} id Resource's ID.
					 * @param {String=} lcid (Optional) Locale ID.
					 *
					 * @return {Object} Promise that will be resolved with the Document when the Resource is loaded.
					 */
					'ensureLoaded' : function (model, id, lcid) {
						if (this.isFullyLoaded(model)) {
							var q = $q.defer();
							resolveQ(q, model);
							return q.promise;
						}
						return this.resource(model, id, lcid);
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#isFullyLoaded
					 *
					 * @description
					 * Tells whether the given `doc` has already been fully loaded or not.
					 *
					 * @param {Document} doc Document object.
					 * @returns {Boolean} True if `doc` has been fully loaded.
					 */
					'isFullyLoaded' : function (doc) {
						return Utils.isDocument(doc) && doc.META$.loaded === true;
					},


					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#collection
					 *
					 * @description
					 * Loads a collection via a 'GET' REST call.
					 *
					 * @param {String} model Model name or URL of a RESTful service that returns a Collection.
					 * @param {Object} params Parameters (limit, offset, sort, ...)
					 *
					 * @returns {Promise} Promise that will be resolved when the collection is loaded.
					 * The Promise is resolved with the whole response as argument.
					 */
					'collection' : function (model, params) {
						var q = $q.defer(), url;
						if (angular.isObject(params) && angular.isObject(params.filter))
						{
							if (Utils.isModelName(model)) {
								url = this.getCollectionUrl(model, {});
							} else {
								if (model.charAt(0) === '/') {
									url = Utils.makeUrl(REST_BASE_URL + model.substr(1), {});
								} else {
									url = Utils.makeUrl(model, {});
								}
							}
							$http.post(
									url + 'filtered/', params,
									getHttpConfig(transformResponseCollectionFn)
								).success(function (data) {
									resolveQ(q, data);
								})
								.error(function (data) {
									rejectQ(q, data);
								});
						}
						else
						{
							if (Utils.isModelName(model)) {
								url = this.getCollectionUrl(model, params);
							} else {
								if (model.charAt(0) === '/') {
									url = Utils.makeUrl(REST_BASE_URL + model.substr(1), params);
								} else {
									url = Utils.makeUrl(model, params);
								}
							}
							$http.get(
									url,
									getHttpConfig(transformResponseCollectionFn)
								).success(function (data) {
									resolveQ(q, data);
								})
								.error(function (data) {
									rejectQ(q, data);
								});
						}
						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#loadCorrection
					 *
					 * @description
					 * Loads the Correction for the given `resource` (Document).
					 *
					 * @param {Document} resource The Document.
					 *
					 * @returns {Promise} Promise that will be resolved when the Correction has been applied on the given `resource`.
					 */
					'loadCorrection' : function (resource) {
						var q = $q.defer();
						if (Utils.hasCorrection(resource)) {
							$http.get(resource.META$.actions['correction'].href, getHttpConfig())
								.success(function restResourceSuccessCallback (data) {
									Utils.applyCorrection(resource, data);
									resolveQ(q, resource);
								})
								.error(function (data) {
									rejectQ(q, data);
								});
						}
						else {
							rejectQ(q, 'No correction available on the given Document');
						}
						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#save
					 *
					 * @description
					 * Saves the given `resource` via a 'POST' (creation) or 'PUT' (update) REST call.
					 *
					 * @param {Document} resource The Document to be saved.
					 * @param {Document=} currentTreeNode Current Document that should be used as the parent in the tree.
					 * @param {Array=} propertiesList Array of properties to save.
					 *
					 * @return {Object} Promise that will be resolved when the Document is successfully saved.
					 * Promise is resolved with the saved Document as argument.
					 */
					'save' : function (resource, currentTreeNode, propertiesList) {
						var mainQ = $q.defer(),
							url,
							method,
							REST = this;

						// mainQ is the Promise that will be resolved when all the "actions" (correction, tree, ...)
						// have been called successfully.

						// Make a copy of the resource object and remove unwanted properties (META$).
						resource = angular.copy(resource);

						if (Utils.isNew(resource)) {
							// If resource is new (see isNew()), we must POST on the Collection's URL.
							method = 'post';
							// Remove temporary ID
							delete resource.id;
							url = this.getCollectionUrl(resource.model);
						} else {
							DocumentCache.removeAll();
							// If resource is NOT new (already been saved), we must PUT on the Resource's URL.
							method = 'put';
							url = this.getResourceUrl(resource);
							// Save only the properties listed here + the properties of the Correction (if any).
							if (angular.isArray(propertiesList)) {
								if (Utils.hasCorrection(resource)) {
									angular.forEach(resource.META$.correction.propertiesNames, function (propName) {
										if (propertiesList.indexOf(propName) === -1) {
											propertiesList.push(propName);
										}
									});
								}
								var toSave = {};
								angular.forEach(propertiesList, function (prop) {
									if (resource.hasOwnProperty(prop)) {
										toSave[prop] = resource[prop];
									}
								});
								resource = toSave;
							}
						}

						delete resource.META$;

						// For child-documents, only send the ID.
						angular.forEach(resource, function (value, name) {
							if (Utils.isDocument(value)) {
								resource[name] = value.id;
							}
						});

						// REST call:
						$http[method](url, resource, getHttpConfig(transformResponseResourceFn))

							// Save SUCCESS:
							// 1) a ChangeDocument instance is created via the response interceptor,
							// 2) load its Correction (if any),
							// 3) insert resource in tree (if needed).
							.success(function successCallback (doc, status)
							{
								// 1) "doc" is a ChangeDocument instance.

								function maybeInsertResourceInTree (resource, qToResolve)
								{
									if (status === HTTP_STATUS_CREATED) {
										lastCreatedDocument = resource;
									}

									if ( ! Utils.isTreeNode(resource) && (status === HTTP_STATUS_CREATED || resource.treeName === null) && currentTreeNode)
									{
										// Load model's information to check if the document should be inserted in a tree.
										REST.modelInfo(resource).then(

											// modelInfo success
											function (modelInfo) {
												if (!modelInfo.metas || !modelInfo.metas.treeName) {
													resolveQ(qToResolve, resource);
												} else {
													$http.post(currentTreeNode.META$.treeNode.url + '/', { "id" : doc.id }, getHttpConfig())
														.success(function (nodeData)
														{
															doc = buildChangeDocument(doc, resource);
															if (status === HTTP_STATUS_CREATED) {
																lastCreatedDocument = doc;
															}
															resolveQ(qToResolve, doc);
														})
														.error(function errorCallback (data, status)
														{
															data.httpStatus = status;
															rejectQ(qToResolve, data);
														});
												}
											},

											// modelInfo error
											function (data) {
												rejectQ(qToResolve, data);
											}
										);
									} else {
										resolveQ(qToResolve, resource);
									}
								}

								// 2) load its Correction (if any)
								// After being saved, a Document may have a Correction attached to it, especially
								// if it was PUBLISHED on the website.
								if (Utils.hasCorrection(doc)) {
									REST.loadCorrection(doc).then(function (doc) {
										maybeInsertResourceInTree(doc, mainQ);
									});
								}
								else {
									// 3) insert resource in tree (if needed)
									maybeInsertResourceInTree(doc, mainQ);
								}

							})

							// Save ERROR: reject main promise (mainQ).
							.error(function errorCallback (data, status) {
								data.httpStatus = status;
								rejectQ(mainQ, data);
							});

						return mainQ.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#delete
					 *
					 * @description
					 * Deletes the given `resource` via a <em>DELETE</em> REST call.
					 *
					 * @param {Document} resource The Document to be deleted.
					 *
					 * @return {Promise} Promise that will be resolved when the Document is successfully deleted.
					 * Promise is resolved with the deleted Resource as argument.
					 */
					'delete' : function (resource) {
						var q = $q.defer();

						$http['delete'](this.getResourceUrl(resource.model, resource.id, null), getHttpConfig())
							.success(function successCallback (data) {
								// When deleting a resource, the response's body is empty with a 204.
								// So don't expect anything in the Promise's argument...
								resolveQ(q, data);
							})
							.error(function errorCallback (data, status) {
								data.httpStatus = status;
								rejectQ(q, data);
							});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#executeTaskByCodeOnDocument
					 *
					 * @description
					 * Execute a Task identified by its code on the given Document, with optional parameters.
					 *
					 * @param {String} taskCode The Task's code.
					 * @param {Document} doc The Document.
					 * @param {Object=} params Parameters.
					 *
					 * @returns {Promise} Promise resolved when the Task has been completed successfully.
					 */
					'executeTaskByCodeOnDocument' : function (taskCode, doc, params) {
						var q = $q.defer(),
							rest = this;

						if (! Utils.isDocument(doc)) {
							throw new Error("Parameter 'resource' should be a valid Document.");
						}

						if (! doc.META$.actions || ! doc.META$.actions.hasOwnProperty(taskCode)) {
							q.reject("Action '" + taskCode + "' is not available for Document '" + doc.id + "'.");
						}
						else {
							DocumentCache.removeAll();
							// Load the Task Document
							// TODO Optimize:
							// Can we call 'execute' directly with '/execute' at the end of the Task URL?
							$http.get(doc.META$.actions[taskCode].href, getHttpConfig(transformResponseResourceFn))

								.success(function (task) {

									// Execute Task.
									rest.executeTask(task, params).then(
										// Success
										function (task) {
											// Task has been executed and we don't need it here anymore.
											rest.resource(doc).then(function (updatedDoc) {
												resolveQ(q, updatedDoc);
											});
										},
										// Error
										function (data) {
											rejectQ(q, data);
										});
								})

								.error(function (data) {
									rejectQ(q, data);
								}
							);
						}

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#executeTask
					 *
					 * @description
					 * Execute a Task with optional parameters.
					 *
					 * @param {Document} task The Task Document.
					 * @param {Object=} params Parameters.
					 *
					 * @returns {Promise} Promise resolved when the Task has been completed successfully.
					 */
					'executeTask' : function (task, params) {
						var q = $q.defer();

						function doExecute (taskObj) {
							if (taskObj.META$.actions['execute']) {
								$http.post(taskObj.META$.actions['execute'].href, params, getHttpConfig(transformResponseResourceFn))
									.success(function (taskObj) {
										angular.extend(task, taskObj);
										resolveQ(q, taskObj);
									})
									.error(function (data) {
										rejectQ(q, data);
									});
							}
							else {
								rejectQ(q, 'Could not execute task ' + taskObj.id);
							}
						}

						if (task.META$.actions['execute']) {
							doExecute(task);
						}
						else {
							q = $q.defer();
							this.resource(task).then(
								// Success
								doExecute,
								// Error
								function (data) {
									rejectQ(q, data);
								}
							);
						}

						return q.promise;
					},

					 /**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#treeUrl
					 *
					 * @description
					 * Returns the URL of a tree from its `treeName`.
					 *
					 * @param {String} treeName The Tree name.
					 *
					 * @returns {String} REST URL to load the resources of tree `treeName`.
					 */
					'treeUrl' : function (treeName) {
						return REST_BASE_URL + 'resourcestree/' + _ToSlash(treeName) + '/';
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#treeChildren
					 *
					 * @description
					 * Loads the tree children of the given `resource`.
					 *
					 * @param {Document} resource Parent Document in a tree.
					 * @param {Object=} params Parameters.
					 *
					 * @returns {Promise} Promise resolved with the children when they are loaded.
					 */
					'treeChildren' : function (resource, params) {
						var q = $q.defer(),
							url;

						if (angular.isString(resource)) {
							url = this.treeUrl(resource);
						} else if (angular.isObject(resource) && resource.META$ && resource.META$.treeNode && resource.META$.treeNode.url) {
							url = resource.META$.treeNode.url + '/';
						}

						if (url) {
							url = Utils.makeUrl(url, params);
							$http.get(url, getHttpConfig(transformResponseCollectionFn))
								.success(function restCollectionSuccessCallback (data) {
									resolveQ(q, data);
								})
								.error(function restCollectionErrorCallback (data) {
									rejectQ(q, data);
								});
						} else {
							resolveQ(q, null);
						}

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#treeNode
					 *
					 * @description
					 * Loads the TreeNode object of the given `resource`.
					 *
					 * @param {Document} resource The Document.
					 *
					 * @returns {Promise} Promise resolved when the TreeNode is loaded.
					 */
					'treeNode' : function (resource) {
						var q = $q.defer(),
							url;

						if (angular.isString(resource)) {
							url = resource;
						} else if (angular.isObject(resource) && resource.META$ && resource.META$.treeNode && resource.META$.treeNode.url) {
							url = resource.META$.treeNode.url;// + '/';
						} else {
							throw new Error("'resource' parameter should be a TreeNode URL or a ChangeDocument.");
						}

						$http.get(url, getHttpConfig(transformResponseResourceFn))
							.success(function restTreeNodeSuccessCallback (data) {
								resolveQ(q, data);
							})
							.error(function restTreeNodeErrorCallback (data) {
								rejectQ(q, data);
							});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#treeAncestors
					 *
					 * @description
					 * Loads the tree ancestors of the given `resource`.
					 *
					 * @param {Document} resource Document in a tree.
					 * @param {Object=} params Parameters.
					 *
					 * @returns {Promise} Promise resolved with the ancestors when they are loaded.
					 */
					'treeAncestors' : function (resource) {
						var	q = $q.defer(),
							url;

						if (Utils.isDocument(resource)) {
							url = resource.META$.treeNode.url + "/ancestors/";
						} else {
							throw new Error("REST.treeAncestors() parameter should be a TreeNode URL or a ChangeDocument object.");
						}

						$http.get(
								url,
								getHttpConfig(transformResponseCollectionFn)
							).success(function (data) {
								resolveQ(q, data);
							})
							.error(function (data) {
								rejectQ(q, data);
							});

						q.promise.then(function (ancestors) {
							// Ship Root folder that is useless in the Backoffice UI.
							if (ancestors.resources.length >= 1 && ancestors.resources[0].model === "Rbs_Generic_Folder") {
								ancestors.resources.shift();
							}
							ancestors.resources.push(resource);
						});

						return q.promise;
					},


					/**
					 * Recursively load blocks for all vendors and modules.
					 *
					 * @returns Promise
					 */
					'blocks' : function () {
						var	q = $q.defer(),
							blocks = {},
							promises = [];

						$http.get(REST_BASE_URL + 'blocks/', getHttpConfigWithCache()).success(function (dataVendors) {
							forEach(dataVendors.links, function (link) {
								if (link.rel !== 'self') {
									promises.push($http.get(link.href, getHttpConfigWithCache()));
								}
							});
							$q.all(promises).then(function (vendors) {
								promises = [];

								forEach(vendors, function (vendor) {

									forEach(vendor.data.links, function (link) {
										if (link.rel !== 'self') {
											promises.push($http.get(link.href, getHttpConfigWithCache()));
										}
									});

									$q.all(promises).then(function (modules) {

										forEach(modules, function (module) {
											forEach(module.data.resources, function (block) {
												blocks[block.name] = block;
											});
										});

										resolveQ(q, blocks);
									});

								});

							});
						});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#blockInfo
					 *
					 * @description
					 * Returns information about a Block from its `blockName`.
					 *
					 * @param {String} blockName The block's full name.
					 *
					 * @returns {Promise} Promise resolved with the block's information.
					 */
					'blockInfo' : function (blockName) {
						var	q = $q.defer();

						// TODO Use UI language
						$http.get(REST_BASE_URL + 'blocks/' + _ToSlash(blockName), getHttpConfigWithCache()).success(function (block) {
							resolveQ(q, block.properties);
						});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#modelInfo
					 *
					 * @description
					 * Returns information about a Document Model from its `modelName`.
					 *
					 * @param {String} modelName The Document Model full name.
					 *
					 * @returns {Promise} Promise resolved with the Model's information.
					 */
					'modelInfo' : function (modelName) {
						var	q = $q.defer();

						if (Utils.isDocument(modelName)) {
							modelName = modelName.model;
						}

						// TODO Use UI language
						$http.get(REST_BASE_URL + 'models/' + _ToSlash(modelName), getHttpConfigWithCache()).success(function (model) {
							resolveQ(q, model);
						});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#query
					 *
					 * @description
					 * Sends a query to search for documents. Query is sent via a POST call.
					 *
					 * Query objects can be built with the {@link RbsChange.service:Query Query service}.
					 *
					 * @param {Object} queryObject The Query object.
					 * @param {Object=} params Parameters.
					 *
					 * @returns {Promise} Promise resolved with a collection of documents that match the filters.
					 */
					'query' : function (queryObject, params) {
						var	q = $q.defer();

						$http.post(
							Utils.makeUrl(REST_BASE_URL + 'query/', params),
							queryObject,
							getHttpConfig(transformResponseCollectionFn)
						).success(function (data) {
							resolveQ(q, data);
						})
						.error(function (data) {
							rejectQ(q, data);
						});

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#action
					 *
					 * @description
					 * Calls the action `actionName` with the given `params` (HTTP GET).
					 *
					 * Use {@link RbsChange.service:REST#postAction `postAction()`} to send the action with an HTTP POST.
					 *
					 * @param {String} actionName The action's name.
					 * @param {Object=} params Parameters.
					 * @param {Boolean=} cache If true, the result is cached by AngularJS.
					 *
					 * @returns {Promise} Promise resolved with the action's result.
					 */
					'action' : function (actionName, params, cache) {
						var	q = $q.defer(),
							url;

						url = Utils.makeUrl(REST_BASE_URL + 'actions/' + actionName + '/', params);

						$http.get(url, cache === true ? getHttpConfigWithCache() : getHttpConfig())
							.success(function restActionSuccessCallback (data) {
								resolveQ(q, data);
							})
							.error(function restActionErrorCallback (data, status) {
								data.httpStatus = status;
								rejectQ(q, data);
							});

						digest();

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#postAction
					 *
					 * @description
					 * Calls the action `actionName` with the given `params` (HTTP POST).
					 *
					 * Same as {@link RbsChange.service:REST#action `action()`}, but with an HTTP POST.
					 *
					 * @param {String} actionName The action's name.
					 * @param {Object=} params Parameters.
					 * @param {Boolean=} cache If true, the result is cached by AngularJS.
					 *
					 * @returns {Promise} Promise resolved with the action's result.
					 */
					'postAction' : function (actionName, content, params) {
						var	q = $q.defer(),
							url;

						url = Utils.makeUrl(REST_BASE_URL + 'actions/' + actionName + '/', params);

						$http.post(url, content, getHttpConfig())
							.success(function restActionSuccessCallback (data) {
								resolveQ(q, data);
							})
							.error(function restActionErrorCallback (data, status) {
								data.httpStatus = status;
								rejectQ(q, data);
							});

						digest();

						return q.promise;
					},

					/**
					 * @ngdoc function
					 * @methodOf RbsChange.service:REST
					 * @name RbsChange.service:REST#call
					 *
					 * @description
					 * Calls a REST service with its full URL.
					 *
					 * @param {String} url Full URL
					 * @param {Object=} params Parameters.
					 * @param {Function=} transformer Response Transformer to use.
					 *
					 * @returns {Promise} Promise resolved with the action's result.
					 */
					'call' : function (url, params, transformer) {
						var	q = $q.defer();

						$http.get(Utils.makeUrl(url, params), getHttpConfig(transformer))
							.success(function restActionSuccessCallback (data) {
								resolveQ(q, data);
							})
							.error(function restActionErrorCallback (data, status) {
								if (!data)
								{
									data = {};
								}
								data.status = status;
								rejectQ(q, data);
							});

						digest();

						return q.promise;
					},

					//
					// Storage
					//

					/**
					 * @ngdoc service
					 * @id RbsChange.service:REST.storage
					 * @name REST[storage]
					 *
					 * @description Sub-service of {@link RbsChange.service:REST REST} that provides methods to deal
					 * with file uploads.
					 *
					 * Inject {@link RbsChange.service:REST REST service} to use it:
					 * <code>REST.storage.method(...)</code>
					 */
					'storage' : {

						/**
						 * @ngdoc function
						 * @methodOf RbsChange.service:REST.storage
						 * @name RbsChange.service:REST.storage#upload
						 *
						 * @description
						 * Uploads a file on the server.
						 *
						 * @param {DOMElement} fileElm The input[file] element.
						 * @param {String} storageName Name of the storage configuration on the server.
						 *
						 * @returns {Promise} Promise resolved when the file has been uploaded.
						 */
						'upload' : function (fileElm, storageName) {
							var	q = $q.defer(),
								formData = new FormData();

							fileElm = fileElm.get(0);
							if (!fileElm.files) {
								window.alert('Browser not compatible');
								rejectQ(q, 'Browser not compatible');
								digest();
							}

							// Using the HTML5's File API:
							// https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/FormData
							if (fileElm.files.length > 0) {
								if (!storageName) {
									storageName = 'tmp';
								}
								formData.append('file', fileElm.files[0]);

								window.jQuery.ajax({
									"url"         : REST_BASE_URL + 'storage/'+ storageName + '/',
									"type"        : "POST",
									"method"      : "POST",
									"data"        : formData,
									"processData" : false,  // tell jQuery not to process the data,
									"contentType" : false,  // tell jQuery not to change the ContentType,

									"success" : function (data) {
										resolveQ(q, data);
										digest();
									},

									"error"   : function (jqXHR, textStatus, errorThrown) {
										var error;
										try {
											error = JSON.parse(jqXHR.responseText);
										} catch (e) {
											error = {
												"code"    : errorThrown || 'UPLOAD-ERROR',
												"message" : "Could not upload file: " + jqXHR.responseText
											};
											if (jqXHR.responseText) {
												error.message = "Could not upload file: " + jqXHR.responseText;
											} else {
												error.message = "Could not upload file.";
											}
										}
										rejectQ(q, error);
										digest();
									}

								});
							} else {
								window.alert('Please select a file');
								rejectQ(q, 'No file');
								digest();
							}

							return q.promise;
						},

						/**
						 * @ngdoc function
						 * @methodOf RbsChange.service:REST.storage
						 * @name RbsChange.service:REST.storage#displayUrl
						 *
						 * @description
						 * Returns the URL to display the given storage element.
						 *
						 * @param {Object} storage Storage object.
						 *
						 * @returns {String} string Display URL.
						 */
						'displayUrl' : function (storage) {
							if (angular.isObject(storage) && angular.isArray(storage.links))
							{
								var links = storage.links, link, i;
								for (i = 0; i < links.length; i++)
								{
									link = links[i];
									if (link.rel === 'data')
									{
										return link.href;
									}
								}
							}
							else if (angular.isString(storage))
							{
								return storage;
							}
							else if (!storage)
							{
								return null;
							}
							throw new Error("'storage' should be an object with links/data.");
						},

						/**
						 * @ngdoc function
						 * @methodOf RbsChange.service:REST.storage
						 * @name RbsChange.service:REST.storage#info
						 *
						 * @description
						 * Returns information about the given storage element.
						 *
						 * @param {String} storagePath Storage Path.
						 *
						 * @returns {Promise} Promise resolved with storage element's information.
						 */
						'info' : function (storagePath) {
							var q = $q.defer();

							if (Utils.startsWith(storagePath, "change://")) {
								$http.get(REST_BASE_URL + 'storage/' + storagePath.substr(9), getHttpConfig()).success(function (storageInfo) {
									storageInfo.fileName = storagePath.substr(9);
									resolveQ(q, storageInfo);
								});
							} else {
								rejectQ(q, "'storagePath' should begin with 'change://'.");
							}
							return q.promise;
						}
					}

				};

				return REST;
			}
		];

	});

})();