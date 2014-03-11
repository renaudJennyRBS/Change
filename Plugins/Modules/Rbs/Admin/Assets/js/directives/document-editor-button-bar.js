(function ($)
{
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentEditorButtonBar', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.i18n', 'RbsChange.Navigation', 'RbsChange.REST', function ($rootScope, $compile, Dialog, Utils, Actions, Settings, Events, i18n, Navigation, REST)
	{
		return {
			restrict : 'E',
			transclude : true,
			templateUrl : 'Rbs/Admin/js/directives/document-editor-button-bar.twig',
			require : '^rbsDocumentEditorBase',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.disableDelete = true;

				function updateDisableDelete ()
				{
					scope.disableDelete = ! Utils.isDocument(scope.document) || scope.document.isNew() || attrs.disableDelete === 'true';
				}

				attrs.$observe('disableDelete', updateDisableDelete);
				scope.$on(Events.EditorReady, updateDisableDelete);


				scope.confirmReset = function ($event)
				{
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.admin.adminjs.confirm_restore | ucf'),
						i18n.trans('m.rbs.admin.adminjs.confirm_restore_message | ucf'),
						scope,
						{
							'pointedElement' : $($event.target),
							'primaryButtonText' : i18n.trans('m.rbs.admin.adminjs.restore_data_button | ucf'),
							'cssClass' : 'warning'
						}
					).then(function () {
							scope.reset();
						});
				};


				scope.confirmDelete = function ($event)
				{
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.admin.adminjs.confirm_delete | ucf'),
						i18n.trans('m.rbs.admin.adminjs.confirm_delete_message | ucf'),
						scope,
						{
							'pointedElement' : $($event.target),
							'primaryButtonText' : i18n.trans('m.rbs.admin.adminjs.delete_data_button | ucf'),
							'primaryButtonClass' : 'btn-danger',
							'cssClass' : 'danger'
						}
					).then(function () {
							REST['delete'](scope.document).then(function ()
							{
								scope.goBack();
							});
						});
				};


				scope.$on('Change:EditorPreSubmit', function (event, doc, promises)
				{
					if (Utils.hasCorrection(scope.document)) {
						promises.push(Dialog.confirmEmbed(
							element.find('.confirmation-area'),
							i18n.trans('m.rbs.admin.adminjs.confirm_update_correction | ucf'),
							i18n.trans('m.rbs.admin.adminjs.confirm_update_correction_message | ucf'),
							scope,
							{
								'pointedElement' : $(element).find('[data-role=save]').first(),
								'cssClass' : 'warning'
							}
						));
					}
				});

			}

		};
	}]);

})(window.jQuery);