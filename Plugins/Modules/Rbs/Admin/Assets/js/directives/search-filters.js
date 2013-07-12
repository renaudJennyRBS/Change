(function () {

	"use strict";

	var forEach = angular.forEach,
		likeModes = {
			'beginsWith' : 'begin',
			'endsWith'   : 'end',
			'contains'   : 'any'
		};

	function searchFiltersDirectiveFn ($timeout, ArrayUtils, Utils, REST, Breadcrumb, SavedSearches) {

		return {
			"restrict"    : 'E',
			"replace"     : true,
			"templateUrl" : 'Rbs/Admin/js/directives/search-filters.twig',

			"scope" : {
				"model" : "@",
				"query" : "=filterQuery",
				"textSearch" : "="
			},

			"link" : function postLink(scope, elm) {

				if (! elm.is('[model]')) {
					throw new Error("Please provide a 'model' attribute with a valid Model name.");
				}

				var modelMeta;

				scope.availableFilters = {};
				scope.appliedFilters   = [];
				scope.operator         = "and";
				scope.treePolicy       = Breadcrumb.getCurrentNode() ? "descendantOf" : "all";
				scope.appliedFiltersOriginal = angular.copy(scope.appliedFilters);

				scope.$watch('textSearch', initSearch, true);


				//
				// Load Model's information.
				//
				function loadModelsInfo () {
					var promise = REST.modelInfo(scope.model)
					promise.then(function (modelInfo) {
						modelMeta = modelInfo.metas;

						if (modelMeta.publishable && ! modelInfo.properties.hasOwnProperty("published")) {
							modelInfo.properties.published = {
								"name" : "published",
								"type" : "Boolean"
							};
						}

						forEach(modelInfo.properties, function (propertyObj, name) {

							propertyObj.name = name;

							// FIXME Localization
							if (name === 'publicationStatus') {
								propertyObj.possibleValues = [
									{ "value" : "DRAFT",       "label" : "Brouillon" },
									{ "value" : "DEACTIVATED", "label" : "Désactivé" },
									{ "value" : "PUBLISHABLE", "label" : "Publiable" },
									{ "value" : "ACTIVE",      "label" : "Activé" },
									{ "value" : "VALIDATION",  "label" : "En cours de validation"}
								];
							}

							// FIXME Find another way to determine which selector to use?
							if (propertyObj.type === 'Document') {
								switch (propertyObj.documentType) {
									case 'Rbs_Website_Website' :
									case 'Rbs_Theme_PageTemplate' :
										propertyObj.selectorType = 'dropdown';
										break;
								}
							}

						});

						scope.availableFilters = modelInfo.properties;
					});
					return promise;
				}


				function initSearch (textSearch) {
					if (textSearch) {
						loadModelsInfo().then(function () {
							elm.show();
							if (textSearch === '...') {
								textSearch = '';
							}
							ArrayUtils.clear(scope.appliedFilters);
							parseQuery(textSearch);

							// Apply filter if there is a value.
							if (textSearch) {
								scope.applyFilters();
							}
						});
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

						} else {

							var value = applied.value;
							if (applied.filter.possibleValues && angular.isObject(value) && value.value) {
								value = value.value;
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
			searchFiltersDirectiveFn
		]
	);

})();