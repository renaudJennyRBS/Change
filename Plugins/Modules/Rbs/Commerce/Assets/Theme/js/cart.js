(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceCartData() {
		return {
			restrict : 'C',
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

	function rbsAddressFields($http) {
		return {
			restrict : 'AE',
			require : 'ngModel',
			scope : true,
			templateUrl : '/address-fields.static.tpl',

			link : function (scope, elm, attrs, ngModel) {
				scope.countryCode = null;
				scope.fieldsDef = [];
				scope.fieldValues = {};
				scope.leftColumnElementClass = attrs.leftColumnElementClass;
				scope.rightColumnElementClass = attrs.rightColumnElementClass;
				scope.readonly = attrs.readonly;

				attrs.$observe('countryCode', function(newValue){
					console.log('countryCode', newValue);
					if (newValue != scope.countryCode) {
						scope.fieldsDef = [];
						scope.countryCode = newValue;
					}
				});

				attrs.$observe('readonly', function(newValue){
					console.log('attrs.readonly', newValue);
					scope.readonly = (newValue == 'true');
				});

				scope.$watch('countryCode', function(newValue) {
					console.log('countryCode', newValue);
					if (newValue) {
						$http.post('Action/Rbs/Geo/GetAddressFields', {countryCode: newValue})
							.success (function(data) {
								console.log('GetAddressFields success');
								scope.generateFieldsEditor(data);
							})
							.error(function(data, status, headers) {
								console.log('GetAddressFields error', data, status, headers);
							});
					}
				});

				ngModel.$render = function ngModelRenderFn () {
					scope.fieldValues = ngModel.$viewValue;
				};

				scope.generateFieldsEditor = function (addressFields) {
					var fieldsDef = addressFields.rows;
					if (angular.isObject(fieldsDef)) {
						if (!angular.isObject(ngModel.$viewValue)) {
							ngModel.$setViewValue({});
						}
						scope.fieldsDef = fieldsDef;
						var fieldValues = ngModel.$viewValue;
						var fields = scope.fieldsDef;
						var field;
						for (var i = 0; i < fields.length; i++) {
							field = fields[i];
							var v = null;
							if(fieldValues.hasOwnProperty(field.code)) {
								v = fieldValues[field.code];
							}
							if(v === null) {
								v = field.defaultValue;
								fieldValues[field.code] = v;
							}
						}

						if (angular.isObject(addressFields.fieldsLayout)) {
							fieldValues.__layout = addressFields.fieldsLayout;
						}
						else {
							fieldValues.__layout = undefined;
						}
					}
				};
			}
		}
	}
	rbsAddressFields.$inject = ['$http'];
	app.directive('rbsAddressFields', rbsAddressFields);

	function rbsCommerceShippingModeSelector($http, $compile) {
		return {
			restrict : 'AE',
			require : 'ngModel',
			scope : true,
			templateUrl : '/shipping-mode-selector.static.tpl',

			link : function (scope, element, attrs, ngModel) {
				scope.modes = [];
				scope.directiveName = null;
				scope.selectedId = null;

				$http.post('Action/Rbs/Commerce/GetCompatibleShippingModes', {lines: scope.lines})
					.success (function(data) {
						console.log('GetCompatibleShippingModes success');
						scope.modes = data;
						if (scope.modes.length == 1) {
							scope.selectMode(0);
						}
					})
					.error(function(data, status, headers) {
						console.log('GetCompatibleShippingModes error', data, status, headers);
					});

				scope.selectMode = function(index) {
					console.log('selectMode', index);
					var mode = scope.modes[index];
					if (mode.id != ngModel.modeId) {
						ngModel.modeId = mode.id;
						scope.selectedId = mode.id;
						scope.directiveName = mode.directiveName;

						var html = '<div class="configuration-zone"';
						if (mode.directiveName) {
							html += ' ' + mode.directiveName + '=""';
						}
						html += '></div>';
						element.find('.configuration-zone').replaceWith(html);
						$compile(element.find('.configuration-zone'))(scope);
					}
					console.log('scope.directiveName', scope.directiveName);
				}
			}
		}
	}
	rbsCommerceShippingModeSelector.$inject = ['$http', '$compile'];
	app.directive('rbsCommerceShippingModeSelector', rbsCommerceShippingModeSelector);

	function rbsCommerceShippingModeConfigurationHome() {
		return {
			restrict : 'AE',
			scope : true,
			templateUrl : '/shipping-mode-configuration-home.static.tpl'

			/* TODO */
		}
	}
	app.directive('rbsCommerceShippingModeConfigurationHome', rbsCommerceShippingModeConfigurationHome);

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
		scope.information = {address: {}};
		scope.shipping = [];
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
				.success(function(data) {
					// TODO refresh
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

		loadCurrentCart();
	}
	rbsCommerceOrderProcessController.$inject = ['$scope', '$http'];
	app.controller('rbsCommerceOrderProcessController', rbsCommerceOrderProcessController);

	/* Animations */
	function rbsVerticalIfAnimation() {
		return {
			enter : function(element, done) {
				jQuery(element).css({
					overflow: 'hidden',
					height: 0
				});
				jQuery(element).animate({
					height: element.find('.vertical-if-animation-content').height()
				}, 500, function () {
					element.css('height', 'auto');
					done();
				});
			},

			leave : function(element, done) {
				jQuery(element).css({
					height: element.find('.vertical-if-animation-content').height()
				});
				jQuery(element).animate({
					overflow: 'hidden',
					height: 0
				}, 500, done);
			}
		};
	}
	app.animation('.vertical-if-animation', rbsVerticalIfAnimation);

	function rbsVerticalShowHideAnimation() {
		return {
			beforeAddClass : function(element, className, done) {
				if (className == 'ng-hide') {
					jQuery(element).animate({
						overflow: 'hidden',
						height: 0
					}, done);
				}
				else {
					done();
				}
			},

			removeClass : function(element, className, done) {
				if (className == 'ng-hide') {
					element.css({
						height: 0,
						overflow: 'hidden'
					});
					jQuery(element).animate({
						height: element.find('.vertical-show-hide-animation-content').height()
					}, function () {
						element.css('height', 'auto');
						done();
					});
				}
				else {
					done();
				}
			}
		};
	}
	app.animation('.vertical-show-hide-animation', rbsVerticalShowHideAnimation);
})();