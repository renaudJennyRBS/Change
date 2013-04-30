(function ($) {

	var app = angular.module('RbsChange');

	var timeZones = [
	                 {
	                    'code'  : 'GMT',
	                    'label' : "Greenwich",
	                    'offset': '+00:00'
	                 },
	                 {
	                    'code'  : 'GMT+1',
	                    'label' : "Paris, Madrid",
	                    'offset': '+01:00'
	                 }
	                 ];


	/**
	 * @name dateSelector
	 *
	 * @attribute name
	 */
	app.directive('dateSelector', ['RbsChange.Dialog', '$rootScope', function (Dialog, $rootScope) {
		return {
			restrict: 'E',

			templateUrl: 'Change/Admin/js/directives/date-selector.html',

			require: 'ng-model',

			replace: true,

			scope: true,

			link : function (scope, elm, attrs, ngModel) {
				scope.name = attrs.name;
				scope.timeZone = $rootScope.timeZone;
				scope.timeZones = timeZones;

				var dInput = $(elm).find('[data-role="input-date"]').first();
				var hInput = $(elm).find('[data-role="input-hour"]').first();
				var mInput = $(elm).find('[data-role="input-minute"]').first();
				var seconds = 0;

				elm.find('.modal').modal({
					backdrop : false,//'static',
					keyboard : true,
					show     : false
				});

				scope.openTimeZoneSelector = function () {
					scope.selectedTimeZone = scope.timeZone;
					elm.find('.modal').modal('show');
				};

				scope.selectTimeZone = function (timeZoneScope) {
					// timeZoneScope = when to use the selected time zone?
					// - 'form'   : this time only (this form)
					// - 'session': the working session
					// - 'forever': always (saved in the user preferences)
					elm.find('.modal').modal('hide');
					scope.timeZone = scope.selectedTimeZone;
				};

				dInput.datepicker();
				dInput.change(function () {
					scope.$apply(function () {
						ngModel.$setViewValue(getFullDate());
					});
				});

				hInput.change(function () {
					scope.$apply(function () {
						ngModel.$setViewValue(getFullDate());
					});
				});
				mInput.change(function () {
					scope.$apply(function () {
						ngModel.$setViewValue(getFullDate());
					});
				});

				ngModel.$render = function () {
					if (this.$viewValue) {
						var date = new Date(this.$viewValue);
						dInput.val(date.getFullYear()+'-'+fixDoubleZero(date.getMonth()+1)+'-'+fixDoubleZero(date.getDate()));
						hInput.val(date.getHours());
						mInput.val(date.getMinutes());
					}
				};

				function getFullDate () {
					console.log("fullDate: ", (dInput.val() + 'T' + fixDoubleZero(hInput.val()) + ':' + fixDoubleZero(mInput.val()) + ':' + fixDoubleZero(seconds) + scope.timeZone.offset));
					return dInput.val() + 'T' + fixDoubleZero(hInput.val()) + ':' + fixDoubleZero(mInput.val()) + ':' + fixDoubleZero(seconds) + scope.timeZone.offset;
				}

				function fixDoubleZero (val) {
					val = '' + val;
					if (val.length === 1) {
						val = '0' + val;
					}
					return val;
				}
			}
		};
	}]);

})(window.jQuery);