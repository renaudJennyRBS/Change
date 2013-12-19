(function () {

	"use strict";

	function rbsOrderOrderEditorLineEditor ()
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
				ngModel.$render = function ngModelRenderFn () {
					scope.doc = ngModel.$viewValue;
					var taxCategories = scope.doc.items[0].taxCategories
					if(!angular.isObject(taxCategories) || taxCategories instanceof Array){
						scope.doc.items[0].taxCategories = {};
					}
				};

				scope.currentTaxInfo = [];

				scope.$watch('priceInfo.taxZone', function(taxZone, oldValue){
					var taxInfo = [];
					if(taxZone){
						angular.forEach(scope.priceInfo.taxInfo, function(tax){
							if(tax.zones.indexOf(taxZone) > -1){
								taxInfo.push(tax);
							}
						});
						scope.currentTaxInfo = taxInfo;
					}
					else{
						scope.currentTaxInfo = [];
					}

				});
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderLineEditor', rbsOrderOrderEditorLineEditor);
})();