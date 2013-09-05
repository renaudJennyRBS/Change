(function () {

	"use strict";

	var	app = angular.module('RbsChange');

	/**
	 * TODO: this directive is only use for notification time interval (on user admin profile).
	 * refactor this directive to use it with anything need time-interval-input
	 */
	app.directive('rbsTimeIntervalInput', function () {

		return {
			restrict   : 'E',
			scope      : {
				label  : '@',
				ngModel: '='
			},
			templateUrl: 'Rbs/Admin/js/directives/time-interval-input.twig',

			link : function(scope, elm, attrs) {
				scope.durations = { years: 0, months: 0, weeks: 0, days: 0, hours: 0, minutes: 0, seconds: 0 };
				if (angular.isDefined(attrs.show))
				{
					scope.show = {};
					var elements = attrs.show.split(',');
					angular.forEach(elements, function(element){
						scope.show[element] = true;
					});
				}
				else
				{
					//if show is not defined, show all
					angular.forEach(scope.durations, function(element){
						scope.show[element] = true;
					});
				}

				//time interval is ISO 8601 string (like P3Y6M4DT12H30M5S) for 3 years, 6 months,  day and 10 hours
				var mtiRegexp = /^P([0-9]?[.,]?[0-9]+)Y([0-9]?[.,]?[0-9]+)M([0-9]?[.,]?[0-9]+)W([0-9]?[.,]?[0-9]+)DT([0-9]?[.,]?[0-9]+)H([0-9]?[.,]?[0-9]+)M([0-9]?[.,]?[0-9]+)S$/;
				//                  11111111111111111   22222222222222222   33333333333333333    44444444444444444   55555555555555555   66666666666666666   77777777777777777
				if (mtiRegexp.test(scope.ngModel))
				{
					var matches = scope.ngModel.match(mtiRegexp);
					scope.durations.years = parseInt(matches[1], 10);
					scope.durations.months = parseInt(matches[2], 10);
					scope.durations.weeks = parseInt(matches[3], 10);
					scope.durations.days = parseInt(matches[4], 10);
					scope.durations.hours = parseInt(matches[5], 10);
					scope.durations.minutes = parseInt(matches[6], 10);
					scope.durations.seconds = parseInt(matches[7], 10);
				}

				function makeISO8601Duration()
				{
					//check if all fileds are 0, an return empty string
					var makeDuration = false;
					angular.forEach(scope.durations, function(duration){
						if (duration != 0)
						{
							makeDuration = true;
						}
					});
					if (makeDuration)
					{
						return 'P' + scope.durations.years + 'Y' + scope.durations.months + 'M' + scope.durations.weeks + 'W' +
							scope.durations.days + 'DT' +
							scope.durations.hours + 'H' + scope.durations.minutes + 'M' + scope.durations.seconds + 'S';
					}
					else
					{
						return '';
					}
				}

				scope.$watchCollection('durations', function (){
					scope.ngModel = makeISO8601Duration();
				});
			}
		};

	});

})();
