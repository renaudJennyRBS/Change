(function () {

	"use strict";

	function changeEditorWebsiteFunctionalPage($rootScope, Breadcrumb, REST) {

		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Website/FunctionalPage/editor.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {

				var contentSectionInitialized = false;

				scope.onLoad = function () {
					if (!scope.document.section){
						var nodeId =  Breadcrumb.getCurrentNodeId();
						if (nodeId) {
							REST.resource(nodeId).then(function (doc){scope.document.section = doc})
						}
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

				editorCtrl.init('Rbs_Website_FunctionalPage');

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

				scope.preSave = function (document) {
					var p = $q.defer();
					if (!document.pageTemplate.mailSuitable) {
						p.resolve();
					}
					else {
						var error = {
							code: 999999,
							message: i18n.trans('m.rbs.website.admin.page_template_not_suitable_help | ucf'),
							httpStatus: 500
						};
						p.reject(error);
					}
					return p.promise;
				};
			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsiteFunctionalPage.$inject = [
		'$rootScope',
		'RbsChange.Breadcrumb',
		'RbsChange.REST'
	];
	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpage', changeEditorWebsiteFunctionalPage);

	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageTranslate(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Website/FunctionalPage/editor-translate.twig',
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
				editorCtrl.init('Rbs_Website_FunctionalPage');
			}
		};
	}

	changeEditorWebsitePageTranslate.$inject = [
		'RbsChange.REST'
	];

	app.directive('rbsDocumentEditorRbsWebsiteFunctionalpageTranslate', changeEditorWebsitePageTranslate);

})();