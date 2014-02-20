(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function rbsCatalogProductData() {
		return {
			restrict: 'A',
			templateUrl: '/addLineToCart.tpl',
			replace: false,
			scope: false,

			link: function(scope, elm, attrs) {
				var v = parseInt(attrs.productId, 10);
				scope.baseProductId = scope.product.id = isNaN(v) ? 0 : v;
				scope.product.designation = attrs.designation;
				scope.redirectUrl = attrs.redirectUrl;
			}
		}
	}

	app.directive('rbsCatalogProductData', rbsCatalogProductData);

	function rbsCatalogProductAvailability() {
		return {
			restrict: 'A',
			templateUrl: '/productAvailability.tpl',
			replace: false,
			scope: false,
			transclude: true,

			link: function(scope, elm, attrs) {
			}
		}
	}

	app.directive('rbsCatalogProductAvailability', rbsCatalogProductAvailability);

	function rbsCatalogProductPrice() {
		return {
			restrict: 'A',
			templateUrl: '/productPrice.tpl',
			replace: false,
			require: 'ngModel',
			scope: false,
			transclude: true,

			link: function(scope, elm, attrs, ngModel) {
				var display = (attrs.hasOwnProperty('display')) ? (attrs.display == "1") : false;
				var displayWithTax = (attrs.hasOwnProperty('displayWithTax')) ? (attrs.displayWithTax == "1") : false;
				ngModel.$setViewValue({display: display, displayWithTax: displayWithTax});
			}
		}
	}

	app.directive('rbsCatalogProductPrice', rbsCatalogProductPrice);

	function rbsCatalogProductPictograms() {
		return {
			restrict: 'A',
			templateUrl: '/productPictograms.tpl',
			replace: false,
			scope: false,
			transclude: true,

			link: function(scope, elm, attrs) {
				if (attrs.hasOwnProperty('pictogramFormats')) {
					angular.extend(scope['pictogramFormats'], angular.fromJson(attrs['pictogramFormats']));
				}
			}
		}
	}

	app.directive('rbsCatalogProductPictograms', rbsCatalogProductPictograms);

	function rbsCatalogProductVisuals() {
		return {
			restrict: 'A',
			templateUrl: '/productVisuals.tpl',
			replace: false,
			scope: false,
			transclude: true,

			link: function(scope, elm, attrs) {
				if (attrs.hasOwnProperty('visualFormats')) {
					angular.extend(scope.visualFormats, angular.fromJson(attrs.visualFormats));
				}
			}
		}
	}

	app.directive('rbsCatalogProductVisuals', rbsCatalogProductVisuals);

	function rbsCatalogVariantData() {
		return {
			restrict: 'A',
			template: '<div></div>',
			replace: true,
			scope: false,

			link: function(scope, elm, attrs) {
				scope.variantGroupId = attrs.variantGroupId;
				scope.axes = angular.fromJson(attrs.axes);
			}
		}
	}

	app.directive('rbsCatalogVariantData', rbsCatalogVariantData);

	function RbsCatalogProductController(scope, $http) {
		scope.productLoading = false;

		scope.redirectUrl = null;
		scope.pricesConfig = {};
		scope.pictogramFormats = {};
		scope.visualFormats = {};

		// Variant Config
		scope.axesValues = [];
		scope.selectedAxesValues = [];
		scope.axes = null;

		// Base Product
		scope.baseProductId = 0;

		// Product
		scope.product = {'id': 0};

		setCurrentProduct(null);

		scope.addLine = function() {
			if (scope.product.id !== 0) {
				var data = {
					key: scope.product.id,
					designation: scope.product.title,
					quantity: scope.quantity,
					options: {productId: scope.product.id},
					items: [
						{codeSKU: scope.stock.sku}
					]
				};

				$http.post('Action/Rbs/Commerce/AddLineToCart', data, {})
					.success(function() {
						if (scope.redirectUrl) {
							window.location.href = scope.redirectUrl;
						}
					})
					.error(function(data, status, headers) {
						console.log('error', data, status, headers);
					});
			}
		};

		scope.$watch('axes', function(val) {
			var i, product, productAxisVal, productId = scope.product.id,
				axesLength, index = 0, parentValues = [];
			if (val) {
				axesLength = val.axesValues.length;
				buildSelectAxisValues(index, parentValues, val.products, val.axesValues);

				product = findProduct(productId, val.products);
				if (product) {
					for (index = 0; index < axesLength; index++) {
						productAxisVal = product.values[index];
						i = -1;
						if (scope.axesValues[index]) {
							i = getIndexOfValue(scope.axesValues[index], productAxisVal.value);
						}
						if (i != -1) {
							scope.selectedAxesValues[index] = scope.axesValues[index][i];
							parentValues.push(productAxisVal);
							if (index + 1 < axesLength) {
								buildSelectAxisValues(index + 1, parentValues, val.products, val.axesValues);
							}

							if (index == axesLength - 1) {
								loadProduct(product)
							}
						}
						else {
							scope.selectedAxesValues[index] = null;
						}
					}
				}
				else {
					for (i = 0; i < axesLength; i++) {
						scope.selectedAxesValues[i] = null;
					}
				}
			}
		});

		scope.variantChanged = function(axisIndex) {
			scope.selectedAxesValues.length = axisIndex + 1;
		};

		scope.$watchCollection('selectedAxesValues', function(val) {
			if (!val || !scope.axes) {
				return;
			}

			var i, expected = [], axes = scope.axes.axesValues, products = scope.axes.products;
			for (i = 0; i < val.length; i++) {
				if (val[i] === null) {
					break;
				}
				expected.push({id: val[i].id, value: val[i].value});
			}

			if (expected.length < axes.length) {
				buildSelectAxisValues(expected.length, expected, products, axes);
			}

			var significantAxesCount = expected.length;
			for (i = significantAxesCount; i < axes.length; i++) {
				expected.push({id: axes[i].id, value: null});
			}

			while (significantAxesCount > 0)
			{
				// Look for a product with these axis values.
				for (i = 0; i < products.length; i++) {
					if (eqAxesValues(expected, products[i].values)) {
						if (products[i].id != scope.product.id) {
							loadProduct(products[i]);
						}
						return;
					}
				}
				// Iteratively remove the last axis value look for a parent intermediate product.
				significantAxesCount--;
				expected[significantAxesCount].value = null;
			}
			setCurrentProduct(null);
		});

		function loadProduct(product) {
			scope.productLoading = true;
			var params = {
				productId: product.id,
				axesValues: product.values,
				formats: { visuals: scope.visualFormats, pictograms: scope.pictogramFormats }
			};
			$http.post('Action/Rbs/Catalog/ProductResult', params)
				.success(function(data) {
					scope.productLoading = false;
					setCurrentProduct(data);
				})
				.error(function() {
					scope.productLoading = false;
					setCurrentProduct(null);
				});
		}

		function setCurrentProduct(data) {
			if (data) {
				scope.product = data.general;
				scope.prices = data.prices;
				scope.stock = data.stock;
				scope.pictograms = data.pictograms.data;
				scope.visuals = data.visuals.data;
				scope.quantity = data.stock.minQuantity;
			}
			else {
				scope.product = {'id': null};
				scope.prices = null;
				scope.stock = null;
				scope.pictograms = null;
				scope.visuals = null;
				scope.quantity = 1;
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

		function buildSelectAxisValues(index, parentAxesValue, products, axes) {
			var values = [], value, axisId;
			angular.forEach(products, function(product) {
				if (eqAxesValues(parentAxesValue, product.values)) {
					value = product.values[index].value;
					axisId = product.values[index].id;
					if (value !== null && (getIndexOfValue(values, value) == -1)) {
						var title = value;
						if (axes[index].hasOwnProperty('defaultValues') && axes[index]['defaultValues'].length > 0) {
							for (var i = 0; i < axes[index]['defaultValues'].length; i++) {
								if (axes[index]['defaultValues'][i].hasOwnProperty('title') &&
									axes[index]['defaultValues'][i]['value'] == value) {
									title = axes[index]['defaultValues'][i]['title'];
									break;
								}
							}
						}
						values.push({id: axisId, value: value, title: title, index: index})
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
					if (aav.id == eav.id && aav.value !== eav.value) {
						return false;
					}
				}
			}
			return true;
		}

		function getIndexOfValue(array, value) {
			var e, ev;
			for (e = 0; e < array.length; e++) {
				ev = array[e];
				if (ev.hasOwnProperty('value') && ev.value == value) {
					return e;
				}
			}
			return -1;
		}
	}

	RbsCatalogProductController.$inject = ['$scope', '$http'];
	app.controller('RbsCatalogProductController', RbsCatalogProductController);
})();