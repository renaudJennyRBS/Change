/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	/**
	 * This Directive is to be applied on <a/> elements.
	 * It adds/removes a CSS class if the current URL equals/differs from the 'href' attribute.
	 */

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-url-match-class
	 * @restrict A
	 *
	 * @description
	 * Apply this Directive on <code>&lt;a/&gt;</code> elements to automatically apply the `active` class on them
	 * when the current URL matches the one in the `href` attribute.
	 *
	 * @param {String=} rbs-url-match-class CSS class name to apply when the URL matches the `href` (defaults to `active`).
	 */
	angular.module('RbsChange').directive('rbsUrlMatchClass', ['$rootScope', '$location', function ($rootScope, $location)
	{
		function isCurrentUrl (href) {
			if (href.charAt(0) !== '/') {
				href = '/' + href;
			}
			var currentUrl = $location.url(), p;

			if (currentUrl === href) {
				return true;
			}

			if ((p = currentUrl.indexOf('?')) !== -1) {
				currentUrl = currentUrl.substr(0, p);
			}
			return currentUrl === href;
		}

		return {
			restrict : 'A',

			link : function (scope, iElement, iAttrs)
			{
				if (! iElement.is('a')) {
					throw new Error("Directive 'urlMatchClass' must be applied on <a/> elements.");
				}

				var	target,
					cssClass = iAttrs.urlMatchClass || 'active';

				if (iElement.parent().is('li') && iElement.closest('li').length) {
					target = iElement.parent();
				} else {
					target = iElement;
				}

				function update () {
					if (isCurrentUrl(iElement.attr('href'))) {
						target.addClass(cssClass);
					} else {
						target.removeClass(cssClass);
					}
				}

				$rootScope.$on('$routeChangeSuccess', update);
				$rootScope.$on('$routeUpdate', update);
				iAttrs.$observe('href', update);

				update();
			}
		};
	}]);

})();