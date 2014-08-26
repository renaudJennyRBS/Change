(function() {
	"use strict";

	function rbsDocumentEditorRbsOrderCreditNoteNew($location, REST, i18n, NotificationCenter, ErrorFormatter) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.onReady = function() {
					// Pre-fill fields if there is data in query url.
					var search = $location.search();
					if (search.hasOwnProperty('orderId')) {
						var successCallback = function(data) {
							scope.document.ownerId = data.ownerId;
							scope.document.targetIdentifier = data.identifier;
							scope.document.amount = data.totalAmountWithTaxes;
							scope.document.currencyCode = data.currencyCode;
							scope.document.contextData = {from : 'order', orderId: data.id};
						};
						var errorCallback = function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.order.admin.invalid_query_order | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						};
						REST.resource('Rbs_Order_Order', search.orderId).then(successCallback, errorCallback);
					}
				};
			}
		};
	}

	rbsDocumentEditorRbsOrderCreditNoteNew.$inject = ['$location', 'RbsChange.REST', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderCreditNoteNew', rbsDocumentEditorRbsOrderCreditNoteNew);
})();