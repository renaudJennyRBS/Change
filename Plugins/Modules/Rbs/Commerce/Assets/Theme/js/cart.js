(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceCartData() {
		return {
			restrict : 'C',
			template : '<div></div>',
			replace : true,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				var cart;
				if (attrs.hasOwnProperty('cart')) {
					cart = angular.fromJson(attrs.cart);
				}
				if (!angular.isObject(cart)) {
					cart = {};
				}
				ngModel.$setViewValue(cart);
			}
		}
	}

	app.directive('rbsCommerceCartData', rbsCommerceCartData);

	function rbsCommerceCartController(scope, $http) {
		scope.cart = null;
		scope.loading = false;

		console.log('Init rbsCommerceCartController');

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false}).success(function(data) {
				console.log('GetCurrentCart success');
				scope.loading = false;
				scope.cart = data;
			}).error(function(data, status, headers) {
				console.log('GetCurrentCart error', data, status, headers);
				scope.loading = false;
				scope.cart = {};
			});
		}

		loadCurrentCart();
	}

	rbsCommerceCartController.$inject = ['$scope', '$http'];
	app.controller('rbsCommerceCartController', rbsCommerceCartController);
})();