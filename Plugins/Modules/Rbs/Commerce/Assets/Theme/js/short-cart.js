(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsShortCart($rootScope, AjaxAPI) {
		var cacheCartDataKey = 'cartData';
		return {
			restrict: 'A',
			templateUrl: '/rbsShortCart.tpl',
			replace: false,
			scope: {},
			link: function(scope, elem, attrs) {
				var cacheKey = attrs['cacheKey'];
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.cart = AjaxAPI.globalVar(cacheCartDataKey);

				function loadCurrentCart() {
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, {detailed: false, visualFormats: 'listItem'});
					request.success(function(data, status, headers, config) {
						var cart = data.dataSets;
						if (!cart || angular.isArray(cart)) {
							scope.cart = null;
						} else {
							scope.cart = cart;
						}
					}).error(function(data, status, headers, config) {
						scope.cart = null;
						console.log('loadCurrentCart error', data, status);
					})
				}

				if (angular.isUndefined(scope.cart)) {
					loadCurrentCart();
				}

				$rootScope.$on('rbsRefreshCart', function(event, params) {
					scope.cart = params['cart'];
				});

				$rootScope.$on('rbsUserConnected', function(event, params) {
					loadCurrentCart();
				});
			}
		}
	}

	rbsShortCart.$inject = ['$rootScope', 'RbsChange.AjaxAPI'];
	app.directive('rbsShortCart', rbsShortCart);
})();

