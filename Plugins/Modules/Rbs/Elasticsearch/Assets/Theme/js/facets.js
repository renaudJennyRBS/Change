(function($) {
	"use strict";
	var app = angular.module('RbsChangeApp');

	app.factory('RecursionHelper', ['$compile', function($compile) {
		return {
			/**
			 * Manually compiles the element, fixing the recursion loop.
			 * @param element
			 * @param [link] A post-link function, or an object with function(s) registered via pre and post properties.
			 * @returns An object containing the linking functions.
			 */
			compile: function(element, link) {
				// Normalize the link parameter
				if (angular.isFunction(link)) {
					link = { post: link };
				}

				// Break the recursion loop by removing the contents
				var contents = element.contents().remove();
				var compiledContents;
				return {
					pre: (link && link.pre) ? link.pre : null,
					/**
					 * Compiles and re-adds the contents
					 */
					post: function(scope, element) {
						// Compile the contents
						if (!compiledContents) {
							compiledContents = $compile(contents);
						}
						// Re-add the compiled contents to the element
						compiledContents(scope, function(clone) {
							element.append(clone);
						});

						// Call the post-linking function, if any
						if (link && link.post) {
							link.post.apply(null, arguments);
						}
					}
				};
			}
		};
	}]);

	app.directive('rbsElasticsearchFacetContainer', function() {
		return {
			restrict: 'A',
			scope: true,
			controller: ['$scope', '$element', '$http', 'RbsChange.AjaxAPI', function(scope, $element, $http, AjaxAPI) {
				var cacheKey = $element.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				var data = AjaxAPI.globalVar(cacheKey);
				scope.facets = data['facetValues'];

				scope.submit = function() {
					var facetFilters = buildFacetFilters(scope.facets);
					var href = scope.parameters.formAction;

					if (facetFilters) {
						var query = { "facetFilters": facetFilters };
						if (href.indexOf('?') > 0) {
							href += '&' + buildQueryString(query);
						}
						else {
							href += '?' + buildQueryString(query);
						}
					}
					window.location.href = href;
				};

				scope.reset = function(event) {
					unSelectDescendant(scope.facets);
					refresh()
				};

				function buildQueryString(obj, prefix) {
					var str = [];
					angular.forEach(obj, function(v, p) {
						var k = prefix ? prefix + "[" + p + "]" : p;
						str.push(typeof v == "object" ? buildQueryString(v, k) : encodeURIComponent(k) + "=" +
						encodeURIComponent(v));
					});
					return str.join("&");
				}

				function refresh() {
					if (scope.facets && scope.facets.length > 1) {
						scope.parameters.facetFilters = buildFacetFilters(scope.facets);
						$http.post('Action/Rbs/Elasticsearch/StoreFacet', scope.parameters)
							.success(function(data, status, headers, config) {
								scope.facets = data;
							})
							.error(function(data, status, headers, config) {
								console.log('error', data, status, headers, config);
							});
					}
				}

				this.refresh = refresh;

				this.getFacets = function() {
					return scope.facets;
				};

				function selectAncestors(facet) {
					var parent = getFacetByFieldName(facet.parent);
					var multipleChoice = parent.parameters.multipleChoice;
					for (var z = 0; z < parent.values.length; z++) {
						var value = parent.values[z];
						for (var i = 0; i < value.aggregationValues.length; i++) {
							if (value.aggregationValues[i] === facet) {
								value.selected = true;
								if (parent.parent) {
									selectAncestors(parent);
								}
							}
							else if (!multipleChoice) {
								value.selected = false;
								if (value.aggregationValues) {
									unSelectDescendant(value.aggregationValues);
								}
							}
						}
					}
				}

				this.selectAncestors = selectAncestors;

				function unSelectDescendant(facets) {
					for (var i = 0; i < facets.length; i++) {
						var facet = facets[i];
						if (facet.values) {
							for (var z = 0; z < facet.values.length; z++) {
								var value = facet.values[z];
								value.selected = false;
								if (value.aggregationValues) {
									unSelectDescendant(value.aggregationValues);
								}
							}
						}
					}
				}

				this.unSelectDescendant = unSelectDescendant;

				function getFacetByFieldName(fieldName, facets) {
					if (facets === undefined) {
						facets = scope.facets;
					}
					for (var i = 0; i < facets.length; i++) {
						var facet = facets[i];
						if (facet.fieldName === fieldName) {
							return facet;
						}
						if (facet.values) {
							for (var z = 0; z < facet.values.length; z++) {
								var value = facet.values[z];
								if (value.aggregationValues) {
									var subFacet = getFacetByFieldName(fieldName, value.aggregationValues);
									if (subFacet) {
										return subFacet;
									}
								}
							}
						}

					}
					return null;
				}

				function buildFacetFilters(facets) {
					var facetFilters = null;
					for (var i = 0; i < facets.length; i++) {
						var facet = facets[i];
						if (angular.isFunction(facet.buildFilter)) {
							var filters = facet.buildFilter();
							if (filters) {
								if (!facetFilters) {
									facetFilters = {};
								}
								facetFilters[facet.fieldName] = filters;
							}
						}
						else if (facet.values) {
							for (var z = 0; z < facet.values.length; z++) {
								var value = facet.values[z];
								if (value.selected) {
									var key = '' + value.key;
									if (!facetFilters) {
										facetFilters = {};
									}
									if (!facetFilters[facet.fieldName]) {
										facetFilters[facet.fieldName] = {};
									}
									facetFilters[facet.fieldName][key] = 1;
									var filter = null;
									if (value.aggregationValues && value.aggregationValues.length) {
										filter = buildFacetFilters(value.aggregationValues);
										if (filter) {
											facetFilters[facet.fieldName][key] = filter;
										}
									}
								}
							}
						}
					}
					return facetFilters;
				}
			}]
		}
	});

	app.directive('rbsElasticsearchFacet', ['RecursionHelper', function(RecursionHelper) {
		function link(scope, element, attrs, rbsElasticsearchFacetController) {
			scope.isCollapsed = attrs['isCollapsed'] == 'true';
			scope.multipleChoice = scope.facet.parameters.multipleChoice;

			scope.selectionChange = function(value) {
				if (!scope.multipleChoice) {
					for (var z = 0; z < scope.facet.values.length; z++) {
						if (value !== scope.facet.values[z]) {
							scope.facet.values[z].selected = false;
						}
					}
				}
				if (!value.selected && value.aggregationValues) {
					rbsElasticsearchFacetController.unSelectDescendant(value.aggregationValues);
				}
				else if (value.selected && scope.facet.parent) {
					rbsElasticsearchFacetController.selectAncestors(scope.facet);
				}

				rbsElasticsearchFacetController.refresh();
			};

			scope.hasValue = function() {
				return scope.facet.values && scope.facet.values.length;
			}
		}

		return {
			restrict: 'A',
			require: '^rbsElasticsearchFacetContainer',
			templateUrl: '/rbsElasticsearchFacet.tpl',
			scope: { facet: '=' },
			compile: function(element) {
				// Use the compile function from the RecursionHelper,
				// And return the linking function(s) which it returns
				return RecursionHelper.compile(element, link);
			}
		};
	}]);
})(window.jQuery);