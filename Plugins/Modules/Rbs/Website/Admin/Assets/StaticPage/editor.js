(function () {

	"use strict";

	function changeEditorWebsitePage ($rootScope, REST, Breadcrumb, structureEditorService) {

		return {
			restrict    : 'C',
			templateUrl : 'Rbs/Website/StaticPage/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {

				scope.onReady = function () {
					scope.editableContentInfo = structureEditorService.getContentInfo(scope.document.editableContent);
					if (!scope.document.section && Breadcrumb.getCurrentNode()) {
						scope.document.section = Breadcrumb.getCurrentNode();
					}
				};

				scope.initSection = function (sectionName) {
					if (sectionName === 'content') {
						if (scope.document.pageTemplate)
						{
							REST.resource(scope.document.pageTemplate).then(function (template)
							{
								scope.pageTemplate = { "html" : template.htmlForBackoffice, "data" : template.editableContent };
							});
						}
					}
				};

				editorCtrl.init('Rbs_Website_FunctionalPage');

				// This is for the "undo" dropdown menu:
				// Each item automatically activates its previous siblings.
				$('[data-role=undo-menu]').on('mouseenter', 'li', function ()
				{
					$(this).siblings().removeClass('active');
					$(this).prevAll().addClass('active');
				});

				$rootScope.$watch('website', function (website) {
					if (scope.document && ! scope.document.website) {
						scope.document.website = website;
					}
				}, true);

			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsitePage.$inject = [
		'$rootScope',
		'RbsChange.REST',
		'RbsChange.Breadcrumb',
		'structureEditorService'
	];
	app.directive('rbsDocumentEditorRbsWebsiteStaticpage', changeEditorWebsitePage);


	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageLocalized ($location, Dialog, UrlManager) {
		var directive = changeEditorWebsitePage ($location, Dialog, UrlManager);
		directive.templateUrl = 'Rbs/Website/StaticPage/editor-localized.twig';
		return directive;
	}

	changeEditorWebsitePageLocalized.$inject = changeEditorWebsitePage.$inject;
	app.directive('rbsDocumentEditorRbsWebsiteStaticpageLocalized', changeEditorWebsitePageLocalized);

})();