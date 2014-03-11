(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	function changeEditorWebsiteFunctionalPage($rootScope, REST, $routeParams)
	{
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link : function (scope)
			{
				var contentSectionInitialized = false;

				scope.onLoad = function () {
					if (scope.document.isNew() && $routeParams.website && !scope.document.website) {
						scope.document.website = $routeParams.website;
						REST.resource($routeParams.website).then(function (doc){ scope.document.website = doc});
					}
				};

				scope.initSection = function (sectionName) {
					if (sectionName === 'content') {
						scope.loadTemplate();
						contentSectionInitialized = true;
					}
				};

				scope.$on('Navigation.saveContext', function (event, args) {
					args.context.savedData('pageTemplate', scope.pageTemplate);
				});

				scope.onRestoreContext = function (currentContext) {
					scope.pageTemplate = currentContext.savedData('pageTemplate');
				};

				scope.loadTemplate = function () {
					var pt = scope.document.pageTemplate;
					if (pt) {
						if (!scope.pageTemplate || scope.pageTemplate.id != pt.id) {
							REST.resource(pt).then(function (template) {
								scope.pageTemplate = {id: template.id, html: template.htmlForBackoffice, data: template.editableContent};
							});
						}
					}
				};

				scope.leaveSection = function (section) {
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').show();
						$('#rbsWebsitePageBlockPropertiesAside').hide();
					}
				};

				scope.enterSection = function (section) {
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').hide();
						$('#rbsWebsitePageBlockPropertiesAside').show();
					}
				};

				// This is for the "undo" dropdown menu:
				// Each item automatically activates its previous siblings.
				$('[data-role=undo-menu]').on('mouseenter', 'li', function () {
					$(this).siblings().removeClass('active');
					$(this).prevAll().addClass('active');
				});

				$rootScope.$watch('website', function (website) {
					if (scope.document && !scope.document.website) {
						scope.document.website = website;
					}
				}, true);

				scope.$watch('document.pageTemplate', function () {
					scope.loadTemplate();
				}, true);
			}
		};

	}

	changeEditorWebsiteFunctionalPage.$inject = [
		'$rootScope', 'RbsChange.REST', '$routeParams'
	];
	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpage', changeEditorWebsiteFunctionalPage);

})(window.jQuery);