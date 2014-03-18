(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsShortCart($rootScope) {
		return {
			restrict: 'A',
			templateUrl: '/shortCart.tpl',
			replace: false,
			scope: false,
			transclude: true,

			link: function(scope) {

				$rootScope.$on('rbsRefreshCart', function(event, params)
				{;
					scope.cart = params['cart'];
				});

			}
		}
	}

	rbsShortCart.$inject = ['$rootScope'];
	app.directive('rbsShortCart', rbsShortCart);
})();

