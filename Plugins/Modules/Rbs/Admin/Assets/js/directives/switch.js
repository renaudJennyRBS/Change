(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @name switch
	 * @description A switch button to bind to a model's boolean property.
	 *
	 * @attribute label-on Label for the "on" position
	 * @attribute label-off Label for the "off" position
	 * @attribute confirm-on Confirmation message when switching from "off" to "on"
	 * @attribute confirm-off Confirmation message when switching from "on" to "off"
	 * @attribute confirm-title Title in the confirmation dialog box (requires "confirm-on" and/or "confirm-off")
	 *
	 * @example: <code><switch confirm-off="Are you sure to disable this element?" ng-model="myModel.active"/></code>
	 */
	app.directive('switch', ['RbsChange.Dialog', function (Dialog) {
		return {
			restrict : 'E',

			template : '<div class="switch-on-off switch">' +
				'<div class="switch-button"></div>' +
				'<label class="on">{{labelOn}}</label>' +
				'<label class="off">{{labelOff}}</label>' +
				'</div>',

			require: 'ng-model',

			replace: true,

			// Create isolated scope
			scope: true,

			link : function (scope, elm, attrs, ngModel) {
				var sw = $(elm),
					valueOff, valueOn, acceptedValuesOn,
					confirmTitle
					;

				// FIXME Localization
				scope.labelOn = attrs.labelOn || 'oui';
				scope.labelOff = attrs.labelOff || 'non';
				valueOff = attrs.valueOff || false;
				valueOn = attrs.valueOn || true;
				acceptedValuesOn = attrs.acceptedValuesOn || [];
				confirmTitle = attrs.confirmTitle || 'Confirmation';

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