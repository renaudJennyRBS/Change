(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function productDataLink(scope, elm, attrs) {
		var v = parseInt(attrs.productId, 10);
		scope.baseProductId = scope.product.id = isNaN(v) ? 0 : v;

		if (attrs.hasOwnProperty('productTitle')) {
			scope.product.title = attrs['productTitle'];
		}
		if (attrs.hasOwnProperty('stockSku')) {
			scope.stock = { sku: attrs['stockSku'] };
		}
		if (attrs.hasOwnProperty('productQuantity')) {
			scope.quantity = parseInt(attrs['productQuantity']);
		}

		scope.redirectUrl = attrs.redirectUrl;

		scope.modalId = attrs.modalId;
		scope.sectionId = attrs.sectionId;
	}

	function rbsCatalogSimpleProductData() {
		return {
			restrict: 'A',
			templateUrl: '/addSimpleLineToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}

	app.directive('rbsCatalogSimpleProductData', rbsCatalogSimpleProductData);

	function rbsCatalogVariantProductData() {
		return {
			restrict: 'A',
			templateUrl: '/addVariantLineToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}

	app.directive('rbsCatalogVariantProductData', rbsCatalogVariantProductData);

	function rbsCatalogProductItemData() {
		return {
			restrict: 'A',
			templateUrl: '/addItemLineToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}

	app.directive('rbsCatalogProductItemData', rbsCatalogProductItemData);

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

	function addLine(scope, $http, $compile, $rootScope) {
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
			if (scope.modalId) {
				data.modalInfos = {
					sectionPageFunction: 'Rbs_Catalog_ProductAddedToCart',
					sectionId: scope.sectionId,
					productId: scope.product.id
				};
			}

			scope.modalContentLoading = true;
			$http.post('Action/Rbs/Commerce/AddLineToCart', data, {})
				.success(function(resultData) {
					// Launch event
					$rootScope.$broadcast('rbsRefreshCart', {'cart':resultData.cart});

					if (scope.modalId) {
						if (resultData.hasOwnProperty('modalContentUrl') && resultData['modalContentUrl']) {
							var mainContentElement = jQuery('#' + scope.modalId + ' .modal-main-content');
							mainContentElement.html('<div class="text-center"><img alt="" src="data:image/gif;base64,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" /></div>');
							scope.hideModalContent = false;
							$http.get(resultData.modalContentUrl)
								.success(function(resultData2) {
									jQuery('#' + scope.modalId + ' .modal-loading').hide();
									mainContentElement.html(resultData2);
									$compile(mainContentElement.contents())(scope);
									mainContentElement.show();
									scope.modalContentLoading = false;
								})
								.error(function(data, status, headers) {
									scope.hideModalContent = true;
									scope.modalContentLoading = false;
									console.log('error', data, status, headers);
								});
						}
						else {
							scope.hideModalContent = true;
						}
						jQuery('#' + scope.modalId).modal({});
					}
					else if (scope.redirectUrl) {
						window.location.href = scope.redirectUrl;
					}
				})
				.error(function(data, status, headers) {
					console.log('error', data, status, headers);
				});
		}
	}

	function initializeScope(scope) {
		scope.productLoading = false;
		scope.productPresentation = null;

		scope.redirectUrl = null;
		scope.modalId = null;
		scope.sectionId = null;
		scope.modalContentLoading = false;
		scope.pricesConfig = {};
		scope.pictogramFormats = {};
		scope.visualFormats = {};
		scope.stock = null;
		scope.quantity = 1;

		// Variant Config
		scope.axesValues = [];
		scope.selectedAxesValues = [];
		scope.axes = null;

		// Base Product
		scope.baseProductId = 0;

		// Product
		scope.product = {'id': 0};
	}

	function RbsCatalogSimpleProductController(scope, $http, $compile, $rootScope) {
		initializeScope(scope);

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope);
		};
	}

	RbsCatalogSimpleProductController.$inject = ['$scope', '$http', '$compile', '$rootScope'];
	app.controller('RbsCatalogSimpleProductController', RbsCatalogSimpleProductController);
	function RbsCatalogProductItemController(scope, $http, $compile, $rootScope) {
		initializeScope(scope);

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope);
		};
	}
	RbsCatalogProductItemController.$inject = ['$scope', '$http', '$compile', '$rootScope'];
	app.controller('RbsCatalogProductItemController', RbsCatalogProductItemController);

	function RbsCatalogProductSetController(scope, $http, $compile) {
		scope.productLoading = false;
	}

	RbsCatalogProductSetController.$inject = ['$scope', '$http', '$compile'];
	app.controller('RbsCatalogProductSetController', RbsCatalogProductSetController);

	function RbsCatalogVariantProductController(scope, $http, $compile, $rootScope) {
		initializeScope(scope);
		setCurrentProduct(null);

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope);
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

			while (significantAxesCount > 0) {
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
				scope.product = data['general'];
				scope.prices = data.prices;
				scope.stock = data.stock;
				scope.pictograms = data.pictograms.data;
				scope.visuals = data.visuals.data;
				scope.quantity = data.stock['minQuantity'];
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
	RbsCatalogVariantProductController.$inject = ['$scope', '$http', '$compile', '$rootScope'];
	app.controller('RbsCatalogVariantProductController', RbsCatalogVariantProductController);
})();