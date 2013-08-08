(function ($, $script, moment) {

	"use strict";

	var	app = angular.module('RbsChange');

	// Detect native date picker.
	var inputEl = document.createElement("input");
	inputEl.setAttribute("type", "date");
	var isNativeDatePickerAvailable = inputEl.type === 'date';


	function loadTimeZoneInfo (timeZone, callback, Loading) {
		console.log("loadTimeZoneInfo: ", timeZone);
		var continent = timeZone.substring(0, timeZone.indexOf('/'));
		if (Loading) {
			Loading.start("Chargement des informations de zone pour " + continent); // TODO i18n
		}
		$script('Rbs/Admin/lib/moment/tz/' + continent + '.js', function () {
			if (Loading) {
				Loading.stop();
			}
			if (angular.isFunction(callback)) {
				callback();
			}
		});
	}


	/**
	 * @name dateSelector
	 */
	app.directive('dateSelector', ['RbsChange.Dialog', '$rootScope', 'RbsChange.i18n', 'RbsChange.Settings', function (Dialog, $rootScope, i18n, Settings) {
		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/date-selector.twig',
			require     : 'ngModel',
			replace     : true,
			scope       : true,

			link : function (scope, elm, attrs, ngModel) {

				scope.timeZone = Settings.get('TimeZone');
				loadTimeZoneInfo(scope.timeZone);

				var	dInput = $(elm).find('[data-role="input-date"]').first(),
					hInput = $(elm).find('[data-role="input-hour"]').first(),
					mInput = $(elm).find('[data-role="input-minute"]').first(),
					datePicker;

				// If 'id' and 'input-id' attributes are found are equal, move this id to the real input field
				// so that the binding with the '<label/>' element works as expected.
				// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
				if (attrs.id && attrs.id === attrs.inputId) {
					dInput.attr('id', attrs.id);
					elm.removeAttr('id');
					elm.removeAttr('input-id');
				}

				scope.openTimeZoneSelector = function ($event) {
					Dialog.embed(
						$(elm).find('.timeZoneSelectorContainer'),
						{
							"title"    : i18n.trans('m.rbs.admin.admin.js.time-zone-selector-title | ucf'),
							"contents" : '<time-zone-selector time-zone="timeZone"></time-zone-selector>'
						},
						scope,
						{
							"pointedElement" : $event.target
						}
					);
				};

				function setTimeZone (tz) {
					loadTimeZoneInfo(tz, function () {
						scope.$apply(function () {
							ngModel.$setViewValue(getFullDate());
						});
					});
				}

				scope.$on('Change:TimeZoneChanged', function (event, tz) {
					scope.timeZone = tz;
				});

				scope.$watch('timeZone', function (newValue, oldValue) {
					if (newValue !== oldValue && ngModel.$viewValue) {
						setTimeZone(newValue);
					}
				}, true);

				if ( ! isNativeDatePickerAvailable) {
					datePicker = dInput.datepicker().data('datepicker');
				}

				ngModel.$render = function () {
					if (ngModel.$viewValue && !(angular.isNumber(ngModel.$viewValue) && isNaN(ngModel.$viewValue))) {
						var date = moment.utc(ngModel.$viewValue).tz(scope.timeZone);
						if (isNativeDatePickerAvailable) {
							dInput.val(date.format('YYYY-MM-DD'));
						} else {
							dInput.datepicker('setDate', moment.utc([date.year(), date.month(), date.day()]).toDate());
						}
						hInput.val(date.hours());
						mInput.val(date.minutes());
					} else {
						dInput.val(null);
						hInput.val('0');
						mInput.val('0');
					}
				};
				ngModel.$render();

				dInput.change(updateDate);
				hInput.change(updateDate);
				mInput.change(updateDate);

				function updateDate () {
					scope.$apply(function () {
						ngModel.$setViewValue(getFullDate());
					});
				}

				function getDateValue () {
					if (isNativeDatePickerAvailable) {
						var inputValue = dInput.val();
						return inputValue != '' ? new Date(inputValue) : null;
					} else {
						return datePicker.date;
					}
				}

				// Merge the date coming from the "datepicker" and the hour/minute information coming from the
				// two additional input fields. The result is a Date object correctly set.
				function getFullDate () {
					var date = getDateValue();
					if (date == null)
					{
						return null;
					}

					return moment.utc(date).tz(scope.timeZone).hours(parseInt(hInput.val(), 10))
						.minutes(parseInt(mInput.val(), 10))
						.second(0).milliseconds(0).toDate();
				}

			}
		};
	}]);


	/**
	 * @name timeZoneSelector
	 */
	app.directive('timeZoneSelector', ['$rootScope', 'RbsChange.Dialog', 'RbsChange.Loading', 'RbsChange.Settings', function ($rootScope, Dialog, Loading, Settings) {
		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/time-zone-selector.twig',
			replace     : true,

			scope : {
				"timeZone" : "="
			},

			link : function (scope, elm) {

				scope.$watch('selectedTimeZone', function (tz) {
					if (tz) {
						loadTimeZoneInfo(tz, function () {
							var now = moment().tz(tz);
							scope.$apply(function () {
								scope.formattedTz = now.format('LLLL');
								scope.tzOffset = now.format('Z');
							});
						}, Loading);
					}
				});

				if (scope.timeZone) {
					scope.selectedTimeZone = scope.timeZone;
				}

				scope.$on('$destroy', function () {
					Dialog.closeEmbedded();
				});

				scope.select = function () {
					scope.timeZone = scope.selectedTimeZone;
					angular.element(elm.closest('form')).scope().$broadcast('Change:TimeZoneChanged', scope.timeZone);
					if (scope.saveSetting) {
						Settings.set('TimeZone', scope.timeZone, true).then(function () {
							Dialog.closeEmbedded();
						});
					} else {
						Dialog.closeEmbedded();
					}
				};
			}
		};
	}]);

})(window.jQuery, $script, moment);