(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsFormButtonBar', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.i18n', 'RbsChange.Navigation', 'RbsChange.REST', function ($rootScope, $compile, Dialog, Utils, Actions, Settings, Events, i18n, Navigation, REST) {

		return {
			restrict: 'E',
			transclude: true,

			templateUrl : 'Rbs/Admin/js/directives/form-button-bar.twig',
			require : '^rbsDocumentEditor',
			scope : true,

			link : function (scope, element, attrs, editorCtrl) {

				// Initialize the zone before the buttons with a content that comes from the rest of the world :)
				var shouldLoadContents = true;
				scope.$on(Events.EditorReady, function (event, args) {
					if (shouldLoadContents) {

						shouldLoadContents = false;
						var contents = [];
						$rootScope.$broadcast(Events.EditorFormButtonBarContents, {
							'contents' : contents,
							'document' : args.document
						});
						if (contents.length) {
							element.find('[data-role="preContents"]').empty().append($compile(contents.join(''))(scope));
						}
					}

					scope.navigationContext = Navigation.getCurrentContext();

					scope.rejectNavigationContext = function ()
					{
						Navigation.setSelectionContextValue();
					};
				});


				scope.$on('$locationChangeSuccess', function (event) {
					scope.navigationContext = Navigation.getCurrentContext();
				});


				scope.confirmReset = function ($event) {
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.admin.adminjs.confirm_restore | ucf'),
						i18n.trans('m.rbs.admin.adminjs.confirm_restore_message | ucf'),
						scope,
						{
							'pointedElement': $($event.target),
							'primaryButtonText': i18n.trans('m.rbs.admin.adminjs.restore_data_button | ucf'),
							'cssClass': 'warning'
						}
					).then(function () {
							scope.reset();
						});
				};


				scope.confirmDelete = function ($event) {
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.admin.adminjs.confirm_delete | ucf'),
						i18n.trans('m.rbs.admin.adminjs.confirm_delete_message | ucf'),
						scope,
						{
							'pointedElement': $($event.target),
							'primaryButtonText': i18n.trans('m.rbs.admin.adminjs.delete_data_button | ucf'),
							'primaryButtonClass' : 'btn-danger',
							'cssClass': 'danger'
						}
					).then(function () {
							REST['delete'](scope.document).then(function ()
							{
								if (scope.navigationContext) {
									scope.rejectNavigationContext();
								} else {
									scope.goBack();
								}
							});
						});
				};


				scope.preSubmitCorrectionCheck = function (doc) {
					if (Utils.hasCorrection(doc)) {
						return Dialog.confirmEmbed(
							element.find('.confirmation-area'),
							i18n.trans('m.rbs.admin.adminjs.confirm_update_correction | ucf'),
							i18n.trans('m.rbs.admin.adminjs.confirm_update_correction_message | ucf'),
							scope,
							{
								'pointedElement' : $(element).find('[data-role=save]').first()
							}
						);
					}
					return null;
				};

				scope.publish = function ($event) {
					var action;

					// Determine which action should be called.
					if (angular.isDefined(scope.document.META$.actions.startValidation)) {
						action = 'startValidation';
					} else if (angular.isDefined(scope.document.META$.actions.startPublication)) {
						action = 'startPublication';
					} else {
						console.error("Could not publish document: no action is defined for this. Available actions are: ", scope.document.META$.actions);
						return;
					}

					Actions.execute(
						action,
						{
							'$docs'   : [ scope.document ],
							'$target' : $($event.target),
							'$scope'  : scope
						}
					).then(function (data) {
							scope.$emit(Events.EditorDocumentUpdated, data[0]);
						});
				};

			}

		};
	}]);

})(window.jQuery);