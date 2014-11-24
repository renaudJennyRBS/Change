(function(jQuery) {
	"use strict";
	var app = angular.module('RbsChangeApp');

	//---------------- Menu
	function resizeSidebar() {
		jQuery('.process-sidebar').width(function() { return jQuery(this).parent().width(); });
	}

	jQuery(window).resize(function() {
		resizeSidebar();
	});

	resizeSidebar();

	jQuery('.process-sidebar').affix({
		offset: {
			top: function() { return jQuery('.process-sidebar-container').offset().top - 15; },
			bottom: function() {
				var node = jQuery('.process-sidebar-container').parent();
				return jQuery(document).outerHeight() - node.offset().top - node.outerHeight();
			}
		}
	});

	function rbsCommerceProcessMenu() {
		return {
			restrict: 'AE',
			templateUrl: '/menu.static.tpl',
			link: function(scope) {
			}
		}
	}
	app.directive('rbsCommerceProcessMenu', rbsCommerceProcessMenu);

	function rbsCommerceProcess($rootScope, $compile, AjaxAPI) {
		var cacheCartDataKey = 'cartData';

		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceProcess.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				var self = this;
				var processInfo = null;
				scope.currentStep = null;
				scope.steps = ['identification', 'shipping', 'payment', 'confirm'];
				scope.processData = {};
				scope.loading = false;

				this.loading = function(loading) {
					if (angular.isDefined(loading)) {
						scope.loading = (loading == true);
					}
					return scope.loading;
				};

				this.loadCartData = function(withProcessData) {
					scope.loading = true;
					var params = {detailed: true, visualFormats: scope.parameters['imageFormats']};
					if (withProcessData) {
						params.dataSets = "process";
					}
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, params);
					request.success(function(data, status, headers, config) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							self.setCartData(cartData);
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						scope.loading = false;
					});
					return request;
				};

				this.updateCartData = function(actions) {
					scope.loading = true;
					var request = AjaxAPI.putData('Rbs/Commerce/Cart', actions, {detailed: true, visualFormats: scope.parameters['imageFormats']});
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

				this.showPrices = function(asObject) {
					var showPrices = (scope.parameters && (scope.parameters.displayPricesWithTax || scope.parameters.displayPricesWithoutTax));
					if (asObject && showPrices) {
						return {
							currencyCode: this.getCurrencyCode(),
							displayPricesWithTax: this.parameters('displayPricesWithTax'),
							displayPricesWithoutTax: this.parameters('displayPricesWithoutTax')
						}
					}
					return showPrices;
				};

				this.getCurrencyCode = function() {
					return scope.currencyCode;
				};

				this.parameters = function(name) {
					if (scope.parameters) {
						if (angular.isUndefined(name)) {
							return scope.parameters;
						} else {
							return scope.parameters[name];
						}
					}
					return null;
				};

				this.getCartData = function() {
					return scope.cartData;
				};

				this.getProcessInfo = function() {
					return processInfo;
				};

				this.setCartData = function(cartData) {
					scope.cartData = cartData;
					if (cartData.processData) {
						processInfo = cartData.processData;
					}
					if (this.showPrices()) {
						scope.currencyCode = cartData.common.currencyCode;
					} else {
						scope.currencyCode = null;
					}
					$rootScope.$broadcast('rbsRefreshCart', {'cart': cartData });
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
					if (html != '') {
						$compile(html)(scope, function (clone) {
							parentNode.append(clone);
						});
					}
				};

				this.redrawLines = function() {
					var linesContainer = elem.find('[data-role="cart-lines"]');
					var directiveName = angular.isFunction(this.getLineDirectiveName) ? this.getLineDirectiveName : function(line) {
						return 'rbs-commerce-process-line-default';
					};
					var lines = scope.cartData.lines;
					var html = [];
					angular.forEach(lines, function(line, idx){
						html.push('<tr data-line="cartData.lines['+ idx +']" ' + directiveName(line) + '=""></tr>');
					});
					this.replaceChildren(linesContainer, scope, html.join(''));
				};

				scope.$watch('cartData', function(cartData, oldCartData) {
						if (cartData) {
							self.redrawLines();
							if (!scope.currentStep) {
								self.nextStep();
							}
						}
					}
				);

				this.nextStep = function () {
					this.setCurrentStep(this.getNextStep(scope.currentStep));
				};

				this.getNextStep = function (step) {
					if (!step) {
						return scope.steps[0];
					}
					for (var i = 0; i < scope.steps.length - 1; i++) {
						if (step == scope.steps[i]) {
							return scope.steps[i + 1];
						}
					}
					return null;
				};

				this.setCurrentStep = function(currentStep) {
					scope.currentStep = currentStep;
					var enabled = currentStep !== null, checked = enabled;
					for (var i =0; i < scope.steps.length; i++) {
						var step = scope.steps[i];
						var stepProcessData = this.getStepProcessData(step);
						if (step == currentStep) {
							checked = false;
							stepProcessData.isCurrent = true;
							stepProcessData.isChecked = checked;
							stepProcessData.isEnabled = enabled;
							enabled = false;
						} else {
							stepProcessData.isCurrent = false;
							stepProcessData.isChecked = checked;
							stepProcessData.isEnabled = enabled;
						}
					}
				};

				this.isStepEnabled = function(step) {
					for (var i = 0; i < scope.steps.length; i++) {
						if (scope.steps[i] == step) {
							return true;
						}
						if (scope.steps[i] == scope.currentStep) {
							return false;
						}
					}
					return false;
				};

				this.isStepChecked = function(stepName) {
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

				this.getStepProcessData = function(step) {
					if (step === null) {
						return {name: step, isCurrent: false, isEnabled: false, isChecked: false};
					}
					if (!angular.isObject(scope.processData[step])) {
						scope.processData[step] = {name: step, isCurrent: false, isEnabled: false, isChecked: false};
					}
					return scope.processData[step];
				};

				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);


				var cartData = AjaxAPI.globalVar(cacheCartDataKey);

				if (!cartData) {
					this.loadCartData(true);
				} else {
					this.setCartData(cartData);
				}
			}],

			link: function(scope, elem, attrs, controller) {
				scope.showPrices = controller.showPrices();
				scope.isStepEnabled = function(step) {
					return controller.getStepProcessData(step).isEnabled;
				};
				scope.isStepChecked = function(step) {
					return controller.getStepProcessData(step).isChecked;
				}
			}
		}
	}

	rbsCommerceProcess.$inject = ['$rootScope', '$compile', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceProcess', rbsCommerceProcess);

	function rbsCommerceProcessLineDefault() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceProcessLineDefault.tpl',
			require: '^rbsCommerceProcess',
			replace: true,
			scope: {line: "="},
			link: function(scope, elem, attrs, processController) {
				scope.showPrices = processController.showPrices();
				scope.currencyCode = processController.getCurrencyCode();
				scope.parameters = processController.parameters();
				scope.quantity = scope.line.quantity;
				if (!scope.line.unitBasedAmountWithTaxes && scope.line.basedAmountWithTaxes) {
					scope.line.unitBasedAmountWithTaxes = (scope.line.basedAmountWithTaxes / scope.quantity);
				}
				if (!scope.line.unitBasedAmountWithoutTaxes && scope.line.basedAmountWithoutTaxes) {
					scope.line.unitBasedAmountWithoutTaxes = (scope.line.basedAmountWithoutTaxes / scope.quantity);
				}
			}
		}
	}

	rbsCommerceProcessLineDefault.$inject = [];
	app.directive('rbsCommerceProcessLineDefault', rbsCommerceProcessLineDefault);


	function rbsCommerceIdentificationStep($rootScope, AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceIdentificationStep.tpl',
			require: '^rbsCommerceProcess',
			scope: {},
			link: function(scope, elem, attrs, processController) {
				var cartData = processController.getCartData();

				scope.processData = processController.getStepProcessData('identification');

				scope.processData.userId = processController.parameters('userId');
				scope.processData.login = processController.parameters('login');
				if (scope.processData.userId) {
					scope.processData.email = processController.parameters('email');
				} else {
					scope.processData.email = cartData.process.email;
				}

				scope.processData.realm = processController.parameters('realm');
				scope.processData.confirmed = processController.parameters('confirmed');

				scope.login = function() {
					scope.processData.errors = [];
					var data = {login: scope.processData.login || scope.processData.email, 'password': scope.processData.password,
						realm: scope.processData.realm, ignoreProfileCart: true};
					if (scope.processData.hasOwnProperty('rememberMe')) {
						data.rememberMe = scope.processData.rememberMe
					}
					var request = AjaxAPI.putData('Rbs/User/Login', data);
					request.success(function(data, status, headers, config) {
						var params = {accessorId: data.dataSets.user.accessorId,
							accessorName: data.dataSets.user.name };
						$rootScope.$broadcast('rbsUserConnected', params);

						processController.loadCartData().success(function() {
							var cartData = processController.getCartData();

							scope.processData.confirmed = true;
							scope.processData.email = cartData.process.email;
							scope.processData.userId = cartData.common.userId;
							processController.nextStep();
						});
					}).
					error(function(data, status, headers, config) {
						scope.processData.errors = [data.message];
						scope.processData.password = null;
						console.log('login error', data, status);
					});
				};

				scope.canSetEmail = function() {
					return scope.processData.email && scope.processData.email == scope.processData.confirmEmail;
				};

				scope.setEmail = function() {
					scope.processData.errors = [];
					AjaxAPI.getData('Rbs/User/CheckEmailAvailability', {email: scope.processData.email})
						.success(function(data) {
							scope.processData.email = data.dataSets.user.availableEmail;
							var actions = {
								setAccount: {email: scope.processData.email}
							};
							processController.updateCartData(actions).success(function() {
								processController.nextStep();
								scope.processData.confirmEmail = null;
							})
						})
						.error(function(data) {
							scope.processData.errors = [data.message];
						});
				};

				scope.changeUser = function() {
					scope.processData.errors = [];
					var request = AjaxAPI.getData('Rbs/User/Logout', {'keepCart': true});
					request.success(function(data, status, headers, config) {
						var params = {accessorId: null, accessorName: null};
						$rootScope.$broadcast('rbsUserConnected', params);

						scope.processData.userId = 0;
						scope.processData.login = null;
						scope.processData.email = null;
						scope.processData.confirmed = false;
						var actions = {
							updateContext: {acceptTermsAndConditions : true}
						};
						processController.updateCartData(actions);
					}).error(function(data, status, headers, config) {
						scope.processData.errors = [data.message];
						console.log('changeUser error', data, status);
					});
				};

				scope.setCurrentStep = function() {
					processController.setCurrentStep('identification');
				};

				scope.next = function() {
					processController.nextStep();
				}
			}
		}
	}
	rbsCommerceIdentificationStep.$inject = ['$rootScope', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceIdentificationStep', rbsCommerceIdentificationStep);

	function rbsCommerceShippingStep(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShippingStep.tpl',
			require: '^rbsCommerceProcess',
			scope: {},
			link: function(scope, elem, attrs, processController) {

				scope.processData = processController.getStepProcessData('shipping');
				scope.processData.errors = [];
				scope.userAddresses = [];
				scope.shippingZone = null;
				scope.taxesZones = null;

				scope.isCategory = function(shippingMode, category) {
					if (scope.shippingModesInfo.hasOwnProperty(category)) {
						var shippingModes = scope.shippingModesInfo[category];
						for (var i = 0; i < shippingModes.length; i++) {
							if (shippingModes[i].common.id == shippingMode.id) {
								return true;
							}
						}
					}
					return false;
				};

				scope.hasCategory = function(category) {
					return scope.shippingModesInfo.hasOwnProperty(category);
				};

				scope.getModesByCategory = function(category) {
					return scope.shippingModesInfo.hasOwnProperty(category) ? scope.shippingModesInfo[category] : [];
				};

				scope.loadUserAddresses = function() {
					if (scope.processData.userId) {
						AjaxAPI.getData('Rbs/Geo/Address/', {userId: scope.processData.userId, matchingZone: scope.shippingZone || scope.taxesZones})
							.success(function(data, status, headers, config) {
								scope.userAddresses = data.items;
							}).error(function(data, status, headers, config) {
								scope.userAddresses = [];
							})
					} else {
						scope.userAddresses = [];
					}
				};

				scope.setCurrentStep = function() {
					processController.setCurrentStep('shipping');
				};

				scope.shippingModesValid = function() {
					for (var i = 0; i< scope.processData.shippingModes.length; i++) {
						var shippingMode = scope.processData.shippingModes[i];
						if (!angular.isFunction(shippingMode.valid) || !shippingMode.valid()){
							return false;
						}
					}
					return true;
				};

				scope.next = function() {
					scope.saveMode().success(function() {
						var cartData = processController.getCartData();
						scope.processData.shippingModes = angular.copy(cartData['process']['shippingModes']);
						angular.forEach(scope.processData.shippingModes, function(shippingMode) {
							shippingMode.shippingZone = scope.shippingZone;
							shippingMode.taxesZones = scope.taxesZones;
						});
						processController.nextStep();
					});
				};

				scope.saveMode = function() {
					var actions = {setShippingModes: []};
					angular.forEach(scope.processData.shippingModes, function (shippingMode) {
						if (angular.isFunction(shippingMode.valid) && shippingMode.valid()) {
							actions.setShippingModes.push(shippingMode.valid(true));
						}
					});
					return processController.updateCartData(actions);
				};

				function initializeProcessData() {
					var cartData = processController.getCartData();
					scope.processData.userId = cartData.common.userId;
					scope.processData.processId = cartData.process.orderProcessId;

					var processInfo = processController.getProcessInfo();
					scope.shippingModesInfo = processInfo && processInfo['shippingModes'] ? processInfo['shippingModes'] : {};
					scope.shippingZone = processInfo.shippingZone;
					scope.taxesZones = processInfo.taxesZones;

					if (!scope.processData.shippingModes) {
						scope.processData.shippingModes = angular.copy(cartData['process']['shippingModes']);
					}
					angular.forEach(scope.processData.shippingModes, function(shippingMode) {
						shippingMode.shippingZone = scope.shippingZone;
						shippingMode.taxesZones = scope.taxesZones;
					});
				}

				initializeProcessData();
				scope.loadUserAddresses();
			}
		}
	}
	rbsCommerceShippingStep.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsCommerceShippingStep', rbsCommerceShippingStep);

	function rbsCommerceModeSelector($sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceModeSelector.tpl',
			require: '^rbsCommerceProcess',
			scope: {
				shippingMode: "=",
				shippingModeInfo: "=",
				showPrices: "="
			},
			link: function (scope, elem, attrs, processController) {
				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				}
			}
		}
	}
	rbsCommerceModeSelector.$inject = ['$sce'];
	app.directive('rbsCommerceModeSelector', rbsCommerceModeSelector);


	function rbsCommerceShippingAtHomeStep(AjaxAPI, $sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShippingAtHomeStep.tpl',
			require: '^rbsCommerceProcess',
			scope: {
				processId: "=",
				shippingMode: "=",
				shippingModesInfo: "=",
				userId: "=",
				userAddresses: "="
			},
			link: function (scope, elem, attrs, processController) {

				scope.loadShippingModes = true;
				scope.atHomeAddress = {common:{}, fields:{}, lines:[]};
				scope.atHomeAddressIsValid = false;
				scope.modeIds = {};

				function cleanupAddress(address) {
					var cleanAddress = {common:{addressFieldsId: address.common.addressFieldsId},
						fields: address.fields, lines: address.lines};
					return cleanAddress;
				}

				function atHomeValid(returnData) {
					if (returnData) {
						var shippingMode = scope.shippingMode;
						var data = {
							id: shippingMode.id, title: shippingMode.title,
							lineKeys: shippingMode.lineKeys,
							address: cleanupAddress(scope.atHomeAddress),
							options: {category: shippingMode.options.category}
						};
						return data;
					}
					var valid = !scope.shippingMode.edition && scope.modeIds[scope.shippingMode.id] && !scope.isEmptyAddress(scope.atHomeAddress);
					return valid;
				}

				scope.getDefaultUserAddress = function(userAddresses) {
					var defaultUserAddress = null, address;
					if (angular.isArray(userAddresses)) {
						for (var i = 0; i < userAddresses.length; i++) {
							address = userAddresses[i];
							if (address['default']) {
								if (address['default']['shipping']) {
									return address;
								} else if (address['default']['default']) {
									defaultUserAddress = address;
								}
							}
						}
					}
					return defaultUserAddress;
				};

				scope.isEmptyAddress = function(address) {
					if (angular.isObject(address) && !angular.isArray(address)) {
						if (address.fields && address.fields.countryCode && address.lines && address.lines.length) {
							return false;
						}
					}
					return true;
				};

				scope.showPrices = processController.showPrices(true);

				scope.$watch('atHomeAddress', function (address) {
					scope.shippingMode.allowedShippingModesInfo = [];
					if (!scope.isEmptyAddress(address)) {
						processController.loading(true);
						scope.loadShippingModes = true;

						var params = scope.shippingMode.taxesZones ? {dataSets:'fee'} : {};

						AjaxAPI.getData('Rbs/Commerce/Process/'+ scope.processId +'/ShippingModesByAddress/', {address:address}, params)
							.success(function(data, status, headers, config) {
								if (scope.atHomeAddress !== address) {
									return;
								}
								var validCurrentMode = false;
								angular.forEach(data.items, function(modeData) {
									if (modeData.common.id == scope.shippingMode.id) {
										scope.shippingMode.valid = atHomeValid;
										validCurrentMode = true;
									}
									angular.forEach(scope.shippingModesInfo, function(shippingModeInfo) {
										if (shippingModeInfo.common.id == modeData.common.id) {
											if (params.dataSets == 'fee') {
												shippingModeInfo.fee = modeData.fee;
											}
											scope.shippingMode.allowedShippingModesInfo.push(shippingModeInfo);
										}
									});
								});
								if (!validCurrentMode) {
									scope.shippingMode.id = 0;
								}
								processController.loading(false);
								scope.loadShippingModes = false;
							}).error(function(data, status) {
								if (scope.atHomeAddress !== address) {
									return;
								}
								console.log('shippingModesByAddress error', data, status);
								scope.shippingMode.id = 0;
								processController.loading(false);
								scope.loadShippingModes = false;
							});
					}
				});

				scope.$watch('shippingMode.id', function (id) {
					if (id) {
						angular.forEach(scope.shippingMode.allowedShippingModesInfo, function(modeInfo) {
							if (modeInfo.common.id == id) {
								scope.shippingMode.title = modeInfo.common.title;
								if (!angular.isObject(scope.shippingMode.options) || angular.isArray(scope.shippingMode.options)) {
									scope.shippingMode.options = {};
								}
								scope.shippingMode.options.category = modeInfo.common.category;
								scope.shippingMode.valid = atHomeValid;
							}
						});
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				scope.setAddress = function(address) {
					scope.atHomeAddress = address;
					scope.shippingMode.edition = false;
					scope.matchingZoneError = null;
					processController.loading(false);
				};

				scope.editAddress = function() {
					scope.matchingZoneError = null;
					scope.shippingMode.edition = true;
				};

				scope.selectUserAddress= function(address) {
					scope.validateAddress(address);
				};

				scope.useAddress = function() {
					var address = angular.copy(scope.atHomeAddress);
					scope.validateAddress(address).success(function(data) {
						if (scope.userId && address.common && address.common.useName && address.common.name) {
							delete address.common.id;
							delete address.default;
							processController.loading(true);
							AjaxAPI.postData('Rbs/Geo/Address/', address)
								.success(function(data, status, headers, config) {
									var addedAddress = data.dataSets;
									scope.userAddresses.push(addedAddress);
								}).error(function(data, status, headers, config) {
									console.log('useAddress error', data, status);
									processController.loading(false);
								})
						}
					});
				};

				scope.setTaxZone = function(taxZone) {
					if (processController.getCartData().common.zone != taxZone) {
						var actions = {
							setZone: {zone : taxZone}
						};
						processController.updateCartData(actions)
					}
				};

				scope.validateAddress = function(address) {
					var params = {address: address};

					var shippingZone = scope.shippingMode.shippingZone;
					if (shippingZone) {
						params.matchingZone = shippingZone;
					}

					var taxesZones = scope.shippingMode.taxesZones;
					if (taxesZones) {
						params.compatibleZones = taxesZones;
					}

					scope.matchingZoneError = null;
					var request = AjaxAPI.postData('Rbs/Geo/ValidateAddress', params);
					request.success(function(data) {
							if (taxesZones && taxesZones.length) {
								if (angular.isArray(data.dataSets.compatibleZones) && data.dataSets.compatibleZones.length) {
									scope.setAddress(data.dataSets);
									var taxZone = data.dataSets.compatibleZones[0];
									scope.setTaxZone(taxZone);
								} else {
									scope.setTaxZone(null);
								}
							} else {
								scope.setAddress(data.dataSets);
							}
						}).error(function(data, status, headers, config) {
							if (status == 409 && data && data.error &&
								(data.code == 'matchingZone' || data.code == 'compatibleZones')) {
								scope.matchingZoneError = data.message;
							} else {
								console.log('validateAddress error', data, status);
							}
							processController.loading(false);
						});
					return request;
				};

				scope.$watchCollection('shippingModesInfo', function(shippingModesInfo) {
					//Initialisation
					var shippingModeId = scope.shippingMode.id;
					angular.forEach(shippingModesInfo, function(shippingModeInfo) {
						scope.modeIds[shippingModeInfo.common.id] = true;
						if (shippingModeInfo.common.id == shippingModeId) {
							if (!scope.isEmptyAddress(scope.shippingMode.address)) {
								scope.atHomeAddress = scope.shippingMode.address;
							}
						}
					});
				});

				scope.$watchCollection('userAddresses', function(userAddresses) {
					if (scope.isEmptyAddress(scope.atHomeAddress)) {
						var defaultUserShippingAddress = scope.getDefaultUserAddress(userAddresses);
						if (defaultUserShippingAddress) {
							scope.selectUserAddress(defaultUserShippingAddress);
							return;
						}
						scope.editAddress();
					}
				});
			}
		}
	}
	rbsCommerceShippingAtHomeStep.$inject = ['RbsChange.AjaxAPI', '$sce'];
	app.directive('rbsCommerceShippingAtHomeStep', rbsCommerceShippingAtHomeStep);

	function rbsCommerceSummaryShippingAtHomeStep($sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceSummaryShippingAtHomeStep.tpl',
			scope: {
				shippingMode: "=",
				shippingModesInfo: "="
			},

			link: function (scope) {
				angular.forEach(scope.shippingModesInfo, function(shippingModeInfo) {
					if (shippingModeInfo.common.id == scope.shippingMode.id) {
						scope.shippingModeInfo = shippingModeInfo;
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				}
			}
		}
	}
	rbsCommerceSummaryShippingAtHomeStep.$inject = ['$sce'];
	app.directive('rbsCommerceSummaryShippingAtHomeStep', rbsCommerceSummaryShippingAtHomeStep);

	function rbsCommerceShippingOtherStep() {
		return {
			restrict: 'A',
			template: '<div></div>',
			require: '^rbsCommerceProcess',
			scope: {
				processId: "=",
				shippingMode: "=",
				shippingModesInfo: "=",
				userId: "=",
				userAddresses: "="
			},
			link: function (scope, elem, attrs, processController) {
				var summary = attrs.summary == 'true';
				scope.showPrices = processController.showPrices(true);

				scope.$watchCollection('shippingModesInfo', function(shippingModesInfo) {
					var html = [];
					angular.forEach(shippingModesInfo, function (shippingModeInfo, index) {
						var directiveName;
						if (summary) {
							if (shippingModeInfo.common.id == scope.shippingMode.id)
							{
								directiveName = shippingModeInfo.directiveNames ? shippingModeInfo.directiveNames.summary : null;
								if (directiveName) {
									html.push('<div '+directiveName+'=""');
									html.push(' data-shipping-mode="shippingMode"');
									html.push(' data-shipping-mode-info="shippingModesInfo['+index+']"');
									html.push('></div>');
								}
							}
						} else {
							directiveName = shippingModeInfo.directiveNames ? shippingModeInfo.directiveNames.editor : null;
							if (directiveName) {
								html.push('<div rbs-commerce-mode-selector=""');
								html.push(' data-show-prices="showPrices"');
								html.push(' data-shipping-mode="shippingMode"');
								html.push(' data-shipping-mode-info="shippingModesInfo['+index+']"');
								html.push('></div>');

								html.push('<div '+directiveName+'="" ');
								html.push(' data-ng-show="shippingMode.id == shippingModesInfo['+index+'].common.id"');
								html.push(' data-process-id="processId"');
								html.push(' data-shipping-mode="shippingMode"');
								html.push(' data-shipping-mode-info="shippingModesInfo['+index+']"');
								html.push(' data-user-id="userId"');
								html.push(' data-user-addresses="userAddresses"');
								html.push('></div>');
							}
						}
					});
					processController.replaceChildren(elem, scope, html.join(''));
				});
			}
		}
	}
	app.directive('rbsCommerceShippingOtherStep', rbsCommerceShippingOtherStep);

	function rbsCommercePaymentStep(AjaxAPI, $sce, $filter, $compile) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommercePaymentStep.tpl',
			require: '^rbsCommerceProcess',
			scope: {},
			link: function(scope, elem, attrs, processController) {

				scope.processData = processController.getStepProcessData('payment');
				scope.processData.errors = [];
				if (!scope.processData.id) {
					scope.processData.id = 0;
				}

				scope.userAddresses = null;
				scope.paymentConnectorsInfo = [];
				scope.paymentConnectorInfo = null;
				scope.transaction = null;

				scope.loadUserAddresses = function() {
					if (scope.processData.userId) {
						AjaxAPI.getData('Rbs/Geo/Address/', {userId: scope.processData.userId})
							.success(function(data, status, headers, config) {
								scope.userAddresses = data.items;
							}).error(function(data, status, headers, config) {
								scope.userAddresses = [];
							})
					} else {
						scope.userAddresses = [];
					}
				};

				scope.getDefaultUserAddress = function(userAddresses) {
					var defaultUserAddress = null, address;
					if (angular.isArray(userAddresses)) {
						for (var i = 0; i < userAddresses.length; i++) {
							address = userAddresses[i];
							if (address['default']) {
								if (address['default']['billing']) {
									return address;
								} else if (address['default']['default']) {
									defaultUserAddress = address;
								}
							}
						}
					}

					if (!defaultUserAddress) {
						var shippingModes = processController.getCartData()['process']['shippingModes'];
						angular.forEach(shippingModes, function(shippingMode) {
							if (!defaultUserAddress && shippingMode.options &&
								shippingMode.options.category == 'atHome' &&
								!scope.isEmptyAddress(shippingMode.address)) {
								defaultUserAddress = shippingMode.address;
							}
						});
					}
					return defaultUserAddress;
				};

				scope.isEmptyAddress = function(address) {
					if (angular.isObject(address) && !angular.isArray(address)) {
						if (address.fields && address.fields.countryCode && address.lines && address.lines.length) {
							return false;
						}
					}
					return true;
				};

				scope.$watchCollection('userAddresses', function(userAddresses) {
					if (angular.isArray(userAddresses)) {
						if (scope.isEmptyAddress(scope.processData.address)) {
							var defaultUserShippingAddress = scope.getDefaultUserAddress(userAddresses);
							if (defaultUserShippingAddress) {
								scope.selectUserAddress(defaultUserShippingAddress);
								return;
							}
							scope.editAddress();
						}
					}
				});

				scope.editAddress = function() {
					scope.processData.addressEdition = true;
				};

				scope.selectUserAddress= function(address) {
					scope.processData.address = angular.copy(address);
					scope.processData.addressEdition = false;
				};

				scope.useAddress = function() {
					var address = angular.copy(scope.processData.address);
					processController.loading(true);
					if (scope.userId && address.common && address.common.useName && address.common.name) {
						delete address.common.id;
						delete address.default;
						AjaxAPI.postData('Rbs/Geo/Address/', address)
							.success(function(data, status, headers, config) {
								var addedAddress = data.dataSets;
								scope.userAddresses.push(addedAddress);
								scope.selectUserAddress(addedAddress);
								processController.loading(false);
							}).error(function(data, status, headers, config) {
								console.log('useAddress error', data, status);
								processController.loading(false);
							})
					} else {
						AjaxAPI.postData('Rbs/Geo/ValidateAddress', {address: address})
							.success(function(data, status, headers, config) {
								scope.selectUserAddress(data.dataSets);
								processController.loading(false);
							}).error(function(data, status, headers, config) {
								console.log('validateAddress error', data, status);
								processController.loading(false);
							})
					}
				};

				scope.hasTransaction = function() {
					return scope.transaction != null;
				};

				scope.modify = function() {
					return scope.transaction = null;
				};

				scope.addCoupon = function() {
					var coupons = angular.copy(scope.processData.coupons);
					coupons.push({code: scope.processData.newCouponCode});
					var actions = {setCoupons: coupons};
					return processController.updateCartData(actions).success(function(data, status, headers, config) {
						var cartData = processController.getCartData();
						refreshAmounts(cartData);
						scope.processData.coupons = cartData['coupons'];
						scope.processData.newCouponCode = null;
					}).error(function(data, status, headers, config) {
						console.log('addCoupon error', data, status);
						scope.processData.newCouponCode = null;
					});
				};

				scope.removeCoupon = function(couponIndex) {
					var coupons = [];
					angular.forEach(scope.processData.coupons, function(coupon, i) {
						if (i != couponIndex) {
							coupons.push(coupon);
						}
					});
					var actions = {setCoupons: coupons};
					return processController.updateCartData(actions).success(function(data, status, headers, config) {
						var cartData = processController.getCartData();
						refreshAmounts(cartData);
						scope.processData.coupons = cartData['coupons'];
					}).error(function(data, status, headers, config) {
						console.log('removeCoupon error', data, status);
					});
				};

				scope.cleanupAddress = function(address) {
					if (address) {
						if (address.common) {
							address.common = {addressFieldsId: address.common.addressFieldsId};
						}
						if (address.default) {
							delete address.default
						}
					}
					return address;
				};

				scope.savePayment = function() {
					var actions = {
						setCoupons: scope.processData.coupons,
						setAddress: scope.processData.address
					};
					processController.updateCartData(actions).success(function(data, status, headers, config) {
						refreshAmounts(processController.getCartData());
						scope.getTransaction();
					}).error(function(data, status, headers, config) {
						console.log('savePayment error', data, status);
					})
				};

				scope.getTransaction = function() {
					processController.loading(true);
					scope.processData.errors = [];
					AjaxAPI.getData('Rbs/Commerce/Cart/Transaction', {}, {dataSets: 'connectors'})
						.success(function(data, status, headers, config) {
							scope.transaction = data.dataSets;
							processController.loading(false);
						}).error(function(data, status, headers, config) {
							console.log('getTransaction error', data, status);
							if (status == 409 && data && data.data) {
								angular.forEach(data.data, function(error) {
									scope.processData.errors.push(error.message);
								})
							}
							scope.transaction = null;
							processController.loading(false);
						})
				};

				scope.selectConnector = function(connectorIndex) {
					scope.paymentConnectorInfo = scope.paymentConnectorsInfo[connectorIndex];
					if (scope.paymentConnectorInfo) {
						scope.processData.id = scope.paymentConnectorInfo.common.id;

					}
				};

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				function getConnectIndexById(connectorId)  {
					if (connectorId) {
						for (var i =0; i < scope.paymentConnectorsInfo.length; i++)  {
							if (scope.paymentConnectorsInfo[i]['common'].id == connectorId) {
								return i;
							}
						}
					}
					return null;
				}

				function refreshAmounts(cartData) {
					var currencyCode = cartData.common.currencyCode;
					scope.amountLines = [];

					scope.linesAmount = $filter('rbsFormatPrice')(cartData.amounts.linesAmountWithTaxes || cartData.amounts.linesAmountWithoutTaxes , currencyCode);
					scope.linesProducts = 0;
					angular.forEach(cartData.lines, function(line) {
						scope.linesProducts += line.quantity;
					});


					angular.forEach(cartData.fees, function(fee) {
						var amount = fee.amountWithTaxes || fee.amountWithoutTaxes;
						if (amount) {
							var line = {amount:  $filter('rbsFormatPrice')(amount , currencyCode),
								title: fee.designation};
							scope.amountLines.push(line);
						}
					});

					angular.forEach(cartData.discounts, function(discount) {
						var amount = discount.amountWithTaxes || discount.amountWithoutTaxes;
						if (amount) {
							var line = {amount:  $filter('rbsFormatPrice')(amount, currencyCode),
							title: discount.title};
							scope.amountLines.push(line);
						}
					});

					if (cartData.totalTaxes && cartData.totalTaxes.length) {
						var taxesAmount = 0;
						angular.forEach(cartData.totalTaxes, function(tax) {
							if (tax.hasOwnProperty('value')) {
								taxesAmount += tax.value;
							}
						});
						scope.taxesAmount = $filter('rbsFormatPrice')(taxesAmount, currencyCode);
					} else {
						scope.taxesAmount = null;
					}

					if (cartData.creditNotes.length) {
						var creditNotesAmount = 0;
						angular.forEach(cartData.creditNotes, function(creditNote) {
							if (creditNote.hasOwnProperty('amountWithTaxes')) {
								creditNotesAmount += creditNote.amountWithTaxes;
							}
						});
						scope.creditNotesAmount = $filter('rbsFormatPrice')(creditNotesAmount, currencyCode);
					}

					scope.paymentAmount = $filter('rbsFormatPrice')(cartData.amounts.paymentAmount, currencyCode);
				}

				function initializeProcessData() {
					var cartData = processController.getCartData();
					refreshAmounts(cartData);
					scope.processData.userId = cartData.common.userId;
					scope.processData.processId = cartData.process.orderProcessId;
					scope.processData.address = cartData.process.address;
					if (!scope.processData.address) {
						scope.processData.address = {common: {}, fields:{}};
					}
					scope.processData.coupons = cartData.coupons;

					var processInfo = processController.getProcessInfo();
					scope.paymentConnectorsInfo = (processInfo && processInfo['paymentConnectors'] && processInfo['paymentConnectors']['default']) ? processInfo['paymentConnectors']['default'] : [];

					var connectorIndex = getConnectIndexById(scope.processData.id);
					if (connectorIndex !== null) {
						scope.selectConnector(connectorIndex);
					} else {
						scope.processData.id = 0;
					}

					scope.loadUserAddresses();
				}

				function redrawConnectorConfiguration(htmlConnectors) {
					var linesContainer = elem.find('[data-role="connector-configuration-zone"]');
					processController.replaceChildren(linesContainer, scope, htmlConnectors);
				}

				scope.$watch('transaction', function(transaction) {
					if (transaction && transaction.connectors) {
						var html = [];
						angular.forEach(transaction.connectors, function(connector, i) {
							if (connector.transaction && connector.transaction.directiveName) {
								var infoIndex = null;
								angular.forEach(scope.paymentConnectorsInfo, function(paymentConnectorInfo, i) {
									if (paymentConnectorInfo.common.id == connector.common.id) {
										infoIndex = i;
									}
								});
								if (infoIndex !== null) {
									html.push('<div data-' + connector.transaction.directiveName + '=""');
									html.push(' data-ng-if="'+ connector.common.id +' == processData.id"');
									html.push(' data-process-data="processData"');
									html.push(' data-transaction="transaction"');
									html.push(' data-connector-configuration="transaction.connectors['+ i +']"');
									html.push(' data-connector-info="paymentConnectorsInfo['+ infoIndex +']"');
									html.push('></div>');
								}
							}
						});
						redrawConnectorConfiguration(html.length ? html.join('') : null);
					} else {
						redrawConnectorConfiguration(null);
					}
				});

				initializeProcessData();
			}
		}
	}
	rbsCommercePaymentStep.$inject = ['RbsChange.AjaxAPI', '$sce', '$filter', '$compile'];
	app.directive('rbsCommercePaymentStep', rbsCommercePaymentStep);
})(window.jQuery);