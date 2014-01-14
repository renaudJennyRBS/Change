(function () {

	"use strict";

	/**
	 * This Directive is to be applied on <a/> elements.
	 * It adds/removes a CSS class if the current URL equals/differs from the 'href' attribute.
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