(function() {
	"use strict";

	function rbsOrderOrderEditorShippingModes(REST, $filter, i18n, NotificationCenter, ErrorFormatter, Events) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Order/Order/shippingModes.twig',
			scope: {
				'addressDocuments': "=",
				'shippingModes': "=",
				'lines': "=",
				'orderId': "@",
				'orderProcessingStatus': "@",
				'isOrderEditable': "="
			},

			link: function(scope) {
				scope.data = {
					editedShippingIndex: null
				};
				scope.shippingDetails = {};
				scope.addressDefined = {};

				scope.getLinesNumbers = function(shippingMode) {
					var matchingLines, lineNumbers = [];
					angular.forEach(shippingMode.lineKeys, function(lineKey) {
						matchingLines = $filter('filter')(scope.lines, { key: lineKey });
						angular.forEach(matchingLines, function(line) {
							lineNumbers.push(line.index + 1);
						});
					});
					return lineNumbers.join(', ');
				};

				scope.refreshShippingModes = function() {
					if (!angular.isArray(scope.shippingModes)) {
						scope.shippingModes = [];
					}

					var shippingModes = scope.shippingModes;
					angular.forEach(shippingModes, function(shippingMode) {
						shippingMode.lineKeys = [];
						// Set the status to 'unavailable' because we can't be clear with shipment status.
						if (scope.shippingDetails[shippingMode.id]) {
							scope.shippingDetails[shippingMode.id].status = 'unavailable';
						}
					});

					angular.forEach(scope.lines, function(line) {
						var shippingModeId = (line.options) ? line.options.shippingMode : null;
						if (shippingModeId) {
							var matchingShippingModes = $filter('filter')(shippingModes, { id: shippingModeId });
							if (matchingShippingModes.length) {
								angular.forEach(matchingShippingModes, function(shippingMode) {
									shippingMode.lineKeys.push(line.key);
								});
							}
							else {
								shippingModes.push({ id: shippingModeId, lineKeys: [line.key] });
							}
						}
					});

					angular.forEach(shippingModes, function(shippingMode) {
						if (!shippingMode.hasOwnProperty('title')) {
							var detail = scope.shippingDetails[shippingMode.id];
							shippingMode.title = detail ? detail.label : '[' + shippingMode.id + ']';
						}
					});
				};

				// This watches for modifications in the address doc in order to fill the address form.
				scope.$watch('shippingModes', function(shippingModes, old) {
					if (angular.isObject(shippingModes) && !angular.isObject(old)) {
						angular.forEach(scope.shippingModes, function(shipping) {
							if (shipping.id) {
								scope.addressDefined[shipping.id] = angular.isObject(shipping.address);
							}
						});
					}
				}, true);

				// This refreshes shippingModesObject to be synchronized with order editor.
				scope.$on('shippingModesUpdated', function() {
					scope.refreshShippingModes();
				});

				scope.$on(Events.EditorPostSave, function() {
					if (angular.isObject(scope.shippingModes)) {
						angular.forEach(scope.shippingModes, function(shipping) {
							if (shipping.id) {
								scope.addressDefined[shipping.id] = angular.isObject(shipping.address);
							}
						});
					}
				});

				scope.$watch('orderId', function(orderId) {
					var shippingDetails = {};
					if (orderId > 0) {
						REST.collection('Rbs_Shipping_Mode').then(function(response) {
							angular.forEach(response.resources, function(shippingDoc) {
								shippingDetails[shippingDoc.id] = shippingDoc;
							});

							for (var i = 0; i < scope.shippingModes.length; i++) {
								if (scope.shippingModes[i].lineKeys.length) {
									loadShippingModeStatus(scope.shippingModes[i].id);
								}
							}
						});
					}
					scope.shippingDetails = shippingDetails;
				});

				function loadShippingModeStatus(shippingId) {
					if (scope.orderId && scope.orderProcessingStatus == 'processing') {
						var params = {
							orderId: scope.orderId,
							shippingModeId: shippingId
						};
						var successCallback = function(data) {
							scope.shippingDetails[shippingId].status = data.status;
						};
						var errorCallback = function(error) {
							NotificationCenter.error(
								i18n.trans('m.rbs.order.adminjs.shipment_invalid_request_remainder | ucf'),
								ErrorFormatter.format(error)
							);
							console.error(error);
						};
						REST.call(REST.getBaseUrl('rbs/order/orderRemainder'), params)
							.then(successCallback, errorCallback);
					}
				}
			}
		};
	}

	rbsOrderOrderEditorShippingModes.$inject = [ 'RbsChange.REST', '$filter', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Events' ];
	angular.module('RbsChange').directive('rbsOrderShippingModes', rbsOrderOrderEditorShippingModes);
})();