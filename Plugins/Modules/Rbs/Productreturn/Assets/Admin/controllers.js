(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function rbsProductreturnOrderProductReturns(scope, REST, $routeParams, NotificationCenter, i18n, ErrorFormatter) {
		scope.shipments = [];

		if (!scope.document) {
			REST.resource('Rbs_Order_Order', $routeParams.id).then(function(order) {
				scope.document = order;
				loadReturns();
			});
		}
		else {
			loadReturns();
		}

		function loadReturns() {
			var productReturnsLink = scope.document.getLink('productReturns');
			if (productReturnsLink) {
				var successCallback = function(data) {
					scope.productReturns = data.resources;
				};
				var errorCallback = function(error) {
					NotificationCenter.error(
						i18n.trans('m.rbs.order.admin.invalid_query_order_product_returns | ucf'),
						ErrorFormatter.format(error)
					);
					console.error(error);
				};
				REST.call(productReturnsLink, { column: ['code', 'processingStatus'] })
					.then(successCallback, errorCallback);
			}
		}
	}

	rbsProductreturnOrderProductReturns.$inject = ['$scope', 'RbsChange.REST', '$routeParams', 'RbsChange.NotificationCenter',
		'RbsChange.i18n', 'RbsChange.ErrorFormatter'];
	app.controller('rbsProductreturnOrderProductReturns', rbsProductreturnOrderProductReturns);
})();