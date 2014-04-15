(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsThresholdsEditor', [ rbsThresholdsEditorDirective ]);

	function rbsThresholdsEditorDirective () {
		return {
			restrict : 'E',
			scope : {thresholdsValues: "="},
			templateUrl : 'Document/Rbs/Stock/Sku/thresholdsEditor.twig',

			link : function (scope, elm, attrs) {
				scope.$watch('thresholdsValues', function (value) {
					if (angular.isArray(value))
					{
						scope.thresholds = value;
					}
					else
					{
						scope.thresholdsValues  = [{l:0,c:"UNAVAILABLE"}, {l:1000000,c:"AVAILABLE"}];
					}
				}, true);

				scope.createThreshold = function (index) {
					var old = scope.thresholds,
					    newThresholds = [],
						i, l;

					for (i = 0; i < old.length; i++)
					{
						if (index === i)
						{
							if (i > 0)
							{
								l = old[i].l - Math.round((old[i].l - old[i-1].l) / 2);
							}
							else
							{
								l = old[i].l - 10;
							}
							newThresholds.push({l:l, c : (l > 0 ? "AVAILABLE" : "UNAVAILABLE")});
						}
						newThresholds.push(old[i]);
					}

					scope.thresholdsValues = newThresholds;
				};

				scope.removeThreshold = function(index){
					var old = scope.thresholds,
					    newThresholds = [],
						i;

					for (i = 0; i < old.length; i++)
					{
						if (index !== i)
						{
							newThresholds.push(old[i]);
						}
					}
					scope.thresholdsValues = newThresholds;
				};

				scope.sortThreshold = function(){
					var array = scope.thresholdsValues, i;

					for (i = 0; i < array.length; i++)
					{
						if (array[i].l >= 1000000)
						{
							array[i].l = 1000000;
							if (i < array.length - 1)
							{
								array[i].l--;
							}
						}
					}

					scope.thresholdsValues = array.sort(function(a, b) {
						return (a.l === b.l ? 0 : (a.l < b.l ? -1 : 1));
					});
				};
			}
		};
	}


	/**
	 * Controller for list of movement.
	 *
	 * @param $scope
	 * @param $http
	 * @param REST
	 * @param $routeParams
	 * @param Utils
	 * @constructor
	 */
	function MovementListController($scope, $http, REST, $routeParams, Utils) {

		$scope.movements = {};

		REST.resource('Rbs_Stock_Sku', $routeParams.id, $routeParams.LCID).then(function (doc) {
			$scope.document = doc;
		});

		$scope.loadMovements = function (params) {
			var url = Utils.makeUrl('resources/Rbs/Stock/Sku/' + $routeParams.id + '/movement/', params);
			$http.get(REST.getBaseUrl(url)).success(function(data) {
				$scope.movements = data;
			});
		};

		$scope.loadMovements({});

	}

	MovementListController.$inject = ['$scope', '$http', 'RbsChange.REST', '$routeParams', 'RbsChange.Utils'];
	app.controller('Rbs_Stock_Movement_ListController', MovementListController);

	/**
	 * Controller for list of movement.
	 *
	 * @param $scope
	 * @param $http
	 * @param REST
	 * @param $routeParams
	 * @param Utils
	 * @constructor
	 */
	function ReservationListController($scope, $http, REST, $routeParams, Utils) {

		$scope.reservations = {};

		REST.resource('Rbs_Stock_Sku', $routeParams.id, $routeParams.LCID).then(function (doc) {
			$scope.document = doc;
		});

		$scope.loadReservations = function (params) {
			var url = Utils.makeUrl('resources/Rbs/Stock/Sku/' + $routeParams.id + '/reservation/', params);
			$http.get(REST.getBaseUrl(url)).success(function(data) {
				$scope.reservations = data;
			});
		};

		$scope.loadReservations({});
	}

	ReservationListController.$inject = ['$scope', '$http', 'RbsChange.REST', '$routeParams', 'RbsChange.Utils'];
	app.controller('Rbs_Stock_Reservation_ListController', ReservationListController);

})();