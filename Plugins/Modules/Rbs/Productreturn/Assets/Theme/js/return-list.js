(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	/**
	 * Return list controller.
	 */
	function RbsProductreturnReturnListController(scope, $window, AjaxAPI) {
		scope.pleaseWait = false;

		scope.cancelReturn = function cancelReturn(returnId) {
			scope.pleaseWait = true;
			AjaxAPI.putData('Rbs/Productreturn/ProductReturn/' + returnId, { 'cancelRequest': true }, { detailed: false })
				.success(function() {
					$window.location.reload();
				})
				.error(function(data, status, headers) {
					scope.pleaseWait = false;
					console.log('error', data, status, headers);
				});
		};

		scope.printUrl = function printUrl(printUrl) {
			window.open(printUrl);
		}
	}

	RbsProductreturnReturnListController.$inject = ['$scope', '$window', 'RbsChange.AjaxAPI'];
	app.controller('RbsProductreturnReturnListController', RbsProductreturnReturnListController);
})();