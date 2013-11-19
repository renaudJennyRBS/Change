(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Order')
				.route('home', 'Rbs/Order', { 'redirectTo': 'Rbs/Order/Order/'});

			$delegate.routesForModels([
				'Rbs_Order_Order',
				'Rbs_Order_Invoice',
				'Rbs_Order_Process'
			]);

			return $delegate;
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Order_Invoice');
	__change.createEditorForModel('Rbs_Order_Process');


	app.controller('Rbs_Order_Order_ListController', ['$scope', '$q', 'RbsChange.REST', function ($scope, $q, REST)
	{

		function changeOrdersStatus(orders, property, value)
		{
			var promises = [];
			angular.forEach(orders, function (order) {
				order[property] = value;
				promises.push(REST.save(order));
			});
			return $q.all(promises);
		}

		$scope.extend =
		{
			markAsPayed : function (orders)
			{
				return changeOrdersStatus(orders, 'paymentStatus', 'payed');
			},

			markAsShipped : function (orders)
			{
				return changeOrdersStatus(orders, 'shippingStatus', 'shipped');
			},

			markAsDelivered : function (orders)
			{
				return changeOrdersStatus(orders, 'shippingStatus', 'delivered');
			},

			markAsFinalized : function (orders)
			{
				return changeOrdersStatus(orders, 'processingStatus', 'finalized');
			},

			markAsPrepared : function (orders)
			{
				return changeOrdersStatus(orders, 'shippingStatus', 'prepared');
			},

			cancel : function (orders)
			{
				return changeOrdersStatus(orders, 'processingStatus', 'canceled');
			}
		};
	}]);

})();