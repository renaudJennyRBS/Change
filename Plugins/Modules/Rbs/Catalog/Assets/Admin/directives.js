(function ()
{
	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsDocumentPreviewRbsCatalogProduct', function ()
	{
		return {
			restrict : 'E',
			scope : {
				document : '='
			},
			template :
				'<p><a href="(= document | rbsURL =)"><strong>(= document.label =)</strong><br/><small>(= document.sku.code =)</small></a></p>' +
				'<p><img rbs-storage-image="document.visuals[0].id" thumbnail="M"/></p>' +
				'<p><img ng-repeat="v in document.visuals" ng-if="! $first" rbs-storage-image="v.id" thumbnail="S"/></p>'
		};
	});

	app.directive('rbsDocumentFilterStockInventory', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Catalog/rbs-document-filter-stock-inventory.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = null;
				}

				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.hasOwnProperty('level'));
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterProductCodes', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Catalog/rbs-document-filter-product-codes.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = null;
				}

				if (!scope.filter.parameters.codeName) {
					scope.filter.parameters.codeName = 'code';
				}

				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					var codeName = scope.filter.parameters.codeName;
					return codeName && op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});
})();