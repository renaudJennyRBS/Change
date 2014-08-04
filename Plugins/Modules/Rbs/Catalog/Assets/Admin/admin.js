(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', 'RbsChange.i18n', function ($templateCache, $rootScope, $location, REST, i18n)
	{
		$templateCache.put(
			'picker-item-Rbs_Catalog_Product.html',
			'<span style="line-height: 30px"><img rbs-storage-image="item.adminthumbnail" thumbnail="XS"/> (= item.label =) <small class="text-muted">(= item.model|rbsModelLabel =)</small></span>'
		);
	}]);

	app.controller('rbsAxisAttributesSelector', function ($scope) {
		$scope.axisAttributesQuery= { "model": "Rbs_Catalog_Attribute",
			"where": {
				"and" : [
					{"op" : "eq",
						"lexp" : {"property" : "axis"},
						"rexp" : {"value": true}
					},
					{"op" : "in",
						"lexp" : {"property" : "valueType"},
						"rexp" : ["Property", "Integer", "DocumentId", "Float", "DateTime", "String"]
					}
				]
			}
		};
	});

	app.controller('rbsOtherAttributesSelector', function ($scope) {
		$scope.otherAttributesQuery= { "model": "Rbs_Catalog_Attribute",
			"where": {
				"and" : [
					{"op" : "eq",
						"lexp" : {"property" : "axis"},
						"rexp" : {"value": false}
					}
				]
			}
		};
	});

	function rbsAsideSectionProductList(REST, Query) {
		return {
			restrict: 'E',
			templateUrl: 'Rbs/Catalog/aside-section-product-list.twig',
			scope: true,
			link: function(scope) {
				scope.ready = false;
				scope.productList = null;
				scope.$watch('document.id', function(id) {
					scope.ready = false;
					if (id) {
						var query = Query.simpleQuery('Rbs_Catalog_SectionProductList', 'synchronizedSection', id);
						query.limit = 1;
						REST.query(query).then(function (docs) {
							scope.ready = true;
							if (docs && docs.resources && docs.resources.length) {
								scope.productList = docs.resources[0];
							}
						});
					}
				});


			}
		}
	}
	rbsAsideSectionProductList.$inject = ['RbsChange.REST', 'RbsChange.Query'];

	angular.module('RbsChange').directive('rbsAsideSectionProductList', rbsAsideSectionProductList);
})();