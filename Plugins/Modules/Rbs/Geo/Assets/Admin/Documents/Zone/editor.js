(function (jQuery) {
	"use strict";

	var app = angular.module('RbsChange');

	function rbsDocumentEditorRbsGeoZone() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onLoad = function() {
					if (!angular.isObject(scope.document.addressFilterData) || angular.isArray(scope.document.addressFilterData)) {
						scope.document.addressFilterData = {};
					}
				};
			}
		}
	}

	app.directive('rbsDocumentEditorRbsGeoZoneNew', rbsDocumentEditorRbsGeoZone);
	app.directive('rbsDocumentEditorRbsGeoZoneEdit', rbsDocumentEditorRbsGeoZone);

	app.directive('rbsGeoAddressFilterDefinitions', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Geo/addressFiltersDefinition.twig',
			replace: true,
			link: function(scope, element, attrs, filterContainerCtrl) {
				if (attrs.hasOwnProperty('definitions')) {
					var definitions = angular.fromJson(attrs['definitions']);
					angular.forEach(definitions, function(definition) {
						if (definition.hasOwnProperty('name') &&
							definition.hasOwnProperty('directiveName') &&
							definition.hasOwnProperty('config')) {
							if (!definition.hasOwnProperty('parameters')) {
								definition.parameters = {};
							}
							definition.label = definition.config.listLabel || definition.config.label;
							definition.group = definition.config.group;
							filterContainerCtrl.addFilterDefinition(definition);
						}
					});
				}
			}
		};
	});

	app.directive('rbsGeoAddressFilterCountryField', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Geo/Documents/Zone/rbs-geo-address-filter-country-field.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = 'eq';
				}

				scope.isConfigured = function() {
					return scope.filter.parameters.operator && scope.filter.parameters.value;
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}
		};
	});

	app.directive('rbsGeoAddressFilterZipField', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Geo/Documents/Zone/rbs-geo-address-filter-zip-field.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = 'match';
				}

				scope.isConfigured = function() {
					return scope.filter.parameters.operator && scope.filter.parameters.value;
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}
		};
	});

	app.directive('rbsGeoAddressFilterField', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Geo/Documents/Zone/rbs-geo-address-filter-field.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = 'eq';
				}

				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}
		};
	});
})(window.jQuery);