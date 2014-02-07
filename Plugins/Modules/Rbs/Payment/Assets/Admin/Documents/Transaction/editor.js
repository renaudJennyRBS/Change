(function() {
	"use strict";

	function Editor(i18n, Dialog, $http, REST) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Payment/Transaction/editor.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function(scope, elm, attrs, editorCtrl) {
				function callActionUrlAndReload(url) {
					if (url) {
						$http.post(url, {}, REST.getHttpConfig(REST.resourceTransformer()))
							.success(function(document) {
								if (document.id == scope.document.id) {
									scope.original = document;
									scope.reset();
								}
								else {
									console.log(document);
								}
							})
							.error(function errorCallback(data, status) {
								console.log(data, status);
							});
					}
				}

				scope.validatePayment = function($event) {
					var message = i18n.trans('m.rbs.payment.adminjs.transaction_confirm_validate_payment | ucf');
					var title = i18n.trans('m.rbs.payment.adminjs.transaction_confirm_validate_payment_title | ucf');
					Dialog.confirmLocal($event.target, title, message, { placement: 'top' })
						.then(function() {
							callActionUrlAndReload(scope.document.META$.actions['validatePayment'].href);
						});
				};

				scope.refusePayment = function($event) {
					var message = i18n.trans('m.rbs.payment.adminjs.transaction_confirm_refuse_payment | ucf');
					var title = i18n.trans('m.rbs.payment.adminjs.transaction_confirm_refuse_payment_title | ucf');
					Dialog.confirmLocal($event.target, title, message, { placement: 'top' })
						.then(function() {
							callActionUrlAndReload(scope.document.META$.actions['refusePayment'].href);
						});
				};

				editorCtrl.init('Rbs_Payment_Transaction');
			}
		};
	}

	Editor.$inject = ['RbsChange.i18n', 'RbsChange.Dialog', '$http', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPaymentTransaction', Editor);
})();