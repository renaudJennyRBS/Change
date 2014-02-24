(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function VariantListController(scope, $routeParams, REST) {
		scope.loaded = false;
		scope.hasJobs = false;
		REST.resource($routeParams.id).then(function(doc) {
			scope.document = doc;
			scope.hasJobs = (angular.isArray(doc.jobs) && doc.jobs.length > 0);
			scope.loaded = true;
		});

		scope.loadQuery = {
			"model": "Rbs_Catalog_Product",
			"where": {
				"and": [
					{
						"op": "eq",
						"lexp": {
							"property": "variant"
						},
						"rexp": {
							"value": "true"
						}
					},
					{
						"op": "eq",
						"lexp": {
							"property": "variantGroup"
						},
						"rexp": {
							"value": $routeParams.id
						}
					}
				]
			}
		};
	}

	VariantListController.$inject = ['$scope', '$routeParams', 'RbsChange.REST'];
	app.controller('Rbs_Catalog_VariantGroup_VariantListController', VariantListController);
})();