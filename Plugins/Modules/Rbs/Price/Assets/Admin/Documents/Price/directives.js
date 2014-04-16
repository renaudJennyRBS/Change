(function (jQuery)
{
	"use strict";

	function rbsPriceModifierReservationQuantity () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Price/Documents/Price/modifierReservationQuantity.twig',
			scope: {options:'=', price:'=', priceContext:'='},
			link : function (scope, element, attrs) {
				if (!angular.isArray(scope.options['thresholds']) || scope.options['thresholds'].length == 0) {
					scope.options['thresholds'] = [{l:10, v:null}];
				}

				scope.sortThreshold = function() {
					var array = scope.options['thresholds'];
					scope.options['thresholds'] = array.sort(function(a, b) {
						return (a.l === b.l ? 0 : (a.l < b.l ? -1 : 1));
					});
				};

				scope.removeThreshold = function(index) {
					var old = scope.options['thresholds'],
						newThresholds = [],
						i;

					for (i = 0; i < old.length; i++)
					{
						if (index !== i)
						{
							newThresholds.push(old[i]);
						}
					}
					scope.options['thresholds'] = newThresholds;
				};

				scope.addThreshold = function() {
					var thresholds = scope.options['thresholds'], i, l = 10;
					for (i = 0; i < thresholds.length; i++) {
						if (thresholds[i].l && thresholds[i].l > l) {
							l = thresholds[i].l;
						}
					}
					thresholds.push({l:l + 10, v : null});
					scope.options['thresholds'] = thresholds;
				}
			}
		}
	}
	angular.module('RbsChange').directive('rbsPriceModifierReservationQuantity', rbsPriceModifierReservationQuantity);


	function rbsPriceModifierLinesAmount () {
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Price/Documents/Price/modifierLinesAmount.twig',
			scope: {options:'=', price:'=', priceContext:'='},
			link : function (scope, element, attrs) {
				if (!angular.isArray(scope.options['thresholds']) || scope.options['thresholds'].length == 0) {
					scope.options['thresholds'] = [{l:10, v:null}];
				}

				scope.sortThreshold = function() {
					var array = scope.options['thresholds'];
					scope.options['thresholds'] = array.sort(function(a, b) {
						return (a.l === b.l ? 0 : (a.l < b.l ? -1 : 1));
					});
				};

				scope.removeThreshold = function(index) {
					var old = scope.options['thresholds'],
						newThresholds = [],
						i;

					for (i = 0; i < old.length; i++)
					{
						if (index !== i)
						{
							newThresholds.push(old[i]);
						}
					}
					scope.options['thresholds'] = newThresholds;
				};

				scope.addThreshold = function() {
					var thresholds = scope.options['thresholds'], i, l = 10;
					for (i = 0; i < thresholds.length; i++) {
						if (thresholds[i].l && thresholds[i].l > l) {
							l = thresholds[i].l;
						}
					}
					thresholds.push({l:l + 10, v : null});
					scope.options['thresholds'] = thresholds;
				}
			}
		}
	}
	angular.module('RbsChange').directive('rbsPriceModifierLinesAmount', rbsPriceModifierLinesAmount);

	function rbsAsidePrice() {
		return {
			restrict: 'E',
			templateUrl: 'Rbs/Price/Documents/Price/aside.twig'
		}
	}

	angular.module('RbsChange').directive('rbsAsidePrice', rbsAsidePrice);
})(window.jQuery);


