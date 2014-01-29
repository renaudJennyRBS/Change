(function () {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function rbsCatalogProductData() {
		return {
			restrict : 'A',
			templateUrl : '/addLineToCart.tpl',
			replace : false,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				var v =  parseInt(attrs.stockLevel, 10);
				scope.stockLevel = isNaN(v) ? 0 : v;

				v = parseInt(attrs.stockMin, 10);
				scope.stockMin = isNaN(v) ? 0 : v;

				if (attrs.hasOwnProperty('stockStep')){
					v = parseInt(attrs.stockStep, 10);
					scope.stockStep = isNaN(v) ? 0 : v;

					if (attrs.hasOwnProperty('stockMax')) {
						v = parseInt(attrs.stockMax, 10);
						scope.stockMax = isNaN(v) ? scope.stockLevel : v;
					} else {
						scope.stockMax = scope.stockLevel;
					}
				}

				scope.quantity = Math.min(scope.stockMin, scope.stockLevel);

				if (scope.quantity > 0 && attrs.sku && attrs.price)
				{
					scope.canBeOrdered = true;
				}
				else
				{
					scope.canBeOrdered = false;
				}

				var config = {
					key: attrs.key,
					productId : attrs.productId,
					designation: attrs.designation,
					sku: attrs.sku,
					redirectUrl: attrs.redirectUrl
				};
				ngModel.$setViewValue(config);
			}
		}
	}
	app.directive('rbsCatalogProductData', rbsCatalogProductData);

	function rbsCatalogProductAvailability() {
		return {
			restrict : 'A',
			templateUrl : '/productAvailability.tpl',
			replace : false,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				scope.level = (attrs.hasOwnProperty('level')) ? parseInt(attrs.level, 10) : null;
				scope.threshold = (attrs.hasOwnProperty('threshold')) ? attrs.threshold : null;
				scope.thresholdClass = (attrs.hasOwnProperty('thresholdClass')) ? attrs.thresholdClass : null;
				scope.thresholdTitle = (attrs.hasOwnProperty('thresholdTitle')) ? attrs.thresholdTitle: null;
				ngModel.$setViewValue({level: scope.level, threshold: scope.threshold,
					thresholdClass: scope.thresholdClass, thresholdTitle: scope.thresholdTitle});
			}
		}
	}
	app.directive('rbsCatalogProductAvailability', rbsCatalogProductAvailability);


	function rbsCatalogProductPrice() {
		return {
			restrict : 'A',
			templateUrl : '/productPrice.tpl',
			replace : false,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				var display = (attrs.hasOwnProperty('display')) ? (attrs.display == "1") : false;
				var displayWithTax = (attrs.hasOwnProperty('displayWithTax')) ? (attrs.displayWithTax == "1") : false;
				scope.prices = (attrs.hasOwnProperty('prices')) ? angular.fromJson(attrs.prices) : {};
				ngModel.$setViewValue({display: display, displayWithTax: displayWithTax});
			}
		}
	}
	app.directive('rbsCatalogProductPrice', rbsCatalogProductPrice);


	function rbsCatalogVariantData() {
		return {
			restrict : 'A',
			template : '<div></div>',
			replace : true,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				scope.variantGroupId = attrs.variantGroupId;
				var config = {
					variantGroupId: attrs.variantGroupId,
					axes : angular.fromJson(attrs.axes)
				};
				ngModel.$setViewValue(config);
			}
		}
	}

	app.directive('rbsCatalogVariantData', rbsCatalogVariantData);

	function RbsCatalogProductController(scope, $http)
	{
		scope.quantity = 1;
		scope.stockLevel = 0;
		scope.stockMin = 0;
		scope.stockMax = 0;
		scope.stockStep = 1;

		scope.productConfig = {};
		scope.pricesConfig = {};

		scope.productAvailability = {};
		scope.prices = {};

		scope.variantConfig = {};

		scope.selectedAxesValues = [];

		scope.axesValues = [];

		scope.addLine = function() {
			var data =  {key: scope.productConfig.key,
				designation: scope.productConfig.designation,
				quantity: scope.quantity,
				options: {productId: scope.productConfig.productId},
				items: [{codeSKU: scope.productConfig.sku}] };
			$http.post('Action/Rbs/Commerce/AddLineToCart', data, {}).success(function(data, status, headers) {
				if (scope.productConfig.redirectUrl) {
					window.location.href = scope.productConfig.redirectUrl;
				}
			}).error(function(data, status, headers) {
				console.log('error', data, status, headers);
			});
		};

		scope.$watch('variantConfig', function(val) {
			var i, product, productAxisVal, productId = scope.productConfig.productId,
				axesLength, index = 0, parentValues = [];
			if (val && val.hasOwnProperty('axes')) {
				axesLength = val.axes.axesValues.length;
				buildSelectAxisValues(index, parentValues, val.axes.products);

				product = findProduct(productId, val.axes.products);
				if (product) {
					for (index = 0; index < axesLength; index++) {
						productAxisVal = product.values[index];
						i = getIndexOfValue(scope.axesValues[index], productAxisVal.value);
						if (i != -1) {
							scope.selectedAxesValues[index] = scope.axesValues[index][i];
							parentValues.push(productAxisVal);
							if (index + 1 < axesLength) {
								buildSelectAxisValues(index + 1, parentValues, val.axes.products);
							}
						} else {
							scope.selectedAxesValues[index] = null;
						}
					}
				} else {
					for (i = 0; i < axesLength; i++) {
						scope.selectedAxesValues[i] = null;
					}
				}
			}
		});

		scope.variantChanged  = function(axisIndex) {
			scope.selectedAxesValues.length = axisIndex + 1;
		};

		scope.$watchCollection('selectedAxesValues', function(val) {
			if (!val || !scope.variantConfig || !scope.variantConfig.hasOwnProperty('axes')) {
				return;
			}
			var i, expected = [], axes = scope.variantConfig.axes.axesValues, products = scope.variantConfig.axes.products;
			for (i = 0; i < val.length; i++) {
				if (val[i] === null) {
					break;
				}
				expected.push({id:val[i].id, value: val[i].value});
			}

			if (expected.length < axes.length) {
				buildSelectAxisValues(expected.length, expected, products);
			}

			for (i = expected.length; i < axes.length; i++) {
				expected.push({id:axes[i].id, value: null});
			}

			setCurrentProduct(null);

			for (i = 0; i < products.length; i++) {
				if (eqAxesValues(expected, products[i].values)) {
					if (products[i].id != scope.productConfig.productId) {
						$http.post('Action/Rbs/Catalog/ProductResult', {
							productId: products[i].id,
							axesValues: products[i].values
						}).success(function (data) {
								setCurrentProduct(data);
						});
					}
					return;
				}
			}
		});

		function setCurrentProduct(data) {
			if (data) {
				scope.productConfig.productId = data.productId;
				scope.productConfig.key = data.key;
				scope.productConfig.designation = data.designation;
				scope.productConfig.sku = data.stock.sku;

				scope.stockLevel = data.stock.level;
				scope.stockMin = data.stock.minQuantity;
				scope.stockMax = data.stock.maxQuantity;
				scope.stockStep = data.stock.quantityIncrement;

				scope.threshold = data.stock.threshold;
				scope.thresholdTitle = data.stock.thresholdTitle;
				scope.thresholdClass = data.stock.thresholdClass;

				scope.quantity = Math.min(scope.stockMin, scope.stockLevel);
				scope.productAvailability = data.stock;
				scope.prices = data.prices;
			} else {
				scope.productConfig.productId = 0;
				scope.productConfig.key = null;
				scope.productConfig.designation = '';
				scope.productConfig.sku = null;

				scope.productAvailability = {};
				scope.prices = {};

				scope.stockLevel = 0;
				scope.stockMin = 0;
				scope.stockMax = 0;
				scope.stockStep = 0;
				scope.threshold = null;
				scope.thresholdTitle = null;
				scope.thresholdClass = null;
				scope.quantity = 0;
			}
		}

		function findProduct(productId, products) {
			var i;
			for (i = 0; i < products.length; i++) {
				if (products[i].id == productId) {
					return products[i];
				}
			}
			return null;
		}

		function buildSelectAxisValues(index, parentAxesValue, products) {
			var values = [], value, axisId;
			angular.forEach(products, function(product) {
				if (eqAxesValues(parentAxesValue, product.values)) {
					value = product.values[index].value;
					axisId = product.values[index].id;
					if (value !== null && (getIndexOfValue(values, value) == -1)) {
						values.push({id: axisId, value: value, title:value, index: index})
					}
				}
			});
			scope.axesValues[index] = values;
		}

		function eqAxesValues(expected, actual) {
			var e, eav, a, aav;
			for (e = 0; e < expected.length; e++) {
				eav = expected[e];
				for (a = 0; a < actual.length; a++) {
					aav = actual[a];
					if (aav.id == eav.id) {
						if (aav.value !== eav.value)
						{
							return false;
						}
					}
				}
			}
			return true;
		}

		function getIndexOfValue(array, value) {
			var e, ev;
			for (e = 0; e < array.length; e++) {
				ev = array[e];
				if (ev.hasOwnProperty('value') && ev.value == value)
				{
					return e;
				}
			}
			return -1;
		}
	}
	RbsCatalogProductController.$inject = ['$scope', '$http'];
	app.controller('RbsCatalogProductController', RbsCatalogProductController);
})();