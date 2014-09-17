(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsShippingFeesEvaluation($http, $sce) {
		return {
			restrict: 'A',
			templateUrl: 'Theme/Rbs/Base/Rbs_Commerce/shipping-fees-evaluation.twig',
			replace: false,
			scope: false,

			link: function(scope, elm, attrs) {
				scope.displayPrices = attrs.hasOwnProperty('displayPrices');
				scope.displayPricesWithTax = attrs.hasOwnProperty('displayPricesWithTax');
				scope.data = null;
				scope.currentCountry = null;
				scope.currentShippingModes = [];

				$http.post('Action/Rbs/Commerce/GetShippingFeesEvaluation', {refresh: false})
					.success(function(data) {
						scope.data = data;
						if (scope.data.length == 0)
						{
							scope.data = null;
						}
						if (scope.data.countriesCount == 1){
							scope.currentCountry = scope.data.countries[0].code;
						}
					})
					.error(function(data, status, headers) {
						console.log('GetShippingFeesEvaluation error', data, status, headers);
						scope.data = null;
					}
				);

				scope.$watch('currentCountry', function(){
					scope.currentShippingModes = [];
					if (scope.currentCountry != null)
					{
						for (var i = 0; i < scope.data.shippingModes.length; i++)
						{
							if (scope.data.shippingModes[i].countries.hasOwnProperty(scope.currentCountry))
							{
								scope.currentShippingModes.push(scope.data.shippingModes[i]);
							}
						}
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsShippingFeesEvaluation.$inject = ['$http', '$sce'];
	app.directive('rbsShippingFeesEvaluation', rbsShippingFeesEvaluation);
})();

