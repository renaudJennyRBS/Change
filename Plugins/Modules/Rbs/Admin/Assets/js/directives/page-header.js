(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('pageHeader', ['RbsChange.FormsManager', 'RbsChange.Dialog', 'RbsChange.Breadcrumb', function (FormsManager, Dialog, Breadcrumb) {
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

				scope.openLocalizationOptions = function () {
					Dialog.embed(
						element.find('.embedded-content'),
						{
							'contents' : '<div document-localization-options="document" language="language" reference-language="refLCID"></div>',
							'title'    : "Options de traduction"
						},
						scope,
						{
							'pointedElement' : $(element).find('[data-role="choose-locale"]')
						}
					);
				};

				scope.openCorrectionViewer = function ($event) {
					Dialog.embed(
						element.find('.embedded-content'),
						{
							'contents' : '<div rbs-correction-viewer document="document"></div>',
							'title'    : "Correction"
						},
						scope,
						{
							'pointedElement' : $(element).find('[data-role="view-correction"]')
						}
					);
				};

				Breadcrumb.ready().then(function () {
					scope.currentFolder = Breadcrumb.getCurrentNode();
					scope.$on('Change:TreePathChanged', function () {
						scope.currentFolder = Breadcrumb.getCurrentNode();
					});
				});

			}

		};
	}]);

})(window.jQuery);