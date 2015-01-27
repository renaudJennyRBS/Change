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
					for (var z = 0; z < parent.values.length; z++) {
						var value = parent.values[z];
						for (var i = 0; i < value['aggregationValues'].length; i++) {
							if (value['aggregationValues'][i] === facet) {
								value.selected = true;
								if (angular.isFunction(parent.selectionChange)) {
									parent.selectionChange(value);
								}
								return;
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
								if (value['aggregationValues']) {
									unSelectDescendant(value['aggregationValues']);
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

	app.directive('rbsElasticsearchFacet', ['RecursionHelper', '$compile', function(RecursionHelper, $compile) {
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

		function link(scope, element, attrs) {
			scope.isCollapsed = attrs['isCollapsed'] == 'true';

			var directiveName = scope.facet.parameters.renderingMode;
			if (!directiveName) {
				console.error('No rendering mode for facet!', scope.facet);
			}
			else if (directiveName.indexOf('-') < 0) {
				directiveName = 'rbs-elasticsearch-facet-' + directiveName;
			}
			console.log(directiveName);

			var container = element.find('.facet-values-container');
			container.html('<div data-' + directiveName + '></div>');
			$compile(container.contents())(scope);
		}
	}]);

	app.directive('rbsElasticsearchFacetRadio', ['RecursionHelper', function(RecursionHelper) {
		return {
			restrict: 'A',
			require: '^rbsElasticsearchFacetContainer',
			templateUrl: '/rbsElasticsearchFacetRadio.tpl',
			scope: false,
			compile: function(element) {
				// Use the compile function from the RecursionHelper,
				// And return the linking function(s) which it returns
				return RecursionHelper.compile(element, link);
			}
		};

		function link(scope, element, attrs, rbsElasticsearchFacetController) {
			scope.facet.selectionChange = function(value) {
				for (var z = 0; z < scope.facet.values.length; z++) {
					if (value !== scope.facet.values[z]) {
						scope.facet.values[z].selected = false;
					}
				}
				if (!value.selected && value['aggregationValues']) {
					rbsElasticsearchFacetController.unSelectDescendant(value['aggregationValues']);
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
	}]);

	app.directive('rbsElasticsearchFacetCheckbox', ['RecursionHelper', function(RecursionHelper) {
		return {
			restrict: 'A',
			require: '^rbsElasticsearchFacetContainer',
			templateUrl: '/rbsElasticsearchFacetCheckbox.tpl',
			scope: false,
			compile: function(element) {
				// Use the compile function from the RecursionHelper,
				// And return the linking function(s) which it returns
				return RecursionHelper.compile(element, link);
			}
		};

		function link(scope, element, attrs, rbsElasticsearchFacetController) {
			scope.facet.selectionChange = function (value) {
				if (!value.selected && value['aggregationValues']) {
					rbsElasticsearchFacetController.unSelectDescendant(value['aggregationValues']);
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
	}]);

	app.directive('rbsElasticsearchFacetInterval', ['$filter', function ($filter) {
		return {
			restrict: 'A',
			require: '^rbsElasticsearchFacetContainer',
			templateUrl: '/rbsElasticsearchFacetInterval.tpl',
			scope: false,
			link: function(scope, element, attrs, rbsElasticsearchFacetController) {
				scope.facet.selectionChange = function (value) {
					if (value.selected && scope.facet.parent) {
						rbsElasticsearchFacetController.selectAncestors(scope.facet);
					}

					rbsElasticsearchFacetController.refresh();
				};

				scope.hasValue = function() {
					return scope.facet.values && scope.facet.values.length;
				};

				scope.range = {
					interval: scope.facet.parameters.interval,
					absMin: null,
					absMax: null,
					min: null,
					max: null
				};
				var lastMin, lastMax;

				scope.facet.buildFilter = function() {
					return { min: scope.range.min, max: scope.range.max };
				};

				for (var i = 0; i < scope.facet.values.length; i++) {
					var value = scope.facet.values[i].key;
					var upValue = value + scope.range.interval;
					if (scope.range.absMin == null || value < scope.range.absMin) {
						scope.range.absMin = value;
					}
					if (scope.range.absMax == null || upValue > scope.range.absMax) {
						scope.range.absMax = upValue;
					}
					if (scope.facet.values[i].selected) {
						if (scope.range.min == null || value < scope.range.min) {
							scope.range.min = value;
						}
						if (scope.range.max == null || upValue > scope.range.max) {
							scope.range.max = upValue;
						}
					}
				}

				if (scope.facet.parameters['minFilter'] !== undefined && scope.facet.parameters['minFilter'] !== null) {
					scope.range.min = parseInt(scope.facet.parameters['minFilter']);
				}
				else if (scope.range.min === null) {
					scope.range.min = scope.range.absMin;
				}

				if (scope.facet.parameters['maxFilter'] !== undefined && scope.facet.parameters['maxFilter'] !== null) {
					scope.range.max = parseInt(scope.facet.parameters['maxFilter']);
				}
				else if (scope.range.max === null) {
					scope.range.max = scope.range.absMax;
				}

				lastMin = scope.range.min;
				lastMax = scope.range.max;

				var sliderJqElement = element.find('.range-slider');
				sliderJqElement.noUiSlider({
					start: [scope.range.min, scope.range.max],
					behaviour: 'drag-tap',
					connect: true,
					margin: scope.range.interval,
					step: scope.range.interval,
					range: { 'min': [scope.range.absMin], 'max': [scope.range.absMax] }
				});

				function synchronizeValues() {
					var values = sliderJqElement.val();
					var newMin = parseInt(values[0]);
					var newMax = parseInt(values[1]);
					if (scope.range.min != parseInt(values[0]) || scope.range.max != newMax) {
						scope.range.min = newMin;
						scope.range.max = newMax;
						scope.$digest();
					}
				}

				function synchronizeValuesAndRefresh() {
					var values = sliderJqElement.val();
					var newMin = parseInt(values[0]);
					var newMax = parseInt(values[1]);
					if (lastMin != parseInt(values[0]) || lastMax != newMax) {
						scope.range.min = lastMin = newMin;
						scope.range.max = lastMax = newMax;
						scope.$digest();
						for (var i = 0; i < scope.facet.values.length; i++) {
							var key = scope.facet.values[i].key;
							scope.facet.values[i].selected = (key >= scope.range.min && key < scope.range.max);
						}
						scope.facet.customValues = [
							{
								key: 'min',
								selected: true,
								value: scope.range.min
							},
							{
								key: 'max',
								selected: true,
								value: scope.range.max
							}
						];
						rbsElasticsearchFacetController.refresh();
					}
				}

				sliderJqElement.on({
					slide: synchronizeValues,
					set: synchronizeValuesAndRefresh,
					change: synchronizeValuesAndRefresh
				});

				if (scope.facet.parameters['sliderShowPips'] == true) {
					scope.showPips = true;

					sliderJqElement.noUiSlider_pips({
						mode: 'steps',
						values: scope.facet.values.length,
						density: scope.range.interval
					});
				}

				if (scope.facet.parameters['sliderShowTooltips'] !== false) {
					scope.showTooltips = true;

					sliderJqElement.Link('lower').to('-inline-<div class="tooltip top" role="tooltip"></div>', updateTooltip);
					sliderJqElement.Link('upper').to('-inline-<div class="tooltip top" role="tooltip"></div>', updateTooltip);
				}

				if (scope.facet.parameters['sliderShowLabels'] == true) {
					scope.showLabels = true;
				}

				function updateTooltip(value) {
					var formattedValue = $filter('rbsFormatPrice')(value, null, 0);
					jQuery(this).html(
						'<div class="tooltip-arrow"></div>' +
						'<div class="tooltip-inner">' +
						'<span>' + formattedValue + '</span>' +
						'</div>'
					);
				}
			}
		}
	}]);
})(window.jQuery);