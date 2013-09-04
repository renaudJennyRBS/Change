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

			compile : function (tElement, tAttrs, transcludeFn) {

				//TODO: let choose wich input you want (year, month, days, hours, minutes, seconds, etc...)

				return function(scope, elm, attrs) {
					//time interval is ISO 8601 string (like P1DT10H0M) for 1 day and 10 hours
					var mtiRegexp = /^P([0-9]+)DT([0-9]+)H([0-9]+)M$/;
					//                  111111    222222   333333
					if (mtiRegexp.test(scope.ngModel))
					{
						var matches = scope.ngModel.match(mtiRegexp);
						scope.days = parseInt(matches[1], 10);
						scope.hours = parseInt(matches[2], 10);
						scope.minutes = parseInt(matches[3], 10);
					}
					else
					{
						scope.days = 0;
						scope.hours = 0;
						scope.minutes = 0;
					}

					scope.$watch('days', function (){
						scope.ngModel = makeISO8601Duration();
					});
					scope.$watch('hours', function (){
						scope.ngModel = makeISO8601Duration();
					});
					scope.$watch('minutes', function (){
						scope.ngModel = makeISO8601Duration();
					});

					function makeISO8601Duration()
					{
						if (scope.days || scope.hours || scope.minutes)
						{
							return 'P' + scope.days + 'DT' +
								scope.hours + 'H' +
								scope.minutes + 'M';
						}
						else
						{
							return '';
						}
					}
				}
			}
		};

	});

})();
