(function ($) {

	"use strict";

	var	app = angular.module('RbsChange');

	function fixDoubleZero (val) {
		val = '' + val;
		if (val.length === 1) {
			val = '0' + val;
		}
		return val;
	}

	/**
	 * @name dateSelector
	 *
	 * @attribute name
	 */
	app.directive('dateSelector', ['RbsChange.Dialog', '$rootScope', function (Dialog, $rootScope) {
		return {
			restrict    : 'E',
			templateUrl : 'Change/Admin/js/directives/date-selector.html',
			require     : 'ng-model',
			replace     : true,
			scope       : true,

			link : function (scope, elm, attrs, ngModel) {
				scope.timeZone = $rootScope.timeZone;

				var	dInput = $(elm).find('[data-role="input-date"]').first(),
					hInput = $(elm).find('[data-role="input-hour"]').first(),
					mInput = $(elm).find('[data-role="input-minute"]').first(),
					datePicker;

				scope.openTimeZoneSelector = function ($event) {
					Dialog.embed(
						$(elm).find('.timeZoneSelectorContainer'),
						{
							"title"    : "Sélection du fuseau horaire",
							"contents" : '<time-zone-selector time-zone="timeZone"></time-zone-selector>'
						},
						scope,
						{
							"pointedElement" : $event.target
						}
					);
				};

				scope.$watch('timeZone', function () {
					ngModel.$setViewValue(getFullDate());
				}, true);

				scope.selectTimeZone = function (timeZoneScope) {
					// timeZoneScope = when to use the selected time zone?
					// - 'form'   : this time only (this form)
					// - 'session': the working session
					// - 'forever': always (saved in the user preferences)
					//elm.find('.modal').modal('hide');
					//scope.timeZone = scope.selectedTimeZone;
					ngModel.$setViewValue(getFullDate());
				};

				function updateDate () {
					scope.$apply(function () {
						ngModel.$setViewValue(getFullDate());
					});
				}

				datePicker = dInput.datepicker().data('datepicker');
				dInput.change(updateDate);
				hInput.change(updateDate);
				mInput.change(updateDate);

				ngModel.$render = function () {
					if (ngModel.$viewValue) {
						var date = new Date(ngModel.$viewValue);
						dInput.datepicker('setValue', ngModel.$viewValue);
						hInput.val(date.getHours());
						mInput.val(date.getMinutes());
					}
				};
				ngModel.$render();

				function getFullDate () {
					var date = datePicker.date, y, m, d, h, mm, s, dateStr;
					y = date.getFullYear();
					m = date.getMonth() + 1;
					d = date.getDate();
					h = parseInt(hInput.val(), 10);
					mm = parseInt(mInput.val(), 10);
					s = 0;
					dateStr = y + '-' + fixDoubleZero(m) + '-' + fixDoubleZero(d) + 'T' + fixDoubleZero(h) + ':' + fixDoubleZero(mm) + ':' + fixDoubleZero(s) + scope.timeZone.offset;
					$(elm).find('.fullDateString').html(dateStr + ' &mdash; ' + new Date(dateStr).toUTCString());
					return new Date(dateStr);
				}

			}
		};
	}]);



	app.directive('timeZoneSelector', ['$rootScope', 'RbsChange.Dialog', function ($rootScope, Dialog) {
		return {
			restrict    : 'E',
			templateUrl : 'Change/Admin/js/directives/time-zone-selector.html',
			replace     : true,

			scope       : {
				"timeZone": "="
			},

			link : function (scope, elm, attrs) {
				// FIXME Load time zones from the server
				scope.timeZones = [
					{
						'code'  : 'GMT',
						'label' : "Greenwich",
						'offset': '+00:00'
					},
					{
						'code'  : 'GMT+1',
						'label' : "Paris, Madrid",
						'offset': '+01:00'
					},
					{
						'code'  : 'GMT+2',
						'label' : "South Africa",
						'offset': '+02:00'
					}

				];

				if (scope.timeZone) {
					var i;
					for (i=0 ; i<scope.timeZones.length ; i++) {
						if (scope.timeZone.code === scope.timeZones[i].code) {
							scope.selectedTimeZone = scope.timeZones[i];
							break;
						}
					}
				}

				scope.getTimeZoneLabel = function (tz) {
					return tz.label + ' (' + tz.code + ')';
				};

				if (!scope.selectedTimeZone) {
					scope.selectedTimeZone = scope.timeZones[0];
				}

				scope.select = function () {
					scope.timeZone = scope.selectedTimeZone;
					Dialog.closeEmbedded();
				};
			}
		};
	}]);

})(window.jQuery);