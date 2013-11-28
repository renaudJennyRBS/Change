(function () {

	"use strict";

	var app = angular.module('RbsChange');

	function VariantListController(scope, routeParams)
	{
		console.log('VariantListController', scope, routeParams);
		scope.loadQuery = {
			"model": "Rbs_Catalog_Product",
			"where": {
				"and" : [
					{
						"op" : "eq",
						"lexp" : {
							"property" : "variant"
						},
						"rexp" : {
							"value": "true"
						}
					},
					{
						"op" : "eq",
						"lexp" : {
							"property" : "variantGroup"
						},
						"rexp" : {
							"value": routeParams.id
						}
					}
				]
			}
		}
	}
	VariantListController.$inject = ['$scope', '$routeParams'];
	app.controller('Rbs_Catalog_VariantGroup_VariantListController', VariantListController);

})();