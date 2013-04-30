(function () {

	"use strict";

	var forEach = angular.forEach,
		likeModes = {
			'beginsWith': 'begin',
			'endsWith': 'end',
			'contains': 'any'
		};

	function searchFiltersDirectiveFn ($timeout, ArrayUtils, Utils, Dialog, REST, Breadcrumb, SavedSearches) {

		return {
			"restrict"    : 'E',
			"replace"     : true,
			"templateUrl" : 'Change/Admin/js/directives/search-filters.html',

			"scope" : {
				"model" : "@",
				"query" : "=",
				"textSearch" : "="
			},

			"link" : function postLink(scope, elm, attrs) {

				if (!scope.model) {
					throw new Error("Please provide a 'model' attribute with a valid Model name.");
				}

				var modelMeta;

				scope.availableFilters = {};
				scope.appliedFiltersOriginal = angular.copy(scope.appliedFilters);
				scope.appliedFilters   = [];
				scope.operator         = "and";
				scope.treePolicy       = Breadcrumb.getCurrentNode() ? "descendantOf" : "all";

				scope.$watch('textSearch', function (textSearch) {
					if (textSearch) {
						elm.show();
						if (textSearch === '...') {
							textSearch = '';
						}
						scope.addFilter(textSearch);

						// Apply filter if there is a value.
						if (textSearch) {
							scope.applyFilters();
						}
					}
				});


				//
				// Load Model's information.
				//

				REST.modelInfo(scope.model).then(function (modelInfo) {
					modelMeta = modelInfo.metas;

					if (modelMeta.publishable && ! modelInfo.properties.hasOwnProperty("published")) {
						modelInfo.properties.published = {
							"name" : "published",
							"type" : "Boolean"
						};
					}

					forEach(modelInfo.properties, function (propertyObj, name) {

						propertyObj.name = name;

						// FIXME To be removed when labels are translated server side
						if (!propertyObj.label || propertyObj.label.substr(0, 2) === 'm.') {
							propertyObj.label = propertyObj.name;
						}

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
							case 'Change_Website_Website' :
							case 'Change_Theme_PageTemplate' :
								propertyObj.selectorType = 'dropdown';
								break;
							}
						}

					});

					scope.availableFilters = modelInfo.properties;
				});


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



				/*
								var defaultFilters, dlName;

								function getFilterDefByName (name) {
									for (var af=0 ; af<scope.availableFilters.length ; af++) {
										if (scope.availableFilters[af].name === name) {
											return scope.availableFilters[af];
										}
									}
									return null;
								}

								function getFilterDefValue (filterDef, filterValue) {
									var p;
									if (filterDef !== null && filterDef.possibleValues) {
										for (p=0 ; p<filterDef.possibleValues.length ; p++) {
											if (filterDef.possibleValues[p].value.toLowerCase() === filterValue.toLowerCase()) {
												return filterDef.possibleValues[p];
											}
										}
									}
									return filterValue;
								}

								function fixType (value) {
									var fixed = parseFloat(value);
									if (!isNaN(fixed)) {
										return fixed;
									}
									return value;
								}

								function addDefaultFilter (value) {
									var filters = parseQuery(value),
										filterDef,
										filterValue,
										f;
									ArrayUtils.clear(scope.appliedFilters);

									for (f=0 ; f<filters.length ; f++) {
										filterDef = getFilterDefByName(filters[f].name);
										filterValue = fixType(getFilterDefValue(filterDef, filters[f].value));
										scope.appliedFilters.push({
											'filter': filterDef,
											'value': filterValue,
											'modifier': filters[f].modifier ? filters[f].modifier : ((filterDef.type === 'text') ? 'contains' : 'equals')
										});
									}
									scope.applyFilters();
								}

								function parseQuery (query) {
									var filters = [],
										tokens = null,
										t, i, op,
										parsed,
										operators = [ '<>', '<=', '>=', '!=', '>', '<', '=' ],
										key, val;

									if (query) {
										query = query.trim()
													 .toLowerCase()
													 .replace(' et ', '&').replace(' ou ', '|')
													 .replace(' and ', '&').replace(' or ', '|');
									} else {
										query = '';
									}

									if (query.indexOf('=') !== -1 || query.indexOf('<') !== -1 || query.indexOf('>') !== -1) {
										if (query.indexOf('&') !== -1) {
											scope.operator = 'AND';
											tokens = query.split('&');
										} else if (query.indexOf(',') !== -1) {
											scope.operator = 'AND';
											tokens = query.split(',');
										} else if (query.indexOf('|') !== -1) {
											scope.operator = 'OR';
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

													// Fix operator since the UI does not handle '<' and '>'.
													if (op === '<') {
														op = '<=';
													} else if (op === '>') {
														op = '>=';
													} else if (op === '=') {
														// If queryStr is not a number, use 'contains' instead of 'equals' for the operator.
														op = isNaN(parseInt(val, 10)) ? 'contains' : 'equals';
													} else if (op === '!=' || op === '<>') {
														op = 'notEquals';
													}

													// Here is our filter!
													filters.push({
														'name': key,
														'value': val,
														'modifier': op
													});
													break;
												}
											}
										}
									} else {
										filters.push({
											'name': scope.availableFilters[0].name,
											'value': query
										});
									}

									return filters;
								}

								scope.appliedFiltersOriginal = angular.copy(scope.appliedFilters);

								scope.$watch('query', function (value, oldValue) {
									//console.log("SearchFilters: query changed: ", value, oldValue);
									if (angular.isString(scope.query)) {
										addDefaultFilter(scope.query);
										elm.show();
									}
								}, true);

								scope.$watch('operator', function () {
									scope.applyFilters();
								}, true);

								if (attrs.filters) {
									defaultFilters = angular.fromJson(attrs.filters);
									if (!angular.isArray(defaultFilters)) {
										throw new Error("Default filters should be an Array.");
									}
									for (var i=0 ; i<defaultFilters.length ; i++) {
										var f = defaultFilters[i];
										f.filter = getFilterDefByName(f.property);
										f.value = getFilterDefValue(f.filter, f.value);
										scope.appliedFilters.push(f);
									}
									elm.show();
								}

								scope.isUnchanged = function () {
									return angular.equals(scope.appliedFiltersOriginal, scope.appliedFilters);
								};

								scope.addToFavorites = function ($event) {
									var confirm = Dialog.confirmLocal(
											$event.target,
											"Enregister la recherche ?",
											"Cette recherche va être ajoutée dans le menu de gauche du module actuel.",
											{
												question: "Souhaitez-vous l'ajouter aussi dans le [b]menu de la page d'accueil[/b] pour un accès direct ?",
												placement: 'bottom'
											});
									confirm.done(function () {
										console.log('Adding search to the home page...');
									})
									.fail(function () {
										console.log('Favorite search will NOT be added to the home page.');
									});
								};
				*/
			}

		};

	}


	angular.module('RbsChange').directive(
		'searchFilters',
		[
			'$timeout',
			'RbsChange.ArrayUtils',
			'RbsChange.Utils',
			'RbsChange.Dialog',
			'RbsChange.REST',
			'RbsChange.Breadcrumb',
			'RbsChange.SavedSearches',
			searchFiltersDirectiveFn
		]
	);

})();