(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceProcessLineDefault() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceProcessLineDefault.tpl',
			replace: true,
			scope: {
				line: '=',
				processEngine: '='
			},
			link: function(scope) {
				scope.showPrices = scope.processEngine.showPrices();
				scope.currencyCode = scope.processEngine.getCurrencyCode();
				scope.parameters = scope.processEngine.parameters();
				scope.quantity = scope.line.quantity;
				if (!scope.line.unitBasedAmountWithTaxes && scope.line['basedAmountWithTaxes']) {
					scope.line.unitBasedAmountWithTaxes = (scope.line['basedAmountWithTaxes'] / scope.quantity);
				}
				if (!scope.line.unitBasedAmountWithoutTaxes && scope.line['basedAmountWithoutTaxes']) {
					scope.line.unitBasedAmountWithoutTaxes = (scope.line['basedAmountWithoutTaxes'] / scope.quantity);
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
			scope: {
				processEngine: '='
			},
			link: function(scope) {
				var cartData = scope.processEngine.getObjectData();

				scope.processData = scope.processEngine.getStepProcessData('identification');

				scope.processData.userId = scope.processEngine.parameters('userId');
				scope.processData.login = scope.processEngine.parameters('login');
				if (scope.processData.userId) {
					scope.processData.email = scope.processEngine.parameters('email');
				}
				else {
					scope.processData.email = cartData.process.email;
				}

				scope.processData.realm = scope.processEngine.parameters('realm');
				scope.processData.confirmed = scope.processEngine.parameters('confirmed');

				scope.login = function() {
					scope.processData.errors = [];
					var data = {
						login: scope.processData.login || scope.processData.email, 'password': scope.processData.password,
						realm: scope.processData.realm, ignoreProfileCart: true
					};
					if (scope.processData.hasOwnProperty('rememberMe')) {
						data.rememberMe = scope.processData.rememberMe
					}
					var request = AjaxAPI.putData('Rbs/User/Login', data);
					request.success(function(data) {
						var params = {
							accessorId: data.dataSets.user.accessorId,
							accessorName: data.dataSets.user.name
						};
						$rootScope.$broadcast('rbsUserConnected', params);

						scope.processEngine.loadObjectData().success(function() {
							var cartData = scope.processEngine.getObjectData();

							scope.processData.confirmed = true;
							scope.processData.email = cartData.process.email;
							scope.processData.userId = cartData.common.userId;
							scope.processEngine.nextStep();
						});
					}).
						error(function(data, status) {
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
					AjaxAPI.getData('Rbs/User/CheckEmailAvailability', { email: scope.processData.email })
						.success(function(data) {
							scope.processData.email = data.dataSets.user['availableEmail'];
							var actions = {
								setAccount: { email: scope.processData.email }
							};
							var request = scope.processEngine.updateObjectData(actions);
							if (request) {
								request.success(function() {
									scope.processEngine.nextStep();
									scope.processData.confirmEmail = null;
								});
							}
							else {
								scope.processEngine.nextStep();
								scope.processData.confirmEmail = null;
							}
						})
						.error(function(data) {
							scope.processData.errors = [data.message];
						});
				};

				scope.changeUser = function() {
					scope.processData.errors = [];
					var request = AjaxAPI.getData('Rbs/User/Logout', { 'keepCart': true });
					request.success(function() {
						var params = { accessorId: null, accessorName: null };
						$rootScope.$broadcast('rbsUserConnected', params);

						scope.processData.userId = 0;
						scope.processData.login = null;
						scope.processData.email = null;
						scope.processData.confirmed = false;
						var actions = {
							updateContext: { acceptTermsAndConditions: true }
						};
						scope.processEngine.updateObjectData(actions);
					});
					request.error(function(data, status) {
						scope.processData.errors = [data.message];
						console.log('changeUser error', data, status);
					});
				};

				scope.setCurrentStep = function() {
					scope.processEngine.setCurrentStep('identification');
				};

				scope.next = function() {
					scope.processEngine.nextStep();
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
			scope: {
				processEngine: '='
			},
			link: function(scope) {
				scope.processData = scope.processEngine.getStepProcessData('shipping');
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
						AjaxAPI.getData('Rbs/Geo/Address/',
							{ userId: scope.processData.userId, matchingZone: scope.shippingZone || scope.taxesZones })
							.success(function(data) {
								scope.userAddresses = data.items;
							}).error(function() {
								scope.userAddresses = [];
							})
					}
					else {
						scope.userAddresses = [];
					}
				};

				scope.setCurrentStep = function() {
					scope.processEngine.setCurrentStep('shipping');
				};

				scope.shippingModesValid = function() {
					for (var i = 0; i < scope.processData.shippingModes.length; i++) {
						var shippingMode = scope.processData.shippingModes[i];
						if (!angular.isFunction(shippingMode.valid) || !shippingMode.valid()) {
							return false;
						}
					}
					return true;
				};

				scope.next = function() {
					var request = scope.saveMode();
					if (request) {
						request.success(function() {
							var cartData = scope.processEngine.getObjectData();
							scope.processData.shippingModes = angular.copy(cartData['process']['shippingModes']);
							angular.forEach(scope.processData.shippingModes, function(shippingMode) {
								shippingMode.shippingZone = scope.shippingZone;
								shippingMode.taxesZones = scope.taxesZones;
							});
							scope.processEngine.nextStep();
						});
					}
					else {
						var cartData = scope.processEngine.getObjectData();
						scope.processData.shippingModes = angular.copy(cartData['process']['shippingModes']);
						angular.forEach(scope.processData.shippingModes, function(shippingMode) {
							shippingMode.shippingZone = scope.shippingZone;
							shippingMode.taxesZones = scope.taxesZones;
						});
						scope.processEngine.nextStep();
					}
				};

				scope.saveMode = function() {
					var actions = { setShippingModes: [] };
					angular.forEach(scope.processData.shippingModes, function(shippingMode) {
						if (angular.isFunction(shippingMode.valid) && shippingMode.valid()) {
							actions.setShippingModes.push(shippingMode.valid(true));
						}
					});
					return scope.processEngine.updateObjectData(actions);
				};

				function initializeProcessData() {
					var cartData = scope.processEngine.getObjectData();
					scope.processData.userId = cartData.common.userId;
					scope.processData.processId = cartData.process.orderProcessId;

					var processInfo = scope.processEngine.getProcessInfo();
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
			scope: {
				shippingMode: '=',
				shippingModeInfo: '=',
				showPrices: '=',
				processEngine: '='
			},
			link: function(scope) {
				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommerceModeSelector.$inject = ['$sce'];
	app.directive('rbsCommerceModeSelector', rbsCommerceModeSelector);

	function rbsCommerceShippingAtHomeStep(AjaxAPI, $sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShippingAtHomeStep.tpl',
			scope: {
				processId: '=',
				shippingMode: '=',
				shippingModesInfo: '=',
				userId: '=',
				userAddresses: '=',
				processEngine: '='
			},
			link: function(scope) {
				scope.loadShippingModes = true;
				scope.atHomeAddress = { common: {}, fields: {}, lines: [] };
				scope.atHomeAddressIsValid = false;
				scope.modeIds = {};

				function cleanupAddress(address) {
					return {
						common: { addressFieldsId: address.common.addressFieldsId },
						fields: address.fields, lines: address.lines
					};
				}

				function atHomeValid(returnData) {
					var shippingMode = scope.shippingMode;
					if (returnData) {
						return {
							id: shippingMode.id, title: shippingMode.title,
							lineKeys: shippingMode.lineKeys,
							address: cleanupAddress(scope.atHomeAddress),
							options: { category: shippingMode.options.category }
						};
					}
					return !shippingMode.edition && scope.modeIds[shippingMode.id] && !scope.isEmptyAddress(scope.atHomeAddress);
				}

				scope.getDefaultUserAddress = function(userAddresses) {
					var defaultUserAddress = null, address;
					if (angular.isArray(userAddresses)) {
						for (var i = 0; i < userAddresses.length; i++) {
							address = userAddresses[i];
							if (address['default']) {
								if (address['default']['shipping']) {
									return address;
								}
								else if (address['default']['default']) {
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

				scope.showPrices = scope.processEngine.showPrices(true);

				scope.$watch('atHomeAddress', function(address) {
					scope.shippingMode.allowedShippingModesInfo = [];
					if (!scope.isEmptyAddress(address)) {
						scope.processEngine.loading(true);
						scope.loadShippingModes = true;

						var params = scope.shippingMode.taxesZones ? { dataSets: 'fee' } : {};

						AjaxAPI.getData('Rbs/Commerce/Process/' + scope.processId + '/ShippingModesByAddress/',
							{ address: address }, params)
							.success(function(data) {
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
								scope.processEngine.loading(false);
								scope.loadShippingModes = false;
							}).error(function(data, status) {
								if (scope.atHomeAddress !== address) {
									return;
								}
								console.log('shippingModesByAddress error', data, status);
								scope.shippingMode.id = 0;
								scope.processEngine.loading(false);
								scope.loadShippingModes = false;
							});
					}
				});

				scope.$watch('shippingMode.id', function(id) {
					if (id) {
						var found = false;
						angular.forEach(scope.shippingMode.allowedShippingModesInfo, function(modeInfo) {
							if (modeInfo.common.id == id) {
								found = true;
								scope.shippingMode.title = modeInfo.common.title;
								if (!angular.isObject(scope.shippingMode.options) ||
									angular.isArray(scope.shippingMode.options)) {
									scope.shippingMode.options = {};
								}
								scope.shippingMode.options.category = modeInfo.common.category;
								scope.shippingMode.valid = atHomeValid;
							}
						});

						if (!found && scope.shippingMode.edition) {
							scope.setAddress(scope.atHomeAddress);
						}
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				scope.setAddress = function(address) {
					scope.atHomeAddress = address;
					scope.shippingMode.edition = false;
					scope.matchingZoneError = null;
					scope.processEngine.loading(false);
				};

				scope.editAddress = function() {
					scope.shippingMode.id = 0;
					scope.matchingZoneError = null;
					scope.shippingMode.edition = true;
				};

				scope.selectUserAddress = function(address) {
					scope.validateAddress(address);
				};

				scope.useAddress = function() {
					var address = angular.copy(scope.atHomeAddress);
					scope.validateAddress(address).success(function() {
						if (scope.userId && address.common && address.common['useName'] && address.common.name) {
							delete address.common.id;
							address.default = {'default': true, 'shipping': true};
							angular.forEach(scope.userAddresses, function(userAddress) {
								if (userAddress.default) {
									if (userAddress.default.default) {
										delete address.default.default;
									}
									if (userAddress.default.shipping) {
										delete address.default.shipping;
									}
								}
							});

							scope.processEngine.loading(true);
							AjaxAPI.postData('Rbs/Geo/Address/', address)
								.success(function(data) {
									var addedAddress = data.dataSets;
									scope.userAddresses.push(addedAddress);
								}).error(function(data, status) {
									console.log('useAddress error', data, status);
									scope.processEngine.loading(false);
								});
						}
					});
				};

				scope.setTaxZone = function(taxZone) {
					if (scope.processEngine.getObjectData().common.zone != taxZone) {
						var actions = {
							setZone: { zone: taxZone }
						};
						scope.processEngine.updateObjectData(actions);
					}
				};

				scope.validateAddress = function(address) {
					var params = { address: address };

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
							}
							else {
								scope.setTaxZone(null);
							}
						}
						else {
							scope.setAddress(data.dataSets);
						}
					}).error(function(data, status) {
						if (status == 409 && data && data.error &&
							(data.code == 'matchingZone' || data.code == 'compatibleZones')) {
							scope.matchingZoneError = data.message;
						}
						else {
							console.log('validateAddress error', data, status);
						}
						scope.processEngine.loading(false);
					});
					return request;
				};

				scope.$watchCollection('shippingModesInfo', function(shippingModesInfo) {
					// Initialisation.
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
						}
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
				shippingMode: '=',
				shippingModesInfo: '=',
				processEngine: '='
			},

			link: function(scope) {
				angular.forEach(scope.shippingModesInfo, function(shippingModeInfo) {
					if (shippingModeInfo.common.id == scope.shippingMode.id) {
						scope.shippingModeInfo = shippingModeInfo;
					}
				});

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommerceSummaryShippingAtHomeStep.$inject = ['$sce'];
	app.directive('rbsCommerceSummaryShippingAtHomeStep', rbsCommerceSummaryShippingAtHomeStep);

	function rbsCommerceShippingOtherStep() {
		return {
			restrict: 'A',
			template: '<div></div>',
			scope: {
				processId: "=",
				shippingMode: "=",
				shippingModesInfo: "=",
				userId: "=",
				userAddresses: "=",
				processEngine: '='
			},
			link: function(scope, elem, attrs) {
				var summary = attrs.summary == 'true';
				scope.showPrices = scope.processEngine.showPrices(true);

				scope.$watchCollection('shippingModesInfo', function(shippingModesInfo) {
					var html = [];
					angular.forEach(shippingModesInfo, function(shippingModeInfo, index) {
						var directiveName;
						if (summary) {
							if (shippingModeInfo.common.id == scope.shippingMode.id) {
								directiveName = shippingModeInfo['directiveNames'] ? shippingModeInfo['directiveNames'].summary : null;
								if (directiveName) {
									html.push('<div ' + directiveName + '=""');
									html.push(' data-shipping-mode="shippingMode"');
									html.push(' data-shipping-mode-info="shippingModesInfo[' + index + ']"');
									html.push(' data-process-engine="processEngine"');
									html.push('></div>');
								}
							}
						}
						else {
							directiveName = shippingModeInfo['directiveNames'] ? shippingModeInfo['directiveNames'].editor : null;
							if (directiveName) {
								html.push('<div rbs-commerce-mode-selector=""');
								html.push(' data-show-prices="showPrices"');
								html.push(' data-shipping-mode="shippingMode"');
								html.push(' data-shipping-mode-info="shippingModesInfo[' + index + ']"');
								html.push(' data-process-engine="processEngine"');
								html.push('></div>');

								html.push('<div ' + directiveName + '="" ');
								html.push(' data-ng-show="shippingMode.id == shippingModesInfo[' + index + '].common.id"');
								html.push(' data-process-id="processId"');
								html.push(' data-shipping-mode="shippingMode"');
								html.push(' data-shipping-mode-info="shippingModesInfo[' + index + ']"');
								html.push(' data-user-id="userId"');
								html.push(' data-user-addresses="userAddresses"');
								html.push(' data-process-engine="processEngine"');
								html.push('></div>');
							}
						}
					});
					scope.processEngine.replaceChildren(elem, scope, html.join(''));
				});
			}
		}
	}

	app.directive('rbsCommerceShippingOtherStep', rbsCommerceShippingOtherStep);

	function rbsCommercePaymentStep(AjaxAPI, $sce, $filter) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommercePaymentStep.tpl',
			scope: {
				processEngine: '='
			},
			link: function(scope, elem) {

				scope.processData = scope.processEngine.getStepProcessData('payment');
				scope.processData.errors = [];
				if (!scope.processData.id) {
					scope.processData.id = 0;
				}

				scope.userAddresses = null;
				scope.processPaymentConnectorsInfo = [];
				scope.paymentConnectorsInfo = [];
				scope.paymentConnectorInfo = null;
				scope.transaction = null;

				scope.loadUserAddresses = function() {
					if (scope.processData.userId) {
						AjaxAPI.getData('Rbs/Geo/Address/', { userId: scope.processData.userId })
							.success(function(data) {
								scope.userAddresses = data.items;
							}).error(function() {
								scope.userAddresses = [];
							})
					}
					else {
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
								}
								else if (address['default']['default']) {
									defaultUserAddress = address;
								}
							}
						}
					}

					if (!defaultUserAddress) {
						var shippingModes = scope.processEngine.getObjectData()['process']['shippingModes'];
						angular.forEach(shippingModes, function(shippingMode) {
							if (!defaultUserAddress && shippingMode.options &&
								shippingMode.options.category == 'atHome' && !scope.isEmptyAddress(shippingMode.address)) {
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

				scope.selectUserAddress = function(address) {
					scope.processData.address = angular.copy(address);
					scope.processData.addressEdition = false;
				};

				scope.useAddress = function() {
					var address = angular.copy(scope.processData.address);
					scope.processEngine.loading(true);
					if (scope.processData.userId && address.common && address.common['useName'] && address.common.name) {
						delete address.common.id;
						address.default = {'default': true, 'billing': true};
						angular.forEach(scope.userAddresses, function(userAddress) {
							if (userAddress.default) {
								if (userAddress.default.default) {
									delete address.default.default;
								}
								if (userAddress.default.billing) {
									delete address.default.billing;
								}
							}
						});

						AjaxAPI.postData('Rbs/Geo/Address/', address)
							.success(function(data) {
								var addedAddress = data.dataSets;
								scope.userAddresses.push(addedAddress);
								scope.selectUserAddress(addedAddress);
								scope.processEngine.loading(false);
							}).error(function(data, status) {
								console.log('useAddress error', data, status);
								scope.processEngine.loading(false);
							})
					}
					else {
						AjaxAPI.postData('Rbs/Geo/ValidateAddress', { address: address })
							.success(function(data) {
								scope.selectUserAddress(data.dataSets);
								scope.processEngine.loading(false);
							}).error(function(data, status) {
								console.log('validateAddress error', data, status);
								scope.processEngine.loading(false);
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
					coupons.push({ code: scope.processData.newCouponCode });
					var actions = { setCoupons: coupons };

					var request = scope.processEngine.updateObjectData(actions);
					if (request) {
						request.success(function() {
							var cartData = scope.processEngine.getObjectData();
							refreshAmounts(cartData);
							scope.processData.coupons = cartData['coupons'];
							scope.processData.newCouponCode = null;
						});
						request.error(function(data, status) {
							console.log('addCoupon error', data, status);
							scope.processData.newCouponCode = null;
						});
					}
					else {
						var cartData = scope.processEngine.getObjectData();
						refreshAmounts(cartData);
						scope.processData.coupons = cartData['coupons'];
						scope.processData.newCouponCode = null;
					}
					return request;
				};

				scope.removeCoupon = function(couponIndex) {
					var coupons = [];
					angular.forEach(scope.processData.coupons, function(coupon, i) {
						if (i != couponIndex) {
							coupons.push(coupon);
						}
					});
					var actions = { setCoupons: coupons };

					var request = scope.processEngine.updateObjectData(actions);
					if (request) {
						request.success(function() {
							var cartData = scope.processEngine.getObjectData();
							refreshAmounts(cartData);
							scope.processData.coupons = cartData['coupons'];
						});
						request.error(function(data, status) {
							console.log('removeCoupon error', data, status);
						});
					}
					else {
						var cartData = scope.processEngine.getObjectData();
						refreshAmounts(cartData);
						scope.processData.coupons = cartData['coupons'];
					}
					return request;
				};

				scope.cleanupAddress = function(address) {
					if (address) {
						if (address.common) {
							address.common = { addressFieldsId: address.common.addressFieldsId };
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

					var request = scope.processEngine.updateObjectData(actions);
					if (request) {
						request.success(function() {
							refreshAmounts(scope.processEngine.getObjectData());
							scope.getTransaction();
						});
						request.error(function(data, status) {
							console.log('savePayment error', data, status);
						});
					}
					else {
						refreshAmounts(scope.processEngine.getObjectData());
						scope.getTransaction();
					}
				};

				scope.getTransaction = function() {
					scope.processEngine.loading(true);
					scope.processData.errors = [];
					AjaxAPI.getData('Rbs/Commerce/Cart/Transaction', {}, { dataSets: 'connectors' })
						.success(function(data) {
							scope.transaction = data.dataSets;
							scope.processEngine.loading(false);
						}).error(function(data, status) {
							console.log('getTransaction error', data, status);
							if (status == 409 && data && data.data) {
								angular.forEach(data.data, function(error) {
									scope.processData.errors.push(error.message);
								})
							}
							scope.transaction = null;
							scope.processEngine.loading(false);
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

				function getConnectIndexById(connectorId) {
					if (connectorId) {
						for (var i = 0; i < scope.processPaymentConnectorsInfo.length; i++) {
							if (scope.processPaymentConnectorsInfo[i]['common'].id == connectorId) {
								return i;
							}
						}
					}
					return null;
				}

				function refreshAmounts(cartData) {
					var currencyCode = cartData.common.currencyCode;
					scope.amountLines = [];

					scope.linesAmount = $filter('rbsFormatPrice')(cartData.amounts.linesAmountWithTaxes ||
					cartData.amounts.linesAmountWithoutTaxes, currencyCode);
					scope.linesProducts = 0;
					angular.forEach(cartData.lines, function(line) {
						scope.linesProducts += line.quantity;
					});

					angular.forEach(cartData['fees'], function(fee) {
						var amount = fee['amountWithTaxes'] || fee['amountWithoutTaxes'];
						if (amount) {
							var line = {
								amount: $filter('rbsFormatPrice')(amount, currencyCode),
								title: fee.designation
							};
							scope.amountLines.push(line);
						}
					});

					angular.forEach(cartData['discounts'], function(discount) {
						var amount = discount['amountWithTaxes'] || discount['amountWithoutTaxes'];
						if (amount) {
							var line = {
								amount: $filter('rbsFormatPrice')(amount, currencyCode),
								title: discount.title
							};
							scope.amountLines.push(line);
						}
					});

					if (cartData['totalTaxes'] && cartData['totalTaxes'].length) {
						var taxesAmount = 0;
						angular.forEach(cartData['totalTaxes'], function(tax) {
							if (tax.hasOwnProperty('value')) {
								taxesAmount += tax.value;
							}
						});
						scope.taxesAmount = $filter('rbsFormatPrice')(taxesAmount, currencyCode);
					}
					else {
						scope.taxesAmount = null;
					}

					if (cartData.creditNotes.length) {
						var creditNotesAmount = 0;
						angular.forEach(cartData.creditNotes, function(creditNote) {
							if (creditNote.hasOwnProperty('amountWithTaxes')) {
								creditNotesAmount += creditNote['amountWithTaxes'];
							}
						});
						scope.creditNotesAmount = $filter('rbsFormatPrice')(creditNotesAmount, currencyCode);
					}

					scope.paymentAmount = $filter('rbsFormatPrice')(cartData.amounts.paymentAmount, currencyCode);
				}

				function initializeProcessData() {
					var cartData = scope.processEngine.getObjectData();
					refreshAmounts(cartData);
					scope.processData.userId = cartData.common.userId;
					scope.processData.processId = cartData.process.orderProcessId;
					scope.processData.address = cartData.process.address;
					if (!scope.processData.address) {
						scope.processData.address = { common: {}, fields: {} };
					}
					scope.processData.coupons = cartData.coupons;

					var processInfo = scope.processEngine.getProcessInfo();
					scope.processPaymentConnectorsInfo = (processInfo && processInfo['paymentConnectors'] &&
					processInfo['paymentConnectors']['default']) ? processInfo['paymentConnectors']['default'] : [];

					var connectorIndex = getConnectIndexById(scope.processData.id);
					if (connectorIndex === null) {
						scope.processData.id = 0;
					}

					scope.loadUserAddresses();
				}

				function redrawConnectorConfiguration(htmlConnectors) {
					var linesContainer = elem.find('[data-role="connector-configuration-zone"]');
					scope.processEngine.replaceChildren(linesContainer, scope, htmlConnectors);
				}

				scope.$watch('transaction', function(transaction) {
					scope.paymentConnectorsInfo = [];
					if (transaction && transaction['connectors']) {
						var html = [];
						angular.forEach(transaction['connectors'], function(connector, i) {
							if (connector.transaction && connector.transaction.directiveName) {
								var infoIndex = null;
								angular.forEach(scope.processPaymentConnectorsInfo, function(paymentConnectorInfo) {
									if (paymentConnectorInfo.common.id == connector.common.id) {
										infoIndex = scope.paymentConnectorsInfo.length;
										scope.paymentConnectorsInfo.push(paymentConnectorInfo);
									}
								});
								if (infoIndex !== null) {
									html.push('<div data-' + connector.transaction.directiveName + '=""');
									html.push(' data-ng-if="' + connector.common.id + ' == processData.id"');
									html.push(' data-process-data="processData"');
									html.push(' data-transaction="transaction"');
									html.push(' data-connector-configuration="transaction.connectors[' + i + ']"');
									html.push(' data-connector-info="paymentConnectorsInfo[' + infoIndex + ']"');
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

	rbsCommercePaymentStep.$inject = ['RbsChange.AjaxAPI', '$sce', '$filter'];
	app.directive('rbsCommercePaymentStep', rbsCommercePaymentStep);
})();