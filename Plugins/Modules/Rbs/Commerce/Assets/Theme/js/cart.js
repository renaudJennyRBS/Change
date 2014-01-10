(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceCartData() {
		return {
			restrict : 'A',
			template : '<div></div>',
			replace : true,
			require : 'ngModel',
			scope: false,

			link : function (scope, elm, attrs, ngModel) {
				var cart;
				if (attrs.hasOwnProperty('cart')) {
					cart = angular.fromJson(attrs.cart);
				}
				if (!angular.isObject(cart)) {
					cart = {};
				}
				ngModel.$setViewValue(cart);
			}
		}
	}
	app.directive('rbsCommerceCartData', rbsCommerceCartData);

	function rbsCommerceCartLine() {
		return {
			restrict : 'AE',
			templateUrl : '/simpleLine.static.tpl',
			link : function (scope) {
				scope.originalQuantities[scope.index] = scope.line.quantity;

				scope.isQuantityEdited = function () {
					return scope.originalQuantities[scope.index] != scope.line.quantity
				}
			}
		}
	}
	app.directive('rbsCommerceCartLine', rbsCommerceCartLine);

	function rbsCommerceProcessMenu() {
		return {
			restrict : 'AE',
			templateUrl : '/menu.static.tpl',
			link : function (scope) {
				jQuery('body').scrollspy({ target: '.process-sidebar' });
				scope.$watch('currentStep', function () {
					jQuery('body').each(function () { $(this).scrollspy('refresh'); });
				});
			}
		}
	}
	app.directive('rbsCommerceProcessMenu', rbsCommerceProcessMenu);

	function rbsCommerceShippingModeSelector($http, $compile, $sce) {
		return {
			restrict : 'AE',
			scope : {
				delivery : '=',
				zoneCode : '='
			},
			templateUrl : '/shipping-mode-selector.static.tpl',

			link : function (scope, element, attributes) {
				scope.modes = [];
				scope.display = { readonly: attributes.readonly };

				attributes.$observe('readonly', function(newValue){
					console.log('rbsCommerceShippingModeSelector - attributes.readonly', newValue);
					scope.display.readonly = (newValue == 'true');
				});

				$http.post('Action/Rbs/Commerce/GetCompatibleShippingModes', {lines: scope.lines})
					.success (function(data) {
						console.log('rbsCommerceShippingModeSelector - GetCompatibleShippingModes success');
						scope.modes = data;
						if (scope.modes.length == 1) {
							scope.selectMode(0);
						}
						else {
							for (var i = 0; i < scope.modes.length; i++) {
								if (scope.modes[i].id == scope.delivery.modeId) {
									scope.currentMode = scope.modes[i];
								}
							}
						}
					})
					.error(function(data, status, headers) {
						console.log('rbsCommerceShippingModeSelector - GetCompatibleShippingModes error', data, status, headers);
					});

				scope.selectMode = function(index) {
					console.log('rbsCommerceShippingModeSelector - selectMode', index);
					var mode = scope.modes[index];
					if (mode.id != scope.delivery.modeId) {
						scope.delivery.modeId = mode.id;
						scope.currentMode = mode;

						var html = '<div class="configuration-zone"';
						if (mode.directiveName) {
							html += ' ' + mode.directiveName + '=""';
						}
						html += '></div>';
						element.find('.configuration-zone').replaceWith(html);
						$compile(element.find('.configuration-zone'))(scope);
					}
					console.log('rbsCommerceShippingModeSelector - scope.display.directiveName', mode.directiveName);
				};

				scope.trustHtml = function (html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}
	rbsCommerceShippingModeSelector.$inject = ['$http', '$compile', '$sce'];
	app.directive('rbsCommerceShippingModeSelector', rbsCommerceShippingModeSelector);

	function rbsCommerceShippingModeConfigurationHome() {
		return {
			restrict : 'AE',
			scope : false,
			templateUrl : '/shipping-mode-configuration-home.static.tpl',
			link : function (scope) {
				scope.delivery.usePostalAddress = 1;
				if (!scope.delivery.hasOwnProperty('address')) {
					scope.delivery.address = {};
				}

				scope.isReadOnly = function() {
					return scope.readonly;
				}
			}
		}
	}
	app.directive('rbsCommerceShippingModeConfigurationHome', rbsCommerceShippingModeConfigurationHome);

	function rbsCommercePaymentConnectorSelector($http, $compile, $sce) {
		return {
			restrict : 'AE',
			scope : {
				payment : '='
			},
			templateUrl : '/payment-connector-selector.static.tpl',

			link : function (scope, element) {
				scope.connectors = [];
				scope.selectedConnector = null;
				scope.directiveName = null;

				$http.post('Action/Rbs/Commerce/GetCompatiblePaymentConnectors')
					.success (function(data) {
						console.log('GetCompatiblePaymentConnectors success');
						scope.connectors = data;
						if (scope.connectors.length == 1) {
							scope.selectConnector(0);
						}
					})
					.error(function(data, status, headers) {
						console.log('GetCompatiblePaymentConnectors error', data, status, headers);
					});

				scope.selectConnector = function(index) {
					console.log('selectConnector', index);
					var connector = scope.connectors[index];
					if (connector.id != scope.payment.connectorId) {
						scope.selectedConnector = connector;
						scope.payment.connectorId = connector.id;
						scope.directiveName = connector.directiveName;

						var html = '<div class="configuration-zone"';
						if (connector.directiveName) {
							html += ' ' + connector.directiveName + '=""';
						}
						html += '></div>';
						element.find('.configuration-zone').replaceWith(html);
						$compile(element.find('.configuration-zone'))(scope);
					}
					console.log('scope.directiveName', scope.directiveName);
				};

				scope.trustHtml = function (html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}
	rbsCommercePaymentConnectorSelector.$inject = ['$http', '$compile', '$sce'];
	app.directive('rbsCommercePaymentConnectorSelector', rbsCommercePaymentConnectorSelector);

	function rbsCommercePaymentConnectorDeferred() {
		return {
			restrict : 'AE',
			scope : false,
			templateUrl : '/payment-connector-deferred.static.tpl',
			link : function (scope) {
			}
		}
	}
	app.directive('rbsCommercePaymentConnectorDeferred', rbsCommercePaymentConnectorDeferred);

	/**
	 * Cart controller.
	 */
	function rbsCommerceCartController(scope, $http) {
		scope.readonlyCart = false;
		scope.cart = null;
		scope.loading = false;
		scope.originalQuantities = {};

		console.log('Init rbsCommerceCartController');

		function setCart(data) {
			console.log('setCart');
			scope.loading = false;
			scope.cart = data;
			scope.originalQuantities = {};
		}

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false})
				.success(function(data) {
					console.log('GetCurrentCart success');
					setCart(data);
				})
				.error(function(data, status, headers) {
					console.log('GetCurrentCart error', data, status, headers);
					setCart({});
				}
			);
		}

		scope.deleteLine = function(index) {
			scope.loading = true;
			var line = scope.cart.lines[index];
			$http.post('Action/Rbs/Commerce/UpdateCartLine', {lineKey: line.key, delete: true})
				.success (function(data) {
					console.log('UpdateCartLine success');
					setCart(data);
				})
				.error(function(data, status, headers) {
					console.log('UpdateCartLine error', data, status, headers);
				}
			);
		};

		scope.updateLine = function(index) {
			scope.loading = true;
			var line = scope.cart.lines[index];
			$http.post('Action/Rbs/Commerce/UpdateCartLine', {lineKey: line.key, quantity: line.quantity})
				.success (function(data) {
					console.log('UpdateCartLine success');
					setCart(data);
				})
				.error(function(data, status, headers) {
					console.log('UpdateCartLine error', data, status, headers);
				}
			);
		};

		scope.canOrder = function() {
			if (!scope.cart || !scope.cart.lines || scope.cart.lines.count < 1) {
				return false;
			}
			var result = true;
			angular.forEach(scope.cart.lines, function(line, index) {
				if (line.quantity != scope.originalQuantities[index]) {
					result = false;
				}
			});
			return result;
		};

		loadCurrentCart();
	}
	rbsCommerceCartController.$inject = ['$scope', '$http'];
	app.controller('rbsCommerceCartController', rbsCommerceCartController);

	/**
	 * Order process controller.
	 */
	function rbsCommerceOrderProcessController(scope, $http) {
		scope.readonlyCart = true;
		scope.cart = null;
		scope.loading = false;
		scope.originalQuantities = {};
		scope.information = { address: {}, email: null, confirmEmail: null };
		scope.shipping = [];
		scope.payment = { newCouponCode: null };
		scope.errors = [];
		scope.currentStep = null;
		scope.steps = ['cart', 'information', 'shipping', 'payment', 'confirm'];

		console.log('Init rbsCommerceOrderProcessController');

		function setCart(data) {
			scope.loading = false;
			scope.cart = data;
		}

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false})
				.success(function(data) {
					console.log('GetCurrentCart success');
					setCart(data);
					scope.setCurrentStep('information');
				})
				.error(function(data, status, headers) {
					console.log('GetCurrentCart error', data, status, headers);
					setCart({});
				}
			);
		}

		function clearErrors() {
			scope.errors = [];
		}

		function addError(message) {
			scope.errors.push(message);
		}

		scope.isStepEnabled = function(stepName) {
			for (var i = 0; i < scope.steps.length; i++) {
				if (scope.steps[i] == stepName) {
					return true;
				}
				if (scope.steps[i] == scope.currentStep) {
					return false;
				}
			}
			return false;
		};

		scope.isStepChecked = function(stepName) {
			for (var i = 0; i < scope.steps.length; i++) {
				if (scope.steps[i] == scope.currentStep) {
					return false;
				}
				if (scope.steps[i] == stepName) {
					return true;
				}
			}
			return false;
		};

		scope.setCurrentStep = function (stepName) {
			scope.currentStep = stepName;
			var methodName = 'prepare' + stepName.charAt(0).toUpperCase() + stepName.slice(1) + 'Step';
			console.log(methodName);
			if (scope.hasOwnProperty(methodName)) {
				scope[methodName]();
				console.log(methodName);
			}
		};

		scope.isCurrentStep = function (stepName) {
			return scope.currentStep == stepName;
		};

		/**
		 * Information step
		 */
		scope.isInformationStepComplete = function() {
			//TODO
			return true;
		};

		scope.canAuthenticate = function() {
			return scope.information.login && scope.information.password;
		};
		scope.authenticate = function() {
			var postData = {
				realm: scope.information.realm,
				login: scope.information.login,
				password: scope.information.password
			};
			$http.post('Action/Rbs/User/Login', postData)
				.success(function(data) {
					if (data.hasOwnProperty('accessorId')) {
						scope.cart.accessorId = data.accessorId;
						delete scope.information.password;
					}
					else if (data.hasOwnProperty('error')) {
						clearErrors();
						addError(data.error);
					}
				})
				.error(function(data, status, headers) {
					console.log('Login error', data, status, headers);
				});
		};
		scope.logout = function() {
			$http.post('Action/Rbs/User/Logout')
				.success(function() {
					window.location.reload();
				})
				.error(function(data, status, headers) {
					console.log('Login error', data, status, headers);
				});
		};

		scope.canSetEmail = function() {
			return scope.information.email && scope.information.email == scope.information.confirmEmail;
		};
		scope.setEmail = function() {
			scope.cart.email = scope.information.email;
		};

		scope.isAuthenticated = function() {
			return scope.cart && (scope.cart.email || scope.cart.accessorId);
		};

		/**
		 * Shipping step
		 */
		scope.prepareShippingStep = function () {
			if (scope.shipping.length == 0) {
				// TODO: handle forced shipping modes and reload data from cart.
				var defaultDelivery = { lines: [] };
				for (var i = 0; i < scope.cart.lines.length; i++) {
					defaultDelivery.lines.push(scope.cart.lines[i]);
				}
				scope.shipping.push(defaultDelivery);

				/*var deliveryTemp = { lines: [ scope.cart.lines[0] ] };
				scope.shipping.push(deliveryTemp);*/
			}
		};

		scope.isShippingStepComplete = function() {
			//TODO
			return true;
		};

		/**
		 * Payment step
		 */
		scope.setCoupon = function() {
			// TODO: server call to validate coupon.
			scope.payment.couponCode = scope.payment.newCouponCode;
			delete scope.payment.newCouponCode;
		};

		scope.removeCoupon = function() {
			delete scope.payment.couponCode;
		};

		loadCurrentCart();
	}
	rbsCommerceOrderProcessController.$inject = ['$scope', '$http'];
	app.controller('rbsCommerceOrderProcessController', rbsCommerceOrderProcessController);
})();