(function () {

	"use strict";

	function rbsOrderOrderEditorLineEditor ($q, REST, $http)
	{
		return {
			restrict : 'E',
			templateUrl : 'Document/Rbs/Order/Order/lineEditor.twig',
			require     : 'ngModel',
			scope: {
				'priceInfo' : "="
			},

			link : function (scope, element, attrs, ngModel) {

				scope.doc = {};
				scope.edited = false;
				scope.currentTaxInfo = [];

				ngModel.$render = function ngModelRenderFn () {
					scope.doc = ngModel.$viewValue;
					var price = scope.doc.items[0].price;
					if (!angular.isObject(price.taxCategories)) {
						price.taxCategories = {};
					}
				};

				scope.$watch('doc', function(doc, oldValue){
					if(angular.isObject(oldValue)){
						scope.edited = true;
					}
				});

				scope.$watch('priceInfo.taxZone', function(taxZone) {
					var taxInfo = [];
					if (taxZone) {
						angular.forEach(scope.priceInfo.taxInfo, function(tax) {
							if (tax.zones.indexOf(taxZone) > -1) {
								taxInfo.push(tax);
							}
						});
					}
					scope.currentTaxInfo = taxInfo;
				});

				scope.$on('OrderPreSave', function(event, args){
					if (!scope.edited) {
						return;
					}
					var document = args['document'];
					var promises = args['promises'];
					var q = $q.defer();
					$http.post(REST.getBaseUrl('rbs/order/lineNormalize'),
						{
							'line' : scope.doc,
							'webStore' : document.webStoreId,
							'billingArea' : document.billingAreaId,
							'zone' : document.context.taxZone
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
	rbsOrderOrderEditorLineEditor.$inject = [ '$q', 'RbsChange.REST', '$http' ];
	angular.module('RbsChange').directive('rbsOrderLineEditor', rbsOrderOrderEditorLineEditor);
})();