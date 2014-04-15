(function() {
	"use strict";

	function rbsDocumentEditorRbsOrderInvoiceNew($routeParams, REST, i18n, NotificationCenter, ErrorFormatter) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.onReady = function() {
					// Pre-fill fields if there is data in query url.
					if ($routeParams.hasOwnProperty('orderId')) {
						var successCallback = function(data) {
							scope.document.order = data;
						};
						var errorCallback = function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.order.admin.invalid_query_order | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						};
						REST.resource('Rbs_Order_Order', $routeParams.orderId).then(successCallback, errorCallback);
					}
				};
			}
		};
	}

	rbsDocumentEditorRbsOrderInvoiceNew.$inject = ['$routeParams', 'RbsChange.REST', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderInvoiceNew', rbsDocumentEditorRbsOrderInvoiceNew);
})();