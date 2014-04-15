(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function shipmentsController(scope, REST, $routeParams, NotificationCenter, i18n, ErrorFormatter) {
		scope.shipments = [];

		if (!scope.document) {
			REST.resource('Rbs_Order_Order', $routeParams.id).then(function(order) {
				scope.document = order;
				loadShipments();
			});
		}
		else {
			loadShipments();
		}

		function loadShipments() {
			var shipmentsLink = scope.document.getLink('shipments');
			if (shipmentsLink) {
				var successCallback = function(data) {
					scope.shipments = data.resources;
				};
				var errorCallback = function(error) {
					NotificationCenter.error(
						i18n.trans('m.rbs.order.admin.invalid_query_order_shipments | ucf'),
						ErrorFormatter.format(error)
					);
					console.error(error);
				};
				REST.call(shipmentsLink, { column: ['code', 'shippingModeCode', 'trackingCode', 'carrierStatus'] })
					.then(successCallback, errorCallback);
			}
		}
	}

	shipmentsController.$inject = ['$scope', 'RbsChange.REST', '$routeParams', 'RbsChange.NotificationCenter', 'RbsChange.i18n',
		'RbsChange.ErrorFormatter'];
	app.controller('rbsOrderOrderShipments', shipmentsController);

	function paymentsController(scope, REST, $routeParams, NotificationCenter, i18n, ErrorFormatter) {
		scope.transactions = [];
		scope.invoices = [];

		if (!scope.document) {
			REST.resource('Rbs_Order_Order', $routeParams.id).then(function(order) {
				scope.document = order;
				loadTransactions();
				loadInvoices();
			});
		}
		else {
			loadTransactions();
			loadInvoices();
		}

		function loadTransactions() {
			var transactionsLink = scope.document.getLink('transactions');
			if (transactionsLink) {
				var successCallback = function(data) {
					scope.transactions = data.resources;
				};
				var errorCallback = function(error) {
					NotificationCenter.error(
						i18n.trans('m.rbs.order.admin.invalid_query_order_transactions | ucf'),
						ErrorFormatter.format(error)
					);
					console.error(error);
				};
				REST.call(transactionsLink, { column: [ 'formattedAmount', 'formattedProcessingStatus', 'processingDate'] })
					.then(successCallback, errorCallback);
			}
		}

		function loadInvoices() {
			var invoicesLink = scope.document.getLink('invoices');
			if (invoicesLink) {
				var successCallback = function(data) {
					scope.invoices = data.resources;
				};
				var errorCallback = function(error) {
					NotificationCenter.error(
						i18n.trans('m.rbs.order.admin.invalid_query_order_invoices | ucf'),
						ErrorFormatter.format(error)
					);
					console.error(error);
				};
				REST.call(invoicesLink, { column: [ 'code', 'formattedAmountWithTax', 'creationDate'] })
					.then(successCallback, errorCallback);
			}
		}
	}

	paymentsController.$inject = ['$scope', 'RbsChange.REST', '$routeParams', 'RbsChange.NotificationCenter', 'RbsChange.i18n',
		'RbsChange.ErrorFormatter'];
	app.controller('rbsOrderOrderPayments', paymentsController);
})();