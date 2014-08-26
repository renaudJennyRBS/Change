(function (jQuery)
{
	"use strict";

	function rbsDiscountFreeShippingFee ($routeParams, REST) {

		return {
			restrict : 'A',
			templateUrl : 'Rbs/Discount/freeShippingFee.twig',
			scope: {discount:'=', parameters:'='},
			link : function (scope, element, attrs) {
				if (!scope.parameters.hasOwnProperty('shippingMode')) {
					scope.parameters['shippingMode'] = null;
				}
			}
		}
	}

	rbsDiscountFreeShippingFee.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDiscountFreeShippingFee', rbsDiscountFreeShippingFee);


	function rbsDiscountRowsFixed ($routeParams, REST) {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Discount/rowsFixed.twig',
			scope: {discount:'=', parameters:'='},
			link : function (scope, element, attrs) {
				if (!scope.parameters.hasOwnProperty('amount')) {
					scope.parameters['amount'] = 10.0;
				}
				if (!scope.parameters.hasOwnProperty('withTax')) {
					scope.parameters['withTax'] = true;
				}
			}
		}
	}

	rbsDiscountRowsFixed.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDiscountRowsFixed', rbsDiscountRowsFixed);

	function rbsDiscountRowsPercent ($routeParams, REST) {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Discount/rowsPercent.twig',
			scope: {discount:'=', parameters:'='},
			link : function (scope, element, attrs) {
				if (!scope.parameters.hasOwnProperty('percent')) {
					scope.parameters['percent'] = 5;
				}
			}
		}
	}

	rbsDiscountRowsPercent.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDiscountRowsPercent', rbsDiscountRowsPercent);
})(window.jQuery);