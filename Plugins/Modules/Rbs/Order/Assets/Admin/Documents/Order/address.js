(function() {
	"use strict";

	function rbsOrderOrderEditorAddress() {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Order/Order/address.twig',
			scope: {
				'addressDocuments': "=",
				'address': "="
			},

			link: function(scope, element, attrs) {
				if (!angular.isObject(scope.address)) {
					scope.address = { common: { addressFieldsId: null } };
				}

				scope.populateAddressFields = function(addressDoc) {
					if (angular.isObject(addressDoc)) {
						var addressFields = addressDoc.addressFields;
						if (angular.isObject(addressFields)) {
							scope.address.fields = angular.copy(addressDoc.fieldValues);
							scope.address.common.addressFieldsId = addressFields.id;
						}
					}
				};

				// This watches for modifications in the address doc in order to fill the address form.
				scope.$watch('address.common.id', function(addressId) {
					if (addressId) {
						angular.forEach(scope.addressDocuments, function(addressDoc) {
							if (addressDoc.id == addressId) {
								scope.populateAddressFields(addressDoc);
							}
						});
					}
				}, true);

				// If a value is changed, clear the address id (the id is preserved only if the address is not modified).
				scope.$watch('address.fields', function(fields) {
					var addressId = scope.address.common.id;
					if (addressId) {
						angular.forEach(scope.addressDocuments, function(addressDoc) {
							if (addressDoc.id == addressId) {
								angular.forEach(addressDoc.fieldValues, function(fieldValue, fieldName) {
									if (fieldName.substring(0, 2) !== '__' && fields[fieldName] != fieldValue) {
										scope.address.common.id = null;
									}
								});
							}
						});
					}
				}, true);
			}
		};
	}

	angular.module('RbsChange').directive('rbsOrderAddress', rbsOrderOrderEditorAddress);
})();