(function ($) {

	var app = angular.module('RbsChange');

	/**
	 * @name activateSwitch
	 *
	 * @example: <code><rbs-switch confirm-off="Are you sure to disable this element?" document="myDocument"/></code>
	 */
	app.directive('rbsActivateSwitch', ['$q', 'RbsChange.Dialog', 'RbsChange.Actions', 'RbsChange.i18n', function ($q, Dialog, Actions, i18n) {
		return {
			restrict : 'E',

			template : '<div class="switch-on-off switch" ng-class="{\'busy\':busy}">' +
				'<div class="switch-button"></div>' +
				'<label class="on" ng-bind-html="labelOn"></label>' +
				'<label class="off" ng-bind-html="labelOff"></label>' +
				'</div>',

			replace : true,

			// Create isolated scope
			scope : {
				document : '='
			},

			link : function (scope, elm, attrs) {
				var sw = $(elm);
				scope.labelOn = attrs.labelOn || i18n.trans('m.rbs.admin.adminjs.yes');
				scope.labelOff = attrs.labelOff || i18n.trans('m.rbs.admin.adminjs.no');

				scope.$watch('document', function (doc) {
					if (doc) {
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
					return scope.document.active === true;
				}

				function isOFF () {
					return ! isON();
				}

				function toggleState () {
					scope.$apply('busy = true');
					Actions.execute(isON() ? 'deactivate' : 'activate', {'$docs': [scope.document]}).then(
							// Success
							function (docs) {
								scope.document.active = docs[0].active;
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

						// TODO localize
						Dialog.confirmLocal(
								sw,
								"<strong>Confirmation</strong>",
								attrs.confirmOff
						).then(function () { toggleState(); });

					} else if (attrs.confirmOn && isOFF()) {

						Dialog.confirmLocal(
								sw,
								"<strong>Confirmation</strong>",
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