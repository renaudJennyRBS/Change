(function() {
	"use strict";

	function rbsDocumentEditorRbsMailMailEdit($http, REST, i18n, NotificationCenter, ErrorFormatter, $location) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				// Subject variables handling.
				scope.addSubstitutionVariable = function(variable) {
					if (scope.document.subject) {
						scope.document.subject += '{' + variable + '}';
					}
					else {
						scope.document.subject = '{' + variable + '}';
					}
				};

				// Variation creation.
				scope.addMailVariation = function() {
					$http.post(REST.getBaseUrl('Rbs/Mail/AddMailVariation'), {documentId: scope.document.id})
						.then(
							function(response) {
								$location.path('Rbs/Mail/Mail/' + response.data.properties.id);
							}, function(error) {
								NotificationCenter.error(i18n.trans('m.rbs.mail.admin.mail_add_mail_variation_error | ucf'),
									ErrorFormatter.format(error.data));
							}
						);
				};

				// Content edition.
				var contentSectionInitialized = false;

				scope.initSection = function(sectionName) {
					if (sectionName === 'content') {
						scope.loadTemplate();
						contentSectionInitialized = true;
					}
				};

				scope.loadTemplate = function() {
					if (scope.document.template) {
						REST.resource(scope.document.template).then(function(template) {
							scope.template = { "html": template.htmlForBackoffice, "data": template.editableContent };
						});
					}
				};

				scope.leaveSection = function(section) {
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').show();
						$('#rbsWebsitePageBlockPropertiesAside').hide();
					}
				};

				scope.enterSection = function(section) {
					if (section === 'content') {
						$('#rbsWebsitePageDefaultAsides').hide();
						$('#rbsWebsitePageBlockPropertiesAside').show();
					}
				};

				scope.finalizeNavigationContext = function(context) {
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

				scope.$watch('document.template', function(template, old) {
					if (old && scope.document && template !== old && contentSectionInitialized) {
						scope.loadTemplate();
					}
				}, true);
			}
		};
	}

	rbsDocumentEditorRbsMailMailEdit.$inject = ['$http', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter',
		'RbsChange.ErrorFormatter', '$location'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMailMailEdit', rbsDocumentEditorRbsMailMailEdit);
})();