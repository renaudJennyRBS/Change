(function (jQuery)
{
	"use strict";

	function rbsElasticsearchFacetAttribute() {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Elasticsearch/Documents/Facet/attribute-configuration.twig',
			scope: {facet:'='},
			link : function (scope, element, attrs) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}
	angular.module('RbsChange').directive('rbsElasticsearchFacetAttribute', rbsElasticsearchFacetAttribute);

	function rbsElasticsearchFacetPrice () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Elasticsearch/Documents/Facet/price-configuration.twig',
			scope: {facet:'='},
			link : function (scope, element, attrs) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}
	angular.module('RbsChange').directive('rbsElasticsearchFacetPrice', rbsElasticsearchFacetPrice);

	function rbsElasticsearchFacetSkuthreshold () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Elasticsearch/Documents/Facet/sku-threshold-configuration.twig',
			scope: {facet:'='},
			link : function (scope, element, attrs) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				scope.facet.indexCategory = 'store';
			}
		}
	}
	angular.module('RbsChange').directive('rbsElasticsearchFacetSkuthreshold', rbsElasticsearchFacetSkuthreshold);

})(window.jQuery);


