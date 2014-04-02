(function (jQuery)
{
	"use strict";

	function Editor($compile, REST, $routeParams)
	{
		function redrawPriceModifier($compile, scope, directiveName) {
			var container = jQuery('#RbsPricePriceModifierOptions');
			var collection = container.children();
			collection.each(function() {
				angular.element(jQuery(this)).isolateScope().$destroy();
			});
			collection.remove();

			if (directiveName) {
				var html = '<div ' + directiveName + '="" options="document.options" price="document" price-context="priceContext"></div>'
				container.html(html);
				$compile(container.children())(scope);
			}
		}

		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link : function (scope, elm, attrs) {
				scope.priceContext = {
					discount: false,
					webStore: {},
					billingArea: {},
					taxInfo: null
				};

				scope.onSaveContext = function(context) {
					context.savedData('priceContext', {ctx: scope.priceContext});
				};

				scope.onRestoreContext = function(currentContext) {
					var toRestoreData = currentContext.savedData('priceContext');
					scope.priceContext = toRestoreData.ctx;
				};

				scope.onLoad = function() {
					if (angular.isArray(scope.document.taxCategories) || !angular.isObject(scope.document.taxCategories)) {
						scope.document.taxCategories = {};
					}

					if (scope.document.isNew()) {
						scope.document.priority = 25;
					}

					if (!angular.isObject(scope.document.options) || angular.isArray(scope.document.options)) {
						scope.document.options = {};
					}
				};

				scope.onReady = function(){

					if (!scope.document.sku && $routeParams.productId) {
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product) {
							scope.document.sku = product.sku;
						});
					}

					if (scope.document.isNew() && $routeParams.basePriceId) {
						REST.resource('Rbs_Price_Price', $routeParams.basePriceId).then(function(price) {
							scope.document.sku = price.sku;
							scope.document.webStore = price.webStore;
							scope.document.billingArea = price.billingArea;
							scope.document.taxCategories = price.taxCategories;
							scope.document.basePrice = price;
							scope.priceContext.discount = true;

							REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:price.billingArea.id}).then(function(res) {
								scope.priceContext.taxInfo = res;
							});
						});
					}

					if (scope.document.basePrice) {
						scope.priceContext.discount = true;
					}
				};

				scope.$watch('document.webStore', function(newValue) {
					if (newValue) {
						var webStoreId = (angular.isObject(newValue)) ? newValue.id : newValue;
						if (scope.priceContext.webStore.id != webStoreId) {
							REST.resource('Rbs_Store_WebStore', webStoreId).then(function(res) {
								scope.priceContext.webStore = res;
							});
						}
					} else {
						scope.priceContext.webStore = {};
					}

				});

				scope.$watch('document.billingArea', function(newValue) {
					if (newValue) {
						if (!scope.document.taxCategories) {
							scope.document.taxCategories = {};
						}

						var billingAreaId = (angular.isObject(newValue)) ? newValue.id : newValue;
						if (scope.priceContext.billingArea.id != billingAreaId) {
							REST.resource('Rbs_Price_BillingArea', billingAreaId).then(function(res){
								scope.priceContext.billingArea = res;
							});
							REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:billingAreaId}).then(function(res){
								scope.priceContext.taxInfo = res;
							});
						}
					} else {
						scope.priceContext.billingArea = {};
						scope.priceContext.taxInfo = null;
					}
				});

				scope.$watch('document.valueModifierName', function(directiveName) {
					redrawPriceModifier($compile, scope, directiveName);
				});

				var documentId = -1;
				if ($routeParams.hasOwnProperty('id') && $routeParams.id != 'new') {
					documentId = parseInt($routeParams.id, 10);
				}

				scope.loadPriceQuery = {
					"model": "Rbs_Price_Price",
					"where": {
						"and" : [
							{
								"op" : "eq",
								"lexp" : {
									"property" : "basePrice"
								},
								"rexp" : {
									"value": documentId
								}
							}
						]
					}
				};

			}
		};
	}

	Editor.$inject = ['$compile', 'RbsChange.REST', '$routeParams'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPricePriceEdit', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPricePriceNew', Editor);

})(window.jQuery);