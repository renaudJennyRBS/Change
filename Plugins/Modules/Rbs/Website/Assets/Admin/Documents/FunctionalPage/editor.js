(function () {

	"use strict";

	function changeEditorWebsiteFunctionalPage ($rootScope, $location, Dialog, UrlManager, Breadcrumb, i18n, structureEditorService, REST, $q) {

		return {
			restrict    : 'A',
			templateUrl : 'Document/Rbs/Website/FunctionalPage/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {

				var contentSectionInitialized = false;

				scope.onReady = function () {
					scope.editableContentInfo = structureEditorService.getContentInfo(scope.document.editableContent);
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

				scope.loadTemplate = function () {
					if (scope.document.pageTemplate)
					{
						REST.resource(scope.document.pageTemplate).then(function (template)
						{
							scope.pageTemplate = { "html" : template.htmlForBackoffice, "data" : template.editableContent };
						});
					}
				}

				scope.leaveSection = function (section)
				{
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').show();
						$('#rbsWebsitePageBlockPropertiesAside').hide();
					}

				};

				scope.enterSection = function (section)
				{
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').hide();
						$('#rbsWebsitePageBlockPropertiesAside').show();
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

				scope.$watch('document.pageTemplate', function (pageTemplate, old) {
					if (old && scope.document && pageTemplate !== old && contentSectionInitialized) {
						scope.loadTemplate();
					}
				}, true);

				scope.editPage = function ($event, page) {
					if (scope.isUnchanged()) {
						$location.path(UrlManager.getUrl(page, 'editor'));
					} else {
						Dialog.confirmEmbed(
							element.find('[data-role="edit-page-contents-confirmation"]'),
							i18n.trans('m.rbs.admin.adminjs.confirm | ucf'),
							i18n.trans('m.rbs.website.admin.open_page_editor_warning'),
							scope,
							{
								"pointedElement" : $event.target
							}
						).then(function () {
							scope.onSave = function () {
								$location.path(UrlManager.getUrl(page, 'editor'));
							};
							scope.submit();
						});
					}
				};

			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsiteFunctionalPage.$inject = [
		'$rootScope',
		'$location',
		'RbsChange.Dialog',
		'RbsChange.UrlManager',
		'RbsChange.Breadcrumb',
		'RbsChange.i18n',
		'structureEditorService',
		'RbsChange.REST',
		'$q'
	];
	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpage', changeEditorWebsiteFunctionalPage);



	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageTranslate (REST)
	{
		return {
			restrict    : 'A',
			templateUrl : 'Document/Rbs/Website/FunctionalPage/editor-translate.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				scope.onLoad = function ()
				{
					// Load Template Document
					if (scope.document.pageTemplate) {
						REST.resource(scope.document.pageTemplate).then(function (template) {
							scope.pageTemplate = { "html" : template.htmlForBackoffice, "data" : template.editableContent };
						});
					}
				};
				editorCtrl.init('Rbs_Website_FunctionalPage');
			}
		};
	}

	changeEditorWebsitePageTranslate.$inject = [
		'RbsChange.REST'
	];

	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpageTranslate', changeEditorWebsitePageTranslate);

})();