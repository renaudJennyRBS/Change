(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	function editorDirective ($rootScope, $routeParams, $q, $location, EditorManager, Utils, ArrayUtils, i18n, Breadcrumb, REST, Events, Settings, NotificationCenter, MainMenu, SelectSession, Navigation, ErrorFormatter, UrlManager)
	{
		var CORRECTION_CSS_CLASS = 'correction';


		return {

			// We do not need to create a new scope here:
			// Editors are generally alone in the ngView, which creates a new Scope for the loaded template.

			restrict : 'EA',
			priority : -2,

			controller : ['$scope', '$element', function ($scope, $element)
			{
				var	initializedSections = {},
					translation = false,
					wrappingFormScope;

				// Special trick for localized Documents.
				// In the `form.twig` file for localized Documents, there is an `ng-switch` to load:
				// - the classic Editor (editor.twig)
				// - or the one used for translation (editor-translate.twig).
				// Since `ng-switch` creates an *isolated Scope* for the different cases, the Document loaded
				// in this Directive (`$scope.document`) does not exist in the Scope of `form.twig`.
				// The following bits copy the reference of `$scope.document` in this parent Scope.
				wrappingFormScope = angular.element($element.closest('.document-form')).scope();


				/**
				 * Initialize current Editor.
				 *
 				 * @param modelName
				 */
				this.init = function (modelName)
				{
					$scope.document = {};
					if ($scope.editMode === 'translate') {
						translation = true;
					}

					var document, documentId = 0, promise, defered, navCtx;

					if (! angular.isFunction ($scope.initDocument) || ! (promise = $scope.initDocument())) {
						if ($routeParams.hasOwnProperty('id')) {
							documentId = parseInt($routeParams.id, 10);
						}
					}

					if (! promise)
					{
						if (! isNaN(documentId) && documentId > 0)
						{
							promise = REST.resource(modelName, documentId, $routeParams.LCID);
						}
						else
						{
							defered = $q.defer();
							promise = defered.promise;
							if (! document)
							{
								// Check if we are coming back here following a selection process.
								// In that case, we need to get the attached document instead of creating a new one.
								navCtx = Navigation.getActiveContext();
								if (navCtx && navCtx.isSelection() && Utils.isDocument(navCtx.params.document) && navCtx.params.document.model === modelName && navCtx.params.document.id < 0) {
									document = navCtx.params.document;
								}
								else {
									document = REST.newResource(modelName, Settings.get('LCID'));
									if (! isNaN(documentId) && documentId < 0) {
										document.id = documentId;
									}
								}
							}
							defered.resolve(document);
						}
					}

					promise.then(function (doc) {
						prepareEditor(doc);
					});
				};


				this.registerCreateCascade = function (propertyName, model, title)
				{
					return function () {
						Navigation.start({
							selector : true,
							property : propertyName,
							model : model,
							label : title,
							document : $scope.document,
							ngModel : 'document.' + propertyName
						});
						$location.url(UrlManager.getUrl(model, null, 'new'));
					};
				};


				this.registerEditCascade = function (propertyName, title)
				{
					return function (childDocument) {
						Navigation.start({
							property : propertyName,
							label : title,
							document : $scope.document,
							ngModel : 'document.' + propertyName
						});
						$location.url(UrlManager.getUrl(childDocument));
					};
				};


				$scope.saveProgress = {
					"running"   : false,
					"error"     : false,
					"success"   : false,
					"operation" : null
				};

				function saveOperation (op) {
					switch (op) {
						case 'error':
							$scope.saveProgress.running = false;
							$scope.saveProgress.error = true;
							$scope.saveProgress.operation = null;
							$scope.saveProgress.success = ! $scope.saveProgress.error;
							break;

						case 'success':
							$scope.saveProgress.running = false;
							$scope.saveProgress.error = false;
							$scope.saveProgress.operation = null;
							$scope.saveProgress.success = ! $scope.saveProgress.error;
							break;

						default :
							$scope.saveProgress.running = true;
							$scope.saveProgress.error = false;
							$scope.saveProgress.success = false;
							$scope.saveProgress.operation = op;
					}
				}

				/**
				 * Sends the changes to the server, via a POST (creation) or a PUT (update) request.
				 */
				this.submit = function submitFn () {

					// "preSubmitCorrectionCheck" is not meant to be overwritten: it is implemented in the "rbs-form-button-bar"
					// directive to ask the user what to do when the edited document has a correction.
					var promise;
					if (angular.isFunction($scope.preSubmitCorrectionCheck)) {
						promise = $scope.preSubmitCorrectionCheck($scope.document);
					}

					if (promise) {
						saveOperation("Checking Correction");
						promise.then(doSubmit);
					} else {
						doSubmit();
					}

				};


				/**
				 * Checks whether the given `obj` is a Promise or not.
				 * @param obj
				 * @returns {*}
				 */
				function isPromise (obj) {
					return angular.isObject(obj) && angular.isFunction(obj.then);
				}


				/**
				 * Submits the changes to the server.
				 * If there are files to upload, they will be processed before the document is really saved.
				 */
				function doSubmit ()
				{
					var	preSavePromises = [],
						promise;

					// Check for files to upload...
					if ($element) {
						$element.find('rbs-uploader,[rbs-uploader]').each(function () {
							var scope = angular.element($(this)).scope();
							if (angular.isFunction(scope.upload)) {
								if (isPromise(promise = scope.upload())) {
									preSavePromises.push(promise);
								}
							} else {
								throw new Error("Could not find 'upload()' method in rbsUploader's scope.");
							}
						});
					}

					// Call 'preSave' if present in the Scope: it should return null or a Promise.
					if (angular.isFunction($scope.preSave)) {
						if (isPromise(promise = $scope.preSave($scope.document))) {
							preSavePromises.push(promise);
						}
					}

					// Broadcast an event before the document is saved.
					// The "promises" array can be filled in with promises that will be resolved BEFORE
					// the document is saved.
					saveOperation("Processing pre-save Promises");
					$rootScope.$broadcast(
						Events.EditorPreSave,
						{
							"document" : $scope.document,
							"promises" : preSavePromises
						}
					);

					if (preSavePromises.length) {
						$q.all(preSavePromises).then(
							// Success
							executeSaveAction,
							// Error
							function (err) {
								saveOperation("error");
								console.warn("Editor: pre-save Promises error: ", err);
								NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.save_error'), ErrorFormatter.format(err));
							}
						);
					} else {
						executeSaveAction();
					}
				}


				function executeSaveAction () {
					saveOperation("Saving Document");
					REST.save(
						$scope.document,
						Breadcrumb.getCurrentNode(),
						$scope.changes
					).then(saveSuccessHandler, saveErrorHandler);
				}


				function getSectionOfField (fieldName) {
					var result = null;
					angular.forEach($scope._chgMenu, function (entry) {
						if (angular.isArray(entry.fields)) {
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
					$element
						.find('.form-group[property="'+fieldName+'"]').addClass('error')
						.find('.controls :input').first().focus();
					getSectionOfField(fieldName).invalid.push(fieldName);
				}


				function clearInvalidFields () {
					$element.find('.form-group.property.error').removeClass('error');
					angular.forEach($scope._chgMenu, function (entry) {
						ArrayUtils.clear(entry.invalid);
					});
				}
				this.clearInvalidFields = clearInvalidFields;


				// Updates Document instance in the parent '.document-form' that wraps the Editor.
				// (see 'form.twig' files).
				function updateWrappingForm () {
					if (wrappingFormScope.$id !== $scope.$id) {
						wrappingFormScope.document = $scope.document;
					}
				}
				this.updateWrappingForm = updateWrappingForm;


				function saveSuccessHandler (doc)
				{
					var	postSavePromises = [], result;

					if (!doc.META$.tags) {
						doc.META$.tags = [];
					}
					angular.extend(doc.META$.tags, $scope.document.META$.tags);

					clearInvalidFields();

					// Call 'postSave' if present in the Scope: it should return null or a Promise.
					if (angular.isFunction($scope.postSave)) {
						result = $scope.postSave($scope.document);
						if (result) {
							postSavePromises.push(result);
						}
					}

					// Broadcast an event after the document has been successfully saved.
					// The "promises" array can be filled in with promises that will be resolved AFTER
					// the document is saved.
					saveOperation("Processing post-save Promises");
					$rootScope.$broadcast(Events.EditorPostSave, {
						"document" : doc,
						"promises" : postSavePromises
					});

					$scope.original = angular.copy(doc);
					$scope.reset();
					EditorManager.removeLocalCopy(doc);

					function terminateSave () {
						saveOperation("success");
						$rootScope.$broadcast('Change:DocumentSaved', doc);

						// If a Document has been created, we redirect to the URL of the new Document.
						if ($scope._isNew) {
							EditorManager.removeCreationLocalCopy(doc, $scope._isNewId);
							$location.path(doc.url());
						}

						if (angular.isFunction($scope.onReload)) {
							$scope.onReload($scope.document);
						}

						updateWrappingForm();
					}

					if (postSavePromises.length) {
						$q.all(postSavePromises).then(terminateSave);
					} else {
						terminateSave();
					}
				}


				function saveErrorHandler (reason)
				{
					saveOperation("error");
					NotificationCenter.error(
						i18n.trans('m.rbs.admin.adminjs.save_error'),
						ErrorFormatter.format(reason),
						null,
						{
							$propertyInfoProvider : $scope._chgFieldsInfo
						});

					if (angular.isObject(reason) && angular.isObject(reason.data)) {

						if (angular.isObject(reason.data['properties-errors'])) {
							angular.forEach(reason.data['properties-errors'], function (messages, propertyName) {
								markFieldAsInvalid(propertyName, messages);
							});
						}
						else if (reason.code === "INVALID-VALUE-TYPE") {
							var propertyName = reason.data.name;
							markFieldAsInvalid(propertyName, reason.message);
						}
					}
				}


				/**
				 * Prepares the Editor for the edition of the given `doc`.
				 * @param doc
				 */
				function prepareEditor (doc)
				{
					$scope.section = getCurrentSection();
					$scope.document = doc;
					$scope.isReferenceLanguage = ($scope.document.refLCID === $scope.document.LCID);
					$scope.isLocalized = angular.isDefined($scope.document.refLCID);
					$scope.locales = doc.META$.locales;

					$scope.language = $scope.document.LCID || Settings.get('LCID');
					$scope.parentId = $routeParams.parentId || null;

					$scope.$on('Change:Editor:UpdateMenu', function () {
						initMenu();
					});

					$scope._isNew = $scope.document.isNew();
					if ($scope._isNew) {
						$scope._isNewId = $scope.document.id;
						Breadcrumb.setResource(i18n.trans('m.rbs.admin.adminjs.new_element | ucf'));
					}
					else {
						$scope._isNewId = null;
						Breadcrumb.setResource($scope.document);
					}

					var promises = [
						Breadcrumb.ready(),
						REST.modelInfo($scope.document.model)
					];

					if (translation) {
						// Load reference Document
						promises.push(REST.resource($scope.document.model, $scope.document.id, $scope.document.refLCID));
						promises.push(REST.getAvailableLanguages());

						// Add watch on currentLCID to let the user switch between languages in the editor.
						$scope.$watch('currentLCID', function (lcid, old) {
							if (lcid !== old) {
								$location.url($scope.document.translateUrl(lcid));
							}
						});
					}

					// Editor will be considered ready when:
					// - Breadcrumb is ready,
					// - Information about the Document's Model have been loaded.
					$q.all(promises).then(function (promisesResults)
					{
						$scope.modelInfo = promisesResults[1];
						delete $scope.modelInfo.links;

						if (wrappingFormScope.$id !== $scope.$id) {
							wrappingFormScope.modelInfo = $scope.modelInfo;
							wrappingFormScope.document = $scope.document;
						}

						// Apply default values for new documents.
						if ($scope.document.isNew()) {
							applyDefaultValues($scope.document, $scope.modelInfo);
						}

						if (translation) {
							$scope.currentLCID = $scope.document.LCID;
							$scope.refDocument = promisesResults[2];
							$scope.availableLanguages = promisesResults[3].items;

							$scope.availableTranslations = {};
							angular.forEach($scope.availableLanguages, function (l, id) {
								if (id !== $scope.document.refLCID) {
									$scope.availableTranslations[id] = l;
								}
							});
						}

						var loadedPromises = [], p;

						// Call `$scope.onLoad()` if present.
						if (angular.isFunction($scope.onLoad)) {
							p = $scope.onLoad();
							if (p && angular.isFunction(p.then)) {
								loadedPromises.push(p);
							}
						}

						// Trigger `Events.EditorLoaded`.
						$rootScope.$broadcast(Events.EditorLoaded, {
							"scope"    : $scope,
							"document" : $scope.document,
							"promises" : loadedPromises
						});

						// At this point, `$scope.document` has been loaded and may have been tweaked by the `onLoad()`
						// function in the Scope (if present) and by the handlers listening on `Events.EditorReady`.
						// We consider that the document is now ready: we make a copy of it to create the reference
						// document used to check for changes in the editor.
						if (loadedPromises.length) {
							$q.all(loadedPromises).then(initReferenceDocument);
						} else {
							initReferenceDocument();
						}
					});
				}


				/**
				 * Applies the default values defined in the ModelInfo on the given document.
				 * (only called for new documents).
				 *
				 * @param doc
				 * @param modelInfo
				 */
				function applyDefaultValues (doc, modelInfo)
				{
					angular.forEach(modelInfo.properties, function (propObject, name) {
						if (propObject.hasOwnProperty('defaultValue') && propObject.defaultValue !== null) {
							doc[name] = propObject.defaultValue;
						}
					});
				}


				/**
				 * Checks for Correction on the Document and updates the UI to highlight the fields that have
				 * a Correction.
				 */
				function initCorrection () {
					if (Utils.hasCorrection($scope.original)) {
						angular.forEach($scope.original.META$.correction.propertiesNames, function (property) {
							$element.find('[property="' + property + '"]').addClass(CORRECTION_CSS_CLASS);
						});
					}
				}


				function mergeLocalCopy (doc) {
					var localCopy = EditorManager.getLocalCopy(doc);
					if (localCopy)
					{
						angular.extend(doc, localCopy);
						return true;
					}
					return false;
				}


				/**
				 * Creates the reference document (original) from the current document.
				 * Triggers the `Events.EditorReady` event.
				 */
				function initReferenceDocument ()
				{
					$scope.original = angular.copy($scope.document);

					initCorrection();
					initMenu();

					// --- selection process BEGIN

					var navCtxId, navCtx;
					if ($scope.original.id < 0) {
						navCtxId = 'Editor:' + $scope.original.model + ':new';
					} else {
						navCtxId = 'Editor:' + $scope.original.model + ':' + $scope.original.id;
					}

					navCtx = Navigation.getActiveContext();
					if (mergeLocalCopy($scope.document)) {
						// If local copy has been merged after a selection process,
						// do not notify about this merge.
						if (! navCtx || navCtx.id !== navCtxId || ! navCtx.isSelection()) {
							$scope.$emit('Change:Editor:LocalCopyMerged');
						}
					}

					// Define current context and what has to be done when it is resolved.
					Navigation.setContext($scope, navCtxId, $scope.original.label).then(function (context)
					{
						if (context.isSelection() && context.isForDocumentProperty())
						{
							if (angular.isArray($scope.document[context.params.property])) {
								angular.forEach(context.result, function (selectedDoc)
								{
									if (!ArrayUtils.documentInArray(selectedDoc, $scope.document[context.params.property])) {
										$scope.document[context.params.property].push(selectedDoc);
									}
								});
							}
							else {
								$scope.document[context.params.property] = context.result;
							}
						}
						if (angular.isFunction($scope.finalizeNavigationContext)) {
							$scope.finalizeNavigationContext(context);
						}
					});

					// --- selection process END


					$element.css('display', 'block');

					// Call `$scope.onReady()` if present.
					if (angular.isFunction($scope.onReady)) {
						$scope.onReady();
					}

					$rootScope.$broadcast(Events.EditorReady, {
						"scope"    : $scope,
						"document" : $scope.document
					});

					// Watch for section changes to initialize them if needed.
					$scope.$watch('section', function (section, previousSection) {
						if (section !== undefined && section !== null) {
							initSectionOnce(section);
						}

						if (angular.isDefined(previousSection) && previousSection !== section && angular.isFunction($scope.leaveSection)) {
							$scope.leaveSection(previousSection);
						}
						if (angular.isDefined(section) && angular.isFunction($scope.enterSection)) {
							$scope.enterSection(section);
						}
					});

					$scope.routeParams = $routeParams;
					$scope.$watch('routeParams.section', function () {
						$scope.section = getCurrentSection();
					}, true);

					// Computes a list of changes on the fields in each digest cycle.
					$scope.changes = [];
					$scope.$watch('document', function scopeWatchFn ()
					{
						ArrayUtils.clear($scope.changes);
						angular.forEach($scope.document, function (value, name)
						{
							var original = angular.isDefined($scope.original[name]) ? $scope.original[name] : '';
							if (name !== 'META$' && $scope.changes.indexOf(name) === -1)
							{
								if (Utils.isDocument(original) && Utils.isDocument(value)) {
									if (original.id !== value.id) {
										$scope.changes.push(name);
									}
								}
								else {
									if (! angular.equals(original, value)) {
										$scope.changes.push(name);
									}
								}
							}
						});
					}, true);

					$scope.$on('$routeChangeStart', function () {
						if ($scope.changes.length > 0) {
							EditorManager.saveLocalCopy($scope.document);
						}
					});
				}


				/**
				 * Call `scope.initSection(sectionName)` once per section to do some initialization for the given
				 * section.
				 *
				 * Implement the `initSection()` in your Editor's Scope to initialize the section given as argument.
				 * `initSection()` will be called only once for each section, when the user switches to it.
				 *
				 * @param section
				 */
				function initSectionOnce (section) {
					if (! initializedSections[section] && angular.isFunction($scope.initSection)) {
						$scope.initSection(section);
						initializedSections[section] = true;
					}
				}


				/**
				 * Returns the form section currently displayed.
				 * @returns {string}
				 */
				function getCurrentSection () {
					return $routeParams.section || $location.search()['section'] || '';
				}


				/**
				 * Updates the main menu according to the Editor.
				 */
				function initMenu ()
				{
					var menu = [],
						fields = {},
						matches;

					$element.find('fieldset').each(function (index, fieldset) {
						var $fs = jQuery(fieldset),
							fsData = $fs.data(),
							section,
							entry;

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
							'id'       : section || '',
							'label'    : fsData.formSectionLabel,
							'fields'   : [],
							'required' : [],
							'invalid'  : [],
							'corrected': [],
							'hideWhenCreate' : $fs.attr('hide-when-create') === 'true'
						};

						if (section && section.length) {
							entry.url = Utils.makeUrl($location.absUrl(), { 'section': section });
						} else {
							entry.url = Utils.makeUrl($location.absUrl(), { 'section': null });
						}

						menu.push(entry);

						$fs.find('[property]').each(function (index, ctrlGrp) {
							var $ctrlGrp = $(ctrlGrp),
								$lbl = $ctrlGrp.find('label[for]').first(),
								propertyName = $ctrlGrp.attr('property');

							fields[propertyName] = {
								'label'   : $lbl.text(),
								'section' :
								{
									'id'    : section,
									'label' : entry.label
								}
							};

							entry.fields.push({
								'id'    : propertyName,
								'label' : $lbl.text()
							});
							if ($ctrlGrp.hasClass('required')) {
								entry.required.push(propertyName);
							}
							if ($ctrlGrp.hasClass(CORRECTION_CSS_CLASS)) {
								entry.corrected.push(propertyName);
							}
						});

					});

					if (menu.length) {
						$scope._chgFieldsInfo = fields;
						$scope._chgMenu = menu;
						$scope.$emit('Change:UpdateEditorMenu', menu);
					}
				}

			}],


			compile : function (tElement)
			{
				tElement.css('display', 'none');

				/**
				 * Editor's linking function.
				 */
				return function linkFn (scope, element, attrs, CTRL)
				{
					scope.$on(Events.EditorUpdateDocumentProperties, function onUpdateDocumentPropertiesFn (event, properties) {
						angular.extend(scope.document, properties);
						CTRL.submit();
					});


					/**
					 * Reset the form back to the originally loaded document (scope.original).
					 */
					scope.reset = function resetFn () {
						scope.document = angular.copy(scope.original);
						EditorManager.removeLocalCopy(scope.document);
						scope.saveProgress.error = false;
						CTRL.clearInvalidFields();
						CTRL.updateWrappingForm();
						NotificationCenter.clear();
					};


					/**
					 * Tells whether the editor has changes or not.
					 * @return Boolean
					 */
					scope.isUnchanged = function isUnchangedFn () {
						var p, dv, ov;
						for (p in scope.document)
						{
							if (p !== 'META$' && scope.document.hasOwnProperty(p))
							{
								dv = scope.document[p];
								ov = scope.original ? scope.original[p] : undefined;
								// For sub-documents, we only need to check the ID.
								if (Utils.isDocument(dv) && Utils.isDocument(ov)) {
									if (dv.id !== ov.id) {
										return false;
									}
								}
								else {
									if (! angular.equals(dv, ov)) {
										return false;
									}
								}
							}
						}
						return true;
					};


					scope.submit = function () {
						return CTRL.submit();
					};


					scope.canCancelCascade = function canCancelCascadeFn () {

						//FIXME

						return false;//EditorManager.isCascading();
					};


					scope.cancelCascade = function cancelCascadeFn () {

						//FIXME

						EditorManager.uncascade(null); // null -> do NOT call saveCallback.
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


					scope.hasCorrection = function hasCorrectionFn () {
						return Utils.hasCorrection(scope.document);
					};


					scope.onCancel = function onCancelFn () {
						var navCtx = Navigation.getActiveContext();
						if (navCtx) {
							Navigation.reject();
						}
						else {
							Breadcrumb.goParent();
						}
					};

				};
			}

		};

	}

	editorDirective.$inject = [
		'$rootScope', '$routeParams', '$q',
		'$location',
		'RbsChange.EditorManager',
		'RbsChange.Utils',
		'RbsChange.ArrayUtils',
		'RbsChange.i18n',
		'RbsChange.Breadcrumb',
		'RbsChange.REST',
		'RbsChange.Events',
		'RbsChange.Settings',
		'RbsChange.NotificationCenter',
		'RbsChange.MainMenu',
		'RbsChange.SelectSession',
		'RbsChange.Navigation',
		'RbsChange.ErrorFormatter',
		'RbsChange.UrlManager'
	];

	app.directive('rbsDocumentEditor', editorDirective);




	function editorDirectiveTranslate (i18n) {

		return {

			restrict : 'A',
			// This Directive must be compiled:
			// - before the 'rbsDocumentEditor' Directive (priority: -2) to do some template transformations
			// - after the 'rbsDocumentEditor*' Directive, specialized for each Model (default priority: 0).
			priority : -1,

			compile : function (tElement)
			{
				tElement.find('form').not('[preserve-layout]').each(function ()
				{
					var	$form = $(this),
						$properties = $form.children('[property]'),
						$table = $('<table cellpadding="16" width="100%" class="table table-striped"></table>');

					if ($properties.length)
					{
						$table.append(
							'<tr>' +
								'<th width="50%" class="form-inline">' +
								i18n.trans('m.rbs.admin.adminjs.translate_in | ucf | lab') + ' <select style="margin-bottom: 0;" class="form-control" ng-model="currentLCID" ng-options="lcid as locale.label for (lcid, locale) in availableTranslations"></select>' +
								'</th>' +
								'<th style="border-left: 5px solid #0088CC; background: rgba(0,136,255,0.05);">' +
									i18n.trans('m.rbs.admin.adminjs.reference_language | ucf | lab') + ' (= availableLanguages[refDocument.LCID].label =)' +
								'</th>' +
							'</tr>'
						);

						$properties.each(function ()
						{
							var	$prop = $(this),
								$tr = $('<tr></tr>'),
								$lcell = $('<td width="50%" style="vertical-align: top;"></td>'),
								$rcell = $('<td width="50%" style="border-left: 5px solid #0088CC; vertical-align: top; background: rgba(0,136,255,0.05);"></td>'),
								$refProp,
								ngModel,
								propertyName = $prop.attr('property');

							$table.append($tr);
							$tr.append($lcell);
							$tr.append($rcell);
							$lcell.append($prop);

							$refProp = $prop.clone();
							$refProp.attr('property', 'refDocument.' + propertyName);
							$refProp.attr('readonly', 'true');
							ngModel = $refProp.attr('ng-model');
							if (ngModel) {
								$refProp.attr('ng-model', ngModel.replace('document.', 'refDocument.'));
							}
							$rcell.append($refProp);
							$rcell.append('<button type="button" class="btn btn-default btn-sm copy-reference-value" ng-click="document.' + propertyName + '=refDocument.' + propertyName + '"><i class="icon-level-down icon-rotate-90"></i> ' + i18n.trans("m.rbs.admin.adminjs.use_this_value_in", {"lang": "(= availableLanguages[document.LCID].label =)"}) + '</button>');
						});

						$form.prepend($table);
					}

				});
			}

		};

	}

	editorDirectiveTranslate.$inject = ['RbsChange.i18n'];

	app.directive('rbsDocumentEditorTranslate', editorDirectiveTranslate);


	//
	//
	//


	app.factory('RbsChange.EditorManager', ['$compile', '$http', '$timeout', '$q', '$rootScope', '$routeParams', '$location', '$resource', 'RbsChange.Breadcrumb', 'RbsChange.Dialog', 'RbsChange.MainMenu', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'localStorageService', 'RbsChange.Settings', 'RbsChange.UrlManager', 'RbsChange.Navigation', function ($compile, $http, $timeout, $q, $rootScope, $routeParams, $location, $resource, Breadcrumb, Dialog, MainMenu, REST, Utils, ArrayUtils, localStorageService, Settings, UrlManager, Navigation)
	{
		var	localCopyRepo;

		localCopyRepo = localStorageService.get("localCopy");

		if (localCopyRepo) {
			localCopyRepo = JSON.parse(localCopyRepo);
		}

		if (! angular.isObject(localCopyRepo)) {
			localCopyRepo = {};
			commitLocalCopyRepository();
		}

		// Local copy methods.

		function commitLocalCopyRepository () {
			localStorageService.add("localCopy", JSON.stringify(localCopyRepo));
		}

		function makeLocalCopyKey (doc, tempId) {
			var key = doc.model + '-' + (tempId || doc.id);
			if (doc.LCID) {
				key += '-' + doc.LCID;
			}
			return key;
		}

		return {

			// Local copy public API

			'saveLocalCopy' : function (doc) {
				var	key = makeLocalCopyKey(doc);
				doc.META$.localCopy = {
					saveDate : (new Date()).toString(),
					documentVersion : doc.documentVersion,
					modificationDate : doc.modificationDate,
					publicationStatus : doc.publicationStatus
				};
				delete doc.documentVersion;
				delete doc.modificationDate;
				delete doc.publicationStatus;
				localCopyRepo[key] = doc;
				commitLocalCopyRepository();
			},

			'getLocalCopy' : function (doc) {
				var	key = makeLocalCopyKey(doc),
					rawCopy = localCopyRepo.hasOwnProperty(key) ? localCopyRepo[key] : null;
				return rawCopy;
			},

			'removeLocalCopy' : function (doc) {
				var	key = makeLocalCopyKey(doc);
				if (localCopyRepo.hasOwnProperty(key)) {
					delete localCopyRepo[key];
					delete doc.META$.localCopy;
					commitLocalCopyRepository();
				}
			},

			'removeCreationLocalCopy' : function (doc, tempId) {
				var	key = makeLocalCopyKey(doc, tempId);
				if (localCopyRepo.hasOwnProperty(key)) {
					delete localCopyRepo[key];
					delete doc.META$.localCopy;
					commitLocalCopyRepository();
				}
			},

			'removeAllLocalCopies' : function () {
				for (var key in localCopyRepo) {
					if (localCopyRepo.hasOwnProperty(key)) {
						delete localCopyRepo[key];
					}
				}
				localStorageService.remove("temporaryId");
				commitLocalCopyRepository();
			},

			'getLocalCopies' : function () {
				return localCopyRepo;
			}

		};

	}]);


	app.controller('RbsChangeTranslateEditorController', ['$scope', 'RbsChange.MainMenu', function ($scope, MainMenu) {
		$scope.document = {};
		$scope.editMode = 'translate';
		MainMenu.clear();
	}]);


	app.controller('RbsChangeWorkflowController', ['RbsChange.REST', '$scope', '$filter', '$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.Utils', function (REST, $scope, $filter, $routeParams, Breadcrumb, i18n, Utils) {
		$scope.$watch('model', function (model) {
			if (model) {
				REST.resource(model, $routeParams.id, $routeParams.LCID).then(function (doc) {
					$scope.document = doc;

					var	mi = Utils.modelInfo(model),
						location = [
							[
								i18n.trans('m.' + angular.lowercase(mi.vendor + '.' + mi.module) + '.adminjs.module_name | ucf'),
								$filter('rbsURL')(mi.vendor + '_' + mi.module, 'home')
							],
							[
								i18n.trans('m.' + angular.lowercase(mi.vendor + '.' + mi.module + '.adminjs.' + mi.document) + '_list | ucf'),
								$filter('rbsURL')(model, 'list')
							]
						];

					Breadcrumb.setLocation(location);
					Breadcrumb.setResource(doc, 'Workflow');
				});
			}
		});
	}]);


	/**
	 * Default controller for Document-based views.
	 */
	app.controller('RbsChangeSimpleDocumentController', ['RbsChange.REST', '$scope', '$routeParams', function (REST, $scope, $routeParams) {
		REST.resource($routeParams.id).then(function (doc) {
			$scope.document = doc;
		});
	}]);



	// Validators directives.

	var INTEGER_REGEXP = /^\-?\d*$/;
	app.directive('rbsInteger', function () {
		return {
			require : 'ngModel',
			link : function (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (angular.isNumber(viewValue)) {
						return viewValue;
					}
					else if (viewValue == '' || INTEGER_REGEXP.test(viewValue)) {
						// it is valid
						ctrl.$setValidity('integer', true);
						return viewValue;
					}
					else {
						// it is invalid, return undefined (no model update)
						ctrl.$setValidity('integer', false);
						return undefined;
					}
				});
			}
		};
	});


	var FLOAT_REGEXP = /^\-?\d+((\.|\,)\d+)?$/;
	app.directive('rbsSmartFloat', function () {
		return {
			require : 'ngModel',
			link : function (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (angular.isNumber(viewValue)) {
						return viewValue;
					}
					else if (FLOAT_REGEXP.test(viewValue)) {
						ctrl.$setValidity('float', true);
						return parseFloat(viewValue.replace(',', '.'));
					}
					else if (viewValue == '')
					{
						ctrl.$setValidity('float', true);
						return undefined;
					}
					else {
						ctrl.$setValidity('float', false);
						return undefined;
					}
				});
			}
		};
	});


})(window.jQuery);