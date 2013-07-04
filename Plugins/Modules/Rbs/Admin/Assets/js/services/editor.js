(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	function changeEditorServiceFn ($timeout, $rootScope, $location, $q, FormsManager, MainMenu, Utils, ArrayUtils, Actions, Breadcrumb, REST, Events, Settings) {

		// Used internally to store compiled informations in data attributes.
		var FIELDS_DATA_KEY_NAME = 'chg-form-fields';


		/**
		 * Prepares the scope to be a Document editor.
		 *
		 * @param scope
		 * @param element
		 */
		function prepareScope (scope, element) {

			var parentDocument, parentPropertyName;

			// scope.original has been set via the data-bindings of the editor's directive.
			// We copy the original document into the 'document' property of the scope:
			// scope.document is now the working copy, on which the form controls are working on.
			scope.document = angular.copy(scope.original);

			if (scope._isPrepared) {
				return;
			}

			FormsManager.startEditSession(scope.document);
			scope.$on('$destroy', function () {
				FormsManager.stopEditSession();
			});

			scope.$on(Events.EditorUpdateDocumentProperties, function onUpdateDocumentPropertiesFn (event, properties) {
				angular.extend(scope.document, properties);
				scope.submit();
			});

			/**
			 * Reset the form back to the originally loaded document (scope.original).
			 */
			scope.reset = function resetFn () {
				scope.document = angular.copy(scope.original);
			};

			/**
			 * Tells whether the editor has changes or not.
			 * @return Boolean
			 */
			scope.isUnchanged = function isUnchangedFn () {
				return angular.equals(scope.document, scope.original);
			};


			// Computes a list of changes on the fields in each digest cycle.
			scope.changes = [];
			scope.$watch(function scopeWatchFn () {
				ArrayUtils.clear(scope.changes);
				angular.forEach(scope.document, function (value, name) {
					var original = angular.isDefined(scope.original[name]) ? scope.original[name] : '';
					if (name !== 'META$' && ! angular.equals(original, value)) {
						scope.changes.push(name);
					}
				});
			});

			/*
			FIXME: Ask for confirmation when leaving the page but not when switching between sections.
			// Ask confirmation when leaving a form with unsaved changes.
			var locationChangeStartDeregistrationFn = $rootScope.$on('$locationChangeStart', function (event) {
				if (! scope.isUnchanged() && ! window.confirm("Des données n'ont pas été enregistrées. Si vous quittez la page, ces données seront perdues.\nSouhaitez-vous réellement quitter cette page ?")) {
					event.preventDefault();
				}
			});
			// De-register the $locationChangeStart handler when this scope is destroyed.
			scope.$on('$destroy', function () {
				locationChangeStartDeregistrationFn();
			});
			*/

			function getSectionOfField (fieldName) {
				var result = null;
				angular.forEach(scope.menu, function (entry) {
					if (entry.type === 'section') {
						angular.forEach(entry.fields, function (field) {
							if (field.id === fieldName) {
								result = entry;
							}
						});
					}
				});
				return result;
			}

			function markFieldAsInvalid (fieldName, messages) {
				getSectionOfField(fieldName).invalid.push(fieldName);
			}

			function clearInvalidFields () {
				$(element).find('.control-group.property.error').removeClass('error');
				angular.forEach(scope.menu, function (entry) {
					if (entry.type === 'section') {
						ArrayUtils.clear(entry.invalid);
					}
				});
			}

			function saveSuccessHandler (doc) {
				var	hadCorrection = scope.document.hasCorrection(),
					postSavePromises = [];

				scope.original = angular.copy(doc);

				if (doc.hasCorrection() !== hadCorrection) {
					scope.$emit(Events.EditorDocumentUpdated, doc);
				}

				clearInvalidFields();

				// Add the just-saved-document as a child of 'parentDocument' on property 'parentPropertyName'.
				if (parentDocument && parentPropertyName) {
					if (parentDocument === 'auto') {
						parentDocument = Breadcrumb.getCurrentNode();
					}
					if (parentDocument) {
						console.log("Adding current doc as a child of ", parentDocument, " on property ", parentPropertyName);
						parentDocument[parentPropertyName].push(doc);
						postSavePromises.push(REST.save(parentDocument));
					}
				}

				// Broadcast an event before the document is saved.
				// The "promises" array can be filled in with promises that will be resolved AFTER
				// the document is saved.
				$rootScope.$broadcast(Events.EditorPostSave, {
					"document" : scope.document,
					"promises" : postSavePromises
				});

				function terminateSave () {
					if (FormsManager.isCascading()) {
						console.log("isCascading: -> uncascade()");
						FormsManager.uncascade(doc);
					} else {
						$rootScope.$broadcast('Change:DocumentSaved', doc);
						if (angular.isFunction(scope.onSave)) {
							scope.onSave(doc);
						}
					}
				}

				console.log("Post save promises: ", postSavePromises.length);
				if (postSavePromises.length) {
					$q.all(postSavePromises).then(terminateSave);
				} else {
					terminateSave();
				}
			}

			function saveErrorHandler (reason) {
				clearInvalidFields();
				if (angular.isObject(reason) && angular.isObject(reason.data) && angular.isObject(reason.data['properties-errors'])) {
					angular.forEach(reason.data['properties-errors'], function (messages, propertyName) {
						$(element).find('label[for="'+propertyName+'"]').each(function () {
							$(this).closest('.control-group.property').addClass('error');
							$(this).nextAll('.controls').find(':input').first().focus();
						});
						markFieldAsInvalid(propertyName, messages);
					});
				}
			}


			/**
			 * Sends the changes to the server, via a POST (creation) or a PUT (update) request.
			 */
			scope.submit = function submitFn () {

				function executeSaveAction () {
					REST.save(
						scope.document,
						Breadcrumb.getCurrentNode(),
						scope.changes
					).then(saveSuccessHandler, saveErrorHandler);
				}

				/**
				 * Submits the changes to the server.
				 * If there are files to upload, they will be processed before the document is really saved.
				 */
				function doSubmit () {
					var	preSavePromises = [],
						promise;

					// Check for files to upload...
					if (element) {
						element.find('image-uploader,[image-uploader],.image-uploader').each(function () {
							var scope = angular.element($(this)).scope();
							if (angular.isFunction(scope.upload)) {
								promise = scope.upload();
								if (promise !== null) {
									preSavePromises.push(promise);
								}
							} else {
								throw new Error("Could not find 'upload()' method in imageUploader's scope.");
							}
						});
					}

					// Broadcast an event before the document is saved.
					// The "promises" array can be filled in with promises that will be resolved BEFORE
					// the document is saved.
					$rootScope.$broadcast(Events.EditorPreSave, {
						"document" : scope.document,
						"promises" : preSavePromises
					});

					if (preSavePromises.length) {
						console.log("PreSavePromises: ", preSavePromises.length);
						$q.all(preSavePromises).then(executeSaveAction);
					} else {
						console.log("No files to upload.");
						executeSaveAction();
					}
				}

				// "preSubmitCorrectionCheck" is not meant to be overwritten: it is implemented in the "form-button-bar"
				// directive to ask the user what to do when the edited document has a correction.
				var promise;
				if (angular.isFunction(scope.preSubmitCorrectionCheck)) {
					promise = scope.preSubmitCorrectionCheck(scope.document);
				}

				if (promise) {
					promise.then(doSubmit);
				} else {
					doSubmit();
				}
			};


			scope.canCancelCascade = function canCancelCascadeFn () {
				return FormsManager.isCascading();
			};

			scope.cancelCascade = function cancelCascadeFn () {
				if (FormsManager.isCascading()) {
					FormsManager.uncascade(null); // null -> do NOT call saveCallback.
				}
			};

			/**
			 * Cascade a new Editor and initialize it for creation.
			 * @param doc
			 * @param collapsedTitle
			 * @param callback
			 */
			scope.cascadeCreate = function (doc, collapsedTitle, callback) {
				if (angular.isString(doc)) {
					doc = REST.newResource(doc, scope.document.LCID || Settings.get('language'));
				}
				FormsManager.cascadeEditor(doc, collapsedTitle || scope.document.label, callback);
			};

			/**
			 * Edit the given doc in a cascaded Editor.
			 * @param doc
			 * @param collapsedTitle
			 * @param callback
			 */
			scope.cascadeEdit = function (doc, collapsedTitle, callback) {
				FormsManager.cascadeEditor(doc, collapsedTitle || scope.document.label, callback);
			};

			/**
			 * Duplicate then edit the given doc in a cascaded Editor.
			 * @param doc
			 * @param collapsedTitle
			 * @param callback
			 */
			scope.cascadeDuplicate = function (doc, collapsedTitle, callback) {
				FormsManager.cascadeEditor(Utils.duplicateDocument(doc), collapsedTitle || scope.document.label, callback);
			};

			scope.canGoBack = function canGoBackFn () {
				return scope.isUnchanged();
			};

			scope.goBack = function goBackFn () {
				if (angular.isFunction(scope.onCancel)) {
					scope.onCancel();
				}
			};

			scope.isNew = function isNewFn () {
				return Utils.isNew(scope.original);
			};

			scope.hasStatus = function hasStatusFn (status) {
				if (!scope.document) {
					return false;
				}
				var args = [scope.document];
				ArrayUtils.append(args, arguments);
				return Utils.hasStatus.apply(Utils, args);
			};

			scope.hasCorrectionOnProperty = function hasCorrectionOnPropertyFn (property) {
				return scope.document &&
					scope.document.META$ &&
					scope.document.META$.correction &&
					ArrayUtils.inArray(property, scope.document.META$.correction.propertiesNames) !== -1;
			};

			scope.hasCorrection = function hasCorrectionFn () {
				return Utils.hasCorrection(scope.document);
			};

			scope.isCascading = function isCascadingFn () {
				return FormsManager.isCascading();
			};

			scope.onCancel = function onCancelFn () {
				Breadcrumb.goParent();
			};

			scope.saveAsChildOf = function (parentDoc, propertyName) {
				parentDocument = parentDoc;
				parentPropertyName = propertyName;
			};

			scope._isPrepared = true;

		}


		/**
		 * Parses the editor and finds sections used to automatically build the MainMenu on the left.
		 */
		function compileSectionsAndFields (tElement) {
			var menu = [],
			    fields = {},
			    groups = {},
			    matches;

			tElement.data(FIELDS_DATA_KEY_NAME, fields);

			tElement.find('fieldset').each(function (index, fieldset) {
				var $fs = jQuery(fieldset),
				    fsData = $fs.data(),
				    section,
				    entry;

				if (angular.isDefined(fsData.formSectionGroup) && angular.isUndefined(groups[fsData.formSectionGroup])) {
					groups[fsData.formSectionGroup] = true;
					menu.push({
						'label': fsData.formSectionGroup,
						'type' : 'group'
					});
				}

				section = fsData.ngShow || $fs.attr('ng-show') || $fs.attr('x-ng-show');
				if (section) {
					matches = (/section\s*==\s*'([\w\d\\-]*)'/).exec(section);
					if (matches.length !== 2) {
						console.error("Could not find section ID on fieldset.");
					}
					section = matches[1];
				} else {
					section = fsData.ngSwitchWhen || $fs.attr('ng-switch-when') || $fs.attr('x-ng-switch-when');
				}

				entry = {
					'type'     : 'section',
					'id'       : section || '',
					'label'    : fsData.formSectionLabel,
					'fields'   : [],
					'required' : [],
					'invalid'  : [],
					'corrected': [],
					'hideWhenCreate' : $fs.attr('hide-when-create') === 'true'
				};

				if ( ! FormsManager.isCascading() ) {
					if (section && section.length) {
						entry.url = Utils.makeUrl($location.absUrl(), { 'section': section });
					} else {
						entry.url = Utils.makeUrl($location.absUrl(), { 'section': null });
					}
				}

				menu.push(entry);

				// TODO Fields may be outside of a "div.control-group"
				$fs.find('div.control-group.property').each(function (index, ctrlGrp) {
					var $ctrlGrp = $(ctrlGrp),
					    $lbl = $ctrlGrp.find('label[for]').first();

					fields[$lbl.attr('for')] = {
						'label'   : $lbl.text(),
						'section' :
						{
							'id'    : section,
							'label' : entry.label
						}
					};

					entry.fields.push({
						'id'    : $lbl.attr('for'),
						'label' : $lbl.text()
					});
					if ($ctrlGrp.hasClass('required')) {
						entry.required.push($lbl.attr('for'));
					}
					if ($ctrlGrp.hasClass('success')) { // TODO Change class name?
						entry.corrected.push($lbl.attr('for'));
					}
				});

			});

			return menu;
		}


		this.initScope = function scopeWatchOriginal (scope, element, callback) {
			// Wait for the document to be loaded...
			scope.$watch('original', function () {
				if (Utils.isDocument(scope.original)) {
					prepareScope(scope, element);
					if (element) {
						$timeout(function () {
							scope.menu = compileSectionsAndFields(element);
							MainMenu.build(scope);
						});
					}

					if (scope.original.model !== 'Rbs_Tag_Tag') {
						scope.original.getTags();
					}

					if (angular.isFunction(callback)) {
						// This callback can be used to initialize defaut values in the editor.
						// It will be called only when the Breadcrumb is fully loaded.
						Breadcrumb.ready().then(function () {
							callback.apply(scope);
							$rootScope.$broadcast(Events.EditorReady, {
								"scope"    : scope,
								"document" : scope.document
							});
							// Since this callback (or the event handlers) could have modified 'scope.document'
							// to initialize some default values, we need to re-synchronize
							// 'scope.original' with 'scope.document'.
							scope.original = angular.copy(scope.document);
						});
					}
				}
			}, true);
		};

	}

	changeEditorServiceFn.$inject = [
		'$timeout', '$rootScope', '$location', '$q',
		'RbsChange.FormsManager',
		'RbsChange.MainMenu',
		'RbsChange.Utils',
		'RbsChange.ArrayUtils',
		'RbsChange.Actions',
		'RbsChange.Breadcrumb',
		'RbsChange.REST',
		'RbsChange.Events',
		'RbsChange.Settings'
	];

	app.service('RbsChange.Editor', changeEditorServiceFn);

})(window.jQuery);