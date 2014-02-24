(function() {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentPreviewRbsCatalogProduct', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},
			templateUrl: 'Rbs/Catalog/rbs-document-preview-rbs-catalog-product.twig',
			link: function(scope) {
				REST.ensureLoaded(scope.document);
			}
		};
	}]);

	function rbsDocumentPreviewRbsCatalogProductList(REST) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},
			templateUrl: 'Rbs/Catalog/rbs-document-preview-rbs-catalog-product-list.twig',
			link: function(scope) {
				REST.ensureLoaded(scope.document).then(function(doc) {
					angular.extend(scope.document, doc);
				});
			}
		};
	}
	rbsDocumentPreviewRbsCatalogProductList.$inject = ['RbsChange.REST'];
	app.directive('rbsDocumentPreviewRbsCatalogProductList', rbsDocumentPreviewRbsCatalogProductList);
	app.directive('rbsDocumentPreviewRbsCatalogSectionProductList', rbsDocumentPreviewRbsCatalogProductList);

	app.directive('rbsDocumentPreviewRbsCatalogCrossSellingProductList', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},
			templateUrl: 'Rbs/Catalog/rbs-document-preview-rbs-catalog-cross-selling-product-list.twig',
			link: function(scope) {
				REST.ensureLoaded(scope.document).then(function(doc) {
					angular.extend(scope.document, doc);
					REST.ensureLoaded(scope.document.product).then(function(doc) {
						angular.extend(scope.document.product, doc);
					});
				});
			}
		};
	}]);

	app.directive('rbsDocumentFilterStockInventory', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl: 'Rbs/Catalog/rbs-document-filter-stock-inventory.twig',
			scope: {
				filter: '='
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

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
			}
		};
	});

	app.directive('rbsDocumentFilterProductCodes', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl: 'Rbs/Catalog/rbs-document-filter-product-codes.twig',
			scope: {
				filter: '='
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

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
			}
		};
	});

	app.directive('rbsDocumentFilterProductAttribute', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl: 'Rbs/Catalog/rbs-document-filter-product-attribute.twig',
			scope: {
				filter: '='
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				if (!scope.filter.parameters.hasOwnProperty('operator')) {
					scope.filter.parameters.operator = null;
				}

				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					var attributeId = scope.filter.parameters.attributeId;
					return attributeId && op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.isBoolean = function() {
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
			}
		};
	});
})();