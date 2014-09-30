(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');

	/**
	 * Cart controller.
	 */
	function rbsGeoManageAddressesController(scope, $http) {
		scope.data = {
			addresses: [],
			newAddress: null,
			isNewAddressValid: false,
			editedAddress: null,
			isEditedAddressValid: false
		};

		$http.get('Action/Rbs/Geo/GetAddresses')
			.success(function(data) {
				scope.data.addresses = data;
			})
			.error(function(data, status, headers) {
				console.log('GetAddresses error', data, status, headers);
			}
		);

		scope.openEditAddressForm = function (address) {
			scope.data.editedAddress = address;
		};

		scope.cancelEdition = function () {
			scope.data.editedAddress = null;
		};

		scope.setDefaultAddress = function (address, defaultFor) {
			$http.post('Action/Rbs/Geo/SetDefaultAddress', { id: address.fieldValues['__id'], defaultFor: defaultFor })
				.success(function(data) {
					scope.data.addresses = data;
				})
				.error(function(data, status, headers) {
					console.log('SetDefaultAddress error', data, status, headers);
				}
			);
		};

		scope.updateAddress = function () {
			$http.post('Action/Rbs/Geo/UpdateAddress', scope.data.editedAddress)
				.success(function(data) {
					scope.data.addresses = data;
					scope.data.editedAddress = null;
				})
				.error(function(data, status, headers) {
					console.log('UpdateAddress error', data, status, headers);
				}
			);
		};

		scope.deleteAddress = function (address) {
			$http.post('Action/Rbs/Geo/DeleteAddress', { id: address.fieldValues['__id'] })
				.success(function(data) {
					scope.data.addresses = data;
				})
				.error(function(data, status, headers) {
					console.log('DeleteAddress error', data, status, headers);
				}
			);
		};

		scope.openNewAddressForm = function () {
			scope.data.newAddress = {
				name: '',
				fieldValues: {}
			};
		};

		scope.cancelCreation = function () {
			scope.data.newAddress = null;
		};

		scope.addNewAddress = function () {
			$http.post('Action/Rbs/Geo/AddAddress', scope.data.newAddress)
				.success(function(data) {
					scope.data.addresses = data;
					scope.data.newAddress = null;
				})
				.error(function(data, status, headers) {
					console.log('AddAddress error', data, status, headers);
				}
			);
		};
	}

	rbsGeoManageAddressesController.$inject = ['$scope', '$http'];
	app.controller('rbsGeoManageAddressesController', rbsGeoManageAddressesController);
})();

