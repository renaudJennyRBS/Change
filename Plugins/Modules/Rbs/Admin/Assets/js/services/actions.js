(function ($) {

	"use strict";

	// FIXME Localization

	// FIXME Workflow actions names

	var app = angular.module('RbsChange');

	app.provider('RbsChange.Actions', function RbsChangeActionsProvider() {
		this.$get = ['$http', '$filter', '$q', '$rootScope', 'RbsChange.Dialog', 'RbsChange.Clipboard', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'RbsChange.REST', 'RbsChange.NotificationCenter', 'RbsChange.i18n', 'RbsChange.ErrorFormatter', function ($http, $filter, $q, $rootScope, Dialog, Clipboard, Utils, ArrayUtils, REST, NotificationCenter, i18n, ErrorFormatter) {
			function Actions () {

				this.reset = function () {
					this.actions = {};
					this.actionsForModel = {
						'all': []
					};
				};

				this.reset();


				/**
				 * @param actionName The name of the action to be called.
				 * @param paramsObj A hash object (map) containing the action's arguments.
				 *
				 * @return A promise object.
				 */
				this.execute = function (actionName, paramsObj) {
					var method = '_' + actionName.replace('.', '-'),
					    promise,
					    actionObject;

					if (method in this.actions) {
						paramsObj = paramsObj || {};
						// Call the action with the correct parameters provided in the 'paramsObj' object.
						actionObject = this.actions[method];

						if (actionObject.__execFn) {
							promise = this.actions[method].__execFn.apply(
									this.actions[method],
									Utils.objectValues(paramsObj, this.actions[method].__execFnArgs)
								);
						}

						// Create a promise if the action did not create one, and resolve it right now.
						if (angular.isUndefined(promise)) {
							var defer = $q.defer();
							promise = defer.promise;
							defer.resolve();
						}

						if (actionObject.loading) {
							promise.then(function actionCallback () {
								NotificationCenter.clear();
							}, function actionErrback(reason) {
								NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.action_error | ucf', {ACTION: (actionObject.label || actionName)}), reason, paramsObj);
							});
						} else {
							promise.then(function actionCallback () {
								NotificationCenter.clear();
							}, function actionErrback(reason) {
								NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.action_error | ucf', {ACTION: (actionObject.label || actionName)}), reason, paramsObj);
							});
						}

						return promise;
					} else {
						throw new Error("Action '" + actionName + "' does not seem to exist. Please check 'actions.js'.");
					}
				};


				/**
				 * Checks whether the action identified by 'actionName' should be enabled when applied on the given documents.
				 *
				 * @param actionName The name of the action.
				 * @param $docs An array containing the documents on which the action may be applied.
				 * @param $DL The DocumentList object attached to the documents.
				 *
				 * @return true if the action is enabled, false otherwise.
				 */
				this.isEnabled = function (actionName, $docs, $DL) {
					var method = '_' + actionName.replace('.', '-');
					if (method in this.actions) {
						if ('isEnabled' in this.actions[method]) {
							return this.actions[method].isEnabled($docs, $DL);
						} else {
							return true;
						}
					} else {
						throw new Error("Action '" + actionName + "' does not seem to exist. Please check 'actions.js'.");
					}
				};


				/*
				 * Registers the given 'actionObject'.
				 *
				 * @param actionObject = {
				 *   models       // Full model name, array of full model names or '*' for an action that is available to all models.
				 *   label
				 *   description  // Used as tooltip
				 *   icon
				 *   selection    // integer, "min,max" (max=0 for no upper limit) or "+" for at least one document
				 *   execute      // required: Array with parameters name and function as the last item; should return a promise.
				 *   isEnabled()  // optional (defaults to true): should return a boolean value.
				 * }
				 */
				this.register = function (actionObject) {
					var method = '_' + actionObject.name.replace('.', '-'),
					    i,
					    m;

					if (method in this.actions) {
						throw new Error("Action '" + actionObject.name + "' already exists.");
					}

					if (angular.isDefined(actionObject.execute) && ! angular.isArray(actionObject.execute)) {
						throw new Error("'execute' member in action definition should be an Array: ['param1', 'param2', function (param1, param2) {...}].");
					}

					// Build the function that checks if the action can be enabled
					// according to the number of selected documents.

					var isSelectionOkFn = null;
					if (angular.isNumber(actionObject.selection)) {
						var count = actionObject.selection;
						isSelectionOkFn = function (docs) {
							return docs && docs.length === count;
						};
					} else if (angular.isString(actionObject.selection)) {
						if (actionObject.selection === '+') {
							isSelectionOkFn = function (docs) {
								return docs && docs.length > 0;
							};
						} else if (actionObject.selection.indexOf(',') !== -1) {
							var range = actionObject.selection.split(",");
							range[0] = parseInt(range[0].trim(), 10);
							range[1] = parseInt(range[1].trim(), 10);
							if (range[1] === 0) {
								isSelectionOkFn = function (docs) {
									return docs && docs.length >= range[0];
								};
							} else {
								isSelectionOkFn = function (docs) {
									return docs && docs.length >= range[0] && docs.length <= range[1];
								};
							}
						}
					} else {
						isSelectionOkFn = function () {
							return true;
						};
					}

					// Build the function that checks if the action can be enabled
					// according to the model of the selected documents.

					var isModelOkFn;
					if (angular.isArray(actionObject.models)) {
						isModelOkFn = function (docs) {
							var i;
							for (i=0 ; i<docs.length ; i++) {
								var args = actionObject.models;
								args.unshift(docs[i]);
								if ( ! Utils.isModel.apply(Utils, args) ) {
									return false;
								}
							}
							return true;
						};
					} else {
						isModelOkFn = function (docs) {
							if (angular.isUndefined(docs))
							{
								return true;
							}
							var i;
							for (i=0 ; i<docs.length ; i++) {
								if ( ! Utils.isModel.apply(Utils, [docs[i], actionObject.models]) ) {
									return false;
								}
							}
							return true;
						};
					}

					// Redefine the 'isEnabled()' method of the actionObject by adding the
					// isSelectionOkFn() and isModelOkFn() calls.

					if (isSelectionOkFn) {
						if (angular.isUndefined(actionObject.isEnabled)) {
							actionObject.isEnabled = function(docs) {
								return isSelectionOkFn(docs) && isModelOkFn(docs);
							};
						} else if (angular.isFunction(actionObject.isEnabled)) {
							var isEnabledFn = actionObject.isEnabled;
							actionObject.isEnabled = function(docs, DL) {
								return isSelectionOkFn(docs) && isModelOkFn(docs) && isEnabledFn(docs, DL);
							};
						}
					}


					// Mark this action as available for the given models.

					if (angular.isArray(actionObject.models)) {
						for (i=0 ; i<actionObject.models.length ; i++) {
							m = actionObject.models[i];
							if (angular.isArray(this.actionsForModel[m])) {
								this.actionsForModel[m].push(actionObject);
							} else {
								this.actionsForModel[m] = [actionObject];
							}
						}
					} else {
						m = actionObject.models === '*' ? 'all' : actionObject.models;
						if (angular.isArray(this.actionsForModel[m])) {
							this.actionsForModel[m].push(actionObject);
						} else {
							this.actionsForModel[m] = [actionObject];
						}
					}

					if (actionObject.execute) {
						actionObject.__execFn = actionObject.execute.pop();
						actionObject.__execFnArgs = actionObject.execute;
					}

					this.actions[method] = actionObject;
				};


				this.unregister = function (name) {
					delete this.actions['_' + name.replace('.', '-')];
				};


				this.get = function (actionName) {
					var method = '_' + actionName.replace('.', '-');
					if (method in this.actions) {
						return this.actions[method];
					}
					return null;
				};


				this.getActionsForModels = function () {
					var i,
					    model,
					    actions = [],
					    callback = function (actionObj) {
							actions.push(actionObj.name);
						};

					for (i=0 ; i<arguments.length ; i++) {
						model = arguments[i];
						if (model in this.actionsForModel) {
							angular.forEach(this.actionsForModel[model], callback);
						}
					}
					return actions;
				};


				this.getAllActionsForModels = function () {
					var actions = this.getActionsForAllModels();
					angular.forEach(this.getActionsForModels.apply(this, arguments), function (actionObj) {
						actions.shift(actionObj.name);
					});
					return actions;
				};


				this.getActionsForAllModels = function () {
					var actions = [];
					angular.forEach(this.actionsForModel['all'], function (actionObj) {
						actions.push(actionObj.name);
					});
					return actions;
				};


				// ====== Default actions ======


				/**
				 * Action: addToClipboard
				 * @param $docs Documents on which the action should be applied.
				 */
				this.register({
					name        : 'addToClipboard',
					models      : '*',
					description : i18n.trans('m.rbs.admin.adminjs.action_add_to_clipboard_help | ucf'),
					label       : i18n.trans('m.rbs.admin.adminjs.action_add_to_clipboard | ucf'),
					icon        : "icon-bookmark",
					selection   : "+",

					execute : ['$docs', function ($docs) {
						Clipboard.append($docs);
					}]
				});


				/**
				 * Action: delete
				 * @param $docs Documents on which the action should be applied.
				 * @param confirmMessage Additional message to be displayed in the confirmation dialog.
				 */
				this.register({
					name        : 'delete',
					models      : '*',
					description : i18n.trans('m.rbs.admin.adminjs.action_delete_help | ucf'),
					label       : i18n.trans('m.rbs.admin.adminjs.action_delete | ucf'),
					icon        : "icon-trash",
					selection   : "+",
					cssClass    : "btn-danger-hover",

					execute : ['$docs', '$embedDialog', '$scope', '$target', 'confirmMessage', function ($docs, $embedDialog, $scope, $target, confirmMessage) {
						var correction = false,
						    localized = false,
						    message;

						angular.forEach($docs, function (doc) {
							if (Utils.hasCorrection(doc)) {
								correction = true;
							}
							if (Utils.isLocalized(doc)) {
								localized = true;
							}
						});

						// TODO
						// If there are corrections and/or localizations, ask the user what should be deleted.

						message = i18n.trans('m.rbs.admin.adminjs.action_delete_message | ucf', {DOCUMENTLISTSUMMARY: $filter('rbsDocumentListSummary')($docs)});
						if (correction) {
							message += "<p>" + i18n.trans('m.rbs.admin.adminjs.action_delete_with_correction_message | ucf') + "</p>";
						}
						confirmMessage = confirmMessage || null;

						var promise;
						if ($embedDialog) {
							promise = Dialog.confirmEmbed(
								$embedDialog,
								i18n.trans('m.rbs.admin.adminjs.ask_for_delete | ucf'),
								message,
								$scope,
								{
									'pointedElement'    : $target,
									'primaryButtonClass': 'btn-danger',
									'cssClass': 'danger',
									'primaryButtonText' : i18n.trans('m.rbs.admin.adminjs.delete')
								}
							);
						} else if ($target) {
							// ($el, title, message, options) {
							promise = Dialog.confirmLocal(
								$target,
								i18n.trans('m.rbs.admin.adminjs.ask_for_delete | ucf'),
								message,
								{
									"placement": "bottom"
								}
							);
						} else {
							promise = Dialog.confirm(
								i18n.trans('m.rbs.admin.adminjs.ask_for_delete | ucf'),
								message,
								"danger",
								confirmMessage
							);
						}

						promise.then(function () {
							var promises = [];
							// Call one REST request per document to remove, and store the resulting Promise.
							angular.forEach($docs, function (doc) {
								promises.push(REST['delete'](doc));
							});
							if ($scope && angular.isFunction($scope.reload)) {
								// Refresh the list when all the requests have completed.
								// Notify user if there is an exception during deleting
								$q.all(promises).then(function () {
									$scope.deselectAll();
									$scope.reload();
								}, function (reason){
									NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.error_on_delete | ucf'), ErrorFormatter.format(reason));
								});
							}
						});
					}]
				});


				/**
				 * Action: freeze
				 */
				this.register({
					name        : 'freeze',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_freeze | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_freeze_help | ucf'),
					icon        : "icon-pause",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {

						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.executeTaskByCodeOnDocument('freeze', doc);
							promises.push(promise);
							promise.then(function (updatedDoc) {
								angular.extend(doc, updatedDoc);
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if ( ! $docs[i].isActionAvailable('freeze') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: unfreeze
				 */
				this.register({
					name        : 'unfreeze',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_unfreeze | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_unfreeze_help | ucf'),
					icon        : "icon-play",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {

						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.executeTaskByCodeOnDocument('unfreeze', doc);
							promises.push(promise);
							promise.then(function (updatedDoc) {
								angular.extend(doc, updatedDoc);
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if ( ! $docs[i].isActionAvailable('unfreeze') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: requestValidation
				 */
				this.register({
					name        : 'requestValidation',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_validate | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_validate_help | ucf'),
					icon        : "icon-play",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {

						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.executeTaskByCodeOnDocument('requestValidation', doc);
							promises.push(promise);
							promise.then(function (updatedDoc) {
								angular.extend(doc, updatedDoc);
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if ( ! $docs[i].isActionAvailable('requestValidation') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: publicationValidation
				 */
				this.register({
					name        : 'publicationValidation',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_publish | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_publish_help | ucf'),
					icon        : "icon-rss",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {

						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.executeTaskByCodeOnDocument('publicationValidation', doc);
							promises.push(promise);
							promise.then(function (updatedDoc) {
								angular.extend(doc, updatedDoc);
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if ( ! $docs[i].isActionAvailable('publicationValidation') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: contentValidation
				 */
				this.register({
					name        : 'contentValidation',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_contentvalidation | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_contentvalidation_help | ucf'),
					icon        : "icon-rss",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {

						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.executeTaskByCodeOnDocument('contentValidation', doc);
							promises.push(promise);
							promise.then(function (updatedDoc) {
								angular.extend(doc, updatedDoc);
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if ( ! $docs[i].isActionAvailable('contentValidation') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: save
				 */
				this.register({
					name        : 'save',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.save | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.save_description | ucf'),
					icon        : "icon-ok",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {
						var promises = [];
						// Call one REST request per document and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							promises.push(REST.save(doc));
						});
						return $q.all(promises);
					}]
				});


				/**
				 * Action: reorder
				 */
				this.register({
					name        : 'reorder',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_reorder | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_reorder_help | ucf'),
					icon        : "icon-reorder",

					execute : ['$scope', '$embedDialog', '$target', function ($scope, $embedDialog, $target) {
						Dialog.embed(
							$embedDialog,
							{
								'contents' : '<reorder-panel documents="collection"></reorder-panel>',
								'title'    : "<i class=\"" + this.icon + "\"></i> " + i18n.trans('m.rbs.admin.adminjs.action_apply_correction_ask | ucf')
							},
							$scope,
							{
								'pointedElement': $target
							}
						);
					}],

					isEnabled : function ($docs, $DL) {
						return true;
					}
				});




				/**
				 * Action: activate
				 */
				this.register({
					name        : 'activate',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_activate | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_activate_help | ucf'),
					icon        : "icon-play",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {
						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.call(doc.getActionUrl('activate'));
							promises.push(promise);
							promise.then(function (updatedDoc) {
								doc.active = updatedDoc.active;
								doc.modificationDate = updatedDoc.modificationDate;
								delete doc.META$.actions.activate;
								doc.META$.actions[updatedDoc.action.rel] = updatedDoc.action;
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if (!$docs[i].isActionAvailable('activate') ) {
								return false;
							}
						}
						return true;
					}
				});


				/**
				 * Action: deactivate
				 */
				this.register({
					name        : 'deactivate',
					models      : '*',
					label       : i18n.trans('m.rbs.admin.adminjs.action_deactivate | ucf'),
					description : i18n.trans('m.rbs.admin.adminjs.action_deactivate_help | ucf'),
					icon        : "icon-play",
					selection   : "+",
					loading     : true,

					execute : ['$docs', function ($docs) {
						var promises = [];
						// Call one REST request per document to activate and store the resulting Promise.
						angular.forEach($docs, function (doc) {
							var promise = REST.call(doc.getActionUrl('deactivate'));
							promises.push(promise);
							promise.then(function (updatedDoc) {
								doc.active = updatedDoc.active;
								doc.modificationDate = updatedDoc.modificationDate;
								delete doc.META$.actions.deactivate;
								doc.META$.actions[updatedDoc.action.rel] = updatedDoc.action;
							});
						});
						return $q.all(promises);

					}],

					isEnabled : function ($docs) {
						for (var i=0 ; i<$docs.length ; i++) {
							if (!$docs[i].isActionAvailable('deactivate') ) {
								return false;
							}
						}
						return true;
					}
				});



			}

			return new Actions();

		}];
	});


})( window.jQuery );