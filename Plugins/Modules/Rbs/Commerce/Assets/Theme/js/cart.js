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

	function rbsCommerceCartLine() {
		return {
			restrict : 'AE',
			templateUrl : '/simpleLine.static.tpl',
			link : function (scope, elm, attrs) {
				scope.originalQuantity = scope.line.quantity;
			}
		}
	}

	app.directive('rbsCommerceCartLine', rbsCommerceCartLine);

	function rbsCommerceCartController(scope, $http) {
		scope.readonly = false;
		scope.cart = null;
		scope.loading = false;

		console.log('Init rbsCommerceCartController');

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false})
				.success(function(data) {
					console.log('GetCurrentCart success');
					scope.loading = false;
					scope.cart = data;
				})
				.error(function(data, status, headers) {
					console.log('GetCurrentCart error', data, status, headers);
					scope.loading = false;
					scope.cart = {};
				}
			);
		}

		scope.deleteLine = function(index) {
			scope.loading = true;
			var line = scope.cart.lines[index];
			$http.post('Action/Rbs/Commerce/UpdateCartLine', {lineKey: line.key, delete: true})
				.success (function(data) {
				console.log('UpdateCartLine success');
					scope.loading = false;
					scope.cart = data;
				})
				.error(function(data, status, headers) {
					console.log('UpdateCartLine error', data, status, headers);
				}
			);
		};

		scope.updateLine = function(index) {
			scope.loading = true;
			var line = scope.cart.lines[index];
			$http.post('Action/Rbs/Commerce/UpdateCartLine', {lineKey: line.key, quantity: line.quantity})
				.success (function(data) {
				console.log('UpdateCartLine success');
					scope.loading = false;
					scope.cart = data;
				})
				.error(function(data, status, headers) {
					console.log('UpdateCartLine error', data, status, headers);
				}
			);
		};

		loadCurrentCart();
	}

	rbsCommerceCartController.$inject = ['$scope', '$http'];
	app.controller('rbsCommerceCartController', rbsCommerceCartController);
})();