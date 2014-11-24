(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');


	function rbsGeoManageAddressesController(scope, AjaxAPI) {
		scope.data = {
			addresses: [],
			newAddress: null,
			isNewAddressValid: false,
			editedAddress: null,
			isEditedAddressValid: false
		};

		function loadAddresses() {
			AjaxAPI.getData('Rbs/Geo/Address/', {})
				.success(function(data, status, headers, config) {
					scope.data.addresses = data.items;
				}).error(function(data, status, headers, config) {
					scope.data.addresses = [];
					console.log('loadAddresses error', data, status, headers);
				});
		}

		scope.openEditAddressForm = function (address) {
			scope.data.editedAddress = address;
		};

		scope.cancelEdition = function () {
			scope.data.editedAddress = null;
		};

		scope.setDefaultAddress = function (address, defaultFor) {
			var id = address.common.id;
			address.default[defaultFor] = true;
			AjaxAPI.putData('Rbs/Geo/Address/' + id, address)
				.success(function(data) {
					loadAddresses();
				})
				.error(function(data, status, headers) {
					console.log('setDefaultAddress error', data, status, headers);
				}
			);
		};

		scope.updateAddress = function () {
			var id = scope.data.editedAddress.common.id;
			AjaxAPI.putData('Rbs/Geo/Address/' + id, scope.data.editedAddress)
				.success(function(data) {
					var addedAddress = data.dataSets;
					var addresses = [];
					angular.forEach(scope.data.addresses, function(address) {
						if (address.common.id == id) {
							addresses.push(addedAddress);
						} else {
							addresses.push(address);
						}
					});
					scope.data.addresses = addresses;
					scope.data.editedAddress = null;
				})
				.error(function(data, status, headers) {
					console.log('updateAddress error', data, status, headers);
				}
			);
		};

		scope.deleteAddress = function (address) {
			var id = address.common.id;
			AjaxAPI.deleteData('Rbs/Geo/Address/' + id, scope.data.editedAddress)
				.success(function(data) {
					var addresses = [];
					angular.forEach(scope.data.addresses, function(address) {
						if (address.common.id != id) {
							addresses.push(address);
						}
					});
					scope.data.addresses = addresses;
				})
				.error(function(data, status, headers) {
					console.log('deleteAddress error', data, status, headers);
				}
			);
		};

		scope.openNewAddressForm = function () {
			scope.data.newAddress = {
				common: {name:null},
				fields: {}
			};
		};

		scope.clearAddress = function () {
			scope.data.newAddress.fields = {
				countryCode: scope.data.newAddress.fields.countryCode
			};
		};

		scope.cancelCreation = function () {
			scope.data.newAddress = null;
		};

		scope.addNewAddress = function () {
			AjaxAPI.postData('Rbs/Geo/Address/', scope.data.newAddress)
				.success(function(data) {
					var addedAddress = data.dataSets;
					scope.data.addresses.push(addedAddress);
					scope.data.newAddress = null;
				})
				.error(function(data, status, headers) {
					console.log('addNewAddress error', data, status, headers);
				}
			);
		};

		loadAddresses();
	}

	rbsGeoManageAddressesController.$inject = ['$scope', 'RbsChange.AjaxAPI'];
	app.controller('rbsGeoManageAddressesController', rbsGeoManageAddressesController);
})();

