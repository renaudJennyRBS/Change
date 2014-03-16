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
	 * @name RbsChange.directive:rbs-async-action-button
	 * @restrict A
	 *
	 * @description
	 * Used on a <code>&lt;button/&gt;</code> that triggers an async process to add a loading indicator
	 * while the process is running.
	 * When the process ends, the button can display a success label during a few seconds before returning back to
	 * its original label.
	 *
	 * @param {Boolean} rbs-async-action-button When the value switches to `true`, button is in "loading" state.
	 * When the value switches back to `false`, the button is in "success" state and displays a <em>success icon</em>
	 * with an optional <em>success text</em>.
	 * @param {String=} rbs-async-action-button-done-label Text to display when the button is in success state.
	 * @param {Number=} rbs-async-action-button-done-timeout Timeout for success text in milliseconds (defaults to 1500).
	 */
	app.directive('rbsAsyncActionButton', ['$timeout', function ($timeout)
	{
		var DEFAULT_SUCCESS_TIMEOUT = 1500;

		return {
			restrict : 'A',
			scope : false,
			link : function (scope, iElement, iAttrs)
			{
				var iconEl,
					content,
					delay = iAttrs['rbsAsyncActionButtonDoneTimeout'] ? parseInt(iAttrs['rbsAsyncActionButtonDoneTimeout'], 10) : DEFAULT_SUCCESS_TIMEOUT,
					successLabel = iAttrs['rbsAsyncActionButtonDoneLabel'];

				function startLoading ()
				{
					iElement.attr('disabled', 'disabled');
					iconEl = iElement.children('[class^="icon-"],[class*=" icon-"]').first();
					content = iElement.html();
					iconEl.attr('class', 'icon-spin icon-spinner');
				}

				function stopLoading ()
				{
					if (successLabel) {
						iElement.html('<i class="icon-ok"></i> ' + successLabel);
					} else {
						iElement.html('<i class="icon-ok"></i>');
					}
					$timeout(function () {
						iElement.html(content);
						iElement.removeAttr('disabled');
					}, delay);
				}

				scope.$watch(iAttrs['rbsAsyncActionButton'], function (value, oldValue)
				{
					if (angular.isDefined(value) && value !== oldValue) {
						if (value) {
							startLoading();
						} else {
							stopLoading();
						}
					}
				});
			}
		};
	}]);

})();