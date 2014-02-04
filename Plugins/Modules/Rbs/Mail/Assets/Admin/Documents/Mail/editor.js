(function ()
{
	"use strict";

	/**
	 *
	 * @param $http
	 * @param REST
	 * @param Utils
	 * @param i18n
	 * @param NotificationCenter
	 * @param ErrorFormatter
	 * @param $location
	 * @constructor
	 */
	function Editor($http, REST, Utils, i18n, NotificationCenter, ErrorFormatter, $location)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Mail/Mail/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.addSubstitutionVariable = function (variable)
				{
					if (scope.document.subject)
					{
						scope.document.subject += '{' + variable + '}';
					}
					else
					{
						scope.document.subject = '{' + variable + '}';
					}
				};

				//content editor part
				var contentSectionInitialized = false;

				scope.initSection = function (sectionName)
				{
					if (sectionName === 'content')
					{
						scope.loadTemplate();
						contentSectionInitialized = true;
					}
				};

				scope.loadTemplate = function () {
					if (scope.document.template)
					{
						REST.resource(scope.document.template).then(function (template)
						{
							scope.template = { "html" : template.htmlForBackoffice, "data" : template.editableContent };
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

				scope.finalizeNavigationContext = function (context)
				{
					if (context.params.blockId)
					{
						scope.$broadcast(
							'Change:StructureEditor.setBlockParameter',
							{
								blockId : context.params.blockId,
								property : context.params.property,
								value : context.result
							}
						);
					}
				};

				scope.addMailVariation = function () {
					$http.post(REST.getBaseUrl('Rbs/Mail/AddMailVariation'), {documentId: scope.document.id}).then(function (response){
						var variation = response.data;
						$location.path('Rbs/Mail/Mail/' + variation.properties.id);
					}, function (error){
						NotificationCenter.error(i18n.trans('m.rbs.mail.admin.mail_add_mail_variation_error | ucf'),
							ErrorFormatter.format(error.data));
					});
				};

				scope.$watch('document.template', function (template, old) {
					if (old && scope.document && template !== old && contentSectionInitialized) {
						scope.loadTemplate();
					}
				}, true);

				editorCtrl.init('Rbs_Mail_Mail');
			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.i18n', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', '$location'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMailMail', Editor);
})();