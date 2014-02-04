(function ($) {

	"use strict";

	function changeEditorWebsitePage($rootScope, REST, Breadcrumb) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Website/StaticPage/editor.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {

				var contentSectionInitialized = false;

				scope.onLoad = function () {
					if (!scope.document.section && Breadcrumb.getCurrentNode()) {
						scope.document.section = Breadcrumb.getCurrentNode();
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
						if (!scope.pageTemplate || scope.pageTemplate.id != pt.id)
						{
							REST.resource(pt).then(function (template) {
								scope.pageTemplate = {id:template.id, html: template.htmlForBackoffice, data: template.editableContent};
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

				scope.finalizeNavigationContext = function (context) {
					if (context.params.blockId) {
						scope.$broadcast(
							'Change:StructureEditor.setBlockParameter',
							{
								blockId: context.params.blockId,
								property: context.params.property,
								value: context.result
							}
						);
					}
				};

				editorCtrl.init('Rbs_Website_StaticPage');

				// This is for the "undo" dropdown menu:
				// Each item automatically activates its previous siblings.
				$('[data-role=undo-menu]').on('mouseenter', 'li', function () {
					$(this).siblings().removeClass('active');
					$(this).prevAll().addClass('active');
				});

//TODO : ca sert à quoi ?
//				$rootScope.$watch('website', function (website) {
//					if (scope.document && !scope.document.website) {
//						scope.document.website = website;
//					}
//				}, true);

				scope.$watch('document.pageTemplate', function (pageTemplate) {
					scope.loadTemplate();
				}, true);

			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsitePage.$inject = [
		'$rootScope',
		'RbsChange.REST',
		'RbsChange.Breadcrumb'
	];
	app.directive('rbsDocumentEditorRbsWebsiteStaticpage', changeEditorWebsitePage);

	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageTranslate(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Website/StaticPage/editor-translate.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {
				scope.onLoad = function () {
					// Load Template Document
					if (scope.document.pageTemplate) {
						REST.resource(scope.document.pageTemplate).then(function (template) {
							scope.pageTemplate = { "html": template.htmlForBackoffice, "data": template.editableContent };
						});
					}
				};
				editorCtrl.init('Rbs_Website_StaticPage');
			}
		};
	}

	changeEditorWebsitePageTranslate.$inject = [
		'RbsChange.REST'
	];

	app.directive('rbsDocumentEditorRbsWebsiteStaticpageTranslate', changeEditorWebsitePageTranslate);

})(window.jQuery);