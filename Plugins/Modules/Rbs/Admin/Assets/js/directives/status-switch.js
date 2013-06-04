(function ($) {

	var app = angular.module('RbsChange');

	/**
	 * @name statusSwitch
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
	app.directive('statusSwitch', ['$q', 'RbsChange.Dialog', 'RbsChange.Actions', function ($q, Dialog, Actions) {
		return {
			restrict : 'E',

			template : '<div class="switch-on-off switch" ng-class="{\'busy\':busy}">' +
				'<div class="switch-button"></div>' +
				'<label class="on">oui</label>' +
				'<label class="off">non</label>' +
				'</div>',

			replace: true,

			// Create isolated scope
			scope: {
				document: '='
			},

			link : function (scope, elm, attrs, ngModel) {
				var sw = $(elm),
				    valueOff, valueOn, acceptedValuesOn,
				    confirmTitle,
				    disabledValues
				    ;

				valueOff = 'DEACTIVATED';
				valueOn = (! attrs.publishedCondition || scope.$eval(attrs.publishedCondition)) ? 'PUBLISHABLE' : 'ACTIVE';
				acceptedValuesOn = ['PUBLISHABLE'];
				disabledValues = ['DRAFT', 'VALIDATION'];
				confirmTitle = attrs.confirmTitle || 'Confirmation';

				scope.$watch('document', function (doc) {
					//console.log("StatusSwitch: document has changed: ", doc);
					if (disabledValues.indexOf(doc.publicationStatus) !== -1) {
						sw.attr('disabled', 'disabled');
					} else {
						sw.removeAttr('disabled');
						if (isON()) {
							sw.addClass('on');
							sw.removeClass('off');
						} else {
							sw.addClass('off');
							sw.removeClass('on');
						}
					}
				}, true);

				function isON () {
					return scope.document.publicationStatus === valueOn || acceptedValuesOn.indexOf(scope.document.publicationStatus) !== -1;
				}

				function isOFF () {
					return ! isON();
				}

				function toggleState () {
					scope.$apply('busy = true');
					Actions.execute(isON() ? 'deactivate' : 'activate', {'$docs': [scope.document]}).then(
							// Success
							function (docs) {
								scope.document.publicationStatus = docs[0].publicationStatus;
								scope.busy = false;
							},
							// Error
							function (reason) {
								scope.busy = false;
							}
						);
				}

				sw.click(function () {
					if (sw.attr('disabled') || scope.busy) {
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

						toggleState();

					}
				});

			}
		};
	}]);

})(window.jQuery);