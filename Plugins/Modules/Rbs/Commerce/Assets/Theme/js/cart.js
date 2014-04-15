(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	/**
	 * @param {*} value
	 * @param {boolean} makeClone
	 * @returns {Object}
	 */
	function getObject(value, makeClone) {
		if (!angular.isObject(value) || angular.isArray(value)) {
			return {};
		}
		return makeClone ? angular.copy(value) : value;
	}

	/**
	 * @param {Object} $http
	 * @param {Object} scope
	 * @param {Object} postData
	 * @param {Function=} successCallback
	 * @param {Function=} errorCallback
	 */
	function updateCart($http, scope, postData, successCallback, errorCallback) {
		$http.post('Action/Rbs/Commerce/UpdateCart', postData)
			.success(function(data) {
				scope.cart = data;
				if (angular.isFunction(successCallback)) {
					successCallback(data);
				}
			})
			.error(function(data, status, headers) {
				console.log('UpdateCart error', data, status, headers);
				if (angular.isFunction(errorCallback)) {
					errorCallback(data, status, headers);
				}
			});
	}

	/**
	 * @param {Object} referenceAddress
	 * @param {Array} addresses
	 * @returns {boolean}
	 */
	function hasInvalidAddresses(referenceAddress, addresses) {
		var countryCode = referenceAddress.countryCode;
		for (var i = 0; i < addresses.length; i++) {
			if (addresses[i].fieldValues.countryCode != countryCode) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param {Object} referenceAddress
	 * @param {Array} addressToCheck
	 * @returns {boolean}
	 */
	function isValidAddress(referenceAddress, addressToCheck) {
		return referenceAddress.countryCode != addressToCheck['fieldValues'].countryCode;
	}

	/**
	 * @param {Object} existingAddress
	 * @param {Object} addressToUse
	 */
	function applyAddress(existingAddress, addressToUse) {
		angular.forEach(addressToUse.fieldValues, function(value, key) {
			existingAddress[key] = addressToUse.fieldValues[key];
		});
		angular.forEach(existingAddress, function(value, key) {
			if (!addressToUse.fieldValues.hasOwnProperty(key)) {
				delete(existingAddress[key]);
			}
		});
	}

	function rbsCommerceCartData() {
		return {
			restrict: 'A',
			template: '<div></div>',
			replace: true,
			require: 'ngModel',
			scope: false,

			link: function(scope, elm, attributes, ngModel) {
				var cart;
				if (attributes.hasOwnProperty('cart')) {
					cart = angular.fromJson(attributes.cart);
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
			restrict: 'AE',
			templateUrl: '/simpleLine.static.tpl',
			link: function(scope) {
				scope.originalQuantities[scope.index] = scope.line.quantity;

				scope.isQuantityEdited = function() {
					return scope.originalQuantities[scope.index] != scope.line.quantity
				}
			}
		}
	}

	app.directive('rbsCommerceCartLine', rbsCommerceCartLine);

	function rbsCommerceProcessMenu() {
		return {
			restrict: 'AE',
			templateUrl: '/menu.static.tpl',
			link: function(scope) {
				/*jQuery('body').scrollspy({ target: '.process-sidebar' });
				 scope.$watch('currentStep', function() {
				 jQuery('body').each(function() { $(this).scrollspy('refresh'); });
				 });*/
			}
		}
	}

	app.directive('rbsCommerceProcessMenu', rbsCommerceProcessMenu);

	function rbsCommerceAuthenticationStep() {
		return {
			restrict: 'AE',
			scope: true,
			templateUrl: '/authentication-step.static.tpl',

			link: function(scope, element, attributes) {
				if (!scope.information.hasOwnProperty('realm')) {
					scope.information.realm = attributes.realm;
					if (attributes.login) {
						scope.information.login = attributes.login;
						scope.information.guest = false;
					}
					else {
						scope.information.guest = true;
					}
				}
			}
		}
	}

	app.directive('rbsCommerceAuthenticationStep', rbsCommerceAuthenticationStep);

	function rbsCommerceShippingModeSelector($http, $compile, $sce) {
		return {
			restrict: 'AE',
			scope: {
				delivery: '=',
				zoneCode: '=',
				cart: '=',
				addresses: '='
			},
			templateUrl: '/shipping-mode-selector.static.tpl',

			link: function(scope, element, attributes) {
				scope.modes = [];
				scope.display = { readonly: attributes.readonly };

				function setupConfigurationZone() {
					var mode = scope.currentMode;
					var html = '<div class="configuration-zone"';
					if (mode.directiveName) {
						html += ' ' + mode.directiveName + '=""';
					}
					html += '></div>';
					element.find('.configuration-zone').replaceWith(html);
					$compile(element.find('.configuration-zone'))(scope);
				}

				attributes.$observe('readonly', function(newValue) {
					scope.display.readonly = (newValue == 'true');
				});

				$http.post('Action/Rbs/Commerce/GetCompatibleShippingModes', {lines: scope.lines})
					.success(function(data) {
						scope.modes = data;
						if (scope.modes.length == 1) {
							scope.selectMode(0);
						}
						else {
							for (var i = 0; i < scope.modes.length; i++) {
								if (scope.modes[i].id == scope.delivery.modeId) {
									scope.selectMode(i);
								}
							}
						}
					})
					.error(function(data, status, headers) {
						console.log('rbsCommerceShippingModeSelector - GetCompatibleShippingModes error', data, status, headers);
					});

				scope.selectMode = function(index) {
					var mode = scope.modes[index];
					scope.delivery.modeId = mode.id;
					scope.delivery.modeTitle = mode.title;
					scope.currentMode = mode;
					setupConfigurationZone();
				};

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommerceShippingModeSelector.$inject = ['$http', '$compile', '$sce'];
	app.directive('rbsCommerceShippingModeSelector', rbsCommerceShippingModeSelector);

	// A trivial directive for shipping mode without any configuration.
	function rbsCommerceShippingModeConfigurationNone() {
		return {
			restrict: 'AE',
			scope: false,
			link: function(scope) {
				scope.delivery.isConfigured = true;
				scope.delivery.address = null;
			}
		}
	}

	app.directive('rbsCommerceShippingModeConfigurationNone', rbsCommerceShippingModeConfigurationNone);

	// A directive to configure a shipping mode by selecting an address.
	function rbsCommerceShippingModeConfigurationAddress() {
		return {
			restrict: 'AE',
			scope: false,
			templateUrl: '/shipping-mode-configuration-address.static.tpl',
			link: function(scope) {
				if (!scope.delivery.hasOwnProperty('address')) {
					scope.delivery.address = { __addressFieldsId: null };
				}

				if (!scope.delivery.options.hasOwnProperty('usePostalAddress')) {
					scope.delivery.options.usePostalAddress = 1;
				}

				function applyPostalAddressIfNecessary() {
					if (parseInt(scope.delivery.options.usePostalAddress) == 1) {
						scope.delivery.address = angular.copy(scope.cart.address);
						scope.delivery.isConfigured = true;
					}
					else {
						scope.delivery.isConfigured = false;
					}
				}

				applyPostalAddressIfNecessary();

				scope.$watch('delivery.options.usePostalAddress', applyPostalAddressIfNecessary);

				scope.isReadOnly = function() {
					return scope.readonly;
				};

				scope.hasInvalidAddresses = hasInvalidAddresses;
				scope.isValidAddress = isValidAddress;
				scope.applyAddress = applyAddress;
			}
		}
	}

	app.directive('rbsCommerceShippingModeConfigurationAddress', rbsCommerceShippingModeConfigurationAddress);

	function rbsCommercePaymentConnectorSelector($http, $compile, $sce) {
		return {
			restrict: 'AE',
			scope: {
				payment: '=',
				cart: '='
			},
			templateUrl: '/payment-connector-selector.static.tpl',

			link: function(scope, element) {
				scope.connectors = [];
				scope.selectedConnector = null;

				$http.post('Action/Rbs/Commerce/GetCompatiblePaymentConnectors', {transactionId: scope.payment.transaction.id})
					.success(function(data) {
						scope.connectors = data;
						scope.payment.connectorId = null;
						if (scope.connectors.length == 1) {
							scope.selectConnector(0);
						}
					})
					.error(function(data, status, headers) {
						scope.payment.connectorId = null;
						console.log('GetCompatiblePaymentConnectors error', data, status, headers);
					});

				scope.selectConnector = function(index) {
					var connector = scope.connectors[index];
					if (connector.id != scope.payment.connectorId) {
						scope.selectedConnector = connector;
						scope.payment.connectorId = connector.id;

						var html = '<div class="configuration-zone"';
						if (connector.directiveName) {
							html += ' ' + connector.directiveName + '=""></div>';
						}
						else if (connector.html) {
							html += '>' + connector.html + '</div>';
						}
						else {
							html += '></div>';
						}
						element.find('.configuration-zone').replaceWith(html);
						$compile(element.find('.configuration-zone'))(scope);
					}
				};

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommercePaymentConnectorSelector.$inject = ['$http', '$compile', '$sce'];
	app.directive('rbsCommercePaymentConnectorSelector', rbsCommercePaymentConnectorSelector);

	/**
	 * Cart controller.
	 */
	function rbsCommerceCartController(scope, $http, $rootScope) {
		scope.readonlyCart = false;
		scope.cart = null;
		scope.loading = false;
		scope.originalQuantities = {};

		function setCart(data) {
			scope.loading = false;
			scope.cart = data;
			scope.originalQuantities = {};
		}

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false})
				.success(function(data) {
					setCart(data);
				})
				.error(function(data, status, headers) {
					console.log('GetCurrentCart error', data, status, headers);
					setCart({});
				}
			);
		}

		scope.deleteLine = function(index) {
			if (scope.cart.lines.length > index) {
				scope.loading = true;
				var line = scope.cart.lines[index];
				updateCart($http, scope, { lineQuantities: [
					{ key: line.key, quantity: 0}
				] }, function(data) {
					$rootScope.$broadcast('rbsRefreshCart', {'cart': data});
					setCart(data);
				});
			}
		};

		scope.updateLine = function(index) {
			if (scope.cart.lines[index].quantity != scope.originalQuantities.index) {
				scope.loading = true;
				var line = scope.cart.lines[index];
				updateCart($http, scope, { lineQuantities: [
					{ key: line.key, quantity: line.quantity }
				] }, function(data) {
					$rootScope.$broadcast('rbsRefreshCart', {'cart': data});
					setCart(data);
				});
			}
		};

		scope.canOrder = function() {
			if (!scope.cart || !scope.cart.lines || scope.cart.lines.count < 1 || !scope.cart.orderProcess
				|| scope.cart.errors.length) {
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

	rbsCommerceCartController.$inject = ['$scope', '$http', '$rootScope'];
	app.controller('rbsCommerceCartController', rbsCommerceCartController);

	/**
	 * Order process controller.
	 */
	function rbsCommerceOrderProcessController(scope, $http, $rootScope) {
		scope.readonlyCart = true;
		scope.cart = null;
		scope.loading = false;
		scope.originalQuantities = {};
		scope.information = { errors: [], authenticated: false, email: null };
		scope.shipping = { errors: [], deliveries: [] };
		scope.payment = { errors: [], newCouponCode: null, transaction: null };
		scope.currentStep = null;
		scope.steps = ['cart', 'information', 'shipping', 'payment', 'confirm'];

		$http.get('Action/Rbs/Geo/GetAddresses')
			.success(function(data) {
				scope.addresses = data;
			})
			.error(function(data, status, headers) {
				console.log('GetAddresses error', data, status, headers);
			}
		);

		function setCart(data) {
			scope.loading = false;
			scope.cart = data;
		}

		function loadCurrentCart() {
			scope.loading = true;
			$http.post('Action/Rbs/Commerce/GetCurrentCart', {refresh: false})
				.success(function(data) {
					setCart(data);
					scope.setCurrentStep('information');
				})
				.error(function(data, status, headers) {
					console.log('GetCurrentCart error', data, status, headers);
					setCart({});
				}
			);
		}

		function clearErrors(stepName) {
			scope[stepName].errors = [];
		}

		function addError(stepName, message) {
			scope[stepName].errors.push(message);
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

		scope.setCurrentStep = function(stepName) {
			scope.currentStep = stepName;
			var methodName = 'prepare' + stepName.charAt(0).toUpperCase() + stepName.slice(1) + 'Step';
			if (scope.hasOwnProperty(methodName)) {
				scope[methodName]();
			}
		};

		scope.isCurrentStep = function(stepName) {
			return scope.currentStep == stepName;
		};

		scope.hasInvalidAddresses = hasInvalidAddresses;
		scope.isValidAddress = isValidAddress;
		scope.applyAddress = applyAddress;

		/**
		 * Information step
		 */
		scope.prepareInformationStep = function() {
			scope.payment.transaction = null;
			scope.information.userId = scope.cart.userId;
			scope.information.email = scope.cart.email;
			scope.information.confirmEmail = scope.cart.email;
			scope.information.address = getObject(scope.cart.address, true);
			if (!scope.information.hasOwnProperty('isAddressValid')) {
				scope.information.isAddressValid = false;
			}
		};

		scope.canAuthenticate = function() {
			return scope.information.login && scope.information.password;
		};

		scope.authenticate = function() {
			clearErrors('information');
			var postData = {
				realm: scope.information.realm,
				login: scope.information.login,
				password: scope.information.password
			};
			$http.post('Action/Rbs/User/Login', postData)
				.success(function(data) {
					if (data.hasOwnProperty('accessorId')) {
						var params = { 'accessorId': data['accessorId'], 'accessorName': data['name'] };
						$rootScope.$broadcast('rbsUserConnected', params);

						scope.information.guest = false;
						scope.information.userId = data['accessorId'];

						var postData = { userId: scope.information.userId };
						updateCart($http, scope, postData, scope.setAuthenticated);
					}
					else if (data.hasOwnProperty('errors')) {
						for (var i = 0; i < data.errors.length; i++) {
							addError('information', data.errors[i]);
						}
					}
					delete scope.information.password;
				})
				.error(function(data) {
					for (var i = 0; i < data.errors.length; i++) {
						addError('information', data.errors[i]);
					}
					delete scope.information.password;
				});
		};

		scope.logout = function() {
			clearErrors('information');
			$http.post('Action/Rbs/User/Logout')
				.success(function() { window.location.reload(); })
				.error(function(data, status, headers) { console.log('Logout error', data, status, headers); });
		};

		scope.canSetEmail = function() {
			return scope.information.email && scope.information.email == scope.information.confirmEmail;
		};

		scope.setEmail = function() {
			clearErrors('information');

			var postData = {
				email: scope.information.email,
				userId: 0
			};

			$http.post('Action/Rbs/User/CheckEmailAvailability', postData)
				.success(function() {
					updateCart($http, scope, postData, scope.setAuthenticated);
				})
				.error(function(data) {
					for (var i = 0; i < data.errors.length; i++) {
						addError('information', data.errors[i]);
					}
				});
		};

		scope.setAuthenticated = function() {
			scope.information.authenticated = true;
			scope.prepareInformationStep();

			if (scope.cart.locked) {
				for (var i = 1; i < scope.steps.length; i++) {
					scope.setCurrentStep(scope.steps[i]);
					if (scope.steps[i] == 'payment') {
						break;
					}
				}
			}
		};

		scope.unsetAuthenticated = function() {
			scope.information.authenticated = false;
			scope.prepareInformationStep();
		};

		scope.isAuthenticated = function() {
			return scope.information.authenticated;
		};

		scope.isInformationStepComplete = function() {
			return scope.information.email && scope.information.isAddressValid;
		};

		scope.finalizeInformationStep = function() {
			var postData = { address: scope.information.address };
			var callback = function() {
				scope.information.address = getObject(scope.cart.address, true);
				scope.setCurrentStep('shipping');
			};
			updateCart($http, scope, postData, callback);
		};

		/**
		 * Shipping step
		 */
		scope.prepareShippingStep = function() {
			scope.payment.transaction = null;
			scope.shipping.deliveries = [];
			scope.setShippingDeliveries();

			if (scope.shipping.deliveries.length == 0) {
				// TODO: handle forced shipping modes.
				var defaultDelivery = { lines: [], address: {}, options: { } };
				for (var i = 0; i < scope.cart.lines.length; i++) {
					defaultDelivery.lines.push(scope.cart.lines[i]);
				}
				scope.shipping.deliveries.push(defaultDelivery);

				/*var deliveryTemp = { lines: [ scope.cart.lines[0] ] };
				 scope.shipping.deliveries.push(deliveryTemp);*/
			}
		};

		scope.isShippingStepComplete = function() {
			for (var i = 0; i < scope.shipping.deliveries.length; i++) {
				if (!scope.shipping.deliveries[i].isConfigured) {
					return false;
				}
			}
			return true;
		};

		scope.setShippingDeliveries = function() {
			var i, j, k;
			scope.shipping.deliveries = [];
			if ('shippingModes' in scope.cart && angular.isArray(scope.cart.shippingModes)) {
				for (i = 0; i < scope.cart.shippingModes.length; i++) {
					var cartDelivery = scope.cart.shippingModes[i];
					var delivery = {
						modeId: cartDelivery.id,
						modeTitle: cartDelivery.title,
						lines: [],
						address: getObject(cartDelivery.address, true),
						options: getObject(cartDelivery.options, true)
					};
					for (j = 0; j < cartDelivery.lineKeys.length; j++) {
						var key = cartDelivery.lineKeys[j];
						for (k = 0; k < scope.cart.lines.length; k++) {
							if (key == scope.cart.lines[k].key) {
								delivery.lines.push(scope.cart.lines[k]);
							}
						}
					}
					scope.shipping.deliveries.push(delivery);
				}
			}
		};

		scope.finalizeShippingStep = function() {
			scope.cart.shippingModes = [];
			for (var i = 0; i < scope.shipping.deliveries.length; i++) {
				var delivery = scope.shipping.deliveries[i];
				var lineKeys = [];
				for (var j = 0; j < delivery.lines.length; j++) {
					lineKeys.push(delivery.lines[j].key);
				}

				scope.cart.shippingModes.push({
					id: delivery.modeId,
					title: delivery.modeTitle,
					lineKeys: lineKeys,
					address: delivery.address,
					options: delivery.options
				});
			}
			var postData = { shippingModes: scope.cart.shippingModes };
			updateCart($http, scope, postData, function() {
				scope.setShippingDeliveries();
				scope.setCurrentStep('payment');
			});
		};

		/**
		 * Payment step
		 */
		scope.preparePaymentStep = function() {
			scope.payment.transaction = null;
			scope.payment.coupons = scope.cart.coupons;
		};

		scope.addCoupon = function() {
			scope.payment.coupons.push({ 'code': scope.payment.newCouponCode });
			scope.payment.newCouponCode = '';
			updateCart($http, scope, { coupons: scope.payment.coupons }, function() { scope.setCurrentStep('payment'); });
		};

		scope.removeCoupon = function(index) {
			scope.payment.coupons.splice(index, 1);
			updateCart($http, scope, { coupons: scope.payment.coupons }, function() { scope.setCurrentStep('payment'); });
		};

		scope.hasTransaction = function() {
			return scope.payment.transaction;
		};

		scope.getTransaction = function() {
			clearErrors('payment');
			$http.get('Action/Rbs/Commerce/GetTransaction')
				.success(function(transaction) {
					if (angular.isObject(transaction)) {
						scope.payment.transaction = transaction;
					}
					else {
						addError('payment', transaction);
					}
				})
				.error(function(data, status, headers) { console.log('GetTransaction error', data, status, headers); });
		};

		scope.showLinesAmount = function() {
			return scope.cart && (scope.cart.fees.length > 0 || scope.cart.discounts.length > 0);
		};

		scope.showTotalAmount = function() {
			return scope.cart && (scope.cart.creditNotes.length > 0);
		};

		loadCurrentCart();
	}

	rbsCommerceOrderProcessController.$inject = ['$scope', '$http', '$rootScope'];
	app.controller('rbsCommerceOrderProcessController', rbsCommerceOrderProcessController);
})();