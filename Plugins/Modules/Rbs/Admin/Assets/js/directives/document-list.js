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
		// FIXME: Hard-coded values here.
		PAGINATION_DEFAULT_LIMIT = 20,
		PAGINATION_PAGE_SIZES = [ 10, 20, 30, 50, 75, 100 ],
		DEFAULT_ACTIONS = 'startValidation activate delete(icon)',
		testerEl = $('#rbs-document-list-tester'),
		forEach = angular.forEach;

	app.directive('rbsDocumentList', [
		'$q',
		'$filter',
		'$rootScope',
		'$location',
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
		'RbsChange.FormsManager',
		'RbsChange.Events',
		documentListDirectiveFn
	]);


	function documentListDirectiveFn ($q, $filter, $rootScope, $location, i18n, REST, Loading, Utils, ArrayUtils, Breadcrumb, Actions, NotificationCenter, Device, Settings, FormsManager, Events) {

		/**
		 * Build the HTML used in the "Quick actions" toolbar.
		 * @param dlid
		 * @param tAttrs
		 * @returns {string}
		 */
		function buildQuickActionsHtml (dlid, tAttrs) {
			var	actionDivider = '<span class="divider">|</span>',
				html,
				quickActionsHtml;

			html = '<div class="quick-actions ' + (Device.isMultiTouch() ? 'quick-actions-touch' : 'quick-actions-mouse') + '">';

			if (Device.isMultiTouch()) {
				html += '<i class="icon-chevron-left handle-open"></i><i class="icon-chevron-right handle-close"></i> ';
			}

			function buildDefault () {
				if (__preview[dlid]) {
					return buildPreviewAction() + actionDivider + buildEditAction() + actionDivider + buildDeleteAction();
				}
				return buildEditAction() + actionDivider + buildDeleteAction();
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
				return '<a href="javascript:" ng-click="preview(doc)"> ' + i18n.trans('m.rbs.admin.admin.js.preview') + '</a>';
			}

			if (__quickActions[dlid]) {
				if (__quickActions[dlid].divider) {
					actionDivider = __quickActions[dlid].divider;
				}
				quickActionsHtml = __quickActions[dlid].contents;
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
		 * Initialize columns for <rbs-document-list/>
		 * @param dlid
		 * @param tElement
		 * @param tAttrs
		 * @returns {Array}
		 */
		function initColumns (dlid, tElement, tAttrs, undefinedColumnLabels) {
			var	columns, column,
				$th, $td, $head, $body, html, p, td,
				result = {};

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
					"width"  : "30px",
					"label"  : i18n.trans('m.rbs.admin.admin.js.status | ucf'),
					"content": '<status ng-model="doc"/>',
					"dummy"  : true
				});
			}

			// Selectable column
			if (angular.isUndefined(tAttrs.selectable) || tAttrs.selectable === 'true') {
				columns.unshift({
					"name"   : "selectable",
					"align"  : "center",
					"width"  : "30px",
					"label"  : '<input type="checkbox" ng-model="allSelected.cb"/>',
					"content": '<input type="checkbox" ng-model="doc.selected"/>',
					"dummy"  : true
				});
			}

			// Order in tree column
			if (tAttrs.tree) {
				columns.push({
					"name"  : "nodeOrder",
					"align"  : "right",
					"width"  : "90px",
					"label"  : i18n.trans('m.rbs.admin.admin.js.order | ucf'),
					"content": '(=doc.META$.treeNode.nodeOrder | number=)'
				});
			}

			// Modification Date column
			if (angular.isUndefined(tAttrs.modificationDate) || tAttrs.modificationDate === 'true') {
				columns.push({
					"name"   : "modificationDate",
					"label"  : i18n.trans('m.rbs.admin.admin.js.modification-date | ucf'),
					"format" : "date"
				});
			}

			// Status switch column
			if (tAttrs.publishable === 'true') {
				columns.push({
					"name"   : "publicationStatusSwitch",
					"align"  : "center",
					"width"  : "90px",
					"label"  : i18n.trans('m.rbs.admin.admin.js.activated | ucf'),
					"content": '<status-switch document="doc"/>',
					"dummy"  : true
				});
			}

			tElement.data('columns', {});

			// Update colspan value for preview and empty cells.
			tElement.find('tbody td[colspan=0]').attr('colspan', columns.length);
			tElement.find('tbody td[colspan=0]').attr('colspan', columns.length);

			if (__preview[dlid]) {
				td = tElement.find('tbody tr td.preview');
				if (__preview[dlid]['class']) {
					td.addClass(__preview[dlid]['class']);
				}
				if (__preview[dlid]['style']) {
					td.attr('style', __preview[dlid]['style']);
				}
				td.html('<button type="button" class="close pull-right" ng-click="preview(doc)">&times;</button>' + __preview[dlid].contents);
			}

			while (columns.length) {
				column = columns.shift(0);

				p = column.name.indexOf('.');
				if (p === -1) {
					column.valuePath = column.name;
				} else {
					column.valuePath = column.name;
					column.name = column.name.substring(0, p);
				}

				switch (column.format) {

				case 'number' :
					column.valuePath += '|number';
					if (!column.align) {
						column.align = 'right';
					}
					break;

				case 'date' :
					column.valuePath += "|rbsDateTime";
					if (!column.width) {
						column.width = "150px";
					}
					break;

				}

				result[column.name] = column;

				// Check if the label has been provided or not.
				// If one at least label has not been provided, the Model's information will be
				// loaded to automatically set the columns' header text.
				if ( ! column.label ) {
					undefinedColumnLabels.push(column.name);
				}

				// Create header cell
				if (column.name === 'selectable') {
					$th = $('<th>' + column.label + '</th>');
				} else {
					$th = $(
						'<th ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}" ng-if="isSortable(\'' + column.name + '\')">' +
							'<a href ng-href="(= headerUrl(\'' + column.name + '\') =)" ng-bind-html-unsafe="columns.' + column.name + '.label">' + column.name + '</a>' +
							'<i class="column-sort-indicator" ng-class="{true:\'icon-sort-down\', false:\'icon-sort-up\'}[sort.descending]" ng-if="isSortedOn(\'' + column.name + '\')"></i>' +
							'<i class="column-sort-indicator icon-sort" ng-if="!isSortedOn(\'' + column.name + '\')"></i>' +
						'</th>' +
						'<th ng-if="!isSortable(\'' + column.name + '\')" ng-bind-html-unsafe="columns.' + column.name + '.label">' + column.name + '</th>'
					);
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
					html = '<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}">';
					if (column.primary) {
						html += '<div class="primary-cell">' + column.content + '</div>';
					} else {
						html += column.content;
					}
					html += '</td>';
					$td = $(html);
				} else {
					if (column.thumbnail) {
						if (column.thumbnailPath) {
							column.content = '<img rbs-storage-image="' + column.thumbnailPath + '" thumbnail="' + column.thumbnail + '"/>';
						} else {
							column.content = '<img rbs-storage-image="doc.' + column.valuePath + '" thumbnail="' + column.thumbnail + '"/>';
						}
					} else {
						if (column.converter) {
							column.content = '(= getConvertedValue(doc.' + column.valuePath + ', "' + column.converter + '", "' + column.converterParams + '") =)';
						} else {
							column.content = '(= doc.' + column.valuePath + ' =)';
						}
					}
					if (column.primary) {
						if (tAttrs.tree) {
							$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}"><div class="primary-cell"><a href ng-href="(= doc | documentURL:\'tree\' =)" title="Naviguer vers..."><strong>' + column.content + '</strong></a> <i class="icon-rbs-navigate-to"></i></div></td>');
						} else {
							if (tAttrs.cascadeEdition) {
								$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}"><div class="primary-cell"><a href="javascript:;" ng-click="cascadeEdit(doc)"><strong>' + column.content + '</strong></a></div></td>');
							} else {
								$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}"><div class="primary-cell"><a href ng-href="(= doc | documentURL =)"><strong>' + column.content + '</strong></a></div></td>');
							}
						}
					} else {
						$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}">' + column.content + '</td>');
					}
				}

				if (column.align) {
					$td.css({
						'text-align': column.align,
						'position'  : 'relative'
					});
				}


				// The primary column has extra links for preview, edit and delete.
				if (column.primary) {
					html = buildQuickActionsHtml(dlid, tAttrs);
					if (html !== null) {
						testerEl.html(html);
						$td.find('.primary-cell').prepend(html);

						// Compute real width so that the CSS animation can work correctly when
						// opening this menu.
						// (see showQuickActions() below, in the directive's linking function).
						$td.find('.primary-cell .quick-actions').attr('data-real-width', (testerEl.outerWidth())+'px');
					}
				}

				$td.attr('ng-if', "! isPreview(doc)");
				$body.append($td);
			}

			if (Device.isMultiTouch()) {
				$body.attr('ng-swipe-left', "showQuickActions($event)");
				$body.attr('ng-swipe-right', "hideQuickActions($event)");
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
				'cascadeEdition' : '@',
				'collectionUrl' : '@',
				'extend' : '='
			},


			/**
			 * Directive's compile function:
			 * collect columns definition and templates for columns, grid items and preview.
			 */
			compile : function (tElement, tAttrs) {

				var	dlid, undefinedColumnLabels = [], gridModeAvailable, columns;

				dlid = tElement.data('dlid');
				if (!dlid) {
					throw new Error("<rbs-document-list/> must have a unique and not empty 'data-dlid' attribute.");
				}

				columns = initColumns(dlid, tElement, tAttrs, undefinedColumnLabels);

				gridModeAvailable = initGrid(dlid, tElement);

				/**
				 * Directive's link function.
				 */
				return function linkFn (scope, elm, attrs) {
					var queryObject, search, columnNames, currentPath;

					scope.collection = [];
					scope.gridModeAvailable = gridModeAvailable;
					if (attrs.display) {
						scope.viewMode = attrs.display;
					} else {
						scope.viewMode = gridModeAvailable ? Settings.get('documentListViewMode', 'grid') : 'list';
					}
					scope.columns = columns;
					scope.embeddedActionsOptionsContainerId = 'embeddedActionsOptionsContainerId';
					scope.$DL = scope; // TODO Was used by "bind-action" directive. Still needed?
					scope.useToolBar = attrs.toolbar === 'false' ? false : true;


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
								else {
									console.warn('[Rbs/Admin/Assets/js/directives/document-list.js] ' + columnName + ' does not exist in model infos properties!');
								}
							});
						});
					}

					// The list listens to this event: 'Change:DocumentList:<dlid>:call'
					var self = this;
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
							if (angular.isFunction(result.then)) {
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
						FormsManager.cascadeEditor(
							doc,
							scope.cascadeEdition
						);
					};

					scope.cascadeDuplicate = function (doc) {
						REST.resource(doc).then(function (fullDoc) {
								FormsManager.cascadeEditor(
									Utils.duplicateDocument(fullDoc),
									scope.cascadeEdition
								);
							}
						);
					};

					//
					// Document selection.
					//


					scope.allSelected = {
						'cb' : false
					};
					scope.$watch('allSelected', function (value) {
						angular.forEach(scope.collection, function (doc) {
							doc.selected = scope.allSelected.cb;
						});
					}, true);

					function updateSelectedDocuments () {
						scope.selectedDocuments = $filter('filter')(scope.collection, {'selected': true});
					}

					scope.$watch('collection', updateSelectedDocuments, true);
					updateSelectedDocuments();


					//
					// Actions.
					//


					scope.hasColumn = function (columnName) {
						return angular.isObject(scope.columns[columnName]);
					};


					scope.actions = [];
					var actionList = elm.is('[actions]') ? attrs.actions : 'default';
					if (actionList.length) {
						actionList = actionList.replace('default', DEFAULT_ACTIONS);
						if (! scope.hasColumn('nodeOrder') ) {
							actionList = actionList.replace('nodeOrder', '');
						}
						angular.forEach(actionList.split(/ +/), function (action) {
							scope.actions.push({
								"type" : "single",
								"name" : action
							});
						});
					}


					scope.executeAction = function (actionName, doc, $event) {
						return Actions.execute(actionName, {
							'$docs'   : [ doc ],
							'$target' : $event.target,
							'$scope'  : scope
						});
					};


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


					scope.preview = function (index) {
						if (angular.isObject(index)) {
							if (scope.isPreview(index)) {
								ArrayUtils.removeValue(scope.collection, index);
								return;
							}
							index = scope.collection.indexOf(index);
						}

						var	current = scope.collection[index];

						if (scope.hasPreview(index)) {
							scope.collection.splice(index+1, 1);
							return;
						}

						current.__dlPreviewLoading = true;

						Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-preview | ucf'));
						REST.resource(current).then(function (doc) {
							REST.modelInfo(doc).then(function (modelInfo) {
								var previewPromises = [];
								delete current.__dlPreviewLoading;
								// Copy the current's META$ information into the newly loaded document.
								angular.extend(doc.META$, current.META$);
								doc.__dlPreview = true;
								doc.META$.modelInfo = modelInfo;

								$rootScope.$broadcast(Events.DocumentListPreview, {
									"document" : doc,
									"promises" : previewPromises
								});

								function terminatePreview () {
									scope.collection.splice(index+1, 0, doc);
									Loading.stop();
								}

								if (previewPromises.length) {
									$q.all(previewPromises).then(terminatePreview);
								} else {
									terminatePreview();
								}
							});
						});

					};


					scope.isPreview = function (doc) {
						return doc && doc.__dlPreview === true;
					};


					scope.isPreviewReady = function (doc) {
						return ! doc || ! doc.__dlPreviewLoading;
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
						limit  : search.limit || PAGINATION_DEFAULT_LIMIT,
						total  : 0
					};

					scope.predefinedPageSizes = PAGINATION_PAGE_SIZES;
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
							nbPages = Math.ceil(scope.pagination.total / scope.pagination.limit);
							if (nbPages > 11) {
								for (i=0 ; i<5 ; i++) {
									scope.pages.push(i);
								}
								scope.pages.push('...');
								for (i=nbPages-5 ; i<nbPages ; i++) {
									scope.pages.push(i);
								}
							} else {
								for (i=0 ; i<nbPages ; i++) {
									scope.pages.push(i);
								}
							}
							scope.currentPage = scope.pagination.offset / scope.pagination.limit;
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
						return attrs.defaultSortDir || 'desc';
					}

					scope.sort =  {
						'column'     : getDefaultSortColumn(),
						'descending' : getDefaultSortDir() === 'desc'
					};

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
						return ArrayUtils.inArray(columnName, scope.sortable) !== -1;
					};


					scope.isSortedOn = function (columnName) {
						return scope.sort.column === columnName;
					};


					//
					// Resources loading.
					//


					function documentCollectionLoadedCallback (response) {
						// We are loading a collection, so we can tell the Breadcrumb that there is
						// no end-resource to display.
						Breadcrumb.setResource(null);

						// Available sortable columns
						// FIXME: remove default value here when the server sends this info.
						scope.sortable = response.pagination.availableSort || ['label', 'modificationDate'];
						if (scope.hasColumn('nodeOrder')) {
							scope.sortable.push('nodeOrder');
						}

						scope.pagination.total = response.pagination.count;
						scope.collection = response.resources;

						scope.$broadcast('Change:DocumentListChanged', scope.collection);
					}


					function reload () {
						var promise, params;

						scope.loading = true;

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
						} else if (attrs.model) {
							if (attrs.childrenProperty) {
								console.log("attrs.childrenProperty=", attrs.childrenProperty);
								var currentNode = Breadcrumb.getCurrentNode();
								console.log("currentNode=", currentNode);
								if (currentNode) {
									var children = currentNode[attrs.childrenProperty];
									console.log("children=", children);
									documentCollectionLoadedCallback({
										'resources' : children,
										'pagination': {
											'count' : children.length
										}
									});
								} else {
									Loading.start();
									if (attrs.collectionUrl) {
										promise = REST.collection(attrs.collectionUrl, params);
									} else {
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
										promise = REST.collection(attrs.collectionUrl, params);
									} else {
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
						scope.loading = false;
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
							limit  = parseInt(search.limit || PAGINATION_DEFAULT_LIMIT, 10),
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
						attrs.$observe('collectionUrl', function (value) {
							if (value) {
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

						scope.getConvertedValue = function (value, converter) {
							if (value) {
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
							console.error(error);
							successFn();
						}

						function successFn () {
							Loading.stop();
							initialLoad();
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
							if (! elm.is('[load-query]') && ! elm.is('[collection-url]') && ! search['sort']) {
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
						query.order  = [
							{
								'property' : scope.sort.column,
								'order'    : scope.sort.descending ? 'desc' : 'asc'
							}
						];
						if (attrs.model) {
							query.model = attrs.model;
						}
						return query;
					}


					function watchQueryFn (query, oldValue) {
						if (query !== oldValue) {
							queryObject = angular.copy(query);
							reload();
						} else if (angular.isDefined(query) || angular.isDefined(oldValue)) {
							reload();
						}
					}

					scope.$watch('filterQuery', watchQueryFn, true);
					scope.$watch('loadQuery', watchQueryFn, true);


					var lastQuickActionsShown = null;
					if (Device.isMultiTouch()) {
						scope.showQuickActions = function ($event) {
							if (lastQuickActionsShown) {
								lastQuickActionsShown.removeClass('shown').css({'width':'0'});
							}
							lastQuickActionsShown = $($event.target).closest('tr').find('.quick-actions-touch');
							lastQuickActionsShown.addClass('shown').css({'width': lastQuickActionsShown.attr('data-real-width')});
						};

						scope.hideQuickActions = function ($event) {
							var el = $($event.target).closest('tr').find('.quick-actions-touch');
							if (el.is('.shown')) {
								el.removeClass('shown').css({'width':'0'});
								lastQuickActionsShown = null;
							}
						};
					}
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

})(window.jQuery);