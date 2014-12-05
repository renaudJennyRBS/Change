(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsCommerceCreateAccount() {
		return {
			restrict: 'A',
			require: 'rbsUserCreateAccount',
			link: function (scope, elem, attrs, createAccountController) {
				var data = createAccountController.getData();
				data.transactionId = parseInt(attrs.transactionId);
			}
		}
	}
	app.directive('rbsCommerceCreateAccount', rbsCommerceCreateAccount);
})();