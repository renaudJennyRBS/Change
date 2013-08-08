(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsPriceInput', [
		'RbsChange.REST',
		rbsPriceInputDirective
	]);

	function rbsPriceInputDirective (REST) {

		return {
			restrict : 'E',
			templateUrl : 'Rbs/Price/js/price-input.twig',
			require: 'ng-model',
			replace: 'true',
			scope:    {
				value: '=ngModel',
				currencyCode: '@currencyCode',
				priceWithTax: '@priceWithTax',
				disabled: '=ngDisabled'
			},

			// Create isolated scope

			link : function (scope, elm, attrs, ngModel) {

				// If 'id' and 'input-id' attributes are found are equal, move this id to the real input field
				// so that the binding with the '<label/>' element works as expected.
				// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
				if (attrs.id && attrs.id === attrs.inputId) {
					elm.find('.input-append input[type=text]').attr('id', attrs.id);
					elm.removeAttr('id');
					elm.removeAttr('input-id');
				}

				scope.round10CentsDown = function(){
					var num = Math.floor(scope.value * 10) / 10;
					num.toFixed(2);
					scope.value =  num;
					ngModel.$viewValue = num.toLocaleString("fr-FR");
				};

				scope.roundIntDown = function(){
					var num = Math.floor(scope.value);
					num.toFixed(2);
					scope.value = num.toLocaleString() ;
				};

				scope.round10CentsUp = function(){
					var num = Math.ceil(scope.value * 10) / 10;
					num.toFixed(2);
					scope.value = num;
				};

				scope.roundIntUp = function(){
					var num = Math.ceil(scope.value);
					num.toFixed(2);
					scope.value = num;
				};
			}
		}
	};
})(window.jQuery);