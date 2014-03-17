/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbs-tip
	 * @name User tips
	 * @restrict A
	 *
	 * @description
	 * Displays a tip for the user in a <code>&lt;div class="alert-info"&gt;&lt;/div&gt;</code>.
	 * The user can close the tip and ask to never show again this tip (this information is stored in the
	 * localStorage of the browser).
	 *
	 * @param {String} rbs-tip Unique identifier of the tip. It is recommended to use something similar to
	 * `vendorPluginName...` to avoid conflicts.
	 *
	 * @example
	 * <pre>
	 *     <div ng-show="previewAvailable" rbs-tip="rbsDocumentListGeneralWithPreview">
	 *         ... tip contents ...
	 *     </div>
	 * </pre>
	 */
	app.directive('rbsTip', ['localStorageService', 'RbsChange.i18n', function (localStorage, i18n)
	{
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
						'<a href="javascript:;" ng-click="dismissForEver()"><i class="icon-thumbs-up-alt"></i> ' + i18n.trans('m.rbs.admin.adminjs.tip_dismiss_thank_you') + '</a>' +
					'</div>' +
				'</div>',

			compile : function (tElement, tAttrs)
			{
				if (isDismissed(tAttrs.rbsTip)) {
					tElement.remove();
				}

				// Linking function
				return function (scope, element, attrs)
				{
					scope.dismissForEver = function () {
						$(element).alert('close');
						dismiss(attrs.rbsTip);
					};
				};
			}
		};
	}]);

})();