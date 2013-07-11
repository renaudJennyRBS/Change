(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsTip', ['localStorageService', 'RbsChange.i18n', function (localStorage, i18n) {

		var ls = JSON.parse(localStorage.get('dismissedTips') || '{}');

		function dismiss (tipId) {
			ls[tipId] = true;
			localStorage.add('dismissedTips', JSON.stringify(ls));
		}

		function isDismissed (tipId) {
			return ls[tipId] === true;
		}

		return {
			restrict   : 'A',
			replace    : true,
			transclude : true,
			scope      : true,
			template   :
				'<div class="alert alert-info fade in">' +
					'<button type="button" class="close" data-dismiss="alert">&times;</button>' +
					'<div ng-transclude=""></div>' +
					'<div class="clearfix" style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #BCE8F1;">' +
						'<a href="javascript:;" ng-click="dismissForEver()"><i class="icon-thumbs-up-alt"></i> ' + i18n.trans('m.rbs.admin.admin.js.tip-dismiss-thank-you') + '</a>' +
					'</div>' +
				'</div>',

			compile : function (tElement, tAttrs) {

				if (isDismissed(tAttrs.rbsTip)) {
					tElement.remove();
				}

				// Linking function
				return function (scope, element, attrs) {

					scope.dismissForEver = function () {
						$(element).alert('close');
						dismiss(attrs.rbsTip);
					};

				};
			}

		};
	}]);

})();