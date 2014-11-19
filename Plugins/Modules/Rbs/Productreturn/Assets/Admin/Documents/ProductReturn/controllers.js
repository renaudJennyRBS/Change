(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function shipmentsController(scope, REST, $routeParams, NotificationCenter, i18n, ErrorFormatter) {
		scope.shipments = [];

		if (!scope.document) {
			REST.resource('Rbs_Productreturn_ProductReturn', $routeParams.id).then(function(productReturn) {
				scope.document = productReturn;
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
						i18n.trans('m.rbs.productreturn.admin.invalid_query_product_return_shipments | ucf'),
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
	app.controller('rbsProductreturnProductReturnShipments', shipmentsController);

	function creditNotesController(scope, REST, $routeParams, NotificationCenter, i18n, ErrorFormatter) {
		scope.transactions = [];
		scope.invoices = [];
		scope.creditNotes = [];

		if (!scope.document) {
			REST.resource('Rbs_Productreturn_ProductReturn', $routeParams.id).then(function(productReturn) {
				scope.document = productReturn;
				loadCreditNotes();
			});
		}
		else {
			loadCreditNotes();
		}

		function loadCreditNotes() {
			var creditNotesLink = scope.document.getLink('creditNotes');
			if (creditNotesLink) {
				var successCallback = function(data) {
					scope.creditNotes = data.resources;
				};
				var errorCallback = function(error) {
					NotificationCenter.error(
						i18n.trans('m.rbs.order.admin.invalid_query_product_return_credit_notes | ucf'),
						ErrorFormatter.format(error)
					);
					console.error(error);
				};

				REST.call(creditNotesLink, { column: ['label', 'amount', 'amountNotApplied'] })
					.then(successCallback, errorCallback);
			}
		}
	}

	creditNotesController.$inject = ['$scope', 'RbsChange.REST', '$routeParams', 'RbsChange.NotificationCenter', 'RbsChange.i18n',
		'RbsChange.ErrorFormatter'];
	app.controller('rbsProductreturnProductReturnCreditNotes', creditNotesController);
})();