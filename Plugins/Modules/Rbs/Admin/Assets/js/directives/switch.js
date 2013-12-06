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
	app.directive('switch', ['RbsChange.Dialog', 'RbsChange.i18n', function (Dialog, i18n)
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