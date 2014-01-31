(function () {

	"use strict";

	var app = angular.module('RbsChange');

	function AttributeController($scope)
	{
		$scope.params = {};
		$scope.List = {};

		$scope.loadQuery = {
			"model": "Rbs_Catalog_Attribute",

			"where": {
				"and" : [
					{
						"op" : "notExists",
						"exp" : {
							"model" : "Rbs_Catalog_VariantGroup",
							"property" : "groupAttribute"
						}
					}
				]
			}
		};
	}

	AttributeController.$inject = ['$scope'];
	app.controller('Rbs_Catalog_Attribute_AttributeController', AttributeController);

})();