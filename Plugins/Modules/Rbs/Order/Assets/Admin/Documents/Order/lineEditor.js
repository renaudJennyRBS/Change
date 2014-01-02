(function () {

	"use strict";

	function rbsOrderOrderEditorLineEditor ($q, Events, REST, $http)
	{
		return {
			restrict : 'E',
			templateUrl : 'Document/Rbs/Order/Order/lineEditor.twig',
			require     : 'ngModel',
			scope: {
				'priceInfo' : "="
			},

			link : function (scope, element, attrs, ngModel)
			{
				scope.doc = {};
				scope.itemTaxes = {};
				scope.edited = false;

				ngModel.$render = function ngModelRenderFn () {
					scope.doc = ngModel.$viewValue;
					var taxes = scope.doc.items[0].taxes;
					if(!angular.isObject(taxes)){
						scope.doc.items[0].taxes = [];
					}
					angular.forEach(scope.doc.items[0].taxes, function(itemTax){
						scope.itemTaxes[itemTax.taxCode] = itemTax;
					});
				};

				scope.currentTaxInfo = [];

				scope.$watch('priceInfo.taxZone', function(taxZone, oldValue){
					var taxInfo = [];
					if(taxZone){
						angular.forEach(scope.priceInfo.taxInfo, function(tax){
							if(tax.zones.indexOf(taxZone) > -1){
								taxInfo.push(tax);
								if(!angular.isObject(scope.itemTaxes[tax.code])){
									var itemTax = {taxCode : tax.code, zone: taxZone};
									scope.doc.items[0].taxes.push(itemTax);
									scope.itemTaxes[tax.code] = itemTax;
								}
							}
						});
						scope.currentTaxInfo = taxInfo;
					}
					else{
						scope.currentTaxInfo = [];
					}
				});

				scope.$watch('doc', function(doc, oldValue){
					if(angular.isObject(oldValue)){
						scope.edited = true;
					}
				});

				scope.$watch('doc.items[0].options.boPriceValue', function(value, oldValue){
					if(oldValue != undefined && value != oldValue){
						scope.doc.items[0].priceValue = undefined;
					}
				});

				scope.$on('OrderPreSave', function(event, args){
					if(!scope.edited){
						return;
					}
					var document = args['document'];
					var promises = args['promises'];
					var q = $q.defer();
					$http.post(
						REST.getBaseUrl('rbs/order/lineNormalize'),
						{
							'line' : scope.doc,
							'webStore' : document.webStoreId,
							'billingArea' : document.billingAreaId,
							'zone' : document.contextData.taxZone
						},
						REST.getHttpConfig()
					).success(function (result) {
							if (result !== null && result.line) {
								q.resolve(result);
							} else {
								q.reject(result);
							}
						})
					.error(function (result){q.reject(result)});

					q.promise.then(function(result){angular.extend(scope.doc, result.line);});

					promises.push(q.promise);
				});
			}
		};
	}
	rbsOrderOrderEditorLineEditor.$inject = [ '$q', 'RbsChange.Events', 'RbsChange.REST', '$http' ];
	angular.module('RbsChange').directive('rbsOrderLineEditor', rbsOrderOrderEditorLineEditor);
})();