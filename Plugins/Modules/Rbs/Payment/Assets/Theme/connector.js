(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommercePaymentConnectorDeferred($http) {
		return {
			restrict: 'AE',
			scope: false,
			templateUrl: 'Theme/Rbs/Base/Rbs_Payment/Deferred/connector.twig',
			link: function(scope) {
				scope.loadingConnector = true;

				var postData = {
					connectorId: scope.selectedConnector.id,
					transactionId: scope.payment.transaction.id
				};
				$http.post('Action/Rbs/Payment/GetDeferredConnectorData', postData)
					.success(function(data) {
						scope.connectorData = data;
						scope.loadingConnector = false;
					})
					.error(function(data, status, headers) {
						console.log('GetDeferredConnectorInformation error', data, status, headers);
					});

				scope.confirmOrder = function() {
					$http.post('Action/Rbs/Payment/DeferredConnectorReturnSuccess', postData)
						.success(function(data) {
							window.location = data['redirectURL'];
						})
						.error(function(data, status, headers) {
							console.log('GetDeferredConnectorInformation error', data, status, headers);
						});
				}
			}
		}
	}

	rbsCommercePaymentConnectorDeferred.$inject = ['$http'];
	app.directive('rbsCommercePaymentConnectorDeferred', rbsCommercePaymentConnectorDeferred);
})();