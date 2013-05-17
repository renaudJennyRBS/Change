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
	 */
	app.directive('dateSelector', ['RbsChange.Dialog', '$rootScope', 'RbsChange.i18n', function (Dialog, $rootScope, i18n) {
		return {
			restrict    : 'E',
			templateUrl : 'Change/Admin/js/directives/date-selector.twig',
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
							"title"    : i18n.trans('m.change.admin.admin.js.time-zone-selector-title | ucf'),
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
						dInput.datepicker('setValue', ngModel.$viewValue);
						var date = new Date(ngModel.$viewValue);
						hInput.val(date.getHours());
						mInput.val(date.getMinutes());
					}
				};
				ngModel.$render();

				// Merge the date coming from the "datepicker" and the hour/minute information coming from the
				// two additional input fields. The result is a Date object correctly set. (Well, I hope.)
				function getFullDate () {
					var date = datePicker.date, y, m, d, h, mm, s, dateStr;
					y = date.getFullYear();
					m = date.getMonth() + 1;
					d = date.getDate();
					h = parseInt(hInput.val(), 10);
					mm = parseInt(mInput.val(), 10);
					s = 0;
					dateStr = y + '-' + fixDoubleZero(m) + '-' + fixDoubleZero(d) + 'T' + fixDoubleZero(h) + ':' + fixDoubleZero(mm) + ':' + fixDoubleZero(s) + scope.timeZone.offset;

					// For debug purpose:
					$(elm).find('.fullDateString').html(dateStr + ' &mdash; ' + new Date(dateStr).toUTCString());

					return new Date(dateStr);
				}

			}
		};
	}]);


	/**
	 * @name timeZoneSelector
	 */
	app.directive('timeZoneSelector', ['$rootScope', 'RbsChange.Dialog', function ($rootScope, Dialog) {
		return {
			restrict    : 'E',
			templateUrl : 'Change/Admin/js/directives/time-zone-selector.twig',
			replace     : true,

			scope : {
				"timeZone" : "="
			},

			link : function (scope) {
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

				// Because ngOptions (see the template) only checks equality on objects reference, we need to
				// loop through the options to find which one corresponds to the given time zone (scope.timeZone).
				if (scope.timeZone) {
					var i;
					for (i=0 ; i<scope.timeZones.length ; i++) {
						if (scope.timeZone.code === scope.timeZones[i].code) {
							scope.selectedTimeZone = scope.timeZones[i];
							break;
						}
					}
				}
				if (!scope.selectedTimeZone) {
					scope.selectedTimeZone = scope.timeZones[0];
				}


				// ngOptions does not provide formatting facilities, so here is a small function to format
				// the label of the time zones displayed in the <select/> element.
				scope.getTimeZoneLabel = function (tz) {
					return tz.label + ' (' + tz.code + ')';
				};


				scope.select = function () {
					scope.timeZone = scope.selectedTimeZone;
					Dialog.closeEmbedded();
				};
			}
		};
	}]);

})(window.jQuery);