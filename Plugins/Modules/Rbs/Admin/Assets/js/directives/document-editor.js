(function ($) {

	"use strict";

	var app = angular.module('RbsChange');
	var CORRECTION_CSS_CLASS = 'correction';


	/**
	 * Checks whether the given `obj` is a Promise or not.
	 * @param obj
	 * @returns {*}
	 */
	function isPromise (obj) {
		return angular.isObject(obj) && angular.isFunction(obj.then);
	}


	function editorDirective($rootScope, $routeParams, $q, $location, EditorManager, Utils, ArrayUtils, i18n, REST,
		Events, Settings, NotificationCenter, Navigation, ErrorFormatter, UrlManager, Breadcrumb)
	{

		return {

			restrict : 'A',
			scope : true,

			controller : ['$scope', '$element', function ($scope, $element)
			{
				var	initializedSections = {},
					hasContextData = false,
					menuEntries = [],
					createNewDocumentId,
					modelInfoPromise;


				//-----------------------------------------------//
				//                                               //
				// Scope methods and properties                  //
				//                                               //
				//-----------------------------------------------//


				$scope.document = {};
				$scope.changes = [];

				$scope.isUnchanged = isUnchanged;
				$scope.reset = reset;
				$scope.submit = submit;
				$scope.hasStatus = hasStatus;

				$scope.navigationContext = Navigation.getCurrentContext();


				// Load Model's information.
				modelInfoPromise = REST.modelInfo(getDocumentModelName());
				modelInfoPromise.then(function (modelInfo)
				{
					$scope.modelInfo = modelInfo;
					delete $scope.modelInfo.links;
				});


				$scope.canGoBack = function ()
				{
					return $scope.isUnchanged();
				};


				$scope.goBack = function (event)
				{
					if ($scope.navigationContext) {
						$scope.rejectNavigationContext();
					} else {
						if (angular.isFunction($scope.onCancel)) {
							$scope.onCancel(event);
						}
					}
				};


				$scope.hasCorrection = function ()
				{
					return Utils.hasCorrection($scope.document);
				};


				$scope.onCancel = function ()
				{
					Breadcrumb.goParent();
				};


				$scope.rejectNavigationContext = function ()
				{
					Navigation.setSelectionContextValue();
				};


				$scope.$on('$locationChangeSuccess', function (event)
				{
					$scope.navigationContext = Navigation.getCurrentContext();
				});


				//-----------------------------------------------//
				//                                               //
				// Context and Navigation methods                //
				//                                               //
				//-----------------------------------------------//


				function getContextData ()
				{
					var currentContext = $scope.navigationContext,
						data;

					if (currentContext) {
						data = currentContext.savedData('editor_' + getDocumentModelName());
						if (angular.isObject(data) && data.hasOwnProperty('document'))
						{
							hasContextData = true;
							return data;
						}
					}
					return null;
				}


				function prepareContext ()
				{
					var document,
						contextData = getContextData(),
						currentContext,
						cascadeKey,
						splitKey;

					if (contextData)
					{
						currentContext = Navigation.getCurrentContext();
						document = contextData.document;
						if (angular.isFunction($scope.onRestoreContext)) {
							$scope.onRestoreContext(currentContext);
						}
						cascadeKey = currentContext.valueKey();
						if (cascadeKey)
						{
							splitKey = cascadeKey.split('.');
							if (splitKey.length === 2 && splitKey[0] === 'editor')
							{
								var propertyName = splitKey[1];
								var v = currentContext.value();
								if (angular.isArray(v)) {
									if (!angular.isArray(document[propertyName]))
									{
										document[propertyName] = [];
									}
									angular.forEach(v, function(doc) {
										document[propertyName].push(doc);
									});
								}
								else
								{
									document[propertyName] = v;
								}
							}
						}

						if (document.isNew()) {
							prepareCreation(document);
						} else {
							prepareEdition(document);
						}

						Navigation.popContext(currentContext);
						$scope.navigationContext = Navigation.getCurrentContext();

						return document;
					}
					return null;
				}


				function registerCreateCascade (propertyName, model, title)
				{
					return function() {
						var property = $scope.modelInfo.properties[propertyName];
						var params = {
							selector : true,
							property : propertyName,
							propertyType : property.type,
							model : model,
							label : title,
							document : $scope.document,
							ngModel : 'document.' + propertyName
						};
						var tagerURL = UrlManager.getUrl(model, null, 'new');
						Navigation.startSelectionContext(tagerURL, 'editor.' + propertyName, params);
					};
				}


				function registerEditCascade (propertyName, title)
				{
					return function(doc) {
						var params = {
							property : propertyName,
							label : title,
							document : $scope.document,
							ngModel : 'document.' + propertyName
						};
						var tagerURL = UrlManager.getUrl(doc);
						Navigation.startSelectionContext(tagerURL, null, params);
					};
				}







				//-----------------------------------------------//
				//                                               //
				// Save process                                  //
				//                                               //
				//-----------------------------------------------//


				$scope.saveProgress = {
					"running"   : false,
					"error"     : false,
					"success"   : false,
					"operation" : null
				};


				function saveOperation (op)
				{
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
				 * @public
				 */
				function submit ()
				{
					var promises = [];
					$scope.$broadcast('Change:EditorPreSubmit', $scope.document, promises);

					if (promises.length) {
						saveOperation("Checking Correction");
						$q.all(promises).then(doSubmit, cancelSubmit);
					} else {
						doSubmit();
					}
				}


				function cancelSubmit ()
				{
					saveOperation('success');
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


				/**
				 * Sends the POST/PUT request to the server and dispatches response to the callbacks.
				 */
				function executeSaveAction ()
				{
					saveOperation("Saving Document");
					var pList = $scope.changes;
					pList.push('documentVersion');
					REST.save($scope.document, pList).then(saveSuccessHandler, saveErrorHandler);
				}


				function saveSuccessHandler (doc)
				{
					var	postSavePromises = [],
						result;

					if (! doc.META$.tags) {
						doc.META$.tags = [];
					}
					angular.extend(doc.META$.tags, $scope.document.META$.tags);

					clearInvalidFields();

					// Call 'postSave' if present in the Scope: it should return null or a Promise.
					if (angular.isFunction($scope.postSave)) {
						result = $scope.postSave($scope.document);
						if (isPromise(result)) {
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

					function terminateSave ()
					{
						saveOperation("success");
						$rootScope.$broadcast('Change:DocumentSaved', doc);

						if (createNewDocumentId)
						{
							EditorManager.removeCreationLocalCopy(doc, createNewDocumentId);

							var context = Navigation.getCurrentContext(),
								edtKey,
								model,
								propertyType;

							if (context) {
								edtKey = context.valueKey();
								if (edtKey && edtKey.split('.')[0] === 'editor')
								{
									model = context.param('model');
									propertyType = context.param('propertyType');
									if (model === doc.model) {
										if (propertyType === 'DocumentArray') {
											context.value([doc]);
										} else if (propertyType === 'Document') {
											context.value(doc);
										} else if (propertyType === 'DocumentId') {
											context.value(doc.id);
										}
									}
								}
							}
						}

						if (angular.isFunction($scope.terminateSave)) {
							$scope.terminateSave(doc);
						}
						else if (angular.isFunction($scope.onReload)) {
							$scope.onReload($scope.document);
						}
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

					if (angular.isObject(reason) && angular.isObject(reason.data))
					{
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


				//-----------------------------------------------//
				//                                               //
				// Menu, sections and fields management          //
				//                                               //
				//-----------------------------------------------//


				/**
				 * Returns the section entry for the given named field.
				 * @param fieldName
				 * @returns {*}
				 */
				function getSectionOfField (fieldName)
				{
					var result = null;
					angular.forEach(menuEntries, function (entry) {
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


				/**
				 * Marks the given named field as invalid in this editor.
				 * @param fieldName
				 */
				function markFieldAsInvalid (fieldName)
				{
					$element.find('.form-group[property="' + fieldName + '"]').addClass('error')
						.find('.controls :input').first().focus();
					getSectionOfField(fieldName).invalid.push(fieldName);
				}


				/**
				 * Removes all invalid fields markers.
				 */
				function clearInvalidFields ()
				{
					$element.find('.form-group.property.error').removeClass('error');
					angular.forEach(menuEntries, function (entry) {
						ArrayUtils.clear(entry.invalid);
					});
				}


				/**
				 * Call `scope.initSection(sectionName)` once per section to do some initialization for the given
				 * section.
				 *
				 * Implement the `initSection()` in your Editor's Scope to initialize the section given as argument.
				 * `initSection()` will be called only once for each section, when the user first switches to it.
				 *
				 * @param section
				 */
				function initSectionOnce (section)
				{
					if (! initializedSections[section] && angular.isFunction($scope.initSection)) {
						$scope.initSection(section);
						initializedSections[section] = true;
					}
				}


				/**
				 * Returns the form section currently displayed.
				 * @returns {string}
				 */
				function getCurrentSection ()
				{
					return $routeParams.section || $location.search()['section'] || '';
				}


				$scope.$on('$routeUpdate', function ()
				{
					var s = getCurrentSection();
					if (s !== $scope.section) {
						$scope.section = s;
						$rootScope.$broadcast('Change:EditorSectionChanged', $scope.section);
					}
				});


				// Watch for section changes to initialize them if needed.
				$scope.$watch('section', function (section, previousSection)
				{
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


				/**
				 * Adds an entry in the menu structure for this editor.
				 * @param entry
				 */
				function addMenuEntry (entry)
				{
					menuEntries.push(entry);
					$scope.$emit('Change:UpdateEditorMenu', menuEntries);
				}


				/**
				 * Returns currently registered menu entries for this editor.
				 * @returns {Array}
				 */
				function getMenuEntries ()
				{
					return menuEntries;
				}


				//-----------------------------------------------//
				//                                               //
				// Document management                           //
				//                                               //
				//-----------------------------------------------//


				/**
				 * Prepares the Editor for the edition of the given `doc`.
				 * @param doc
				 */
				function prepareEdition (doc)
				{
					$scope.document = doc;
					$scope.isReferenceLanguage = ($scope.document.refLCID === $scope.document.LCID);
					$scope.isLocalized = angular.isDefined($scope.document.refLCID);
					$scope.locales = doc.META$.locales;

					$scope.language = $scope.document.LCID || Settings.get('LCID');
					$scope.parentId = $routeParams.parentId || null;

					modelInfoPromise.then(function ()
					{
						var loadedPromises = [],
							p;

						// Call `$scope.onLoad()` if present.
						if (angular.isFunction($scope.onLoad)) {
							p = $scope.onLoad();
							if (isPromise(p)) {
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
						// function in the Scope (if present) and by the handlers listening on `Events.EditorLoaded`.
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
				 * Prepares the Editor for the edition of the given `doc`.
				 * @param doc
				 */
				function prepareCreation (doc)
				{
					$scope.document = doc;
					$scope.isReferenceLanguage = ($scope.document.refLCID === $scope.document.LCID);
					$scope.isLocalized = angular.isDefined($scope.document.refLCID);
					$scope.locales = doc.META$.locales;

					$scope.language = $scope.document.LCID || Settings.get('LCID');
					$scope.parentId = $routeParams.parentId || null;

					createNewDocumentId = $scope.document.id;

					modelInfoPromise.then(function ()
					{
						// Apply default values for new documents.
						if (! hasContextData) {
							applyDefaultValues($scope.document, $scope.modelInfo);
						}

						var loadedPromises = [],
							p;

						// Call `$scope.onLoad()` if present.
						if (angular.isFunction($scope.onLoad)) {
							p = $scope.onLoad();
							if (isPromise(p)) {
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
						// function in the Scope (if present) and by the handlers listening on `Events.EditorLoaded`.
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
				function initCorrection ()
				{
					if (Utils.hasCorrection($scope.original)) {
						angular.forEach($scope.original.META$.correction.propertiesNames, function (property) {
							$element.find('[property="' + property + '"]').addClass(CORRECTION_CSS_CLASS);
						});
					}
				}


				/**
				 * Creates the reference document (original) from the current document.
				 * Triggers the `Events.EditorReady` event.
				 */
				function initReferenceDocument ()
				{
					var contextData = getContextData();
					if (contextData) {
						$scope.original = contextData.original;
						EditorManager.removeLocalCopy($scope.document);
					} else {
						$scope.original = angular.copy($scope.document);
					}

					initCorrection();

					// Call `$scope.onReady()` if present.
					if (angular.isFunction($scope.onReady)) {
						$scope.onReady();
					}

					$rootScope.$broadcast(Events.EditorReady, {
						"scope"    : $scope,
						"document" : $scope.document
					});

					// Computes a list of changes on the fields in each digest cycle.
					$scope.$watchCollection('document', function editorDocumentWatch ()
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
								else if (! angular.equals(original, value)) {
									$scope.changes.push(name);
								}
							}
						});
					});
				}


				/**
				 * Reset the form back to the originally loaded document (scope.original).
				 */
				function reset ()
				{
					$scope.document = angular.copy($scope.original);
					EditorManager.removeLocalCopy($scope.document);
					$scope.saveProgress.error = false;
					clearInvalidFields();
					NotificationCenter.clear();
				}


				/**
				 * Tells whether the editor has changes or not.
				 * @return Boolean
				 */
				function isUnchanged ()
				{
					var p, dv, ov;
					for (p in $scope.document)
					{
						if (p !== 'META$' && $scope.document.hasOwnProperty(p))
						{
							dv = $scope.document[p];
							ov = $scope.original ? $scope.original[p] : undefined;
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
				}


				/**
				 * Tells whether the current Document has the given publication status or not.
				 * @param status
				 * @returns {*}
				 */
				function hasStatus (status)
				{
					if (! $scope.document) {
						return false;
					}
					var args = [$scope.document];
					ArrayUtils.append(args, arguments);
					return Utils.hasStatus.apply(Utils, args);
				}


				function getDocumentModelName ()
				{
					return $element.attr('model');
				}



				$scope.$on('$routeChangeStart', function () {
					if (! isUnchanged() ) {
						EditorManager.saveLocalCopy($scope.document);
					}
				});


				//-----------------------------------------------//
				//                                               //
				// Public API of Editor Controller               //
				//                                               //
				// These methods may be called from the other    //
				// directives bound to this editor.              //
				//                                               //
				//-----------------------------------------------//


				return {
					prepareContext : prepareContext,
					registerCreateCascade : registerCreateCascade,
					registerEditCascade : registerEditCascade,

					submit : submit,
					prepareCreation : prepareCreation,
					prepareEdition : prepareEdition,

					clearInvalidFields : clearInvalidFields,
					getCurrentSection : getCurrentSection,
					addMenuEntry : addMenuEntry,
					getMenuEntries : getMenuEntries,

					getDocumentModelName : getDocumentModelName
				};

			}],


			link : function linkFn (scope, element, attrs, ctrl)
			{
				scope.$on(Events.EditorUpdateDocumentProperties, function onUpdateDocumentPropertiesFn (event, properties)
				{
					angular.extend(scope.document, properties);
					ctrl.submit();
				});

				scope.$on('Navigation.saveContext', function (event, args)
				{
					var label = scope.document.label || i18n.trans('m.rbs.admin.adminjs.new_element | ucf');
					args.context.label(label);
					var data = {document: scope.document, original: scope.original};
					args.context.savedData('editor_' + scope.document.model, data);
					if (angular.isFunction(scope.onSaveContext)) {
						scope.onSaveContext(args.context);
					}
				});

				scope.section = ctrl.getCurrentSection();
			}
		};
	}

	editorDirective.$inject = [
		'$rootScope', '$routeParams', '$q',
		'$location', 'RbsChange.EditorManager', 'RbsChange.Utils',
		'RbsChange.ArrayUtils', 'RbsChange.i18n', 'RbsChange.REST',
		'RbsChange.Events', 'RbsChange.Settings', 'RbsChange.NotificationCenter',
		'RbsChange.Navigation', 'RbsChange.ErrorFormatter', 'RbsChange.UrlManager', 'RbsChange.Breadcrumb'
	];

	app.directive('rbsDocumentEditorBase', editorDirective);



	/**
	 * Directive used to create a new Document.
	 */
	app.directive('rbsDocumentEditorNew', ['RbsChange.REST', 'RbsChange.Settings', '$location', function (REST, Settings, $location)
	{
		return {
			restrict : 'A',
			require : '^rbsDocumentEditorBase',
			scope : false,
			priority : 900,

			compile : function (tElement)
			{
				tElement.attr('name', 'form');
				tElement.addClass('form-horizontal');

				return function rbsDocumentEditorNewLink (scope, iElement, iAttrs, ctrl)
				{
					// First, we check if there is a Navigation Context available for this editor.

					// If there is one, it will be resolved by the prepareContext() method, and we don't need
					// to do anything here.
					if (! ctrl.prepareContext())
					{
						// No navigation Context:
						var promise;

						// Is there a 'initDocument()' method in the scope?
						// TODO remove initDocument?
						if (angular.isFunction(scope.initDocument)) {
							promise = scope.initDocument();
						}

						if (isPromise(promise)) {
							promise.then(function (doc) {
								ctrl.prepareCreation(doc);
							});
						} else {
							ctrl.prepareCreation(REST.newResource(ctrl.getDocumentModelName(), Settings.get('LCID')));
						}
					}


					// Function called when a creation has been done.
					scope.terminateSave = function (doc)
					{
						console.log("terminateSave: redirect to: ", doc, doc.url());
						$location.path(doc.url());
					};

				};
			}
		};
	}]);



	/**
	 * Directive used to edit an existing Document.
	 */
	app.directive('rbsDocumentEditorEdit', ['$filter', '$routeParams', '$location', 'RbsChange.NotificationCenter', 'RbsChange.REST', 'RbsChange.i18n', function ($filter, $routeParams, $location, NotificationCenter, REST, i18n)
	{
		return {
			restrict : 'A',
			require : '^rbsDocumentEditorBase',
			scope : false,
			priority : 900,

			compile : function (tElement)
			{
				tElement.attr('name', 'form');
				tElement.addClass('form-horizontal');

				return function rbsDocumentEditorEditLink (scope, iElement, iAttrs, ctrl)
				{
					// First, we check if there is a Navigation Context available for this editor.

					// If there is one, it will be resolved by the prepareContext() method, and we don't need
					// to do anything here.
					if (! ctrl.prepareContext())
					{
						// No navigation Context:
						// Load Document from the server with id and LCID coming from the route's params.
						REST.resource(ctrl.getDocumentModelName(), parseInt($routeParams.id, 10), $routeParams.LCID).then(
							// Success
							function (doc)
							{
								// Check the model name of the loaded Document:
								// It it's not the same as the expected one ("model" attribute), the user is redirected
								// to the right editor for the loaded Document.
								if (doc.model !== ctrl.getDocumentModelName()) {
									$location.path($filter('rbsURL')(doc, 'edit'));
								}
								else {
									ctrl.prepareEdition(doc);
								}
							},
							// Error
							function ()
							{
								NotificationCenter.error(
									i18n.trans('m.rbs.admin.admin.document_does_not_exist | ucf') + ' ' +
									'<a href="' + $filter('rbsURL')(ctrl.getDocumentModelName(), 'new') + '">' +
									i18n.trans('m.rbs.admin.admin.create | ucf | etc') +
									'</a>'
								);
							}
						);
					}
				};
			}
		};
	}]);





	app.directive('rbsEditorSection', ['RbsChange.Utils', '$location', function (Utils, $location)
	{
		return {
			restrict : 'A',
			scope : false,
			require : '^rbsDocumentEditorBase',

			compile : function (tElement)
			{
				// First, the Section is hidden.
				// It will be shown when the user selects a section in the menu
				// (see event 'Change:EditorSectionChanged' below).
				tElement.hide();

				return function rbsEditorSectionLink (scope, iElement, iAttrs, ctrl)
				{
					var sectionId = iAttrs['rbsEditorSection'] || '',
						entry = {
							'id' : sectionId,
							'label' : iAttrs['editorSectionLabel'],
							'fields' : [],
							'required' : [],
							'invalid' : [],
							'corrected': []
						};

					entry.url = Utils.makeUrl($location.absUrl(), { section : (sectionId.length ? sectionId : null) });

					// Search for fields (properties) in this section.
					iElement.find('[property]').each(function (index, ctrlGrp)
					{
						var $ctrlGrp = $(ctrlGrp),
							$lbl = $ctrlGrp.find('label[for]').first(),
							propertyName = $ctrlGrp.attr('property');

						entry.fields.push({
							id : propertyName,
							label : $lbl.text()
						});
						if ($ctrlGrp.hasClass('required')) {
							entry.required.push(propertyName);
						}
						if ($ctrlGrp.hasClass(CORRECTION_CSS_CLASS)) {
							entry.corrected.push(propertyName);
						}
					});

					ctrl.addMenuEntry(entry);

					// Show/hide the section
					function update (section)
					{
						if (section === sectionId) {
							iElement.show();
						} else {
							iElement.hide();
						}
					}

					update(ctrl.getCurrentSection());

					scope.$on('Change:EditorSectionChanged', function (event, section) {
						update(section);
					});
				};
			}

		};
	}]);






})(window.jQuery);