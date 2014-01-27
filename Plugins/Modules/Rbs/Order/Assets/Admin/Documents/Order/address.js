(function () {

	"use strict";

	function rbsOrderOrderEditorAddress ()
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Order/Order/address.twig',
			scope : {
				'addressDocuments' : "=",
				'addressFields' : "=",
				'address': "="
			},

			link : function (scope, element, attrs)
			{
				if (!angular.isObject(scope.address)) {
					scope.address = {};
				}

				scope.populateAddressFields = function(addressDoc) {
					if (angular.isObject(addressDoc)){
						var addressFields = addressDoc.addressFields;
						if (angular.isObject(addressFields)) {
							scope.address = angular.copy(addressDoc.fieldValues);
							scope.address.__addressFieldsId = addressFields.id;
						}
					}
				};

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('address.__id', function (addressId) {
					if (addressId) {
						angular.forEach(scope.addressDocuments, function(addressDoc){
							if (addressDoc.id == addressId) {
								scope.populateAddressFields(addressDoc);
							}
						});
					}
				}, true);

				// If user select no option for addressFields, empty the address
				scope.$watch('addressFields', function (addressFields) {
					if (addressFields)
					{
						if (angular.isObject(addressFields)) {
							scope.address.__addressFieldsId = addressFields.id;
						} else {
							scope.address.__addressFieldsId = addressFields;
						}
					}
				});
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderAddress', rbsOrderOrderEditorAddress);

})();