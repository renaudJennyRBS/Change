(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsGeoAddressEditor(AjaxAPI) {
		return {
			restrict: 'A',
			scope: {
				'address': '=',
				'valid': '=',
				'zoneCode': '='
			},
			templateUrl: '/rbsGeoAddressEditor.tpl',

			link: function(scope, element, attributes) {
				scope.manageName = 'none';
				scope.countries = [];
				scope.fieldsDef = [];

				if (!scope.address) {
					scope.address = {common: {addressFieldsId: null}, fields:{countryCode:null}};
				} else {
					if (!angular.isObject(scope.address.common)) {
						scope.address.common = {addressFieldsId: null};
					}
					if (!angular.isObject(scope.address.fields)) {
						scope.address.fields = {countryCode:null};
					}
				}

				if (attributes.hasOwnProperty('manageName'))  {
					if (attributes.manageName == 'optional' || attributes.manageName == 'none') {
						scope.manageName = attributes.manageName;
					} else {
						scope.manageName = 'required';
					}
				} else {
					scope.manageName = 'none';
				}

				scope.$watch('zoneCode', function(zoneCode) {
					AjaxAPI.getData('Rbs/Geo/AddressFieldsCountries/', {zoneCode: zoneCode})
						.success(function(data) {
							scope.countries = data.items;
							if (scope.address.fields.countryCode) {
								var addressFieldsId = scope.getAddressFieldsId(scope.address.fields.countryCode);
								scope.address.common.addressFieldsId = addressFieldsId;
							}
						})
						.error(function(data, status, headers) {
							console.log('addressFieldsCountries error', data, status, headers);
							scope.countries = [];
						});
				});

				scope.countryTitle = function(countryCode) {
					for (var i = 0; i < scope.countries.length; i++) {
						if (scope.countries[i]['common'].code == countryCode) {
							return scope.countries[i]['common'].title;
						}
					}
					return countryCode;
				};

				scope.getAddressFieldsId = function(countryCode) {
					for (var i = 0; i < scope.countries.length; i++) {
						if (scope.countries[i]['common'].code == countryCode) {
							return scope.countries[i]['common'].addressFieldsId;
						}
					}
					return null;
				};

				scope.$watch('countries', function(newValue) {
					if (angular.isArray(newValue) && newValue.length) {
						var addressFieldsId = null, code = null;
						angular.forEach(newValue, function(country) {
							if (code === null) {
								code = country.common.code;
							} else if (code !== country.common.code) {
								code = false;
							}

							if (addressFieldsId === null) {
								addressFieldsId = country.common.addressFieldsId;
							} else if (addressFieldsId !== country.common.addressFieldsId) {
								addressFieldsId = false;
							}
						});

						if (code) {
							scope.address.fields.countryCode = code;
						} else if (addressFieldsId) {
							scope.address.common.addressFieldsId = addressFieldsId;
						}
					}
				});

				scope.$watch('address.fields.countryCode', function(newValue) {
					if (newValue) {
						var addressFieldsId = scope.getAddressFieldsId(newValue);
						if (addressFieldsId) {
							scope.address.common.addressFieldsId = addressFieldsId;
						}
					}
				});

				scope.$watch('address.common.addressFieldsId', function(newValue) {
					if (newValue) {
						AjaxAPI.getData('Rbs/Geo/AddressFields/' + newValue, {})
							.success(function(data) {
								scope.generateFieldsEditor(data.dataSets);
							})
							.error(function(data, status, headers) {
								console.log('addressFields error', data, status, headers);
								scope.fieldsDef = [];
							});
					}
				});

				scope.$watch('addressForm.$invalid', function(newValue) {
					scope.valid = !newValue;
				});

				scope.generateFieldsEditor = function(addressFields) {
					var fieldsDef = addressFields.fields;
					if (angular.isObject(fieldsDef)) {
						scope.fieldsDef = [];
						var field;
						for (var i = 0; i < fieldsDef.length; i++) {
							field = fieldsDef[i];
							if (field.name != 'countryCode') {
								scope.fieldsDef.push(field);
								var v = null;
								if (scope.address.fields.hasOwnProperty(field.name)) {
									v = scope.address.fields[field.name];
								}
								if (v === null) {
									v = field.defaultValue;
									scope.address.fields[field.name] = v;
								}
							}
						}
					}
				};
			}
		}
	}

	rbsGeoAddressEditor.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsGeoAddressEditor', rbsGeoAddressEditor);
})();