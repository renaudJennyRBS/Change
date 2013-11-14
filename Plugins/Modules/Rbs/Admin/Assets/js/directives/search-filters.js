(function () {

	"use strict";

	var forEach = angular.forEach,
		likeModes = {
			'beginsWith' : 'begin',
			'endsWith'   : 'end',
			'contains'   : 'any'
		};

	function searchFiltersDirectiveFn ($timeout, ArrayUtils, Utils, REST, Breadcrumb, SavedSearches, i18n, $http, $q, $compile) {

		return {
			"restrict"    : 'E',

			"scope" : {
				"model" : "@",
				"query" : "=filterQuery",
				"textSearch" : "="
			},

			"link" : function postLink(scope, elm, attrs) {

				if (! elm.is('[model]')) {
					throw new Error("Please provide a 'model' attribute with a valid Model name.");
				}

				var	modelMeta,
					loaded = false;

				scope.availableFilters = {};
				scope.appliedFilters   = [];
				scope.operator         = "and";
				scope.treePolicy       = Breadcrumb.getCurrentNode() ? "descendantOf" : "all";
				scope.appliedFiltersOriginal = angular.copy(scope.appliedFilters);


				// Wait for 'model' attribute to be evaluated and available.
				attrs.$observe('model', function (value) {
					if (value) {
						// Watch for 'textSearch' changes:
						// It will be fired even if 'textSearch' has changed before 'model' attribute is ready.
						scope.$watch('textSearch', function (value) {
							if (value) {
								if (! loaded) {
									load().then(search);
								}
								else {
									search();
								}
							}
						}, true);
					}
				});


				/**
				 * Load Model's information.
				 */
				function loadModelsInfo () {
					var promise = REST.modelInfo(scope.model);
					promise.then(function (modelInfo)
					{
						modelMeta = modelInfo.metas;

						if (modelMeta.publishable && ! modelInfo.properties.hasOwnProperty("published")) {
							modelInfo.properties.published = {
								"name" : "published",
								"type" : "Boolean"
							};
						}

						forEach(modelInfo.properties, function (propertyObj, name) {
							propertyObj.name = name;
							if (name === 'publicationStatus') {
								propertyObj.possibleValues = [
									{ "value" : "DRAFT", "label" : i18n.trans('m.rbs.admin.adminjs.status-draft') },
									{ "value" : "VALIDATION", "label" : i18n.trans('m.rbs.admin.adminjs.status-validation') },
									{ "value" : "VALIDCONTENT", "label" : i18n.trans('m.rbs.admin.adminjs.status-validcontent') },
									{ "value" : "PUBLISHABLE", "label" : i18n.trans('m.rbs.admin.adminjs.status-publishable') },
									{ "value" : "UNPUBLISHABLE", "label" : i18n.trans('m.rbs.admin.adminjs.status-unpublishable') },
									{ "value" : "FROZEN", "label" : i18n.trans('m.rbs.admin.adminjs.status-frozen') },
									{ "value" : "FILED", "label" : i18n.trans('m.rbs.admin.adminjs.status-filed') }
								];
							}
						});

						scope.availableFilters = modelInfo.properties;
					});
					return promise;
				}


				/**
				 * Load template and Model's information.
				 */
				function load () {
					var all,
						promises = [
							$http.get('Rbs/Admin/js/directives/search-filters.twig', {'cache': true}),
							loadModelsInfo()
						];
					all = $q.all(promises);
					all.then(function (results) {
						elm.append($compile(results[0].data)(scope));
					});
					loaded = true;
					return all;
				}


				/**
				 * Launch search on the value in 'textSearch'.
				 */
				function search () {
					elm.show();

					if (scope.textSearch === '...') {
						scope.textSearch = '';
					}
					ArrayUtils.clear(scope.appliedFilters);
					parseQuery(scope.textSearch);

					// Apply filter if there is a value.
					if (scope.textSearch) {
						scope.applyFilters();
					}
				}


				function getFilterByName (name) {
					for (var filter in scope.availableFilters) {
						if (scope.availableFilters.hasOwnProperty(filter)) {
							if (Utils.equalsIgnoreCase(filter, name) || Utils.equalsIgnoreCase(scope.availableFilters[filter].label, name)) {
								return scope.availableFilters[filter];
							}
						}
					}
					return null;
				}


				function parseQuery (query) {
					var tokens = null,
						t, i, op,
						parsed,
						filter,
						operators = [ '<>', '<=', '>=', '!=', '>', '<', '=' ],
						key, val;

					if (query) {
						query = query.trim()
							.replace(' et ', '&').replace(' ou ', '|')
							.replace(' and ', '&').replace(' or ', '|');
					} else {
						query = '';
					}

					if (query.indexOf('=') !== -1 || query.indexOf('<') !== -1 || query.indexOf('>') !== -1) {
						if (query.indexOf('&') !== -1) {
							scope.operator = 'and';
							tokens = query.split('&');
						} else if (query.indexOf(',') !== -1) {
							scope.operator = 'and';
							tokens = query.split(',');
						} else if (query.indexOf('|') !== -1) {
							scope.operator = 'or';
							tokens = query.split('|');
						} else {
							tokens = [ query ];
						}
						for (t=0 ; t<tokens.length ; t++) {
							for (i=0 ; i<operators.length ; i++) {
								op = operators[i];
								if (tokens[t].indexOf(op) !== -1) {
									parsed = tokens[t].split(op);
									key = parsed[0].trim();
									val = parsed[1].trim();
									if (!isNaN(parseFloat(val))) {
										val = parseFloat(val);
									}

									// Fix operator since the UI does not handle '<' and '>'.
									if (op === '<') {
										op = 'lte';
									} else if (op === '>') {
										op = 'gte';
									} else if (op === '=') {
										// If queryStr is not a number, use 'contains' instead of 'eq' for the operator.
										op = angular.isNumber(val) ? 'eq' : 'contains';
									} else if (op === '!=' || op === '<>') {
										op = 'neq';
									}

									// Here is our filter!
									filter = getFilterByName(key);
									if (filter) {
										scope.appliedFilters.push({
											"filter" : filter,
											"op"     : op,
											"value"  : val
										});
										break;
									}
								}
							}
						}
					} else {
						filter = getNextUnusedFilter();
						scope.appliedFilters.push({
							"filter" : filter,
							"op"     : filter.type === 'String' ? 'contains' : 'eq',
							'value'  : query
						});
					}

					$timeout(function () {
						elm.find('[data-role="filter-value"]:visible').last().focus().select();
					});

				}


				//
				// Filters manipulation.
				//

				function getNextUnusedFilter () {

					var	filter = null,
						usedFilterNames = [],
						name;

					forEach(scope.appliedFilters, function (appliedFilter) {
						usedFilterNames.push(appliedFilter.filter.name);
					});

					// If no filter is used and if the model is editable, let's filter on 'label' first.
					if (modelMeta.editable && ArrayUtils.inArray('label', usedFilterNames) === -1) {

						filter = scope.availableFilters['label'];

					} else {

						// Search for the next unused filter.
						for (name in scope.availableFilters) {
							if (ArrayUtils.inArray(name, usedFilterNames) === -1) {
								filter = scope.availableFilters[name];
								break;
							}
						}

						for (name in scope.availableFilters) {
							filter = scope.availableFilters[name];
							break;
						}
					}

					return filter;
				}


				scope.$on("Change:DocumentList:CancelFilters", function () {
					scope.cancelFiltersAndClose();
				});


				scope.addFilter = function (value) {
					var filter = getNextUnusedFilter();
					scope.appliedFilters.push({
						"filter" : filter,
						"op"     : filter.type === "String" ? "contains" : "eq",
						"value"  : value
					});
					$timeout(function () {
						elm.find('[data-role="filter-value"]:visible').last().focus().select();
					});
				};


				scope.removeFilter = function (index) {
					ArrayUtils.remove(scope.appliedFilters, index);
					scope.applyFilters();
				};


				scope.$watch('operator', function (oldVal, newVal) {
					if (oldVal && newVal && oldVal !== newVal) {
						scope.applyFilters();
					}
				}, true);


				scope.applyFilters = function () {

					var query, where = [];
					query = {
						"model" : scope.model
					};

					forEach(scope.appliedFilters, function (applied) {

						if (applied.filter.name === 'published') {
							where.push({
								"op" : applied.value ? "published" : "notPublished"
							});
						}
						else {
							var value = applied.value;
							if (angular.isDefined(value) && value !== null) {
								if (applied.filter.possibleValues && angular.isObject(value) && value.value) {
									value = value.value;
								}

								if (applied.filter.type === 'Document') {
									applied.op = 'eq';
								}
								if (Utils.isDocument(value)) {
									value = value.id;
								}

								// Simple operators:

								switch (applied.op) {
								case 'eq'  :
								case 'neq' :
								case 'gt'  :
								case 'gte' :
								case 'lt'  :
								case 'lte' :
									where.push({
										"op"   : applied.op,
										"lexp" : { "property" : applied.filter.name },
										"rexp" : { "value"    : value }
									});
									break;

								// "like" operators:

								case 'contains'   :
								case 'beginsWith' :
								case 'endsWith'   :
									where.push({
										"op"   : "like",
										"lexp" : { "property" : applied.filter.name },
										"rexp" : { "value"    : value },
										"mode" : likeModes[applied.op]
									});
									break;
								}
							}
						}
					});

					if (where.length) {

						/*
						TODO
						// Add tree restrictions if needed.
						if (scope.treePolicy !== 'all' && Breadcrumb.getCurrentNode()) {
							where.push({
								"op"   : scope.treePolicy,
								"node" : Breadcrumb.getCurrentNode().id
							});
						}
						*/
						query.where = {};
						query.where[scope.operator] = where;
					} else {
						query.where = null;
					}

					scope.appliedFiltersOriginal = angular.copy(scope.appliedFilters);
					scope.query = query;
				};


				scope.isUnchanged = function () {
					return angular.equals(scope.appliedFiltersOriginal, scope.appliedFilters);
				};


				scope.cancelFiltersAndClose = function () {
					scope.textSearch = '';
					ArrayUtils.clear(scope.appliedFilters);
					scope.applyFilters();
					elm.hide();
				};


				scope.addToFavorites = function () {
					var label = prompt("Donnez un nom à votre recherche");
					if (label && label.trim()) {
						SavedSearches.save(label, scope.query);
					}
				};

			}

		};

	}


	angular.module('RbsChange').directive(
		'searchFilters',
		[
			'$timeout',
			'RbsChange.ArrayUtils',
			'RbsChange.Utils',
			'RbsChange.REST',
			'RbsChange.Breadcrumb',
			'RbsChange.SavedSearches',
			'RbsChange.i18n',
			'$http',
			'$q',
			'$compile',
			searchFiltersDirectiveFn
		]
	);

})();