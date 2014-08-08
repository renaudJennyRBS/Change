(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsAddressForm($http) {
		return {
			restrict: 'AE',
			require: 'ngModel',
			scope: {
				'valid': '=',
				'addressName': '=',
				'savingCallback': '=',
				'hideClearButton': '=',
				'clearAddress': '='
			},
			transclude: true,
			templateUrl: '/address-form.static.tpl',

			link: function(scope, element, attributes, ngModel) {
				scope.countries = [];
				scope.fieldsDef = [];
				scope.fieldValues = {};
				scope.data = {};
				scope.zoneCode = attributes.zoneCode;
				scope.readonly = attributes.readonly;
				scope.manageName = attributes.hasOwnProperty('manageName');

				attributes.$observe('readonly', function(newValue) {
					scope.readonly = (newValue == 'true');
				});

				attributes.$observe('zoneCode', function(newValue) {
					scope.zoneCode = angular.fromJson(newValue);
					$http.post('Action/Rbs/Geo/GetCountriesByZoneCode', {zoneCode: scope.zoneCode})
						.success(function(data) {
							console.log('rbsAddressForm - GetCountriesByZoneCode success');
							scope.countries = data;
							if (data.length == 1) {
								scope.fieldValues.countryCode = data[0].code;
							}
						})
						.error(function(data, status, headers) {
							console.log('rbsAddressForm - GetCountriesByZoneCode error', data, status, headers);
						});
				});

				scope.countryTitle = function(countryCode) {
					for (var i = 0; i < scope.countries.length; i++) {
						if (scope.countries[i].code == countryCode) {
							return scope.countries[i].title;
						}
					}
					return countryCode;
				};

				scope.$watch('fieldValues.countryCode', function(newValue) {
					if (newValue) {
						$http.post('Action/Rbs/Geo/GetAddressFields', {countryCode: newValue})
							.success(function(data) {
								scope.generateFieldsEditor(data);
							})
							.error(function(data, status, headers) {
								console.log('rbsAddressForm - GetAddressFields error', data, status, headers);
							});
					}
				});

				scope.$watch('addressForm.$invalid', function(newValue) {
					scope.valid = !newValue;
				});

				scope.$watch('data.name', function(newValue) {
					scope.addressName = newValue;
				});

				scope.$watch('addressName', function(newValue) {
					scope.data.name = newValue;
				});

				ngModel.$render = function ngModelRenderFn() {
					scope.fieldValues = ngModel.$viewValue;
					scope.data.name = scope.addressName;
				};

				scope.generateFieldsEditor = function(addressFields) {
					var fieldsDef = addressFields.rows;
					if (angular.isObject(fieldsDef)) {
						if (!angular.isObject(ngModel.$viewValue)) {
							ngModel.$setViewValue({});
						}
						scope.fieldsDef = [];
						var fieldValues = ngModel.$viewValue;
						var field;
						for (var i = 0; i < fieldsDef.length; i++) {
							field = fieldsDef[i];
							if (field.name != 'countryCode') {
								scope.fieldsDef.push(field);
								var v = null;
								if (fieldValues.hasOwnProperty(field.name)) {
									v = fieldValues[field.name];
								}
								if (v === null) {
									v = field.defaultValue;
									fieldValues[field.name] = v;
								}
								fieldValues.__addressFieldsId = addressFields.definition;
							}
						}
					}
				};

				if (scope.clearAddress != undefined) {
					scope.clearAddress = function() {
						scope.data.name = '';
						angular.forEach(scope.fieldValues, function(value, key) {
							if (key != 'countryCode' && key != '__addressFieldsId') {
								scope.fieldValues[key] = null;
							}
							scope['addressForm'].$setPristine();
						});
					};
				}
			}
		}
	}

	rbsAddressForm.$inject = ['$http'];
	app.directive('rbsAddressForm', rbsAddressForm);
})();