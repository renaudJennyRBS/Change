(function ($) {

	"use strict";

	angular.module('RbsChange').directive('rbsAddressFields', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		return {
			restrict    : 'EAC',
			require     : 'ngModel',
			scope       : true,
			templateUrl : 'Rbs/Admin/js/directives/address-fields.twig',

			link : function (scope, elm, attrs, ngModel) {

				scope.addressFieldsId = null;
				scope.fieldsDef = [];
				scope.fieldValues = {};

				scope.$watch(attrs.addressFields, function(newValue){
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

				ngModel.$render = function ngModelRenderFn () {
					scope.fieldValues = ngModel.$viewValue;
				};

				scope.generateFieldsEditor = function (addressFields) {
					var editorDefinition = addressFields.editorDefinition;
					if (angular.isObject(editorDefinition)) {
						if (!angular.isObject(ngModel.$viewValue)) {
							ngModel.$setViewValue({});
						}
						scope.fieldsDef = editorDefinition.fields;
						var fieldValues = ngModel.$viewValue;
						var fields = scope.fieldsDef;
						var field;
						for (var i = 0; i < fields.length; i++) {
							field = fields[i];
							var v = null;
							if(fieldValues.hasOwnProperty(field.code)) {
								v = fieldValues[field.code];
							}
							if(v === null) {
								v = field.defaultValue;
								fieldValues[field.code] = v;
							}
						}

						if (angular.isObject(addressFields.fieldsLayout)) {
							fieldValues.__layout = addressFields.fieldsLayout;
						}
						else {
							fieldValues.__layout = undefined;
						}
					}
				};
			}
		};
	}]);

})(window.jQuery);