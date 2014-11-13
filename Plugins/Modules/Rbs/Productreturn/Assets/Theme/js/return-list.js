(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	/**
	 * Return list controller.
	 */
	function RbsProductreturnReturnListController(scope, $window, AjaxAPI) {
		scope.cancelReturn = function cancelReturn(returnId, waitingMessage) {
			AjaxAPI.openWaitingModal(waitingMessage);
			AjaxAPI.putData('Rbs/Productreturn/ProductReturn/' + returnId, { 'cancelRequest': true }, { detailed: false })
				.success(function() {
					$window.location.reload();
				})
				.error(function(data, status, headers) {
					AjaxAPI.closeWaitingModal();
					console.log('error', data, status, headers);
				});
		};
	}

	RbsProductreturnReturnListController.$inject = ['$scope', '$window', 'RbsChange.AjaxAPI'];
	app.controller('RbsProductreturnReturnListController', RbsProductreturnReturnListController);
})();