/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-switch
	 * @restrict E
	 *
	 * @description
	 * Displays a Yes/No switch control.
	 *
	 * @param {Boolean} ng-model The bound value.
	 * @param {String=} label-on Label for the "on" position
	 * @param {String=} label-off Label for the "off" position
	 * @param {String=} confirm-on Confirmation message when switching from "off" to "on"
	 * @param {String=} confirm-off Confirmation message when switching from "on" to "off"
	 * @param {String=} confirm-title Title in the confirmation dialog box (requires "confirm-on" and/or "confirm-off")
	 */
	app.directive('rbsSwitch', ['RbsChange.Dialog', 'RbsChange.i18n', function (Dialog, i18n)
	{
		return {
			template : '<div class="switch-on-off switch">' +
				'<div class="switch-button"></div>' +
				'<label class="on" ng-bind-html="labelOn"></label>' +
				'<label class="off" ng-bind-html="labelOff"></label>' +
				'</div>',

			restrict : 'E',
			require : 'ngModel',
			replace : true,
			priority : -1, // Let `required=""` directive execute before this one.
			scope : true,

			link : function (scope, elm, attrs, ngModel)
			{
				var sw = $(elm), valueOff, valueOn, acceptedValuesOn, confirmTitle;

				scope.labelOn = attrs.labelOn || i18n.trans('m.rbs.admin.adminjs.yes');
				scope.labelOff = attrs.labelOff || i18n.trans('m.rbs.admin.adminjs.no');
				valueOff = attrs.valueOff || false;
				valueOn = attrs.valueOn || true;
				acceptedValuesOn = attrs.acceptedValuesOn || [];
				confirmTitle = attrs.confirmTitle || i18n.trans('m.rbs.admin.adminjs.confirmation | ucf');

				// Remove all parsers that could invalidate this widget (required=true for example).
				ngModel.$parsers.length = 0;

				ngModel.$render = function () {
					if (isON()) {
						sw.addClass('on');
					} else {
						sw.removeClass('on');
					}
				};

				function isON () {
					return ngModel.$viewValue === valueOn || acceptedValuesOn.indexOf(ngModel.$viewValue) !== -1;
				}

				function isOFF () {
					return ! isON();
				}

				function toggleState () {
					ngModel.$setViewValue(isON() ? valueOff : valueOn);
					ngModel.$render();
				}

				sw.click(function () {
					if (attrs.disabled) {
						return;
					}

					if (attrs.confirmOff && isON()) {

						Dialog.confirmLocal(
								sw,
								"<strong>" + confirmTitle + "</strong>",
								attrs.confirmOff
						).then(function () { toggleState(); });

					} else if (attrs.confirmOn && isOFF()) {

						Dialog.confirmLocal(
								sw,
								"<strong>" + confirmTitle + "</strong>",
								attrs.confirmOn
						).then(function () { toggleState(); });

					} else {

						scope.$apply(toggleState);

					}
				});
			}
		};
	}]);

})(window.jQuery);