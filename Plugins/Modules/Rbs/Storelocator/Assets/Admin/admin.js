(function (jQuery)
{
	"use strict";


	function rbsElasticsearchFacetStorelocatorterritorialunit () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Storelocator/facet-territorial-unit.twig',
			scope: {facet:'='},
			link : function (scope, element, attrs) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				var parameters = scope.facet.parameters;
				if (!angular.isDefined(parameters.unitType)) {
					parameters.unitType = 'DEPARTEMENT';
				}

				if (!angular.isDefined(parameters.multipleChoice)) {
					parameters.multipleChoice = false;
				}
				if (!angular.isDefined(parameters.showEmptyItem)) {
					parameters.showEmptyItem = false;
				}
				scope.facet.indexCategory = 'storeLocator';
			}
		}
	}
	angular.module('RbsChange').directive('rbsElasticsearchFacetStorelocatorterritorialunit', rbsElasticsearchFacetStorelocatorterritorialunit);

	function rbsElasticsearchFacetStorelocatorcountry () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Storelocator/facet-country.twig',
			scope: {facet:'='},
			link : function (scope, element, attrs) {
				if (!angular.isObject(scope.facet.parameters) || angular.isArray(scope.facet.parameters)) {
					scope.facet.parameters = {};
				}
				var parameters = scope.facet.parameters;
				if (!angular.isDefined(parameters.multipleChoice)) {
					parameters.multipleChoice = false;
				}
				if (!angular.isDefined(parameters.showEmptyItem)) {
					parameters.showEmptyItem = false;
				}
				scope.facet.indexCategory = 'storeLocator';
			}
		}
	}
	angular.module('RbsChange').directive('rbsElasticsearchFacetStorelocatorcountry', rbsElasticsearchFacetStorelocatorcountry);
})(window.jQuery);