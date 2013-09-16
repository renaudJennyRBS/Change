(function () {

	"use strict";

	function changeEditorWebsiteFunctionalPage ($rootScope, $location, Dialog, UrlManager, Breadcrumb, i18n, structureEditorService, REST, $q) {

		return {
			restrict    : 'C',
			templateUrl : 'Rbs/Website/FunctionalPage/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {

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
					else if (sectionName === 'functions') {
						initPageFunctions();
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

				function initPageFunctions () {
					$q.all([
						REST.action('collectionItems', { 'code' : 'Rbs_Website_AvailablePageFunctions' }),
						REST.action('collectionItems', { 'code' : 'Rbs_Website_AvailablePageFunctions', 'pageId' : scope.document.id })
					]).then(function (results) {
						scope.allFunctions = results[0].items;
						scope.availableFunctions = results[1].items;
						scope.warningFunctions = {};
						scope.$watch('document.allowedFunctionsCode', function () {
							updateFunctionsStatus();
						}, true);
					});
				}

				function updateFunctionsStatus () {
					scope.warningFunctions = null;
					angular.forEach(scope.document.allowedFunctionsCode, function (funcName) {
						if (! scope.availableFunctions[funcName]) {
							if (scope.warningFunctions === null) {
								scope.warningFunctions = {};
							}
							scope.warningFunctions[funcName] = scope.allFunctions[funcName];
						}
					});
				}

				scope.hasAvailableFunctions = function () {
					var counter = 0;
					angular.forEach(scope.availableFunctions, function () {
						counter++;
					});
					return counter > 0;
				};

				scope.editPage = function ($event, page) {
					if (scope.isUnchanged()) {
						$location.path(UrlManager.getUrl(page, 'editor'));
					} else {
						Dialog.confirmEmbed(
							element.find('[data-role="edit-page-contents-confirmation"]'),
							i18n.trans('m.rbs.admin.admin.js.confirm | ucf'),
							i18n.trans('m.rbs.website.admin.js.open-page-editor-warning'),
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
	function changeEditorWebsiteFunctionalPageLocalized ($location, Dialog, UrlManager) {
		var directive = changeEditorWebsiteFunctionalPage ($location, Dialog, UrlManager);
		directive.templateUrl = 'Rbs/Website/FunctionalPage/editor-localized.twig';
		return directive;
	}

	changeEditorWebsiteFunctionalPageLocalized.$inject = changeEditorWebsiteFunctionalPage.$inject;
	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpageLocalized', changeEditorWebsiteFunctionalPageLocalized);

})();