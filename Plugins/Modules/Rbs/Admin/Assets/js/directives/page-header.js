(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsPageHeader', ['RbsChange.Dialog', 'RbsChange.Breadcrumb', function (Dialog, Breadcrumb) {
		return {

			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/page-header.twig',
			replace     : true,

			link: function (scope, element, attrs) {
				attrs.$observe('title', function (value) {
					scope.title = value;
				});

				if (attrs.subTitle) {
					scope.subTitle = attrs.subTitle;
				}

				scope.showDocumentInfo = false;

				scope.toggleDocumentInfo = function () {
					scope.showDocumentInfo = ! scope.showDocumentInfo;
				};

				Breadcrumb.ready().then(function () {
					scope.currentFolder = Breadcrumb.getCurrentNode();
					scope.$on('Change:TreePathChanged', function () {
						scope.currentFolder = Breadcrumb.getCurrentNode();
					});
				});

				scope.$on('Change:Editor:LocalCopyMerged', function () {
					console.log("Local copy has been merged!");
					scope.localCopyMerged = true;
				});

			}

		};
	}]);

})(window.jQuery);