(function () {

	"use strict";

	function rbsOrderOrderEditorShippingModes ( REST, $filter)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Order/Order/shippingModes.twig',
			scope : {
				'addressDocuments' : "=",
				'shippingData' : "=",
				'linesData' : "="
			},

			link : function (scope, element, attrs)
			{

				scope.showShippingAddressUI = false;
				scope.shippingAddress = {};
				scope.editedShippingMode = {};
				scope.shippingDetails = {};

				scope.refreshShippingModes = function()
				{
					if(!angular.isObject(scope.shippingData)){
						scope.shippingData = [];
					}
					var shippingModes = scope.shippingData;
					angular.forEach(shippingModes, function (shippingMode) {
						shippingMode.lines = [];
					});
					angular.forEach(scope.linesData, function (line) {
						var shippingModeId = line.options.shippingMode;
						if(shippingModeId){
							var matchingShippingModes = $filter('filter')(shippingModes, {'id': shippingModeId});
							if(matchingShippingModes.length){
								angular.forEach(matchingShippingModes, function (shippingMode) {
									shippingMode.lines.push(line.index);
								});
							}
							else{
								shippingModes.push({'id': shippingModeId, lines: [line.index]});
							}
						}
					});
				};

				scope.populateShippingDetails = function(response) {
					var shippingDetails = {};
					angular.forEach(response.resources, function (shippingDoc) {
						shippingDetails[shippingDoc.id] = shippingDoc;
					});
					scope.shippingDetails = shippingDetails;
				};

				scope.editShippingAddress = function (shippingId) {
					angular.forEach(scope.shippingData, function (shipping){
						if(shipping.id == shippingId){
							if(!angular.isObject(shipping.address))
							{
								shipping.address = {};
							}
							scope.editedShippingMode = shipping;
						}
					});
					scope.showShippingAddressUI = true;
				};

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('shippingData', function (shippingData, old) {
					if(angular.isObject(shippingData) && !angular.isObject(old)){
						REST.collection('Rbs_Shipping_Mode').then(scope.populateShippingDetails);
					}
				}, true);

				// This refreshes shippingDataObject to be synchronized with parent scope in order editor
				scope.$watchCollection('shippingData', function (shippingData, old) {
					scope.shippingData = shippingData;
					if(angular.isObject(scope.editedShippingMode) && scope.editedShippingMode.id){
						scope.editShippingAddress(scope.editedShippingMode.id);
					}
				});

				// This refreshes shippingDataObject to be synchronized with order editor
				scope.$on('shippingModesUpdated', function (event) {
					scope.refreshShippingModes();
				});

			}
		};
	}

	rbsOrderOrderEditorShippingModes.$inject = [ 'RbsChange.REST', '$filter' ];
	angular.module('RbsChange').directive('rbsOrderShippingModes', rbsOrderOrderEditorShippingModes);

})();