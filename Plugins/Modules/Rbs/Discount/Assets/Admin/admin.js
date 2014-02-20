(function ()
{
	"use strict";

	var	app = angular.module('RbsChange') ;

	function OrderProcessSelector ($scope, $routeParams, $location, REST, $filter) {
		REST.collection('Rbs_Commerce_Process', {limit:1}).then(function (collection) {
			var path;
			if (collection.resources.length) {
				var orderProcess = collection.resources[0];
				var model =  ($routeParams.hasOwnProperty('model')) ? $routeParams.model : 'Rbs_Discount_Discount';
				path = $filter('rbsURL')(model, 'list', {orderProcessId: orderProcess.id});
			} else {
				path = $filter('rbsURL')('Rbs_Commerce_Process', 'list');
			}
			$location.path(path);
		});
	}
	OrderProcessSelector.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', '$filter'];
	app.controller('Rbs_Discount_OrderProcessSelector', OrderProcessSelector);

	/**
	 * @param $scope
	 * @param $route
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @param $filter
	 * @constructor
	 */
	function HeaderController ($scope, $route, $routeParams, $location, REST, $filter, Query)
	{
		$scope.orderProcessId = $routeParams.orderProcessId;
		$scope.currentProcess = null;
		$scope.model = $route.current.$$route.relatedModelName;
		$scope.processes = [];

		$scope.listLoadQuery = Query.simpleQuery($scope.model, 'orderProcess', $scope.orderProcessId);

		REST.query({model: 'Rbs_Commerce_Process'}).then(function (data){
			$scope.processes = data.resources;

			for (var i=0 ; i < data.resources.length; i++) {
				if (data.resources[i].id == $scope.orderProcessId) {
					$scope.currentProcess = data.resources[i];
					break;
				}
			}
		});

		$scope.$watch('currentProcess', function (process) {
			if (process && (process.id != $scope.orderProcessId)) {
				var path = $filter('rbsURL')($scope.model, 'list', {orderProcessId: process.id});
				$location.path(path);
			}
		});
	}

	HeaderController.$inject = ['$scope', '$route', '$routeParams', '$location',
		'RbsChange.REST', '$filter', 'RbsChange.Query'];
	app.controller('Rbs_Discount_HeaderController', HeaderController);
})();