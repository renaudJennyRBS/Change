(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommercePaymentConnectorDeferred($http) {
		return {
			restrict: 'A',
			scope: {
				connectorConfiguration: "=",
				processData: "=",
				transaction: "=",
				connectorInfo: "="
			},
			templateUrl: 'Theme/Rbs/Base/Rbs_Payment/Deferred/connector.twig',
			link: function(scope) {

				var postData = {
					connectorId: scope.connectorConfiguration.common.id,
					transactionId: scope.transaction.common.id
				};

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

	function rbsCommercePaymentConnectorHtml($sce) {
		return {
			restrict: 'A',
			scope: {
				connectorConfiguration: "=",
				processData: "=",
				transaction: "=",
				connectorInfo: "="
			},
			template: '<div data-ng-bind-html="trustHtml(connectorConfiguration.transaction.html)"></div>',
			link: function(scope) {
				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCommercePaymentConnectorHtml.$inject = ['$sce'];
	app.directive('rbsCommercePaymentConnectorHtml', rbsCommercePaymentConnectorHtml);
})();