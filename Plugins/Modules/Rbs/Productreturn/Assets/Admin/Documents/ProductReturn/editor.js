(function() {
	"use strict";

	function Editor(REST, $routeParams, ArrayUtils, $q, Query, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout, $http) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.data = {
					order: null,
					articleCount: null
				};
				scope.priceInfo = {
					decimals: 2,
					currencyCode: null,
					taxInfo: [],
					withTax: false
				};
				scope.userAddresses = [];

				var contextRestored = false;

				scope.$on('Navigation.saveContext', function(event, args) {
					args['context'].savedData('productReturn', {
						data: scope.data,
						userAddresses: scope.userAddresses,
						priceInfo: scope.priceInfo
					});
				});

				scope.onRestoreContext = function onRestoreContext(currentContext) {
					var toRestoreData = currentContext.savedData('productReturn');
					scope.data = toRestoreData.data;
					scope.userAddresses = toRestoreData.userAddresses;
					scope.priceInfo = toRestoreData.priceInfo;
					contextRestored = true;
				};

				function populateAddressList(ownerId) {
					if (!ownerId) {
						scope.userAddresses = [];
						return;
					}

					var query = {
						'model': 'Rbs_Geo_Address',
						'where': {
							'and': [
								{
									'op': 'eq',
									'lexp': { 'property': 'ownerId' },
									'rexp': { 'value': ownerId }
								}
							]
						}
					};

					REST.query(query, { 'column': ['label', 'addressFields', 'fieldValues'] }).then(function(data) {
						scope.userAddresses = data.resources;
					});
				}

				function setOrderByOrderId(orderId) {
					var p = REST.resource('Rbs_Order_Order', orderId);
					p.then(function(orderData) {
						scope.data.order = orderData;
						populateAddressList(orderData['ownerId']);
						scope.priceInfo.currencyCode = orderData.currencyCode;
						REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id: orderData.billingAreaId}).then(function(data) {
							scope.priceInfo.taxInfo = data;
							scope.priceInfo.currentTaxInfo = taxInfosForZone(scope.priceInfo.taxZone, data);
						});
					}, function(error) {
						NotificationCenter.error(i18n.trans('m.rbs.order.admin.invalid_query_order | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
					return p;
				}

				function taxInfosForZone(taxZone, taxInfo) {
					var currentTaxInfo = [];
					if (taxZone && angular.isArray(taxInfo)) {
						for (var i = 0; i < taxInfo.length; i++) {
							if (taxInfo[i].zones.indexOf(taxZone) > -1) {
								currentTaxInfo.push(taxInfo[i]);
							}
						}
					}
					return currentTaxInfo;
				}

				scope.onLoad = function onLoad() {
					setOrderByOrderId(scope.document.orderId);
				};

				scope.onReady = function onReady() {
					if (scope.document.isNew()) {
						// Pre-fill fields if there is data in query url.
						if ($routeParams.hasOwnProperty('orderId')) {
							setOrderByOrderId($routeParams.orderId);
						}
					}
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

				// This watches for modifications in the lines, made by the user, such as quantity for each line.
				scope.$watch('document.lines', function(lines, old) {
					if (scope.document && lines !== old) {
						scope.data.articleCount = 0;
						for (var i = 0; i < lines.length; i++) {
							scope.data.articleCount += lines[i].quantity;
						}
					}
				}, true);

				scope.updateProcessingStatus = function updateProcessingStatus(status, $event) {
					var options = {
						pointedElement: jQuery($event.target),
						primaryButtonClass: 'btn-success',
						cssClass: 'default'
					};
					if (status == 'edition') {
						options.primaryButtonClass = 'btn-warning';
						options.cssClass = 'warning';
					}
					else if (status == 'refused' || status == 'canceled') {
						options.primaryButtonClass = 'btn-danger';
						options.cssClass = 'danger';
					}
					Dialog.confirmEmbed(
						jQuery($event.target).parents('rbs-document-editor-button-bar').find('.confirmation-area'),
						i18n.trans('m.rbs.productreturn.admin.productReturn_update_status_confirm_title_' + status + ' | ucf'),
						i18n.trans('m.rbs.productreturn.admin.productReturn_update_status_confirm_message_' + status + ' | ucf'),
						scope,
						options
					)
						.then(function() {
							scope.document.processingStatus = status;
							// Wait for angular to check changes.
							$timeout(function() { scope.submit(); });
						});
				};

				scope.canProcess = function canProcess(status) {
					if ((status === 'refused' || status === 'canceled') && !scope.document['processingComment']) {
						return false;
					}
					else if (scope.isUnchanged()) {
						return true;
					}
					else if (scope.changes.length == 1 && scope.changes[0] == 'processingComment') {
						return true;
					}
					return false;
				}
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.ArrayUtils', '$q', 'RbsChange.Query', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout', '$http'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsProductreturnProductReturnNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsProductreturnProductReturnEdit', Editor);
})();