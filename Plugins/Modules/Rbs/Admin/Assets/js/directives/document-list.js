/**
 * User: fredericbonjour
 * Date: 24/05/13
 * Time: 09:44
 */
(function ($) {

	"use strict";

	$('<div id="rbs-document-list-tester"></div>').css({
		'position' : 'absolute',
		'top'      : -9999,
		'left'     : -9999,
		'width'    : 'auto',
		'display'  : 'inline-block'
	}).appendTo('body');

	var	app = angular.module('RbsChange'),
		__columns = {},
		__preview = {},
		__gridItems = {},
		__quickActions = {},
		__actions = {},
		PAGINATION_DEFAULT_LIMIT = 20,
		DEFAULT_ACTIONS = 'delete(icon)',
		DEFAULT_PUBLISHABLE_ACTIONS = 'requestValidation publicationValidation freeze(icon) unfreeze(icon) delete(icon)',
		DEFAULT_ACTIVABLE_ACTIONS = 'activate deactivate delete(icon)',
		testerEl = $('#rbs-document-list-tester'),
		forEach = angular.forEach;

	app.constant('RbsChange.PaginationPageSizes', [ 10, 20, 30, 50, 75, 100 ]);

	app.directive('rbsDocumentList', [
		'$q',
		'$filter',
		'$rootScope',
		'$location',
		'$timeout',
		'$cacheFactory',
		'RbsChange.i18n',
		'RbsChange.REST',
		'RbsChange.Loading',
		'RbsChange.Utils',
		'RbsChange.ArrayUtils',
		'RbsChange.Breadcrumb',
		'RbsChange.Actions',
		'RbsChange.NotificationCenter',
		'RbsChange.Device',
		'RbsChange.Settings',
		'RbsChange.EditorManager',
		'RbsChange.Events',
		'RbsChange.PaginationPageSizes',
		'RbsChange.SelectSession',
		documentListDirectiveFn
	]);


	function documentListDirectiveFn ($q, $filter, $rootScope, $location, $timeout, $cacheFactory, i18n, REST, Loading, Utils, ArrayUtils, Breadcrumb, Actions, NotificationCenter, Device, Settings, EditorManager, Events, PaginationPageSizes, SelectSession) {

		/**
		 * Build the HTML used in the "Quick actions" toolbar.
		 * @param dlid
		 * @param tAttrs
		 * @param localActions
		 * @returns {string}
		 */
		function buildQuickActionsHtml (dlid, tAttrs, localActions) {
			var	actionDivider = '<span class="divider">|</span>',
				html,
				quickActionsHtml;

			html = '<div class="quick-actions ' + (Device.isMultiTouch() ? 'quick-actions-touch' : 'quick-actions-mouse') + '">';

			if (Device.isMultiTouch()) {
				html += '<i class="icon-chevron-left handle-open"></i><i class="icon-chevron-right handle-close"></i> ';
			}

			function buildDefault () {
				if (__preview[dlid]) {
					return buildPreviewAction() + actionDivider + buildDeleteAction();
				}
				var out = buildDeleteAction();
				if (tAttrs.publishable === 'true') {
					out += actionDivider + buildWorkflowAction();
				}
				return out;
			}

			function buildDeleteAction () {
				return	'<a href="javascript:;" class="danger" ng-click="remove(doc, $event)">' +
							i18n.trans('m.rbs.admin.admin.js.delete') +
						'</a>';
			}

			function buildEditAction () {
				if (tAttrs.cascadeEdition) {
					return	'<a href="javascript:;" ng-click="cascadeEdit(doc)">' +
								i18n.trans('m.rbs.admin.admin.js.edit') +
							'</a>';
				} else {
					return	'<a href ng-href="(= doc | documentURL =)">' +
								i18n.trans('m.rbs.admin.admin.js.edit') +
							'</a>';
				}
			}

			function buildOtherAction (action) {
				return	'<a href="javascript:;" ng-click="executeAction(\'' + action.name + '\', doc, $event)">' +
							action.label +
						'</a>';
			}

			function buildPreviewAction () {
				return '<a href="javascript:" ng-click="preview(doc, $event)"><i ng-class="{\'icon-spinner icon-spin\':isPreviewLoading(doc), \'icon-chevron-up\':hasPreview($index), \'icon-chevron-down\':!hasPreview($index)}"></i> ' + i18n.trans('m.rbs.admin.admin.js.preview') + '</a>';
			}

			function buildWorkflowAction () {
				return '<a href="javascript:" ng-click="showWorkflow($index, $event)"><i ng-class="{\'icon-chevron-up\':hasWorkflow($index), \'icon-chevron-down\':!hasWorkflow($index)}"></i> ' + i18n.trans('m.rbs.admin.admin.js.workflow') + '</a>';
			}

			function buildForSelectSession (multiple) {
				var html = '<a href="javascript:;" ng-click="selectSession.use(doc)">' +
					i18n.trans('m.rbs.admin.admin.js.select') +
					'</a>';

				if (multiple) {
					html += actionDivider +
						'<a href="javascript:;" ng-click="selectSession.append(doc)">' +
						i18n.trans('m.rbs.admin.admin.js.select-add') +
						'</a>';
				}

				return html;
			}

			if (SelectSession.started()) {
				html += buildPreviewAction() + actionDivider + buildForSelectSession(SelectSession.info().multiple);
			}
			else if (__quickActions[dlid]) {
				if (__quickActions[dlid].divider) {
					actionDivider = __quickActions[dlid].divider;
				}
				quickActionsHtml = __quickActions[dlid].contents;

				if (tAttrs.publishable === 'true' && (! quickActionsHtml || (quickActionsHtml.indexOf('[action default]') === -1 && quickActionsHtml.indexOf('[action workflow]') === -1))) {
					quickActionsHtml += actionDivider + '[action workflow]';
				}

				if (! quickActionsHtml.length) {
					return null;
				}
				quickActionsHtml = quickActionsHtml.replace(/\s*\|\|\s*/g, actionDivider).replace(/\[action\s+([A-Za-z0-9_\-]+)\]/g, function (match, actionName) {
					if (actionName === 'delete') {
						return buildDeleteAction();
					}
					if (actionName === 'preview') {
						return buildPreviewAction();
					}
					if (actionName === 'edit') {
						return buildEditAction();
					}
					if (actionName === 'default') {
						return buildDefault();
					}
					if (actionName === 'workflow') {
						return buildWorkflowAction();
					}

					if (localActions.hasOwnProperty(actionName)) {
						actionName = dlid + '_' + actionName;
					}

					var actionObject = Actions.get(actionName);
					if (actionObject !== null) {
						return buildOtherAction(actionObject);
					}
				});
				html += quickActionsHtml;
			} else {
				html += buildDefault();
			}

			html += '</div>';

			return html;
		}


		/**
		 * @param dlid
		 * @returns {Object}
		 */
		function initLocalActions (dlid) {
			var	localActions = {};
			angular.forEach(__actions[dlid], function (action) {
				if (! action.name) {
					throw new Error("Actions defined in <rbs-document-list/> should have a 'name' parameter.");
				}
				if (localActions[action.name]) {
					throw new Error("Parameter 'name' for actions defined in <rbs-document-list/> should be unique.");
				}

				localActions[action.name] = action;
				Actions.register({
					name        : (dlid + '_' + action.name),
					models      : action.models || '*',
					description : action.description,
					label       : action.label,
					icon        : action.icon,
					selection   : action.selection,

					execute : ['$extend', '$docs', function ($extend, $docs) {
						if (angular.isFunction($extend[action.name])) {
							$extend[action.name]($docs);
						}
						else {
							throw new Error("Method '" + this.name + "' is not defined in '$extend'.");
						}
					}]
				});
			});
			delete __actions[dlid];
			return localActions;
		}


		/**
		 * Initialize columns for <rbs-document-list/>
		 * @param dlid
		 * @param tElement
		 * @param tAttrs
		 * @param undefinedColumnLabels
		 * @param localActions
		 * @returns {Object}
		 */
		function initColumns (dlid, tElement, tAttrs, undefinedColumnLabels, localActions) {
			var	columns, column,
				$th, $td, $head, $body, html, p, inner,
				result = {
					'columns' : {},
					'preview' : false
				};

			columns = __columns[dlid];

			if (!columns) {
				throw new Error("Could not find any column for <rbs-document-list/> directive with id='" + dlid + "'. We are sure you want something to be displayed in this list :)");
			}

			$head = tElement.find('table.document-list thead tr');
			$body = tElement.find('table.document-list tbody tr.normal-row');

			// Status column
			if (tAttrs.publishable === 'true') {
				columns.unshift({
					"name"   : "publicationStatus",
					"align"  : "center",
					"width"  : "44px",
					"label"  : '<abbr title="' + i18n.trans('m.rbs.admin.admin.js.status | ucf') + '">' + i18n.trans('m.rbs.admin.admin.js.status-minified | ucf') + '</abbr>',
					"content": '<a href="javascript:;" ng-click="showWorkflow($index)"><status ng-model="doc"/></a>',
					"dummy"  : true
				});
			}

			// Selectable column
			if (angular.isUndefined(tAttrs.selectable) || tAttrs.selectable === 'true') {
				columns.unshift({
					"name"   : "selectable",
					"align"  : "center",
					"width"  : "30px",
					"label"  : '<input type="checkbox" ng-click="$event.stopPropagation()" ng-model="allSelected.cb"/>',
					"content": '<input type="checkbox" ng-click="$event.stopPropagation()" ng-model="doc.selected"/>',
					"dummy"  : true
				});
			}

			// Tree navigation link
			if (tAttrs.tree) {
				columns.push({
					"name"  : "navigation"
				});
			}

/*
			// Order in tree column
			if (tAttrs.tree) {
				columns.push({
					"name"  : "nodeOrder",
					"align"  : "right",
					"width"  : "70px",
					"label"  : i18n.trans('m.rbs.admin.admin.js.order | ucf'),
					"content": '(=doc.META$.treeNode.nodeOrder | number=)'
				});
			}
*/
			// Modification Date column
			if (angular.isUndefined(tAttrs.modificationDate) || tAttrs.modificationDate === 'true') {
				columns.push({
					"name"   : "modificationDate",
					"label"  : i18n.trans('m.rbs.admin.admin.js.modification-date | ucf'),
					"format" : "date"
				});
			}

			// Activable switch column
			if (tAttrs.activable === 'true') {
				columns.push({
					"name"   : "publicationStatusSwitch",
					"align"  : "center",
					"width"  : "90px",
					"label"  : i18n.trans('m.rbs.admin.admin.js.activated | ucf'),
					"content": '<activate-switch document="doc"/>',
					"dummy"  : true
				});
			}

			tElement.data('columns', {});

			// Update colspan value for preview and empty cells.
			tElement.find('tbody td[data-colspan="auto"]').attr('colspan', columns.length);
			tElement.find('tbody td[data-colspan="auto"]').attr('colspan', columns.length);


			// Prepare preview
			if (!__preview[dlid] && tAttrs.preview === 'true') {
				__preview[dlid] = {};
			}

			if (__preview[dlid]) {
				inner = tElement.find('tbody tr td.preview .inner');
				if (__preview[dlid]['class']) {
					inner.addClass(__preview[dlid]['class']);
				}
				if (__preview[dlid]['style']) {
					inner.attr('style', __preview[dlid]['style']);
				}
				inner.find('[data-role="preview-contents"]').replaceWith(__preview[dlid].contents || '<div ng-include="doc | adminTemplateURL:\'preview-list\'"></div>');
				result.preview = true;
			}

			// Loop through all the columns and build header et body cells.
			while (columns.length) {
				column = columns.shift(0);
				p = column.name.indexOf('.');
				if (p === -1) {
					column.valuePath = column.name;
				} else {
					column.valuePath = column.name;
					column.name = column.name.substring(0, p);
				}

				if (column.name === 'navigation')
				{
					column.content = '<a ng-if="doc.hasUrl(\'tree\')" href ng-href="(= doc|documentURL:\'tree\'=)"><i class="icon-circle-arrow-right icon-large"></i></a>';
					column.width = "40px";
					column.label = "Nav."; // TODO
					column.align = 'center';
				}

				if (! column.sort)
				{
					column.sort = column.name;
				}

				switch (column.format)
				{
					case 'number' :
						column.valuePath += '|number';
						if (!column.align) {
							column.align = 'right';
						}
						break;

					case 'date' :
						if (!column.width) {
							column.width = "160px";
						}
						column.content = '<time data-column="' + column.name + '" display="(= dateDisplay.' + column.name + ' =)" datetime="(=doc.' + column.valuePath + '=)"></time>';
						break;
				}

				result.columns[column.name] = column;

				// Check if the label has been provided or not.
				// If one at least label has not been provided, the Model's information will be
				// loaded to automatically set the columns' header text.
				if ( ! column.label ) {
					undefinedColumnLabels.push(column.name);
				}

				// Create header cell
				if (column.name === 'selectable') {
					$th = $('<th ng-click="allSelected.cb = ! allSelected.cb" style="cursor:pointer;">' + column.label + '</th>');
				} else {
					var toggleDateBtn, htmlTh;

					if (column.format === 'date') {
						toggleDateBtn = '<button type="button" ng-class="{\'active\':dateDisplay.' + column.name + '==\'relative\'}" ng-click="toggleRelativeDates(\'' + column.name + '\')" class="btn btn-xs btn-info pull-right"><i class="icon-time"></i></button>';
					} else {
						toggleDateBtn = '';
					}

					htmlTh = '<th ng-if="isSortable(\'' + column.sort + '\')" ng-class="{\'sorted\':isSortedOn(\'' + column.sort + '\')}">' + toggleDateBtn;

					if (column.localSort === 'true') {
						htmlTh += '<a href="javascript:;" ng-click="toggleLocalSort(\'' + column.sort + '\')" ng-bind-html="columns.' + column.name + '.label">' + column.name + '</a>';
					} else {
						htmlTh += '<a href ng-click="clearLocalSort()" ng-href="(= headerUrl(\'' + column.sort + '\') =)" ng-bind-html="columns.' + column.name + '.label">' + column.name + '</a>';
					}

					htmlTh +=
						'<i class="column-sort-indicator" ng-class="{true:\'icon-sort-down\', false:\'icon-sort-up\'}[isSortDescending()]" ng-if="isSortedOn(\'' + column.sort + '\')"></i>' +
						'<i class="column-sort-indicator icon-sort" ng-if="!isSortedOn(\'' + column.sort + '\')"></i>' +
						'</th>' +
						'<th ng-if="!isSortable(\'' + column.sort + '\')">' + toggleDateBtn + '<span ng-bind-html="columns.' + column.name + '.label">' + column.name + '</span></th>';

					$th = $(htmlTh);
				}
				if (column.align) {
					$th.css('text-align', column.align);
				}
				if (column.width) {
					$th.css('width', column.width);
				}
				$head.append($th);

				// Create body cell
				if (column.content) {

					if (column.name === 'selectable') {
						html = '<td ng-click="doc.selected = ! doc.selected" style="cursor:pointer;">' + column.content + '</td>';
						$td = $(html);
					}
					else {
						// Allow the use of "converted(<property>)" instead of "getConvertedValue(<property>, <columnName>)"
						// in column templates that have a converter defined on them.
						column.content = column.content.replace(/converted\s*\(\s*([a-zA-Z0-9\.]+)\s*\)/, 'getConvertedValue($1, "' + column.name + '")');

						html = '<td ng-class="{' + (column.primary ? '\'preview\':hasPreview(doc),' : '') + '\'sorted\':isSortedOn(\'' + column.name + '\')}">';
						if (column.primary) {
							html += '<div class="primary-cell">' + column.content + '</div>';
						} else {
							html += column.content;
						}
						html += '</td>';
						$td = $(html);
					}

				} else {

					if (column.thumbnail) {
						if (column.thumbnailPath) {
							column.content = '<img rbs-storage-image="' + column.thumbnailPath + '" thumbnail="' + column.thumbnail + '"/>';
						} else {
							column.content = '<img rbs-storage-image="doc.' + column.valuePath + '" thumbnail="' + column.thumbnail + '"/>';
						}
					} else {
						if (column.converter) {
							column.content = '(= getConvertedValue(doc.' + column.valuePath + ', "' + column.name + '") =)';
						} else {
							column.content = '(= doc.' + column.valuePath + ' =)';
						}
					}
					if (column.primary) {
						if (tAttrs.cascadeEdition) {
							$td = $('<td ng-class="{\'preview\':hasPreview(doc),\'sorted\':isSortedOn(\'' + column.name + '\')}"><div class="primary-cell"><a href="javascript:;" ng-click="cascadeEdit(doc)"><strong>' + column.content + '</strong></a></div></td>');
						} else {
							$td = $('<td ng-class="{\'preview\':hasPreview(doc),\'sorted\':isSortedOn(\'' + column.name + '\')}"><div class="primary-cell"><a href ng-href="(= doc | documentURL =)"><strong>' + column.content + '</strong></a></div></td>');
						}
					} else {
						$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}">' + column.content + '</td>');
					}

				}

				if (column.align) {
					$td.css({
						'text-align': column.align
					});
				}

				// The primary column has extra links for preview, edit and delete.
				if (column.primary) {
					html = buildQuickActionsHtml(dlid, tAttrs, localActions);
					if (html !== null) {
						testerEl.html(html);
						$td.find('.primary-cell').prepend(html);

						// Compute real width so that the CSS animation can work correctly when
						// opening this menu.
						// (see showQuickActions() below, in the directive's linking function).
						$td.find('.primary-cell .quick-actions').attr('data-real-width', (testerEl.outerWidth())+'px');
						$td.find('.primary-cell').prepend('<span ng-if="doc.hasCorrection()" class="label label-success pull-right">correction</span>');
					}
				}

				$td.attr('ng-if', "isNormalCell(doc)");
				$body.append($td);
			}

			if (Device.isMultiTouch()) {
				$body.attr('ng-swipe-left', "showQuickActions($index)");
				$body.attr('ng-swipe-right', "hideQuickActions($index)");
			}

			delete __columns[dlid];

			return result;
		}


		/**
		 * Initialize grid mode for <rbs-document-list/>
		 * @param dlid
		 * @param tElement
		 * @returns {boolean}
		 */
		function initGrid (dlid, tElement) {
			var	gridModeAvailable = false,
				inner;

			if (__gridItems[dlid]) {
				gridModeAvailable = true;
				inner = tElement.find('ul.thumbnail-grid li .inner');
				if (__gridItems[dlid]['class']) {
					inner.addClass(__gridItems[dlid]['class']);
				}

				forEach(__gridItems[dlid], function (value, name) {
					if (name !== 'class' && name !== 'content' && ! Utils.startsWith(name, '$')) {
						inner.attr('data-' + Utils.normalizeAttrName(name), value);
					}
				});

				inner.html(__gridItems[dlid].content);
				delete __gridItems[dlid];
			}

			return gridModeAvailable;
		}


		/**
		 * <rbs-document-list/> directive.
		 */
		return {
			restrict    : 'E',
			transclude  : true,
			templateUrl : 'Rbs/Admin/js/directives/document-list.twig',

			scope : {
				'filterQuery' : '=',
				'loadQuery' : '=',
				'onPreview' : '&',
				'onReload' : '&',
				'cascadeEdition' : '@',
				'collectionUrl' : '@',
				'externalCollection' : '=collection',
				'extend' : '=',
				'model' : '@'
			},


			/**
			 * Directive's compile function:
			 * collect columns definition and templates for columns, grid items and preview.
			 */
			compile : function (tElement, tAttrs) {

				var	dlid, undefinedColumnLabels = [], gridModeAvailable, columnResult, localActions;

				dlid = tElement.data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}

				localActions = initLocalActions(dlid);

				columnResult = initColumns(dlid, tElement, tAttrs, undefinedColumnLabels, localActions);

				gridModeAvailable = initGrid(dlid, tElement);

				/**
				 * Directive's link function.
				 */
				return function linkFn (scope, elm, attrs) {
					var queryObject, search, columnNames, currentPath, previewCache, self = this;

					scope.collection = [];

					scope.gridModeAvailable = gridModeAvailable;
					if (attrs.display) {
						scope.viewMode = attrs.display;
					} else {
						scope.viewMode = gridModeAvailable ? Settings.get('documentListViewMode', 'grid') : 'list';
					}
					scope.columns = columnResult.columns;
					scope.dateDisplay = {};
					scope.previewAvailable = columnResult.preview;
					scope.embeddedActionsOptionsContainerId = 'embeddedActionsOptionsContainerId';
					scope.$DL = scope; // TODO Was used by "bind-action" directive. Still needed?
					scope.useToolBar = attrs.toolbar === 'false' ? false : true;


					// Select session
					scope.selectSession = {
						info : SelectSession.info(),
						end : SelectSession.end,
						cancel : SelectSession.rollback,
						clear : SelectSession.clear,
						append : SelectSession.append,
						appendSelected : function () {
							SelectSession.append(scope.selectedDocuments);
							deselectAll();
							return this;
						},
						use : function (doc) {
							SelectSession.append(doc).end();
						}
					};


					// Watch for changes on 'data-*' attributes, and transpose them into the 'data' object of the scope.
					scope.data = {};
					angular.forEach(elm.data(), function (value, key) {
						if (key === 'columns' || key === 'dlid') {
							return;
						}
						scope.$parent.$watch(value, function (v) {
							scope.data[key] = v;
						}, true);
					});


					// Load Model's information and update the columns' header with the correct property label.
					if (undefinedColumnLabels.length && attrs.model) {
						REST.modelInfo(attrs.model).then(function (modelInfo) {
							angular.forEach(undefinedColumnLabels, function (columnName) {
								if (columnName in modelInfo.properties) {
									scope.columns[columnName].label = modelInfo.properties[columnName].label;
								}
							});
						});
					}

					// The list listens to this event: 'Change:DocumentList:<dlid>:call'
					scope.$on('Change:DocumentList:' + dlid + ':call', function (event, args) {
						if (angular.isFunction(scope[args.method])) {
							var q, result;

							// Call the method on the Scope...
							result = scope[args.method].apply(self, args.params || []);

							// Ensure that "args.promises" is an Array.
							if (! angular.isArray(args.promises)) {
								args.promises = [];
							}

							// Store the result as a Promise in the "args.promises" Array.
							if (result && angular.isFunction(result.then)) {
								args.promises.push(result);
							} else {
								q = $q.defer();
								q.resolve(result);
								args.promises.push(q.promise);
							}
						} else {
							console.warn("Received event 'Change:DocumentList:" + dlid + ":call' but no method '" + args.method + "' is defined in the DocumentList's scope.");
						}
					});

					// Save selected view mode is user's settings.
					scope.$watch('viewMode', function (value) {
						Settings.set('documentListViewMode', value);
					}, true);


					scope.cascadeEdit = function (doc) {
						EditorManager.cascadeEditor(
							doc,
							scope.cascadeEdition
						);
					};

					scope.cascadeDuplicate = function (doc) {
						REST.resource(doc).then(function (fullDoc) {
								EditorManager.cascadeEditor(
									Utils.duplicateDocument(fullDoc),
									scope.cascadeEdition
								);
							}
						);
					};


					scope.hasColumn = function (columnName) {
						return angular.isObject(scope.columns[columnName]);
					};



					//
					// Document selection.
					//

					scope.selectionEnabled = scope.hasColumn('selectable');

					function updateSelectedDocuments () {
						scope.selectedDocuments = $filter('filter')(scope.collection, {'selected': true});
						scope.$emit('Change:DocumentList:' + dlid + ':CollectionChanged', scope.collection);
					}

					if (scope.selectionEnabled)
					{
						scope.allSelected = {
							'cb' : false
						};
						scope.$watch('allSelected', function () {
							angular.forEach(scope.collection, function (doc) {
								doc.selected = scope.allSelected.cb;
							});
						}, true);

						scope.$watch('collection', updateSelectedDocuments, true);
						updateSelectedDocuments();
					}


					function deselectAll () {
						scope.allSelected.cb = false;
						angular.forEach(scope.collection, function (doc) {
							if (doc.selected) {
								doc.selected = false;
							}
						});
					}

					//
					// Actions.
					//

					// Locally defined actions.
					var	actionList = elm.is('[actions]') ? attrs.actions : 'default';
					angular.forEach(localActions, function (action) {
						if (actionList.length) {
							actionList += ' ' + action.name;
						}
					});


					scope.actions = [];
					if (actionList.length) {
						if (attrs.publishable === 'true') {
							actionList = actionList.replace('default', DEFAULT_PUBLISHABLE_ACTIONS);
						}
						else if (attrs.activable === 'true') {
							actionList = actionList.replace('default', DEFAULT_ACTIVABLE_ACTIONS);
						}
						else {
							actionList = actionList.replace('default', DEFAULT_ACTIONS);
						}

						if (! scope.hasColumn('nodeOrder') ) {
							actionList = actionList.replace('nodeOrder', '');
						}

						angular.forEach(actionList.split(/ +/), function (action) {
							// Locally defined action?
							if (localActions[action]) {
								var actionId = dlid + '_' + action;
								if (localActions[action].display) {
									actionId += '(' + localActions[action].display + ')';
								}
								else if (localActions[action].icon) {
									actionId += '(icon+label)';
								}
								scope.actions.push({
									"type" : "single",
									"name" : actionId
								});
							}
							else {
								scope.actions.push({
									"type" : "single",
									"name" : action
								});
							}
						});
					}

					scope.executeAction = function (actionName, doc, $event) {
						return Actions.execute(actionName, {
							'$docs'   : [ doc ],
							'$target' : $event.target,
							'$scope'  : scope,
							'$extend' : scope.extend
						});
					};

					// Unregisters the locally defined actions from the Actions service (called from $on('$destroy')).
					function unregisterLocalActions() {
						angular.forEach(localActions, function (action) {
							Actions.unregister(dlid + '_' + action.name);
						});
					}


					scope.remove = function (doc, $event) {
						return scope.executeAction("delete", doc, $event);
					};


					scope.refresh = function () {
						return reload();
					};

					scope.reload = function () {
						return reload();
					};


					scope.isLastCreated = function (doc) {
						return REST.isLastCreated(doc);
					};


					//
					// Embedded preview.
					//

					function doPreview (index, currentItem, newItem) {
						var previewPromises = [];
						delete currentItem.__dlPreviewLoading;
						newItem.__dlPreview = true;
						// Store reference to the original document.
						newItem.__document = currentItem;

						$rootScope.$broadcast(Events.DocumentListPreview, {
							"document" : newItem,
							"promises" : previewPromises
						});

						if (newItem.id) {
							previewCache.put(newItem.id, newItem);
						}

						function terminatePreview () {
							scope.collection.splice(index+1, 0, newItem);
							Loading.stop();
						}

						if (previewPromises.length) {
							$q.all(previewPromises).then(terminatePreview);
						} else {
							terminatePreview();
						}

					}

					previewCache = $cacheFactory('chgRbsDocumentListPreview_' + dlid);
					scope.$on('$destroy', function () {
						previewCache.destroy();
						unregisterLocalActions();
					});

					scope.preview = function (index, $event) {
						if ($event) {
							$event.preventDefault();
						}

						if (angular.isObject(index)) {
							if (scope.isPreview(index)) {
								ArrayUtils.removeValue(scope.collection, index);
								return;
							}
							index = scope.collection.indexOf(index);
						}

						var	current = scope.collection[index], cachedDoc;

						if (scope.hasPreview(index)) {
							scope.collection.splice(index+1, 1);
							return;
						}

						cachedDoc = ($event && $event.shiftKey) ? null : previewCache.get(current.id);
						if (cachedDoc) {
							scope.collection.splice(index+1, 0, cachedDoc);
						} else {
							Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-preview | ucf'));
							current.__dlPreviewLoading = true;
							if (Utils.isDocument(current)) {
								REST.resource(current).then(function (doc) {
									REST.modelInfo(doc).then(function (modelInfo) {
										doc.META$.modelInfo = modelInfo;
										doPreview(index, current, doc);
									});
								});
							} else {
								doPreview(index, current, angular.copy(current));
							}
						}
					};


					scope.isPreview = function (doc) {
						return doc && doc.__dlPreview === true;
					};


					scope.isPreviewReady = function (doc) {
						return ! doc || ! doc.__dlPreviewLoading;
					};


					scope.isPreviewLoading = function (doc) {
						return doc && doc.__dlPreviewLoading;
					};


					scope.hasPreview = function (index) {
						if (angular.isObject(index)) {
							index = scope.collection.indexOf(index);
						}
						var current, next;
						current = scope.collection[index];
						next = (scope.collection.length > (index+1)) ? scope.collection[index+1] : null;
						return (next && next.__dlPreview && next.id === current.id) ? true : false;
					};


					scope.closeAllPreviews = function () {
						var i = 0;
						while (i < scope.collection.length) {
							if (scope.isPreview(scope.collection[i])) {
								scope.collection.splice(i, 1);
							} else {
								i++;
							}
						}
					};


					scope.isNormalCell = function (doc) {
						return ! scope.isPreview(doc) && ! scope.isWorkflow(doc);
					};


					scope.showWorkflow = function (index) {
						var	current = scope.collection[index],
							newItem;

						// Close workflow UI
						if (scope.hasWorkflow(current)) {
							delete current.__hasWorkflow;
							scope.collection.splice(index+1, 1);
						}
						// Show workflow UI
						else {
							current.__hasWorkflow = true;
							newItem = angular.copy(current);
							newItem.__document = current;
							newItem.__workflow = true;
							scope.collection.splice(index+1, 0, newItem);
						}
					};


					scope.closeWorkflow = function (index) {
						var	current = scope.collection[index-1];
						delete current.__hasWorkflow;
						scope.collection.splice(index, 1);
					};


					scope.isWorkflow = function (doc) {
						return doc && doc.__workflow === true;
					};


					scope.hasWorkflow = function (doc) {
						if (angular.isNumber(doc)) {
							doc = scope.collection[doc];
						}
						return doc && doc.__hasWorkflow === true;
					};


					/**
					 * Save the given doc.
					 * @param doc
					 */
					scope.save = function (doc) {
						Loading.start(i18n.trans('m.rbs.admin.admin.js.saving-document | ucf'));
						REST.save(doc).then(function (savedDoc) {
							angular.extend(doc, savedDoc);
							Loading.stop();
						}, function () {
							Loading.stop();
							// FIXME Display error message
						});
					};


					//
					// Pagination.
					//


					search = $location.search();
					scope.pagination = {
						offset : search.offset || 0,
						limit  : search.limit || Settings.get('pagingSize', PAGINATION_DEFAULT_LIMIT),
						total  : 0
					};

					scope.predefinedPageSizes = PaginationPageSizes;
					scope.pages = [];
					scope.currentPage = 0;
					scope.currentTag = 0;

					// Keep pagination data up-to-date.
					scope.$watch(
						'pagination',
						function refresh () {
							var nbPages,
								i;

							scope.pages.length = 0;

							scope.currentPage = scope.pagination.offset / scope.pagination.limit;
							nbPages = Math.ceil(scope.pagination.total / scope.pagination.limit);

							if (nbPages > 11) {

								if (scope.currentPage < 4 || scope.currentPage >= nbPages-4) {
									for (i=0 ; i<6 ; i++) {
										scope.pages.push(i);
									}
									scope.pages.push('...');
									for (i=nbPages-6 ; i<nbPages ; i++) {
										scope.pages.push(i);
									}
								} else {
									for (i=0 ; i<2 ; i++) {
										scope.pages.push(i);
									}
									scope.pages.push('...');
									for (i=scope.currentPage-2 ; i<scope.currentPage+3 ; i++) {
										scope.pages.push(i);
									}
									scope.pages.push('...' + (nbPages-2));
									for (i=nbPages-2 ; i<nbPages ; i++) {
										scope.pages.push(i);
									}
								}
							} else {
								for (i=0 ; i<nbPages ; i++) {
									scope.pages.push(i);
								}
							}

						},
						true
					);

					/**
					 * Is currentPage the first page?
					 * @returns {boolean}
					 */
					scope.isFirstPage = function () {
						return scope.currentPage === 0;
					};

					/**
					 * Is currentPage the last page?
					 * @returns {boolean}
					 */
					scope.isLastPage = function () {
						return scope.pages.length === 0 || scope.currentPage === (scope.pages.length-1);
					};

					/**
					 * Is disabled page?
					 * @returns {boolean}
					 */
					scope.isDisabled = function (page) {
						return !angular.isNumber(page);
					};

					/**
					 * Return the displayed value for the page number
					 * @returns {string}
					 */
					scope.displayPageNumber = function (page) {
						if (angular.isNumber(page)) {
							return page + 1;
						} else {
							return '...';
						}
					}

					/**
					 * Returns URL for a page (pagination)
					 * @returns {string}
					 */
					scope.pageUrl = function (page, limit) {
						var search = angular.copy($location.search());
						search.offset = Math.max(0, page * scope.pagination.limit);
						if (angular.isDefined(limit)) {
							search.limit = limit;
						}
						return Utils.makeUrl($location.absUrl(), search);
					};


					//
					// Sort.
					//

					// Compute columns list to give to the REST server.
					columnNames = [];
					angular.forEach(scope.columns, function (column) {
						if ( ! column.dummy ) {
							columnNames.push(column.name);
						}
						if (column.tags) {
							columnNames.push('tags');
						}

						if (column.format === 'date') {
							scope.dateDisplay[column.name] = '';
						}

					});
					if (attrs.useProperties) {
						angular.forEach(attrs.useProperties.split(/[\s,]+/), function (name) {
							if (columnNames.indexOf(name) === -1) {
								columnNames.push(name);
							}
						});
					}

					function getDefaultSortColumn () {
						return attrs.defaultSortColumn || 'modificationDate';
					}

					function getDefaultSortDir () {
						return attrs.defaultSortDir || (getDefaultSortColumn() === 'modificationDate' ? 'desc' : 'asc');
					}

					scope.sort =  {
						'column'     : getDefaultSortColumn(),
						'descending' : getDefaultSortDir() === 'desc'
					};
					scope.localSortColumn = null;

					scope.headerUrl = function (sortProperty) {
						var search = angular.copy($location.search());
						search.sort = sortProperty;
						if (scope.sort.column === sortProperty) {
							search.desc = ! scope.sort.descending;
						} else {
							search.desc = false;
						}
						return Utils.makeUrl($location.absUrl(), search);
					};

					scope.isSortable = function (columnName) {
						return ArrayUtils.inArray(columnName, scope.sortable) !== -1 || (scope.columns[columnName] && scope.columns[columnName].localSort === 'true');
					};

					scope.isSortedOn = function (columnName) {
						if (scope.localSortColumn !== null) {
							return scope.localSortColumn === ('+' + columnName) || scope.localSortColumn === ('-' + columnName);
						}
						return scope.sort.column === columnName;
					};

					scope.isSortDescending = function () {
						if (scope.localSortColumn !== null) {
							return scope.localSortColumn.charAt(0) === '-';
						}
						return scope.sort.descending;
					};

					scope.toggleLocalSort = function (columnName) {
						if (scope.localSortColumn === ('+' + columnName)) {
							scope.localSortColumn = '-' + columnName;
						} else {
							scope.localSortColumn = '+' + columnName;
						}
					};

					scope.clearLocalSort = function () {
						scope.localSortColumn = null;
					};


					//
					// Resources loading.
					//

					function setExternalCollection (collection) {
						if (angular.isObject(collection) && collection.pagination && collection.resources) {
							documentCollectionLoadedCallback(collection);
							scope.disablePagination = false;
						} else {
							replaceCollection(collection);
							scope.disablePagination = true;
						}
					}

					var useExternalCollection = elm.is('[collection]');
					if (useExternalCollection) {
						// External collection may be already here, if defined directly in the scope.
						if (scope.externalCollection) {
							setExternalCollection(scope.externalCollection);
						}
						scope.$watch('externalCollection', function (collection, oldCollection) {
							if (collection !== oldCollection) {
								if (angular.isObject(collection) && collection.pagination && collection.resources) {
									documentCollectionLoadedCallback(collection);
								} else {
									replaceCollection(collection);
								}
							}
						}, true);
					}

					function replaceCollection (collection) {
						ArrayUtils.clear(scope.collection);
						ArrayUtils.append(scope.collection, collection);
					}

					function documentCollectionLoadedCallback (response) {
						// We are loading a collection, so we can tell the Breadcrumb that there is
						// no end-resource to display.
						Breadcrumb.setResource(null);

						// Available sortable columns
						scope.sortable = response.pagination.availableSort || [];
						if (scope.hasColumn('nodeOrder')) {
							scope.sortable.push('nodeOrder');
						}

						scope.pagination.total = response.pagination.count;

						replaceCollection(response.resources);

						scope.$broadcast('Change:DocumentListChanged', scope.collection);
					}


					function reload () {
						if (useExternalCollection) {
							scope.onReload();
							return;
						}

						var promise, params;

						scope.busy = true;

						params = {
							'offset' : scope.pagination.offset,
							'limit'  : scope.pagination.limit,
							'sort'   : scope.sort.column,
							'desc'   : scope.sort.descending,
							'column' : columnNames
						};

						// TODO Reorganize this to use a query for tree and/or tag

						if (angular.isObject(queryObject) && angular.isObject(queryObject.where)) {
							Loading.start();
							promise = REST.query(prepareQueryObject(queryObject), {'column': columnNames});
						} else if (attrs.tree) {
							Loading.start();
							promise = REST.treeChildren(Breadcrumb.getCurrentNode(), params);
						} else {
							if (attrs.childrenProperty) {
								var currentNode = Breadcrumb.getCurrentNode();
								if (currentNode) {
									var children = currentNode[attrs.childrenProperty];
									documentCollectionLoadedCallback({
										'resources' : children,
										'pagination': {
											'count' : children.length
										}
									});
								} else {
									Loading.start();
									if (attrs.collectionUrl) {
										promise = REST.collection(scope.collectionUrl, params);
									} else if (attrs.model) {
										promise = REST.collection(attrs.model, params);
									}
								}
							} else if (! attrs.parentProperty) {
								Loading.start();
								if (scope.currentFilter) {
									var query = {
										"model" : attrs.model,
										"where" : {
											"and" : []
										}
									};
									$rootScope.$broadcast('Change:DocumentList.ApplyFilter', {
										"filter" : scope.currentFilter,
										"predicates" : query.where.and
									});
									promise = REST.query(prepareQueryObject(query), {'column': columnNames});
								} else {
									if (attrs.collectionUrl) {
										promise = REST.collection(scope.collectionUrl, params);
									} else if (attrs.model) {
										promise = REST.collection(attrs.model, params);
									}
								}
							}
						}

						if (promise) {
							promise.then(
								function (response) {
									stopLoading();
									if (response !== null) {
										documentCollectionLoadedCallback(response);
									}
								},
								function (reason) {
									stopLoading(reason);
								}
							);
							return promise;
						}

						return null;
					}


					function stopLoading (reason) {
						scope.busy = false;
						Loading.stop();
						if (reason) {
							NotificationCenter.error(i18n.trans('m.rbs.admin.admin.js.loading-list-error | ucf'), reason);
						} else {
							NotificationCenter.clear();
						}
					}


					scope.location = $location;
					currentPath = scope.location.path();

					scope.$watch('location.search()', function locationSearchFn (search) {

						// Are we leaving this place?
						if (currentPath !== scope.location.path()) {
							// If yes, there is nothing to do.
							return;
						}

						var	offset = parseInt(search.offset || 0, 10),
							limit  = search.limit ? parseInt(search.limit, 10) : Settings.get('pagingSize', PAGINATION_DEFAULT_LIMIT),
							paginationChanged, sortChanged = false,
							desc = (search.desc === 'true'),
							filter = search.filter,
							filterChanged = scope.currentFilter !== filter;

						paginationChanged = scope.pagination.offset !== offset || scope.pagination.limit !== limit;
						scope.pagination.offset = offset;
						scope.pagination.limit = limit;

						if (search.sort) {
							sortChanged = scope.sort.column !== search.sort;
							scope.sort.column = search.sort;
							if (desc !== scope.sort.descending) {
								sortChanged = true;
								scope.sort.descending = desc;
							}
						}

						scope.currentFilter = filter;

						if (paginationChanged || sortChanged || filterChanged) {
							reload();
						}

					}, true);


					function buildQueryParentProperty () {
						var currentNode = Breadcrumb.getCurrentNode();
						if (angular.isObject(currentNode)) {
							queryObject = {
								"model" : attrs.model,
								"where" : {
									"and" : [{
										"op" : "eq",
										"lexp" : { "property" : attrs.parentProperty },
										"rexp" : { "value"    : currentNode.id }
									}]
								}
							};
							reload();
						}
					}

					if (elm.is('[collection-url]')) {
						attrs.$observe('collectionUrl', function () {
							if (scope.collectionUrl) {
								reload();
							}
						});
					}


					//---------------------------------------------------------
					//
					// Converters
					//
					//---------------------------------------------------------


					function initializeConverters () {
						Loading.start("Initializing converters...");
						var promises = [];
						scope.convertersValues = {};

						scope.getConvertedValue = function (value, columnName) {
							if (value) {
								var converter = scope.columns[columnName].converter;
								if (scope.convertersValues[converter] && scope.convertersValues[converter][value]) {
									return scope.convertersValues[converter][value];
								}
								return '[' + value + ']';
							}
							return '';
						};

						forEach(scope.columns, function (column) {
							var	conv = column.converter,
								params = column.converterParams;
							if (conv) {
								if (conv === 'object' && /^{.*}$/.test(params)) {
									scope.convertersValues[conv] = scope.$eval(params);
								} else {
									scope.convertersValues[conv] = {};
									$rootScope.$broadcast(Events.DocumentListConverterGetValues, {
										"converter" : conv,
										"params"    : column.converterParams,
										"promises"  : promises,
										"values"    : scope.convertersValues[conv]
									});
								}
							}
						});

						function errorFn (error) {
							successFn();
						}

						function successFn () {
							Loading.stop();

							if (elm.is('[model]')) {
								// No model value yet?
								if (attrs.model) {
									initialLoad();
								}
								else {
									attrs.$observe('model', function (model) {
										if (model) {
											initialLoad();
										}
									});
								}
							}
							else {
								initialLoad();
							}
						}

						if (promises.length) {
							$q.all(promises).then(successFn, errorFn);
						} else {
							successFn();
						}
					}

					initializeConverters();


					//---------------------------------------------------------
					//
					// Initial load.
					//
					//---------------------------------------------------------

					function initialLoad () {
						if (scope.externalCollection) {
							return;
						}
						if (attrs.tree) {
							// If in a tree context, reload the list when the Breadcrumb is ready
							// and each time it changes.
							Breadcrumb.ready().then(function () {
								reload();
								scope.$on('Change:TreePathChanged', function () {
									reload();
								});
							});
						} else if (attrs.parentProperty) {
							Breadcrumb.ready().then(function () {
								buildQueryParentProperty();
								scope.$on('Change:TreePathChanged', function () {
									buildQueryParentProperty();
								});
							});
						} else if (attrs.childrenProperty) {
							Breadcrumb.ready().then(function () {
								reload();
								scope.$on('Change:TreePathChanged', function () {
									reload();
								});
							});
						} else {
							// Not in a tree.
							var search = $location.search();
							// If one of "load-query" or "collection-url" attribute is present,
							// the list should not be loaded now: it will be when these objects are $watched.
							// And it works the same way with "sort" parameter in the URL:
							// the $watch() on $location.search() will load the query.
							if (! elm.is('[load-query]') && ! elm.is('[collection-url]') && ! search['sort'] && ! search['filter']) {
								reload();
							}
						}
					}


					// Query

					function prepareQueryObject (query) {
						// Sort by "label" instead of "nodeOrder" when sending a query (search).
						if (scope.sort.column === 'nodeOrder') {
							scope.sort.column = 'label';
							scope.sort.descending = 'asc';
						}
						query.offset = scope.pagination.offset;
						query.limit  = scope.pagination.limit;
						if (!angular.isObject(query.order))
						{
							query.order  = [
								{
									'property' : scope.sort.column,
									'order'    : scope.sort.descending ? 'desc' : 'asc'
								}
							];
						}
						if (attrs.model) {
							query.model = attrs.model;
						}
						return query;
					}


					function watchQueryFn (query) {
						if (! angular.equals(query, queryObject)) {
							queryObject = angular.copy(query);
							reload();
						}
					}

					scope.$watch('filterQuery', watchQueryFn, true);
					scope.$watch('loadQuery', watchQueryFn, true);

					var lastQuickActionsShown = null;
					if (Device.isMultiTouch()) {
						scope.showQuickActions = function ($index) {
							if (lastQuickActionsShown) {
								lastQuickActionsShown.removeClass('shown').css({'width':'0'});
							}
							var target = elm.find('.document-list tbody tr').get($index);
							lastQuickActionsShown = $(target).find('.quick-actions-touch');
							lastQuickActionsShown.addClass('shown').css({'width': lastQuickActionsShown.attr('data-real-width')});
						};

						scope.hideQuickActions = function ($index) {
							var target = elm.find('.document-list tbody tr').get($index);
							var el = $(target).find('.quick-actions-touch');
							if (el.is('.shown')) {
								el.removeClass('shown').css({'width':'0'});
								lastQuickActionsShown = null;
							}
						};
					}


					scope.toggleRelativeDates = function (column) {
						scope.dateDisplay[column] = scope.dateDisplay[column] === 'relative' ? '' : 'relative';
					};


					scope.setBusy = function () {
						scope.busy = true;
					};

					scope.setNotBusy = function () {
						scope.busy = false;
					};

					scope.hasErrors = function (doc) {
						return scope.extend
							&& angular.isFunction(scope.extend.getDocumentErrors)
							&& scope.extend.getDocumentErrors(doc) !== null;
					};

				};

			}

		};

	}


	app.directive('column', ['rbsThumbnailSizes', function (sizes) {

		return {
			restrict : 'E',
			require  : '^rbsDocumentList',

			compile : function (tElement, tAttrs) {

				var content, dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}

				content = tElement.html().trim();
				if (content.length) {
					tAttrs.content = content;
				}

				if (tAttrs.thumbnail) {
					tAttrs.thumbnail = angular.lowercase(tAttrs.thumbnail);
					if (sizes[tAttrs.thumbnail]) {
						tAttrs.thumbnail = sizes[tAttrs.thumbnail];
					}
					if (/^\d+x\d+$/.test(tAttrs.thumbnail)) {
						var dim = tAttrs.thumbnail.split('x');
						tAttrs.maxWidth = dim[0];
						tAttrs.maxHeight = dim[1];
					} else {
						tAttrs.maxWidth = '100';
						tAttrs.maxHeight = '100';
					}
					if (!tAttrs.width) {
						tAttrs.width = tAttrs.maxWidth + 'px';
					}
					if (!tAttrs.align) {
						tAttrs.align = 'center';
					}
				}


				if (!__columns.hasOwnProperty(dlid)) {
					__columns[dlid] = [];
				}
				__columns[dlid].push(tAttrs);

			}
		};

	}]);


	app.directive('gridItem', [function () {

		return {
			restrict : 'E',
			require  : '^rbsDocumentList',

			compile : function (tElement, tAttrs) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}

				tAttrs.content = tElement.html().trim();
				__gridItems[dlid] = tAttrs;

			}
		};

	}]);


	app.directive('preview', [function () {

		return {
			restrict : 'E',
			require  : '^rbsDocumentList',

			compile : function (tElement, tAttrs) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}
				__preview[dlid] = angular.extend({}, tAttrs, {'contents': tElement.html().trim()});

			}
		};

	}]);


	app.directive('quickActions', [function () {

		return {
			restrict : 'E',
			require  : '^rbsDocumentList',

			compile : function (tElement, tAttrs) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}
				__quickActions[dlid] = angular.extend({}, tAttrs, {'contents': tElement.html().trim()});

			}
		};

	}]);



	app.directive('action', [function () {

		return {
			restrict : 'E',
			require  : '^rbsDocumentList',

			compile : function (tElement, tAttrs) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}

				if (!__actions.hasOwnProperty(dlid)) {
					__actions[dlid] = [];
				}
				__actions[dlid].push(tAttrs);
			}
		};

	}]);


})(window.jQuery);
