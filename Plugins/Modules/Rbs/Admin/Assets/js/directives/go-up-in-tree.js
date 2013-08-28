(function () {

	"use strict";

	angular.module('RbsChange').directive('goUpInTree', ['RbsChange.Breadcrumb', function (Breadcrumb) {

		return {

			link : function (scope, elm) {

				function breadcrumbChanged (event, pathInfo) {
					scope.parent = pathInfo.path.length >= 2 ? pathInfo.path[pathInfo.path.length-2] : null;
					if (scope.parent) {
						elm.attr('href', scope.parent.treeUrl());
						elm.removeClass('disabled');
					} else {
						elm.attr('href', 'javascript:;');
						elm.addClass('disabled');
					}
				}
				Breadcrumb.ready().then(function (pathInfo) {
					breadcrumbChanged(null, pathInfo);
					scope.$on('Change:TreePathChanged', breadcrumbChanged);
				});

				elm.html('<i class="icon-level-up"></i>');
			}

		};

	}]);

})();