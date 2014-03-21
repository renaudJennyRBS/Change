(function () {

	"use strict";

	var app = angular.module('RbsChange');

	function RbsCommerceCartList(scope, REST) {
		scope.filter = {};
		scope.collection = {};

		scope.refreshCollection = function(params) {
			var url = REST.getBaseUrl('commerce/cart/');
			if (!angular.isObject(params)) {
				params = {};
			}

			REST.call(url, params).then(function (collection) {
				scope.collection = collection;
			});
		};

		scope.refreshCollection();
	}

	RbsCommerceCartList.$inject = ['$scope', 'RbsChange.REST'];
	app.controller('RbsCommerceCartList', RbsCommerceCartList);

	function RbsCommerceCartEdit(scope, $http, REST, $routeParams, $filter) {
		scope.cart = {};
		REST.call(REST.getBaseUrl('commerce/cart/' + $routeParams.identifier), {}).then(function (result) {
			scope.cart = result.cart;
		});

		scope.getLinesNumbers = function(shippingMode) {
			var matchingLines, lineNumbers = [];
			angular.forEach(shippingMode.lineKeys, function(lineKey) {
				matchingLines = $filter('filter')(scope.cart.lines, { key: lineKey });
				angular.forEach(matchingLines, function(line) {
					lineNumbers.push(line.index + 1);
				});
			});
			return lineNumbers.join(', ');
		};
	}

	RbsCommerceCartEdit.$inject = ['$scope', '$http', 'RbsChange.REST', '$routeParams', '$filter'];
	app.controller('RbsCommerceCartEdit', RbsCommerceCartEdit);

})();