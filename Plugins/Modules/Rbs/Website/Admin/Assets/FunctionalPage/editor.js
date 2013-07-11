(function () {

	"use strict";

	function changeEditorWebsiteFunctionalPage (Editor, $rootScope, $location, Dialog, UrlManager, Breadcrumb, i18n, structureEditorService, FormsManager, REST, $q) {

		return {
			restrict    : 'C',
			templateUrl : 'Rbs/Website/FunctionalPage/editor.twig',
			replace     : true,

			// Create isolated scope
			scope : {
				original : '=document',
				referenceDocument : '=',
				onSave   : '&',
				onCancel : '&',
				section  : '=',
				language : '='
			},

			link: function (scope, elm) {

				Editor.initScope(scope, elm, function () {
					scope.editableContentInfo = structureEditorService.getContentInfo(scope.document.editableContent);
					if (!scope.document.section && Breadcrumb.getCurrentNode()) {
						scope.document.section = Breadcrumb.getCurrentNode();

					}
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
							console.log(scope.document.allowedFunctionsCode);
							updateFunctionsStatus();
						}, true);
//						updateFunctionsStatus();
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

				scope.initSection = function (sectionName) {
					if (sectionName === 'functions') {
						initPageFunctions();
					}
				};


				scope.editPage = function ($event, page) {
/*
					if (scope.isUnchanged()) {
						$location.path(UrlManager.getUrl(page, 'editor'));
					} else {
						Dialog.confirmEmbed(
							elm.find('[data-role="edit-page-contents-confirmation"]'),
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
*/
				};

			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsiteFunctionalPage.$inject = [
		'RbsChange.Editor', '$rootScope',
		'$location',
		'RbsChange.Dialog',
		'RbsChange.UrlManager',
		'RbsChange.Breadcrumb',
		'RbsChange.i18n',
		'structureEditorService',
		'RbsChange.FormsManager',
		'RbsChange.REST',
		'$q'
	];
	app.directive('changeEditorWebsiteFunctionalPage', changeEditorWebsiteFunctionalPage);


	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsiteFunctionalPageLocalized (Editor, $location, Dialog, UrlManager) {
		var directive = changeEditorWebsiteFunctionalPage (Editor, $location, Dialog, UrlManager);
		directive.templateUrl = 'Rbs/Website/FunctionalPage/editor-localized.twig';
		return directive;
	}

	changeEditorWebsiteFunctionalPageLocalized.$inject = changeEditorWebsiteFunctionalPage.$inject;
	app.directive('changeEditorWebsiteFunctionalPageLocalized', changeEditorWebsiteFunctionalPageLocalized);

})();