(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * REST service.
	 */
	app.provider('RbsChange.REST', function RbsChangeRESTProvider () {

		var forEach = angular.forEach,
			REST_BASE_URL,
			HTTP_STATUS_CREATED = 201;

		this.setBaseUrl = function (url) {
			REST_BASE_URL = url;
		};

		this.$get = [
			'$http', '$location', '$q', '$timeout', '$rootScope',
			'RbsChange.Utils',
			'RbsChange.ArrayUtils',
			'RbsChange.Settings',
			'RbsChange.UrlManager',
			'RbsChange.i18n',

			function ($http, $location, $q, $timeout, $rootScope, Utils, ArrayUtils, Settings, UrlManager, i18n) {

				if ( ! REST_BASE_URL ) {
					REST_BASE_URL = $location.protocol() + '://' + $location.host();
					if ($location.port() !== 80) {
						REST_BASE_URL += ':' + $location.port();
					}
					REST_BASE_URL += '/rest.php/';
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
					var splat = string.split('.'),
						obj, i;
					obj = this.META$;
					for (i=0 ; i<splat.length && !obj ; i++) {
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

				ChangeDocument.prototype.url = function (name) {
					return UrlManager.getUrl(this, name || 'form');
				};

				ChangeDocument.prototype.treeUrl = function () {
					return UrlManager.getTreeUrl(this);
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

				ChangeDocument.prototype.hasCorrection = function () {
					return angular.isObject(this.META$.correction);
				};

				ChangeDocument.prototype.getTagsUrl = function () {
					return this.META$.links['self'] ? this.META$.links['self'].href + '/tags/' : null;
				};

				ChangeDocument.prototype.getTags = function () {
					if (this.META$.tags === null) {
						this.META$.tags = [];
						var doc = this;

						if (doc.getTagsUrl() !== null) {
							$http.get(doc.getTagsUrl(),getHttpConfig(transformResponseCollectionFn))
							.success(function (result) {
								angular.forEach(result.resources, function (r) {
									doc.META$.tags.push(r);
								});
							});
						}
					}
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
								'label': lcid, // FIXME Localization
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
							'Accept-Language': angular.lowercase(Settings.language).replace('_', '-')
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
				 * Applies the Correction represented by `correctionData` on the given `resource`.
				 *
				 * @param resource
				 * @param correctionData
				 *
				 * @returns ChangeDocument The `resource` with its correction applied.
				 */
				function applyCorrection (resource, correctionData) {
					var original = angular.copy(resource);
					delete original.META$;
					resource.META$.correction = angular.extend({'original': original}, correctionData.correction);
					return angular.extend(resource, correctionData.properties);
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
					if (data === null || (data.code && data.message)) {
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


				var lastCreatedDocument = null, REST;


				// Public API of the REST service.

				REST = {

					'getHttpConfig' : function () {
						return getHttpConfig();
					},

					'transformObjectToChangeDocument' : function (object) {
						return angular.extend(new ChangeDocument(), object);
					},


					'isLastCreated' : function (doc) {
						return lastCreatedDocument && doc && (doc.id === lastCreatedDocument.id && doc.model === lastCreatedDocument.model);
					},


					'getLastCreated' : function () {
						return lastCreatedDocument;
					},


					/**
					 * @param relativePath
					 * @returns {String}
					 */
					'getBaseUrl' : function (relativePath) {
						return REST_BASE_URL + relativePath;
					},


					/**
					 * Returns the URL of the Resource identified by its `model`, `id` and eventually `lcid`.
					 *
					 * @param id
					 * @param lcid
					 * @param model
					 *
					 * @return String Resource's URL.
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
					 * Returns the URL of the collection for the model `model` and the optional given `params`.
					 *
					 * @param model Model name.
					 * @param params Parameters (limit, offset, sort, ...)
					 *
					 * @return {String} Collection's URL.
					 */
					'getCollectionUrl' : function (model, params) {
						model = Utils.modelInfo(model);
						return Utils.makeUrl(
							REST_BASE_URL + 'resources/' + model.vendor + '/' + model.module + '/' + model.document + '/',
							params
						);
					},


					/**
					 * Creates a new, unsaved resource of the given `model` in the given locale (`lcid`).
					 *
					 * @param model Model name.
					 * @param lcid (Optional) Locale ID (5 chars).
					 *
					 * @return {Object}
					 */
					'newResource' : function (model, lcid) {
						var props = {
							'id'    : Utils.getTemporaryId(),
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
					 * Loads the Resource identified by its `model`, `id` and eventually `lcid`.
					 *
					 * @param model Model name.
					 * @param id Resource's ID.
					 * @param lcid (Optionnal) Locale ID.
					 *
					 * @return {Object} Promise that will be resolved when the Resource is loaded.
					 */
					'resource' : function (model, id, lcid) {
						var q = $q.defer(),
							self = this;

						$http.get(this.getResourceUrl(model, id, lcid), getHttpConfig(transformResponseResourceFn))

							.success(function restResourceSuccessCallback (data) {
								if (Utils.hasCorrection(data)) {
									self.loadCorrection(data).then(function (doc) {
										resolveQ(q, doc);
									});
								} else {
									resolveQ(q, data);
								}
							})
							.error(function restResourceErrorCallback (data, status) {
								if (status === 303 && Utils.isDocument(data)) {
									console.log("Got HTTP 303 (See Other) for document ", data);
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
					 * Loads a collection via a 'GET' REST call.
					 *
					 * @param model Model name OR URL of a RESTful service that returns a Collection.
					 * @param params Parameters (limit, offset, sort, ...)
					 *
					 * @return Promise Promise that will be resolved when the collection is loaded.
					 *                 The Promise is resolved with the whole response as argument.
					 */
					'collection' : function (model, params) {
						var	q = $q.defer(),
							url;

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
								//this.getCollectionUrl(model, params),
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
					 * Loads the Correction for the given `resource`.
					 *
					 * @param resource
					 *
					 * @returns {Promise} Promise that will be resolved when the Correction has been applied on the given `resource`.
					 */
					'loadCorrection' : function (resource) {
						var q = $q.defer();
						$http.get(resource.META$.actions.correction.href, getHttpConfig())
							.success(function restResourceSuccessCallback (data) {
								console.log("loadCorrection: data=", data);
								applyCorrection(resource, data);
								resolveQ(q, resource);
							});
						return q.promise;
					},


					/**
					 * Saves the given `resource` via a 'POST' (creation) or 'PUT' (update) REST call.
					 *
					 * @param resource The Resource to be saved.
					 * @param currentTreeNode ChangeDocument Current TreeNode.
					 *
					 * @return {Object} Promise that will be resolved when `resource` is successfully saved.
					 *                  Promise is resolved with the saved Resource as argument.
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
						delete resource.META$;

						if (Utils.isNew(resource)) {
							// If resource is new (see isNew()), we must POST on the Collection's URL.
							method = 'post';
							// Remove temporary ID
							delete resource.id;
							url = this.getCollectionUrl(resource.model);
						} else {
							// If resource is NOT new (already been saved), we must PUT on the Resource's URL.
							method = 'put';
							url = this.getResourceUrl(resource);
							// Save only the properties listed here.
							if (angular.isArray(propertiesList)) {
								var toSave = {};
								angular.forEach(propertiesList, function (prop) {
									if (resource.hasOwnProperty(prop)) {
										toSave[prop] = resource[prop];
									}
								});
								resource = toSave;
							}
						}

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
							.success(function successCallback (doc, status) {

								// 1) "doc" is a ChangeDocument instance.

								function maybeInsertResourceInTree (resource, qToResolve) {
									console.log("REST.save(): maybeInsertResourceInTree()");

									if (status === HTTP_STATUS_CREATED) {
										lastCreatedDocument = resource;
									}

									if ( ! Utils.isTreeNode(resource) && (status === HTTP_STATUS_CREATED || resource.treeName === null) && currentTreeNode) {

										// Load model's information to check if the document should be inserted in a tree.
										REST.modelInfo(resource).then(

											// modelInfo success
											function (modelInfo) {
												if (!modelInfo.metas || !modelInfo.metas.treeName) {
													console.log("REST.save(): Saved. Not inserted in tree because the model has no tree information (treeName=null).", doc);
													resolveQ(qToResolve, resource);
												} else {
													console.log("REST.save(): Saved. Inserting document ", doc, " in tree " + modelInfo.metas.treeName + " at ", currentTreeNode.META$.treeNode.url);
													$http.post(currentTreeNode.META$.treeNode.url + '/', { "id" : doc.id }, getHttpConfig())
														.success(function (nodeData) {
															console.log("REST.save(): Inserted in tree: node=", nodeData);
															doc = buildChangeDocument(doc, resource);
															if (status === HTTP_STATUS_CREATED) {
																lastCreatedDocument = doc;
															}
															resolveQ(qToResolve, doc);
														})
														.error(function errorCallback (data, status) {
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
										console.log("REST.save(): Saved. Not inserted in tree because no tree node information has been provided or status is not 201 (Created).", doc);
										resolveQ(qToResolve, resource);
									}
								}

								// 2) load its Correction (if any)
								// After being saved, a Document may have a Correction attached to it, especially
								// if it was PUBLISHED on the website.
								if (Utils.hasCorrection(doc)) {
									console.log("REST.save(): Document has a Correction: let's load it!");
									REST.loadCorrection(doc).then(function (doc) {
										console.log("REST.save(): Correction loaded.");
										maybeInsertResourceInTree(doc, mainQ);
									});
								} else {
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
					 * Deletes the given `resource` via a 'DELETE' REST call.
					 *
					 * @param resource The Resource to be deleted.
					 *
					 * @return Promise Promise that will be resolved when `resource` is successfully deleted.
					 *                 Promise is resolved with the deleted Resource as argument.
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
					 * Calls the action `actionName` on the given `resource` with the given `params`.
					 *
					 * @param actionName
					 * @param resource
					 * @param params
					 *
					 * @returns Promise
					 */
					'resourceAction' : function (actionName, resource, params) {
						var q = $q.defer(),
							url;

						url = REST_BASE_URL + 'resourcesactions/' + actionName + '/' + resource.id;
						if (resource.LCID) {
							url += '/' + resource.LCID;
						}
						url = Utils.makeUrl(url, params);

						$http.get(url, getHttpConfig())
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
					 * Calls the action `actionName` on the given `resource` with the given `params` and reloads
					 * the `resource`.
					 *
					 * @param actionName
					 * @param resource
					 * @param params
					 *
					 * @returns Promise
					 */
					'resourceActionThenReload' : function (actionName, resource, params) {
						var q = $q.defer(),
							self = this;

						this.resourceAction(actionName, resource, params).then(
							function () {
								self.resource(resource.model, resource.id, resource.LCID).then(function (rsc) {
									resolveQ(q, rsc);
								});
							},
							function (reason) {
								rejectQ(q, reason);
							}
						);

						return q.promise;
					},


					/**
					 * Returns the URL of a tree from its `treeName`.
					 *
					 * @param treeName
					 *
					 * @returns String
					 */
					'treeUrl' : function (treeName) {
						return REST_BASE_URL + 'resourcestree/' + _ToSlash(treeName) + '/';
					},


					/**
					 * Loads the tree children of the given `resource`.
					 *
					 * @param resource
					 *
					 * @returns Promise
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
					 * Loads the TreeNode object of the given `resource`.
					 *
					 * @param resource
					 *
					 * @returns Promise
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
					 * Loads the tree ancestors of the given `resource`.
					 *
					 * @param resource
					 *
					 * @returns Promise
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
					 * Returns information about a Block.
					 *
					 * @param blockName
					 * @returns Promise
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
					 * Returns information about a Model.
					 *
					 * @param modelName
					 * @returns Promise
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
					 * Sends a query to search for documents. Query is sent via a POST call.
					 *
					 * @param queryObject
					 * @returns Promise, resolved with a collection of documents that match the filters.
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
					 * Calls the action `actionName` with the given `params`.
					 *
					 * @param actionName
					 * @param params
					 *
					 * @returns Promise
					 */
					'action' : function (actionName, params) {
						var	q = $q.defer(),
							url;

						url = Utils.makeUrl(REST_BASE_URL + 'actions/' + actionName + '/', params);

						$http.get(url, getHttpConfig())
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

					'postAction' : function (actionName, content) {
						var	q = $q.defer(),
							url;

						url = REST_BASE_URL + 'actions/' + actionName + '/';

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
					 * @param url Full URL
					 * @param params
					 *
					 * @returns Promise
					 */
					'call' : function (url, params) {
						var	q = $q.defer();

						$http.get(Utils.makeUrl(url, params), getHttpConfig())
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

					'storage' : {

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
							else (!storage)
							{
								return null;
							}
							throw new Error("'storage' should be an object with links/data.");
						},


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