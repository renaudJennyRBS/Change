(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	/**
	 * Return process controller.
	 */
	function RbsProductreturnReturnProcessController(scope, $element, $window, $sce, AjaxAPI) {
		scope.returnData = {
			'shipments': [],
			'returnMode': null,
			'reshippingData': {'mode': {'id': null}, 'address': null },
			'orderData': {}
		};
		scope.orderData = {};
		scope.data = { 'editingLine': null, 'productAjaxData': {}, 'productAjaxParams': {} };

		var reasons = {};
		var processingModes = {};

		scope.getOrderLineByKey = function getOrderLineByKey(key) {
			for (var i = 0; i < scope.orderData.lines.length; i++) {
				var line = scope.orderData.lines[i];
				if (line.key == key) {
					return line;
				}
			}
			return null;
		};

		var cacheKey = $element.attr('data-cache-key');
		if (cacheKey) {
			scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
			scope.data.productAjaxData.webStoreId = scope.parameters.webStoreId;
			scope.data.productAjaxData.billingAreaId = scope.parameters.billingAreaId;
			scope.data.productAjaxData.zone = scope.parameters.zone;
			scope.data.productAjaxParams.visualFormats = scope.parameters['imageFormats'];

			if (angular.isObject($window['__change']) && $window['__change'][cacheKey]) {
				var data = $window['__change'][cacheKey];
				scope.orderData = data.orderData;
				scope.returnData.orderData = data.orderData;
				scope.processData = data.processData;

				for (var reasonIndex = 0; reasonIndex < scope.processData['reasons'].length; reasonIndex++) {
					var reason = scope.processData['reasons'][reasonIndex];
					reasons[reason.id] = reason;
					for (var lineIndex = 0; lineIndex < reason['processingModes'].length; lineIndex++) {
						var mode = reason['processingModes'][lineIndex];
						processingModes[mode.id] = mode;
					}
				}

				for (var shipmentIndex = 0; shipmentIndex < scope.orderData.shipments.length; shipmentIndex++) {
					var shipment = scope.orderData.shipments[shipmentIndex];
					if (!shipment.hasOwnProperty('common') || !shipment.common.hasOwnProperty('shippingDate')) {
						continue;
					}

					var lines = [];
					for (lineIndex = 0; lineIndex < shipment.lines.length; lineIndex++) {
						var shipmentLine = shipment.lines[lineIndex];
						var line = {
							'lineIndex': lineIndex,
							'returnLines': [],
							'shipmentLine': angular.copy(shipmentLine)
						};

						if (shipmentLine.options.hasOwnProperty('lineKey')) {
							line['orderLine'] = scope.getOrderLineByKey(shipmentLine.options['lineKey']);
							if (angular.isObject(line['orderLine'].product)) {
								line.product = line['orderLine'].product;
							}
						}

						if (angular.isObject(line['shipmentLine'].product)) {
							line.product = line['shipmentLine'].product;
						}

						line.alreadyReturned = 0;
						for (var returnIndex = 0; returnIndex < scope.orderData.returns.length; returnIndex++) {
							var returnData = scope.orderData.returns[returnIndex];
							if (returnData.common['statusInfos'].code === 'CANCELED' ||
								returnData.common['statusInfos'].code === 'EDITION') {
								continue;
							}
							for (var returnLineIndex = 0; returnLineIndex < returnData.lines.length; returnLineIndex++) {
								var returnLine = returnData.lines[returnLineIndex];
								if (returnLine.shipmentId == shipment.common.id && returnLine.shipmentLineIndex == lineIndex) {
									line.alreadyReturned += returnLine.quantity;
								}
							}
						}

						lines.push(line);
					}

					scope.returnData.shipments.push({
						'lines': lines,
						'shipmentData': angular.copy(shipment)
					});
				}
			}
		}

		scope.hasReturnLine = function hasReturnLine(shipmentIndex, lineIndex) {
			if (lineIndex !== undefined) {
				return scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines.length > 0;
			}
			else {
				for (var i = 0; i < scope.returnData.shipments.length; i++) {
					for (var j = 0; j < scope.returnData.shipments[i].lines.length; j++) {
						if (scope.returnData.shipments[i].lines[j].returnLines.length > 0) {
							return true;
						}
					}
				}
				return false;
			}
		};

		scope.needsReshipment = function needsReshipment() {
			for (var shipmentIndex = 0; shipmentIndex < scope.returnData.shipments.length; shipmentIndex++) {
				for (var lineIndex = 0; lineIndex < scope.returnData.shipments[shipmentIndex].lines.length; lineIndex++) {
					var line = scope.returnData.shipments[shipmentIndex].lines[lineIndex];
					for (var returnLineIndex = 0; returnLineIndex < line.returnLines.length; returnLineIndex++) {
						var mode = scope.getProcessingModeById(line.returnLines[returnLineIndex]['preferredProcessingMode']);
						if (mode && mode['impliesReshipment']) {
							return true;
						}
					}
				}
			}
			return false;
		};

		scope.$watch('returnData.shipments', function () {
			scope.stepsData.reshipping.isEnabled = scope.needsReshipment();
		}, true);

		scope.isEditing = function isEditing() {
			var editingStep = false;
			angular.forEach(scope.stepsData, function (step) {
				if (step.isEnabled && (!step.isChecked || step.isCurrent)) {
					editingStep = true;
				}
			});
			return editingStep || scope.isEditingLine();
		};

		scope.isEditingLine = function isEditingLine(shipmentIndex, lineIndex, returnLineIndex) {
			if (lineIndex === undefined) {
				return scope.data.editingLine !== null;
			}
			else if (returnLineIndex === undefined) {
				var value = shipmentIndex + '_' + lineIndex + '_';
				return angular.isString(scope.data.editingLine) && scope.data.editingLine.substr(0, value.length) == value;
			}
			else {
				return scope.data.editingLine == shipmentIndex + '_' + lineIndex + '_' + returnLineIndex;
			}
		};

		scope.newReturnLine = function newReturnOnLine(shipmentIndex, lineIndex) {
			var newIndex = scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines.length;
			scope.data.editingLine = shipmentIndex + '_' + lineIndex + '_' + newIndex;

			var returnLine = { 'expanded': true, 'productData': {}, 'options': {} };

			if (scope.getLineRemainingQuantity(shipmentIndex, lineIndex, newIndex) == 1) {
				returnLine.quantity = 1;
			}
			if (scope.processData['reasons'].length == 1) {
				returnLine.quantity = scope.processData['reasons'][0].id;
			}

			scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines.push(returnLine);
		};

		scope.getReasonById = function getReasonById(reasonId) {
			return reasons.hasOwnProperty(reasonId) ? reasons[reasonId] : null;
		};

		scope.getProcessingModeById = function getProcessingModeById(modeId) {
			return processingModes.hasOwnProperty(modeId) ? processingModes[modeId] : null;
		};

		scope.getAvailableProcessingModes = function getAvailableProcessingModes(reason, product) {
			if (angular.isNumber(reason)) {
				reason = scope.getReasonById(reason);
			}

			var availableModes = [];
			if (angular.isObject(reason) && angular.isObject(product)) {
				for (var modeId = 0; modeId < reason['processingModes'].length; modeId++) {
					if (reason['processingModes'][modeId]['allowVariantSelection'] && product.common.type !== 'variant') {
						continue;
					}
					availableModes.push(reason['processingModes'][modeId]);
				}
			}
			return availableModes;
		};

		scope.getLineRemainingQuantity = function getLineRemainingQuantity(shipmentIndex, lineIndex, returnLineIndex) {
			var quantity = scope.returnData.shipments[shipmentIndex].lines[lineIndex].shipmentLine.quantity;
			quantity -= scope.returnData.shipments[shipmentIndex].lines[lineIndex].alreadyReturned;
			for (var i = 0; i < scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines.length; i++) {
				// Ignore the quantity of the current return line if its index is given.
				if (returnLineIndex === undefined || returnLineIndex != i) {
					quantity -= scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines[i].quantity;
				}
			}
			return quantity;
		};

		scope.getAvailableQuantities = function getAvailableQuantities(shipmentIndex, lineIndex, returnLineIndex) {
			var array = [];
			var remainingQuantity = scope.getLineRemainingQuantity(shipmentIndex, lineIndex, returnLineIndex);
			for (var i = 0; i < remainingQuantity; i++) {
				array.push(i + 1);
			}
			return array;
		};

		scope.canChooseOtherVariant = function canChooseOtherVariant(line, returnLine) {
			if (!angular.isObject(line) || !line.hasOwnProperty('product') || line.product.common.type !== 'variant') {
				return false;
			}
			if (!angular.isObject(returnLine) || !returnLine['preferredProcessingMode']) {
				return false;
			}
			var mode = scope.getProcessingModeById(returnLine['preferredProcessingMode']);
			if (!angular.isObject(mode) || !mode.hasOwnProperty('allowVariantSelection')) {
				return false;
			}
			return mode['allowVariantSelection'] === true;
		};

		scope.sendReturnRequest = function sendReturnRequest(waitingMessage) {
			// Prepare data...
			var linesData = [];
			for (var shipmentIndex = 0; shipmentIndex < scope.returnData.shipments.length; shipmentIndex++) {
				var shipment = scope.returnData.shipments[shipmentIndex];
				for (var lineIndex = 0; lineIndex < shipment.lines.length; lineIndex++) {
					var line = shipment.lines[lineIndex];
					for (var returnLineIndex = 0; returnLineIndex < line.returnLines.length; returnLineIndex++) {
						var returnLine = line.returnLines[returnLineIndex];
						var lineData = {
							'shipmentId': shipment.shipmentData.common.id,
							'shipmentLineIndex': line.lineIndex,
							'quantity': returnLine.quantity,
							'reasonId': returnLine.reason,
							'reasonPrecisions': returnLine['precisions'],
							'reasonAttachedFile': returnLine['attachedFile'],
							'preferredProcessingModeId': returnLine['preferredProcessingMode']
						};

						if (scope.canChooseOtherVariant(line, returnLine) && angular.isObject(returnLine.productData)) {
							returnLine.options.reshippingProductId = returnLine.productData.common.id;
						}

						if (angular.isObject(line.orderLine)) {
							returnLine.options.unitAmountWithoutTaxes = line.orderLine.unitAmountWithoutTaxes;
							returnLine.options.unitAmountWithTaxes = line.orderLine.unitAmountWithTaxes;
						}

						lineData.options = returnLine.options;
						linesData.push(lineData);
					}
				}
			}

			var data = {
				'common': {
					'orderId': scope.orderData.common.id,
					'returnModeId': scope.returnData.returnMode
				},
				'lines': linesData
			};

			if (scope.needsReshipment()) {
				data.reshippingData = scope.returnData.reshippingData;
			}

			// Send return request.
			AjaxAPI.openWaitingModal(waitingMessage);
			AjaxAPI.postData('Rbs/Productreturn/ProductReturn/', data, { detailed: false, URLFormats: ['canonical'] })
				.success(function(resultData) {
					var URL = resultData.dataSets.common.URL['canonical'];
					if (URL) {
						$window.location = URL;
					}
					else {
						AjaxAPI.closeWaitingModal();
						console.log('return submitted', resultData);
					}
				})
				.error(function(data, status, headers) {
					console.log('error', data, status, headers);
				});
		};

		scope.trustHtml = function trustHtml(html) {
			return $sce.trustAsHtml(html);
		};
	}

	RbsProductreturnReturnProcessController.$inject = ['$scope', '$element', '$window', '$sce', 'RbsChange.AjaxAPI'];
	app.controller('RbsProductreturnReturnProcessController', RbsProductreturnReturnProcessController);

	function rbsCommerceProcess($compile, $http) {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnProcess.tpl',
			controller : ['$scope', '$element', function(scope) {
				scope.loading = false;
				scope.stepsData = {
					reshipping: { name: 'reshipping', isCurrent: true, isEnabled: false, isChecked: false }
				};
				this.loading = function(loading) {
					if (angular.isDefined(loading)) {
						scope.loading = (loading == true);
					}
					return scope.loading;
				};

				this.loadObjectData = function(withProcessData) {
					console.log('loadObjectData', withProcessData);
					return null;
				};

				this.updateObjectData = function(actions) {
					console.log('updateObjectData', actions);
					if (actions.hasOwnProperty('setReshippingData')) {
						scope.returnData.reshippingData = actions.setReshippingData;
					}
					if (actions.hasOwnProperty('setStepValid')) {
						scope.stepsData[actions.setStepValid].isChecked = true;
					}
					if (actions.hasOwnProperty('setNotCurrentStep')) {
						scope.stepsData[actions.setStepValid].isCurrent = false;
					}

					return null;
				};

				this.getObjectData = function() {
					return scope.returnData;
				};

				this.showPrices = function() {
					return false;
				};

				this.getCurrencyCode = function() {
					return null;
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

				this.getProcessInfo = function() {
					return scope.processData;
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

				this.nextStep = function () {
					console.log('nextStep');
					return null;
				};

				this.getNextStep = function (step) {
					console.log('getNextStep', step);
					return null;
				};

				this.setCurrentStep = function(currentStep) {
					console.log('setCurrentStep', currentStep);
					scope.stepsData[currentStep].isCurrent = true;
				};

				this.getStepProcessData = function(step) {
					console.log('getStepProcessData', step);
					if (scope.stepsData.hasOwnProperty(step)) {
						return scope.stepsData[step];
					}
					return {name: step, isCurrent: false, isEnabled: false, isChecked: false};
				};
			}],

			link: function(scope, elem, attrs, controller) {
				scope.showPrices = controller.showPrices();
				scope.isStepEnabled = function(step) {
					return controller.getStepProcessData(step).isEnabled;
				};
				scope.isStepChecked = function(step) {
					return controller.getStepProcessData(step).isChecked;
				};
			}
		}
	}

	rbsCommerceProcess.$inject = ['$compile', '$http'];
	app.directive('rbsCommerceProcess', rbsCommerceProcess);

	function rbsProductreturnReturnLineSummary() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnReturnLineSummary.tpl',
			scope: true,
			link: function(scope, element, attrs) {
			}
		};
	}

	app.directive('rbsProductreturnReturnLineSummary', rbsProductreturnReturnLineSummary);

	function rbsProductreturnReturnLineEdition() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnReturnLineEdition.tpl',
			scope: true,
			link: function(scope, element, attrs) {
				var formName = attrs['formName'];

				scope.editLine = function editLine(shipmentIndex, lineIndex, returnLineIndex) {
					scope.data.editingLine = shipmentIndex + '_' + lineIndex + '_' + returnLineIndex;
				};

				scope.isLineValid = function isLineValid(line, returnLine) {
					if (scope[formName].$invalid) {
						return false;
					}
					if (returnLine.timeLimitExceeded) {
						return false;
					}
					if (!scope.canChooseOtherVariant(line, returnLine)) {
						return true;
					}
					return scope.isSelectedVariantAvailable(returnLine);
				};

				scope.isSelectedVariantAvailable = function isSelectedVariantAvailable(returnLine) {
					var cart = returnLine.productData['cart'];
					return angular.isObject(cart) && cart['hasStock'];
				};

				scope.validateLine = function validateLine(shipmentIndex, lineIndex, returnLineIndex) {
					var line = scope.returnData.shipments[shipmentIndex].lines[lineIndex];
					var returnLine = line.returnLines[returnLineIndex];
					if (scope.isLineValid(line, returnLine)) {
						scope.data.editingLine = null;
					}
				};

				scope.cancelLine = function cancelLine(shipmentIndex, lineIndex, returnLineIndex) {
					scope.data.editingLine = null;
					scope.returnData.shipments[shipmentIndex].lines[lineIndex].returnLines.splice(returnLineIndex, 1);
				};

				function isTimeLimitExceeded(reason) {
					if (!reason || !reason['timeLimitAfterReceipt']) {
						return false;
					}
					else if (!reason['timeoutMessage']) {
						console.log('A reason with a time limit requires a timeout message!');
					}

					var dateNow = new Date();

					var dateToCheck;
					var timeLimit = reason['timeLimitAfterReceipt'];
					if (scope['shipment'].shipmentData.common['deliveryDate']) {
						dateToCheck = new Date(scope['shipment'].shipmentData.common['deliveryDate']);
						dateToCheck.setDate(dateToCheck.getDate() + timeLimit);
					}
					else {
						dateToCheck = new Date(scope['shipment'].shipmentData.common['shippingDate']);
						dateToCheck.setDate(dateToCheck.getDate() + timeLimit + reason['extraTimeAfterShipping']);
					}

					return dateNow.getTime() > dateToCheck.getTime();
				}

				function autoSelectReturnModeId(reason, returnModeId) {
					if (!reason) {
						return null;
					}

					var processingModes = scope.getAvailableProcessingModes(reason, scope.line.product);
					if (reason['processingModes'].length == 1) {
						return reason['processingModes'][0].id
					}

					for (var i = 0; i < reason['processingModes'].length; i++) {
						if (reason['processingModes'][i].id == returnModeId) {
							return returnModeId;
						}
					}
					return null;
				}

				scope.$watch('returnLine.reason', function watchReason(reasonId) {
					var reason = scope.getReasonById(reasonId);
					if (reason && isTimeLimitExceeded(reason)) {
						scope['returnLine'].timeLimitExceeded = true;
						scope['returnLine'].timeLimitErrorMessage = reason['timeoutMessage'];
					}
					else {
						scope['returnLine'].timeLimitExceeded = false;
						scope['returnLine'].timeLimitErrorMessage = null;
					}

					var modeId = autoSelectReturnModeId(reason, scope['returnLine'].preferredProcessingMode);
					if (modeId !== scope['returnLine'].preferredProcessingMode) {
						scope['returnLine'].preferredProcessingMode = modeId;
					}
				});
			}
		};
	}

	app.directive('rbsProductreturnReturnLineEdition', rbsProductreturnReturnLineEdition);

	function rbsProductreturnVariantSelectorContainer(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnVariantSelectorContainer.tpl',
			scope: true,
			link: function(scope, elment, attrs) {
				var productId = scope.line.product.common.id;

				var params = angular.copy(scope.data.productAjaxParams);
				if (!angular.isArray(params.dataSetNames)) {
					params.dataSetNames = [];
				}
				params.dataSetNames.push('rootProduct');

				AjaxAPI.openWaitingModal(attrs['waitingMessage']);
				var request = AjaxAPI.getData('Rbs/Catalog/Product/' + productId, scope.data.productAjaxData, params);
				request.success(function(data) {
					scope['returnLine'].productData = data['dataSets'];
					AjaxAPI.closeWaitingModal();
				});
				request.error(function(data, status) {
					scope.error = data.message;
					console.log('error', data, status);
					AjaxAPI.closeWaitingModal();
				});
			}
		};
	}

	rbsProductreturnVariantSelectorContainer.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsProductreturnVariantSelectorContainer', rbsProductreturnVariantSelectorContainer);

	function rbsProductreturnShipmentLines($compile) {
		return {
			restrict: 'A',
			controller: function() {
				this.getLineDirectiveName = function(line) {
					return 'data-rbs-productreturn-shipment-line-details-default';
				};
			},
			link: function(scope, elment, attrs, controller) {
				var html = [];
				angular.forEach(scope['shipment'].lines, function(line, lineIndex) {
					html.push('<tr data-line-index="' + lineIndex + '" ' + controller.getLineDirectiveName(line) + '=""></tr>');
					html.push('<tr data-line-index="' + lineIndex + '" data-rbs-productreturn-shipment-line-return=""></tr>');
					html.push('<tr data-line-index="' + lineIndex + '" data-rbs-productreturn-shipment-line-footer=""></tr>');
				});

				if (html.length) {
					$compile(html.join(''))(scope, function(clone) {
						elment.append(clone);
					});
				}
			}
		}
	}

	rbsProductreturnShipmentLines.$inject = ['$compile'];
	app.directive('rbsProductreturnShipmentLines', rbsProductreturnShipmentLines);

	function rbsProductreturnShipmentLineDetailsDefault() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnShipmentLineDetailsDefault.tpl',
			scope: true,
			replace: true,
			link: function(scope, elment, attrs) {
				scope.lineIndex = attrs['lineIndex'];
				scope.line = scope['shipment'].lines[scope.lineIndex];
			}
		}
	}

	app.directive('rbsProductreturnShipmentLineDetailsDefault', rbsProductreturnShipmentLineDetailsDefault);

	function rbsProductreturnShipmentLineReturn() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnShipmentLineReturn.tpl',
			scope: true,
			replace: true,
			link: function(scope, elment, attrs) {
				scope.lineIndex = attrs['lineIndex'];
				scope.line = scope['shipment'].lines[scope.lineIndex];
			}
		}
	}

	app.directive('rbsProductreturnShipmentLineReturn', rbsProductreturnShipmentLineReturn);

	function rbsProductreturnShipmentLineFooter() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnShipmentLineFooter.tpl',
			scope: true,
			replace: true,
			link: function(scope, elment, attrs) {
				scope.lineIndex = attrs['lineIndex'];
				scope.line = scope['shipment'].lines[scope.lineIndex];
			}
		}
	}

	app.directive('rbsProductreturnShipmentLineFooter', rbsProductreturnShipmentLineFooter);

	function rbsProductreturnProductAvailability() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductAvailability.tpl',
			scope: {
				productData: '='
			},
			link: function(scope, elment, attrs) {

			}
		}
	}

	app.directive('rbsProductreturnProductAvailability', rbsProductreturnProductAvailability);

	function rbsProductreturnFileReader($q) {
		var slice = Array.prototype.slice;
		return {
			restrict: 'A',
			require: '?ngModel',
			link: function(scope, element, attrs, ngModel) {
				if (!ngModel) {
					return;
				}

				ngModel.$render = function() {};

				element.bind('change', function(e) {
					var element = e.target;

					var fileData = [];

					var readFile = function readFile(file) {
						fileData.push({ name: file.name, type: file.type, size: file.size });
						var deferred = $q.defer();
						var reader = new FileReader();
						reader.onload = function onLoad(e) {
							deferred.resolve(e.target.result);
						};
						reader.onerror = function onError(e) {
							deferred.reject(e);
						};
						reader.readAsDataURL(file);
						return deferred.promise;
					};

					$q.all(slice.call(element.files, 0).map(readFile))
						.then(function(values) {
							for (var i = 0; i < values.length; i++) {
								var value = values[i];
								if (value) {
									fileData[i].contents = value;
									value = fileData[i];
								}
								else {
									value = null;
								}
								values[i] = value;
							}

							if (element.multiple) {
								ngModel.$setViewValue(values);
							}
							else {
								ngModel.$setViewValue(values.length ? values[0] : null);
							}
						});
				});
			}
		};
	}

	rbsProductreturnFileReader.$inject = ['$q'];
	app.directive('rbsProductreturnFileReader', rbsProductreturnFileReader);

	// Reshipping mode.

	function rbsProductreturnReshippingStep(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceShippingStep.tpl',
			require: '^rbsCommerceProcess',
			scope: {},
			link: function(scope, elem, attrs, processController) {
				scope.hideStepTitle = true;
				scope.processData = processController.getStepProcessData('reshipping');
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
							{ userId: scope.processData.userId, matchingZone: scope.shippingZone })
							.success(function(data) {
								scope.userAddresses = data.items;
							})
							.error(function(data, status, headers, config) {
								console.error('loadUserAddresses', data, status, headers, config);
								scope.userAddresses = [];
							})
					}
					else {
						scope.userAddresses = [];
					}
				};

				scope.setCurrentStep = function() {
					processController.setCurrentStep('reshipping');
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
					scope.saveMode();
				};

				scope.saveMode = function() {
					var shippingMode = scope.processData.shippingModes[0];
					if (!angular.isFunction(shippingMode.valid) || !shippingMode.valid()) {
						return;
					}
					var validData = shippingMode.valid(true);
					shippingMode.address = validData.address;
					shippingMode.options = validData.options;
					var actions = {
						setReshippingData: {
							'mode': { 'id': validData.id, title: validData.title },
							'address': validData.address,
							'options': validData.options
						},
						setStepValid: 'reshipping',
						setNotCurrentStep: 'reshipping'
					};
					return processController.updateObjectData(actions);
				};

				function initializeProcessData() {
					var returnData = processController.getObjectData();
					scope.processData.userId = returnData.orderData.common.userId;

					var processInfo = processController.getProcessInfo();
					scope.processData.processId = processInfo.common.id;
					scope.shippingModesInfo = processInfo && processInfo['reshippingModes'] ? processInfo['reshippingModes'] : {};
					scope.shippingZone = returnData.orderData.common.zone;

					if (!scope.processData.shippingModes) {
						var shippingMode = angular.copy(returnData['reshippingData']);
						shippingMode.shippingZone = scope.shippingZone;
						shippingMode.options = {};
						scope.processData.shippingModes = [ shippingMode ];
					}
				}

				initializeProcessData();
				scope.loadUserAddresses();
			}
		}
	}

	rbsProductreturnReshippingStep.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsProductreturnReshippingStep', rbsProductreturnReshippingStep);
})();