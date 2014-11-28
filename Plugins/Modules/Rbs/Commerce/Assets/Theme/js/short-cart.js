(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceShortCart($rootScope, AjaxAPI) {
		var cacheCartDataKey = 'cartData';
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShortCart.tpl',
			replace: false,
			scope: {},
			link: function(scope, elem, attrs) {
				var cacheKey = attrs['cacheKey'];
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.cartData = AjaxAPI.globalVar(cacheCartDataKey);

				var cartParams = {
					detailed: false,
					URLFormats: 'canonical',
					visualFormats: scope.parameters['imageFormats']
				};

				scope.loading = false;
				scope.readOnly = true;
				if (angular.isUndefined(scope.cartData)) {
					scope.readOnly = false;
					loadCurrentCart();
				}

				function loadCurrentCart() {
					scope.loading = true;
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, cartParams);
					request.success(function(data) {
						var cart = data.dataSets;
						if (!cart || angular.isArray(cart)) {
							scope.cartData = null;
						}
						else {
							scope.cartData = cart;
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						scope.cartData = null;
						scope.loading = false;
						console.log('loadCurrentCart error', data, status, headers, config);
					})
				}

				function updateCartData(actions) {
					scope.loading = true;
					AjaxAPI.openWaitingModal(attrs['deleteProductWaitingMessage']);
					var request = AjaxAPI.putData('Rbs/Commerce/Cart', actions, cartParams);
					request.success(function(data) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							scope.cartData = cartData;
						}
						elem.find('.dropdown-toggle').dropdown('toggle');
						scope.loading = false;
						AjaxAPI.closeWaitingModal();
					}).error(function(data, status, headers, config) {
						console.log('updateCartData error', data, status, headers, config);
						scope.loading = false;
						AjaxAPI.closeWaitingModal();
					});
					return request;
				}

				scope.updateLineQuantity = function updateLineQuantity(key, newQuantity) {
					var actions = {
						'updateLinesQuantity': [
							{ key: key, quantity: newQuantity }
						]
					};
					updateCartData(actions);
				};

				$rootScope.$on('rbsRefreshCart', function onRbsRefreshCart(event, params) {
					scope.cartData = params['cart'];
					scope.loading = false;
				});

				$rootScope.$on('rbsUserConnected', function onRbsUserConnected(event, params) {
					loadCurrentCart();
				});
			}
		}
	}

	rbsCommerceShortCart.$inject = ['$rootScope', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceShortCart', rbsCommerceShortCart);
})();

