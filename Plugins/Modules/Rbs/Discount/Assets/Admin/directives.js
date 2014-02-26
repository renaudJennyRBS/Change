(function (jQuery)
{
	"use strict";

	function RbsDiscountFreeShippingFee ($routeParams, REST) {

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

	RbsDiscountFreeShippingFee.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDiscountFreeShippingFee', RbsDiscountFreeShippingFee);
})(window.jQuery);