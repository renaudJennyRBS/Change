(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsThresholdsEditor', ['RbsChange.REST', 'RbsChange.Utils', '$timeout', rbsThresholdsEditorDirective]);

	function rbsThresholdsEditorDirective (REST, Utils, $timeout) {
		return {
			restrict : 'E',
			scope : {thresholdsValues: "="},
			templateUrl : "Rbs/Stock/Sku/thresholdsEditor.twig",

			link : function (scope, elm, attrs) {
				scope.$watch('thresholdsValues', function (value) {
					if (angular.isArray(value))
					{
						scope.thresholds = value;
					}
					else
					{
						scope.thresholdsValues  = [{l:0,c:"UNAVAILABLE"}, {l:1000000,c:"AVAILABLE"}]
					}
				}, true);

				scope.createThreshold = function(index){
					var old = scope.thresholds;
					var newThresholds = [];

					for (var i = 0; i < old.length; i++)
					{
						if (index == i)
						{
							if (i > 0)
							{
								var l = old[i].l - Math.round((old[i].l - old[i-1].l) / 2);
							}
							else
							{
								var l = old[i].l - 10;
							}
							newThresholds.push({l:l, c : (l > 0 ? "AVAILABLE" : "UNAVAILABLE")});
						}
						newThresholds.push(old[i]);
					}

					scope.thresholdsValues = newThresholds;
				};

				scope.removeThreshold = function(index){
					var old = scope.thresholds;
					var newThresholds = [];

					for (var i = 0; i < old.length; i++)
					{
						if (index != i)
						{
							newThresholds.push(old[i]);
						}
					}
					scope.thresholdsValues = newThresholds;
				};

				scope.sortThreshold = function(){
					var array = scope.thresholdsValues;

					for (var i = 0; i < array.length; i++)
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
						return (a.l == b.l ? 0 : (a.l < b.l ? -1 : 1));
					});
				};
			}
		};
	}
})();