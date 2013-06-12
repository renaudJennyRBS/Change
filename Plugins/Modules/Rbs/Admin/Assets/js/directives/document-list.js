/**
 * User: fredericbonjour
 * Date: 24/05/13
 * Time: 09:44
 */
(function ($) {

	"use strict";

	var	app = angular.module('RbsChange'),
		__columns = {},
		__preview = {},
		__gridItems = {},
		// FIXME: Hard-coded values here.
		PAGINATION_DEFAULT_LIMIT = 20,
		PAGINATION_PAGE_SIZES = [ 2, 5, 10, 15, 20, 30, 50 ],
		DEFAULT_ACTIONS = 'activate reorder delete(icon)';

	app.directive('documentList', [
		'$filter',
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
		documentListDirectiveFn
	]);



	function documentListDirectiveFn ($filter, $location, i18n, REST, Loading, Utils, ArrayUtils, Breadcrumb, Actions, NotificationCenter, Device) {

		/**
		 * Initialize columns for <document-list/>
		 * @param dlid
		 * @param tElement
		 * @param tAttrs
		 * @returns {Array}
		 */
		function initColumns (dlid, tElement, tAttrs) {
			var	columns, undefinedColumnLabels = [], column,
				$th, $td, $head, $body, html;

			columns = __columns[dlid];
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
					"content": "(=doc.modificationDate | date:'medium' =)",
					"width"  : "150px"
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

			if ( ! __preview[dlid] ) {
				__preview[dlid] = '<span ng-if="isPreviewReady(doc)" ng-bind-html-unsafe="doc.document | documentProperties:doc.modelInfo"></span>';
			}
			tElement.find('tbody tr.preview-row td.preview').html(__preview[dlid]);

			while (columns.length) {
				column = columns.shift(0);
				tElement.data('columns')[column.name] = column;

				// Check if the label has been provided or not.
				// If one at least label has not been provided, the Model's information will be
				// loaded to automatically set the columns' header text.
				if ( ! column.label ) {
					undefinedColumnLabels.push(column.name);
				}

				// Create header cell
				if (column.name === 'selectable') {
					$th = $('<th ng-if="!isSortable(\'' + column.name + '\')">' + column.label + '</th>');
				} else {
					$th = $(
						'<th ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}" ng-if="isSortable(\'' + column.name + '\')">' +
							'<a href ng-href="(= headerUrl(\'' + column.name + '\') =)" ng-bind-html-unsafe="columns.' + column.name + '.label">' + column.name + '</a>' +
							'<i class="column-sort-indicator" ng-class="{true:\'icon-sort-down\', false:\'icon-sort-up\'}[sort.descending]" ng-show="isSortedOn(\'' + column.name + '\')"></i>' +
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
					$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}">' + column.content + '</td>');
				} else {
					if (column.thumbnail) {
						if (column.thumbnailPath) {
							column.content = '<img rbs-storage-image="(= ' + column.thumbnailPath + ' =)" thumbnail="' + column.thumbnail + '"/>';
						} else {
							column.content = '<img rbs-storage-image="(= doc.' + column.name + ' =)" thumbnail="' + column.thumbnail + '"/>';
						}
					} else {
						column.content = '(= doc["' + column.name + '"] =)';
					}
					if (column.primary) {
						if (tAttrs.tree) {
							$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}"><div style="position:relative"><a href ng-href="(= doc | documentURL:\'tree\' =)"><strong>' + column.content + '</strong></a></div></td>');
						} else {
							$td = $('<td ng-class="{\'sorted\':isSortedOn(\'' + column.name + '\')}"><div style="position:relative"><a href ng-href="(= doc | documentURL =)"><strong>' + column.content + '</strong></a></div></td>');
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
					html =
						'<div class="quick-actions' + (Device.touch ? '-mobile' : '') + '">' +
							'<a href="javascript:" ng-click="preview(doc)">' +
							//'<i ng-if="!isPreviewReady(doc)" class="icon-spinner icon-spin"></i>' +
							//'<i ng-if="isPreviewReady(doc)" ng-class="{true: \'icon-circle-arrow-up\', false: \'icon-circle-arrow-down\'}[hasPreview(doc)]"></i>' +
							' ' + i18n.trans('m.rbs.admin.admin.js.preview') +
							'</a>';
					if (!tAttrs.picker) {
						html +=
							' | <a href data-ng-href="(= doc | documentURL =)">' +
								i18n.trans('m.rbs.admin.admin.js.edit') +
								'</a> | ' +
								'<a href="javascript:;" class="danger" ng-click="remove(doc, $event)">' +
								i18n.trans('m.rbs.admin.admin.js.delete') +
								'</a>';
					}
					html += '</div>';
					$td.find('div').first().prepend(html);

					if (column.tags === 'true') {
						$td.find('div').first().append('<br/><div class="tags"><span class="tag red">image</span><span class="tag">logo</span><span class="tag green">marque</span></div>');
					}
				}

				$body.append($td);
			}

			if (Device.touch) {
				$body.attr('ng-swipe-left', "showQuickActions($event)");
				$body.attr('ng-swipe-right', "hideQuickActions($event)");
			}

			delete __columns[dlid];

			return undefinedColumnLabels;
		}


		/**
		 * Initialize grid mode for <document-list/>
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
				inner.html(__gridItems[dlid].content);
			}

			delete __gridItems[dlid];

			return gridModeAvailable;
		}


		/**
		 * <document-list/> directive.
		 */
		return {
			restrict    : 'E',
			transclude  : true,
			templateUrl : 'Rbs/Admin/js/directives/document-list.twig',

			scope : {
				// Isolated scope.
				query  : '=',
				picker : '='
			},


			compile : function (tElement, tAttrs) {

				var	dlid, undefinedColumnLabels, gridModeAvailable;

				dlid = tElement.data('dlid');
				if (!dlid) {
					throw new Error("DocumentList must have a unique 'data-dlid' attribute.");
				}

				undefinedColumnLabels = initColumns(dlid, tElement, tAttrs);

				gridModeAvailable = initGrid(dlid, tElement);


				/**
				 * Directive's link function.
				 */
				return function linkFn (scope, elm, attrs) {
					var queryObject, search, columnNames, currentPath;

					scope.collection = [];
					scope.gridModeAvailable = gridModeAvailable;
					scope.viewMode = 'list';
					scope.columns = elm.data('columns');
					scope.embeddedActionsOptionsContainerId = 'embeddedActionsOptionsContainerId';
					scope.$DL = scope;

					// Load Model's information and update the columns' header with the correct property label.
					if (undefinedColumnLabels.length && attrs.model) {
						REST.modelInfo(attrs.model).then(function (modelInfo) {
							angular.forEach(undefinedColumnLabels, function (columnName) {
								scope.columns[columnName].label = modelInfo.properties[columnName].label;
							});
						});
					}


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

					scope.selectedDocuments = $filter('filter')(scope.collection, {'selected': true});

					scope.$watch('collection', function () {
						scope.selectedDocuments = $filter('filter')(scope.collection, {'selected': true});
					}, true);


					//
					// Actions.
					//


					scope.hasColumn = function (columnName) {
						return angular.isObject(elm.data('columns')[columnName]);
					};


					scope.actions = [];
					var actionList = attrs.actions;
					if (! actionList) {
						actionList = 'default';
					}
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


					scope.remove = function (doc, $event) {
						Actions.execute("delete", {
							'$docs'   : [ doc ],
							'$target' : $event.target,
							'$scope'  : scope
						});
					};


					scope.isLastCreated = function (doc) {
						return REST.isLastCreated(doc);
					};


					//
					// Embedded preview.
					//


					scope.preview = function (index) {

						if (angular.isObject(index)) {
							index = scope.collection.indexOf(index);
						}

						if (scope.hasPreview(index)) {
							scope.collection.splice(index+1, 1);
							return;
						}

						var	current = scope.collection[index],
							preview = {
								"__dlPreview" : true
							};
						current.__dlPreviewLoading = true;

						Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-preview | ucf'));
						REST.resource(current).then(function (doc) {
							REST.modelInfo(doc).then(function (modelInfo) {
								delete current.__dlPreviewLoading;
								// Prevent META$ information from being overwritten.
								angular.extend(doc.META$, current.META$);
								preview.document = angular.extend(current, doc);
								preview.modelInfo = modelInfo;
								scope.collection.splice(index+1, 0, preview);
								Loading.stop();
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
						return (next && next.__dlPreview && next.document && next.document.id === current.id) ? true : false;
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

					function getDefaultSortColumn () {
						var defaultSortColumn = attrs.defaultSortColumn, i, c;
						for (i=0 ; i<columnNames.length && !defaultSortColumn; i++) {
							c = columnNames[i];
							if (c !== 'publicationStatus' && c !== 'selectable') {
								defaultSortColumn = c;
							}
						}
						return defaultSortColumn;
					}

					scope.sort =  {
						'column'     : getDefaultSortColumn(),
						'descending' : false
					};


					scope.headerUrl = function (sortProperty) {
						var search = angular.copy($location.search());
						search.sort = sortProperty;
						if (this.sort.column === sortProperty) {
							search.desc = ! this.sort.descending;
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

						if (angular.isObject(queryObject) && angular.isObject(queryObject.where)) {
							Loading.start();
							promise = REST.query(prepareQueryObject(queryObject), {'column': columnNames});
						} else if (attrs.tree) {
							Loading.start();
							promise = REST.treeChildren(Breadcrumb.getCurrentNode(), params);
						} else if (attrs.model && ! attrs.parentProperty) {
							Loading.start();
							promise = REST.collection(attrs.model, params);
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


					scope.reload = function () {
						reload();
					};


					scope.location = $location;
					currentPath = scope.location.path();
					scope.$watch('location.search()', function locationSearchFn (search) {

						// Are we leaving this place?
						if (currentPath !== scope.location.path()) {
							return;
						}

						var	offset = parseInt(search.offset || 0, 10),
							limit  = parseInt(search.limit || PAGINATION_DEFAULT_LIMIT, 10),
							paginationChanged, sortChanged = false,
							desc = (search.desc === 'true');

						paginationChanged = scope.pagination.offset !== offset || scope.pagination.limit !== limit;
						scope.pagination.offset = offset;
						scope.pagination.limit = limit;

						if (search.sort) {
							sortChanged = scope.sort.column !== search.sort;
							scope.sort.column = search.sort;
						}
						if (desc !== scope.sort.descending) {
							sortChanged = true;
						}
						scope.sort.descending = desc;

						if (paginationChanged || sortChanged) {
							console.log("reloading...");
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

					if (attrs.tree) {
						// If in a tree context, reload the list when the Breadcrumb is ready
						// and everytime it changes.
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
					} else {
						// Not in a tree? Just load the flat list.
						reload();
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
							queryObject.model = attrs.model;
						}
						return query;
					}


					scope.$watch('query', function (query, oldValue) {
						if (query !== oldValue) {
							queryObject = angular.copy(query);
							reload();
						} else if (angular.isDefined(query) || angular.isDefined(oldValue)) {
							reload();
						}
					}, true);


					var lastQuickActionsShown = null;
					if (Device.touch) {
						scope.showQuickActions = function ($event) {
							if (lastQuickActionsShown) {
								lastQuickActionsShown.removeClass('shown');
							}
							lastQuickActionsShown = $($event.target).find('.quick-actions-mobile');
							lastQuickActionsShown.addClass('shown');
						};

						scope.hideQuickActions = function ($event) {
							var el = $($event.target).find('.quick-actions-mobile');
							if (el.is('.shown')) {
								el.removeClass('shown');
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
			require  : '^documentList',

			compile : function (tElement, tAttrs) {

				var content, dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("DocumentList must have a unique 'data-dlid' attribute.");
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
			require  : '^documentList',

			compile : function (tElement, tAttrs) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("DocumentList must have a unique 'data-dlid' attribute.");
				}

				tAttrs.content = tElement.html().trim();
				__gridItems[dlid] = tAttrs;

			}
		};

	}]);


	app.directive('preview', [function () {

		return {
			restrict : 'E',
			require  : '^documentList',

			compile : function (tElement) {

				var dlid;

				dlid = tElement.parent().data('dlid');
				if (!dlid) {
					throw new Error("DocumentList must have a unique 'data-dlid' attribute.");
				}
				__preview[dlid] = tElement.html().trim();

			}
		};

	}]);

})(window.jQuery);