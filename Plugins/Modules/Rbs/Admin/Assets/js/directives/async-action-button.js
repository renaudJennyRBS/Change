(function () {

	"use strict";

	var app = angular.module('RbsChange');

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