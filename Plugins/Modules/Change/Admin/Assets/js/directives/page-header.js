(function ($) {

	var app = angular.module('RbsChange');

	app.directive('pageHeader', ['RbsChange.FormsManager', 'RbsChange.Dialog', function (FormsManager, Dialog) {
		return {

			restrict    : 'E',
			templateUrl : 'Change/Admin/js/directives/page-header.html',
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
							'contents' : '<div correction-viewer current="document"></div>',
							'title'    : "Correction"
						},
						scope,
						{
							'pointedElement' : $(element).find('[data-role="view-correction"]')
						}
					);
				};

			}

		};
	}]);

})(window.jQuery);