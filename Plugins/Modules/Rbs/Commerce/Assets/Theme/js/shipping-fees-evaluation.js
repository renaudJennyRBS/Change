(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function rbsShippingFeesEvaluation(AjaxAPI, $sce) {
		return {
			restrict: 'A',
			templateUrl: 'Theme/Rbs/Base/Rbs_Commerce/shipping-fees-evaluation.twig',
			replace: false,
			scope: false,

			link: function(scope, elm, attrs) {
				var visualFormats = attrs.hasOwnProperty('visualFormats')? attrs.visualFormats : 'detailThumbnail';
				scope.displayPricesWithoutTax = attrs.hasOwnProperty('displayPricesWithoutTax');
				scope.displayPricesWithTax = attrs.hasOwnProperty('displayPricesWithTax');
				scope.data = null;
				scope.currentCountry = null;
				scope.currentShippingModes = [];

				AjaxAPI.getData('Rbs/Commerce/Cart/ShippingFeesEvaluation', {}, {visualFormats: visualFormats})
					.success(function(data) {
						scope.data = data.dataSets;
						if (scope.data.countriesCount) {
							if (scope.data.countriesCount == 1) {
								scope.currentCountry = scope.data.countries[0].code;
							}
						} else {
							scope.data = null;
						}
					})
					.error(function(data, status) {
						console.log('shippingFeesEvaluation error', data, status);
						scope.data = null;
					}
				);

				scope.$watch('currentCountry', function() {
					var ids = {};
					scope.currentShippingModes = [];
					if (scope.currentCountry != null) {
						angular.forEach(scope.data.shippingModes, function(shippingMode){
							angular.forEach(shippingMode.deliveryZones, function(zone) {
								if (zone.countryCode == scope.currentCountry && !ids.hasOwnProperty(shippingMode.common.id)) {
									ids[shippingMode.common.id] = true;
									scope.currentShippingModes.push(shippingMode);
								}
							})
						});
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsShippingFeesEvaluation.$inject = ['RbsChange.AjaxAPI', '$sce'];
	app.directive('rbsShippingFeesEvaluation', rbsShippingFeesEvaluation);
})();