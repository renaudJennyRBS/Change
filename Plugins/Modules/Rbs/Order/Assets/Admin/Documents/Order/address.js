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
				scope.addressId = "";
				if (!angular.isObject(scope.address)){
					scope.address = {};
				}

				scope.populateAddressFields = function(addressDoc) {
					if(angular.isObject(addressDoc)){
						var addressFields = addressDoc.addressFields;
						if(angular.isObject(addressFields)){
							scope.addressFields = addressFields.id;
							scope.address = addressDoc.fieldValues;
						}
					}
				};

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('addressId', function (addressId, old) {
					if(addressId){
						angular.forEach(scope.addressDocuments, function(addressDoc){
							if(addressDoc.id == addressId){
								scope.populateAddressFields(addressDoc);
							}
						});
					}
				}, true);

				// If user select no option for addressFields, empty the address
				scope.$watch('addressFields', function (addressFields){
					if (!addressFields){
						scope.address = {};
					}
				})
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderAddress', rbsOrderOrderEditorAddress);

})();