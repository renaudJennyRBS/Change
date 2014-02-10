(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', 'RbsChange.i18n', function ($templateCache, $rootScope, $location, REST, i18n)
	{
		$templateCache.put(
			'picker-item-Rbs_Catalog_Product.html',
			'<span style="line-height: 30px"><img rbs-storage-image="item.adminthumbnail" thumbnail="XS"/> (= item.label =)</span>'
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
})();