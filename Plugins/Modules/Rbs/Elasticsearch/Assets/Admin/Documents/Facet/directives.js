(function() {
	"use strict";

	function rbsElasticsearchFacetAttribute() {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Elasticsearch/Documents/Facet/attribute-configuration.twig',
			scope: { facet: '=' },
			link: function(scope) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				var parameters = scope.facet.parameters;
				if (!angular.isDefined(parameters.renderingMode)) {
					parameters.renderingMode = 'checkbox';
				}
				if (!angular.isDefined(parameters.showEmptyItem)) {
					parameters.showEmptyItem = false;
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}

	angular.module('RbsChange').directive('rbsElasticsearchFacetAttribute', rbsElasticsearchFacetAttribute);

	function rbsElasticsearchFacetPrice() {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Elasticsearch/Documents/Facet/price-configuration.twig',
			scope: { facet: '=' },
			link: function(scope) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				var parameters = scope.facet.parameters;
				if (!angular.isDefined(parameters.withTax)) {
					parameters.withTax = false;
				}
				if (!angular.isDefined(parameters.interval)) {
					parameters.interval = 50;
				}
				if (!angular.isDefined(parameters.renderingMode)) {
					parameters.renderingMode = 'checkbox';
				}
				if (!angular.isDefined(parameters.showEmptyItem)) {
					parameters.showEmptyItem = false;
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}

	angular.module('RbsChange').directive('rbsElasticsearchFacetPrice', rbsElasticsearchFacetPrice);

	function rbsElasticsearchFacetSkuthreshold() {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Elasticsearch/Documents/Facet/sku-threshold-configuration.twig',
			scope: { facet: '=' },
			link: function(scope) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				var parameters = scope.facet.parameters;
				if (!angular.isDefined(parameters.renderingMode)) {
					parameters.renderingMode = 'checkbox';
				}
				if (!angular.isDefined(parameters.showEmptyItem)) {
					parameters.showEmptyItem = false;
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}

	angular.module('RbsChange').directive('rbsElasticsearchFacetSkuthreshold', rbsElasticsearchFacetSkuthreshold);
})();


