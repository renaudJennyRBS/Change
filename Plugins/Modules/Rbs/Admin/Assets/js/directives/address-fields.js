(function() {
	"use strict";

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsAddressFields
	 * @name Address fields
	 * @restrict EA
	 *
	 * @description
	 * Displays the input fields to enter an address.
	 *
	 * @param {Document} address Address Document.
	 */
	angular.module('RbsChange').directive('rbsAddressFields', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'EA',
			require: 'ngModel',
			scope: true,
			templateUrl: 'Rbs/Admin/js/directives/address-fields.twig',

			link: function(scope, elm, attrs, ngModel) {
				scope.addressFieldsId = null;
				scope.fieldsDef = [];
				scope.fieldValues = {};

				scope.$watch(attrs.addressFields, function(newValue) {
					var fieldsId = null;
					if (newValue) {
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id')) {
							fieldsId = newValue.id;
						}
						else {
							fieldsId = parseInt(newValue, 10);
							if (isNaN(fieldsId)) {
								fieldsId = null;
							}
						}
					}

					if (fieldsId != scope.addressFieldsId) {
						scope.fieldsDef = [];
						scope.addressFieldsId = fieldsId;
					}
				});

				scope.$watch('addressFieldsId', function(newValue) {
					if (newValue) {
						REST.resource('Rbs_Geo_AddressFields', newValue).then(scope.generateFieldsEditor);
					}
				});

				ngModel.$render = function ngModelRenderFn() {
					if (ngModel.$viewValue) {
						scope.fieldValues = ngModel.$viewValue;
					}
				};

				scope.generateFieldsEditor = function(addressFields) {
					scope.fieldsDef = angular.copy(addressFields.fields);
					if (angular.isArray(scope.fieldsDef)) {
						if (!angular.isObject(ngModel.$viewValue)) {
							ngModel.$setViewValue({});
						}
						var fieldValues = ngModel.$viewValue;
						var fields = scope.fieldsDef;
						var field;
						for (var i = 0; i < fields.length; i++) {
							field = fields[i];
							var currentLocalization = field.LCID[field.refLCID]; // TODO: Use current LCID of the interface.
							field.title = currentLocalization.title;
							field.matchErrorMessage = currentLocalization.matchErrorMessage;
							var v = null;
							if (fieldValues.hasOwnProperty(field.code)) {
								v = fieldValues[field.code];
							}
							if (v === null) {
								v = field.defaultValue;
								fieldValues[field.code] = v;
							}
						}
					}
				};

				scope.$watchCollection('fieldValues', function(fieldValues) {
					ngModel.$setViewValue(fieldValues);
				});
			}
		};
	}]);
})();