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
			templateUrl : 'Rbs/Price/price-input.twig',
			require: 'ngModel',
			scope: true,

			link : function (scope, elm, attrs, ngModel) {

				// If 'id' and 'input-id' attributes are found are equal, move this id to the real input field
				// so that the binding with the '<label/>' element works as expected.
				// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
				if (attrs.id && attrs.id === attrs.inputId) {
					elm.find('.input-append input[type=text]').attr('id', attrs.id);
					elm.removeAttr('id');
					elm.removeAttr('input-id');
				}

				attrs.$observe('disabled', function(value){
					scope.disabled = angular.isDefined(value) && (value !== "false" && value !== false);
				});

				attrs.$observe('required', function(value){
					scope.required = angular.isDefined(value) && value != "false";
				});

				if (angular.isDefined(attrs.currencyCode))
				{
					scope.$watch(attrs.currencyCode, function (value){
						scope.currencyCode = value;
					}, true);
				}

				if (angular.isDefined(attrs.priceWithTax))
				{
					scope.$watch(attrs.priceWithTax, function (value){
						scope.priceWithTax = value;
					}, true);
				}

				ngModel.$render = function () {
					scope.value = ngModel.$viewValue;
				};

				scope.$watch('value', function (value){
					ngModel.$setViewValue(value);
				});
			}
		}
	}
})(window.jQuery);