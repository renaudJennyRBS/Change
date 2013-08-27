(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('formButtonBar', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events) {

		return {
			restrict: 'E',

			templateUrl: 'Rbs/Admin/js/directives/form-button-bar.twig',
			require : '^rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {

				scope.actionAfterSave = Settings.get('editorActionAfterSave', 'list');

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
				});


				scope.confirmApplyCorrection = function ($event) {
					Actions.execute(
						'applyCorrection',
						{
							'$docs'       : [ scope.document ],
							'$embedDialog': element.find('.confirmation-area'),
							'$target'     : $($event.target),
							'$scope'      : scope
						}
					);
				};


				scope.confirmReset = function ($event) {
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						"Rétablir ?",
						"Vous êtes sur le point de rétablir les dernières données qui ont été enregistrées dans ce formulaire.",
						scope,
						{
							'pointedElement': $($event.target),
							'primaryButtonText': "rétablir les données"
						}
					).then(function () {
							scope.reset();
						});
				};


				scope.preSubmitCorrectionCheck = function (doc) {
					if (Utils.hasCorrection(doc)) {
						return Dialog.confirmEmbed(
							element.find('.confirmation-area'),
							"Mettre à jour la correction ?",
							"Une <strong>correction</strong> est en cours pour ce document. L'enregistrement mettra à jour la correction et le document ne sera modifié qu'après application de la correction.",
							scope,
							{
								'pointedElement' : $(element).find('[data-role=save]').first()
							}
						);
					}
					return null;
				};


				scope.$on('Change:DocumentSaved', function (event, doc) {
					Settings.set('editorActionAfterSave', scope.actionAfterSave, true);
					if (scope.actionAfterSave === 'list') {
						Breadcrumb.goParent();
					}
				});


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