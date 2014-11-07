(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	/**
	 * Return process controller.
	 */
	function RbsProductreturnReturnProcessController(scope, $element, $window, $sce, AjaxAPI) {
		scope.returnData = { 'shipments': [], 'returnMode': null, 'reshippingMode': null };
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
							console.log('orderLine', line['orderLine'], shipmentLine.options['lineKey']);
							if (angular.isObject(line['orderLine'].product)) {
								line.product = line['orderLine'].product;
							}
						}
						else if (angular.isObject(line['shipmentLine'].product)) {
							line.product = line['shipmentLine'].product;
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

		scope.isEditing = function isEditing(shipmentIndex, lineIndex, returnLineIndex) {
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

		scope.sendReturnRequest = function sendReturnRequest() {
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
							console.log('returnLine.productData', returnLine.productData);
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
				data.common.reshippingModeId = scope.returnData.reshippingMode;
				// TODO: reshippingData
			}

			// Send return request.
			AjaxAPI.postData('Rbs/Productreturn/ProductReturn/', data, { detailed: false })
				.success(function(resultData) {
					// TODO
					console.log(resultData);
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

	function rbsProductreturnReturnLine() {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnReturnLine.tpl',
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
					if (!reason || !reason['timeLimitAfterReceipt'] || !reason['timeoutMessage']) {
						return false;
					}
					else if (!reason['timeoutMessage']) {
						console.log('A reason with a time limit requires a timeout message!');
					}

					var dateNow = new Date();

					var dateToCheck;
					if (scope.shipment.shipmentData.common['deliveryDate']) {
						dateToCheck = new Date(scope.shipment.shipmentData.common['deliveryDate']);
					}
					else {
						dateToCheck = new Date(scope.shipment.shipmentData.common['shippingDate']);
						dateToCheck.setDate(dateToCheck.getDate() + reason['timeLimitAfterReceipt']);
					}

					return dateNow.getTime() > dateToCheck.getTime();
				}

				function autoSelectReturnModeId(reason, returnModeId) {
					if (!reason) {
						return null;
					}

					var processingModes = scope.getAvailableProcessingModes(reason, scope.line.product)
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

	app.directive('rbsProductreturnReturnLine', rbsProductreturnReturnLine);

	function rbsProductreturnVariantSelectorContainer(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsProductreturnVariantSelectorContainer.tpl',
			scope: true,
			link: function(scope) {
				var productId = scope.line.product.common.id;

				var params = angular.copy(scope.data.productAjaxParams);
				if (!angular.isArray(params.dataSetNames)) {
					params.dataSetNames = [];
				}
				params.dataSetNames.push('rootProduct');

				var request = AjaxAPI.getData('Rbs/Catalog/Product/' + productId, scope.data.productAjaxData, params);
				request.success(function(data) {
					scope['returnLine'].productData = data['dataSets'];
				});
				request.error(function(data, status) {
					scope.error = data.message;
					console.log('error', data, status);
				});
			}
		};
	}

	rbsProductreturnVariantSelectorContainer.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsProductreturnVariantSelectorContainer', rbsProductreturnVariantSelectorContainer);

	function rbsProductreturnShipmentLines($compile) {
		return {
			restrict: 'A',
			controller: function () {
				this.getLineDirectiveName = function (line) {
					return 'data-rbs-productreturn-shipment-line-details-default';
				};
			},
			link: function(scope, elment, attrs, controller) {
				console.log('rbsProductreturnShipmentLines', scope['shipment']);
				var html = [];
				angular.forEach(scope['shipment'].lines, function(line, lineIndex){
					console.log(line, lineIndex);
					html.push('<tr data-line-index="'+ lineIndex +'" ' + controller.getLineDirectiveName(line) + '=""></tr>');
					html.push('<tr data-line-index="'+ lineIndex +'" data-rbs-productreturn-shipment-line-return=""></tr>');
					html.push('<tr data-line-index="'+ lineIndex +'" data-rbs-productreturn-shipment-line-footer=""></tr>');
				});

				if (html.length) {
					$compile(html.join(''))(scope, function (clone) {
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

					var readFile = function readFile(file) {
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
})();