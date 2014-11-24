(function() {
	"use strict";

	function rbsDocumentEditorRbsProductreturnShipment($location, REST, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.data = {
					'order': null,
					'productReturn': null
				};

				function refreshData() {
					setOrderByOrderId(scope.document.orderId);
					setReturnByReturnId(scope.document.productReturnId);
				}

				scope.onLoad = function onLoad() {
					refreshData();
				};

				scope.onReady = function onReady() {
					// Pre-fill fields if there is data in query url.
					var search = $location.search();
					if (search.hasOwnProperty('productReturnId')) {
						var successCallback = function(data) {
							scope.document.orderId = data.orderId;
							scope.document.productReturnId = data.productReturnId;
							scope.document.shippingModeCode = data.shippingModeCode;
							scope.document.address = data.address;
							scope.document.context = data.context;
							scope.document.lines = data.lines;
							refreshData();
						};
						var errorCallback = function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.productreturn.admin.invalid_query_product_return | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						};
						var params = { productReturnId: search.productReturnId };
						REST.call(REST.getBaseUrl('rbs/productreturn/getReshippingDataForReturn'), params)
							.then(successCallback, errorCallback);
					}
				};

				scope.preSave = function preSave(document) {
					if (angular.isArray(document.lines)) {
						for (var lineIndex = document.lines.length - 1; lineIndex >= 0; lineIndex--) {
							if (document.lines[lineIndex]['quantity'] == 0) {
								document.lines.splice(lineIndex, 1);
							}
						}
					}

					var search = $location.search();
					if (search.hasOwnProperty('productReturnId')) {
						delete search.productReturnId;
					}
				};

				scope.validatePreparation = function validatePreparation($event) {
					var message = i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation | ucf');
					if (!scope.document.address || isObjectEmpty(scope.document.address)) {
						message += '<br /><strong>' +
						i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_empty_address | ucf') +
						'</strong>';
					}
					Dialog.confirmLocal($event.target,
						i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_title | ucf'),
						message,
						{ placement: 'top' }
					).then(function() {
							scope.document.prepared = true;
							// Wait for angular to check changes.
							$timeout(function() {
								scope.submit();
							});
						});
				};

				function isObjectEmpty(object) {
					if (angular.isArray(object)) {
						return object.length === 0;
					}
					if (angular.isObject(object)) {
						return Object.getOwnPropertyNames(object).length === 0;
					}
					return false;
				}

				function setOrderByOrderId(orderId) {
					if (orderId) {
						var p = REST.resource('Rbs_Order_Order', orderId);
						p.then(function(orderData) {
							scope.data.order = orderData;
						}, function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.order.admin.invalid_query_order | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
						return p;
					}
					else {
						scope.data.order = null;
					}
				}

				function setReturnByReturnId(returnId) {
					if (returnId) {
						var p = REST.resource('Rbs_Productreturn_ProductReturn', returnId);
						p.then(function(returnData) {
							scope.data.productReturn = returnData;
						}, function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.productreturn.admin.invalid_query_product_return | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
						return p;
					}
					else {
						scope.data.productReturn = null;
					}
				}

				scope.$watch('document.lines', function(lines) {
					var itemCount = 0;
					if (angular.isArray(lines)) {
						for (var lineIndex = 0; lineIndex < lines.length; lineIndex++) {
							itemCount += lines[lineIndex]['quantity'];
						}
					}
					scope.document.itemCount = itemCount;
				}, true);
			}
		};
	}

	rbsDocumentEditorRbsProductreturnShipment.$inject = ['$location', 'RbsChange.REST', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsProductreturnShipmentNew', rbsDocumentEditorRbsProductreturnShipment);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsProductreturnShipmentEdit', rbsDocumentEditorRbsProductreturnShipment);
})();