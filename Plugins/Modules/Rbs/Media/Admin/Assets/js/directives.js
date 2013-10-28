(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsAvatar', ['RbsChange.REST', rbsAvatarDirective]);

	function rbsAvatarDirective (REST) {

		return {
			restrict : 'E',
			templateUrl : 'Rbs/Media/js/avatar.twig',
			replace: 'true',
			scope: {'size' : '@', 'email' : '@', 'userId' : '@'},
			// Create isolated scope

			link : function (scope, elm, attrs) {

				var myRegex = /ng[A-Z].*/;

				var options = {};
				for (var key in attrs)
				{
					var value = attrs[key];

					if (angular.isString(value) && key != 'size' && key != 'userId' && key != 'email' && !myRegex.test(key))
					{
						options[key] = value;
					}
				}

				console.log(options);

				var params = {
					'size' : scope.size,
					'email' : scope.email,
					'userId' : scope.userId,
					'params' : options
				}

				REST.call(REST.getBaseUrl('Rbs/Avatar'), params).then(function (response){scope.src = response.href}, function (error) {});

			}
		};
	}

})(window.jQuery);