(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceCart($rootScope, $compile, AjaxAPI) {
		var cacheCartDataKey = 'cartData';

		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceCart.tpl',
			scope: {},
			controller: ['$scope', '$element', function(scope, elem) {
				var self = this;
				var modifications = {};

				scope.loading = false;

				this.loadCartData = function() {
					scope.loading = true;
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, {detailed: true, URLFormats: 'canonical', visualFormats: scope.parameters['imageFormats']});
					request.success(function(data, status, headers, config) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							self.setCartData(cartData);
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						console.log('loadCartData error', data, status);
						scope.loading = false;
					});
					return request;
				};

				this.updateCartData = function(actions) {
					scope.loading = true;
					var request = AjaxAPI.putData('Rbs/Commerce/Cart', actions, {detailed: true, URLFormats: 'canonical', visualFormats: scope.parameters['imageFormats']});
					request.success(function(data, status, headers, config) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							self.setCartData(cartData);
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						console.log('updateCartData error', data, status);
						scope.loading = false;
					});
					return request;
				};

				this.updateLineQuantity = function(key, newQuantity) {
					var actions = {
						'updateLinesQuantity': [
							{ key: key, quantity: newQuantity }
						]
					};
					this.updateCartData(actions);
				};

				this.showPrices = function() {
					return (scope.parameters &&
					(scope.parameters.displayPricesWithTax || scope.parameters.displayPricesWithoutTax))

				};

				this.getCurrencyCode = function() {
					return scope.currencyCode;
				};

				this.parameters = function(name) {
					if (scope.parameters) {
						if (angular.isUndefined(name)) {
							return scope.parameters;
						}
						else {
							return scope.parameters[name];
						}
					}
					return null;
				};

				this.getCartData = function() {
					return scope.cartData;
				};

				this.setCartData = function(cartData) {
					scope.cartData = cartData;
					if (this.showPrices()) {
						scope.currencyCode = cartData.common.currencyCode;
					}
					else {
						scope.currencyCode = null;
					}
					modifications = {};
					$rootScope.$broadcast('rbsRefreshCart', { 'cart': cartData });
				};

				this.replaceChildren = function(parentNode, scope, html) {
					var collection = parentNode.children();
					collection.each(function() {
						var isolateScope = angular.element(this).isolateScope();
						if (isolateScope) {
							isolateScope.$destroy();
						}
					});
					collection.remove();
					$compile(html)(scope, function(clone) {
						parentNode.append(clone);
					});
				};

				this.redrawLines = function() {
					var linesContainer = elem.find('[data-role="cart-lines"]');
					var directiveName = angular.isFunction(this.getLineDirectiveName) ? this.getLineDirectiveName : function(line) {
						return 'rbs-commerce-cart-line-default';
					};
					var lines = scope.cartData.lines;
					var html = [];
					angular.forEach(lines, function(line, idx) {
						html.push('<tr data-line="cartData.lines[' + idx + ']" ' + directiveName(line) + '=""></tr>');
					});
					this.replaceChildren(linesContainer, scope, html.join(''));
				};

				this.hasModifications = function() {
					var modified = false;
					angular.forEach(modifications, function(modification, key) {
						if (modification) {
							modified = true;
						}
					});
					return modified;
				};

				this.setModification = function(key, modified) {
					if (modified) {
						modifications[key] = true;
					}
					else {
						delete modifications[key];
					}
				};

				scope.$watch('cartData', function(cartData, oldCartData) {
						if (cartData) {
							self.redrawLines();
							scope.acceptTermsAndConditions = scope.cartData.context.acceptTermsAndConditions;
						}
					}
				);

				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);

				var cartData = AjaxAPI.globalVar(cacheCartDataKey);

				if (!cartData) {
					this.loadCartData();
				}
				else {
					this.setCartData(cartData);
				}
			}],

			link: function(scope, elem, attrs, controller) {
				scope.showPrices = controller.showPrices();

				scope.$watch('acceptTermsAndConditions', function(newValue, oldValue) {
					if (newValue !== oldValue && angular.isDefined(newValue)) {
						var actions = {
							'updateContext': {
								'acceptTermsAndConditions': (newValue == true)
							}
						};
						controller.updateCartData(actions);
					}
				});

				scope.hasModifications = function() {
					return controller.hasModifications();
				};

				scope.canOrder = function() {
					var cartData = scope.cartData;
					if (!cartData
						|| !cartData.lines || !cartData.lines.length
						|| !cartData.process || !cartData.process.orderProcessId || !cartData.process.validTaxBehavior
						|| (cartData.common.errors && cartData.common.errors.length)
						|| !cartData.context.acceptTermsAndConditions) {
						return false;
					}
					return !scope.hasModifications();
				}
			}
		}
	}

	rbsCommerceCart.$inject = ['$rootScope', '$compile', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceCart', rbsCommerceCart);

	function rbsCommerceCartLineDefault() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceCartLineDefault.tpl',
			require: '^rbsCommerceCart',
			replace: true,
			scope: { line: "=" },
			link: function(scope, elem, attrs, cartController) {
				scope.showPrices = cartController.showPrices();
				scope.currencyCode = cartController.getCurrencyCode();
				scope.parameters = cartController.parameters();
				scope.quantity = scope.line.quantity;
				if (!scope.line.unitBasedAmountWithTaxes && scope.line.basedAmountWithTaxes) {
					scope.line.unitBasedAmountWithTaxes = (scope.line.basedAmountWithTaxes / scope.quantity);
				}
				if (!scope.line.unitBasedAmountWithoutTaxes && scope.line.basedAmountWithoutTaxes) {
					scope.line.unitBasedAmountWithoutTaxes = (scope.line.basedAmountWithoutTaxes / scope.quantity);
				}

				scope.updateQuantity = function() {
					cartController.updateLineQuantity(scope.line.key, scope.quantity);
				};

				scope.remove = function() {
					cartController.updateLineQuantity(scope.line.key, 0);
				};

				scope.$watch('quantity', function(quantity) {
					cartController.setModification('line_' + scope.line.key, quantity != scope.line.quantity)
				});

				scope.disabledQuantity = function() {
					return (scope.quantity == scope.line.quantity && cartController.hasModifications());
				};
			}
		}
	}

	rbsCommerceCartLineDefault.$inject = [];
	app.directive('rbsCommerceCartLineDefault', rbsCommerceCartLineDefault);

	function rbsCommerceCartLineVisual() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceCartLineVisual.tpl',
			scope: { product: "=" },
			link: function(scope, elem, attrs, cartController) {
			}
		}
	}

	rbsCommerceCartLineVisual.$inject = [];
	app.directive('rbsCommerceCartLineVisual', rbsCommerceCartLineVisual);

	function rbsCommerceShippingFeesEvaluation(AjaxAPI, $sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShippingFeesEvaluation.tpl',
			replace: false,
			scope: false,

			link: function(scope, elm, attrs) {
				var visualFormats = attrs.hasOwnProperty('visualFormats')? attrs.visualFormats : 'modeThumbnail';
				scope.displayPricesWithoutTax = attrs.hasOwnProperty('displayPricesWithoutTax');
				scope.displayPricesWithTax = attrs.hasOwnProperty('displayPricesWithTax');
				scope.data = null;
				scope.currentCountry = null;
				scope.currentShippingModes = [];

				AjaxAPI.getData('Rbs/Commerce/Cart/ShippingFeesEvaluation', {}, {visualFormats: visualFormats})
					.success(function(data) {
						scope.data = data.dataSets;
						if (scope.data.countriesCount) {
							if (scope.data.countriesCount == 1) {
								scope.currentCountry = scope.data.countries[0].code;
							}
						} else {
							scope.data = null;
						}
					})
					.error(function(data, status) {
						console.log('shippingFeesEvaluation error', data, status);
						scope.data = null;
					}
				);

				scope.$watch('currentCountry', function() {
					var ids = {};
					scope.currentShippingModes = [];
					if (scope.currentCountry != null) {
						angular.forEach(scope.data.shippingModes, function(shippingMode){
							angular.forEach(shippingMode.deliveryZones, function(zone) {
								if (zone.countryCode == scope.currentCountry && !ids.hasOwnProperty(shippingMode.common.id)) {
									ids[shippingMode.common.id] = true;
									scope.currentShippingModes.push(shippingMode);
								}
							})
						});
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommerceShippingFeesEvaluation.$inject = ['RbsChange.AjaxAPI', '$sce'];
	app.directive('rbsCommerceShippingFeesEvaluation', rbsCommerceShippingFeesEvaluation);

	function rbsCommerceShortCart($rootScope, AjaxAPI) {
		var cacheCartDataKey = 'cartData';
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShortCart.tpl',
			replace: false,
			scope: {},
			link: function(scope, elem, attrs) {
				var cacheKey = attrs['cacheKey'];
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.cartData = AjaxAPI.globalVar(cacheCartDataKey);

				var cartParams = {
					detailed: false,
					URLFormats: 'canonical',
					visualFormats: scope.parameters['imageFormats']
				};

				scope.loading = false;
				scope.readOnly = true;
				if (angular.isUndefined(scope.cartData)) {
					scope.readOnly = false;
					loadCurrentCart();
				}

				function loadCurrentCart() {
					scope.loading = true;
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, cartParams);
					request.success(function(data) {
						var cart = data.dataSets;
						if (!cart || angular.isArray(cart)) {
							scope.cartData = null;
						}
						else {
							scope.cartData = cart;
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						scope.cartData = null;
						scope.loading = false;
						console.log('loadCurrentCart error', data, status, headers, config);
					})
				}

				function updateCartData(actions) {
					scope.loading = true;
					AjaxAPI.openWaitingModal(attrs['deleteProductWaitingMessage']);
					var request = AjaxAPI.putData('Rbs/Commerce/Cart', actions, cartParams);
					request.success(function(data) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							scope.cartData = cartData;
						}
						elem.find('.dropdown-toggle').dropdown('toggle');
						scope.loading = false;
						AjaxAPI.closeWaitingModal();
					}).error(function(data, status, headers, config) {
						console.log('updateCartData error', data, status, headers, config);
						scope.loading = false;
						AjaxAPI.closeWaitingModal();
					});
					return request;
				}

				scope.updateLineQuantity = function updateLineQuantity(key, newQuantity) {
					var actions = {
						'updateLinesQuantity': [
							{ key: key, quantity: newQuantity }
						]
					};
					updateCartData(actions);
				};

				$rootScope.$on('rbsRefreshCart', function onRbsRefreshCart(event, params) {
					scope.cartData = params['cart'];
					scope.loading = false;
				});

				$rootScope.$on('rbsUserConnected', function onRbsUserConnected(event, params) {
					loadCurrentCart();
				});
			}
		}
	}

	rbsCommerceShortCart.$inject = ['$rootScope', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceShortCart', rbsCommerceShortCart);
})();