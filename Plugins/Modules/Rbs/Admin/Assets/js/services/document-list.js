(function ($) {

	var app = angular.module('RbsChange');


	/**
	 * This service initializes the given $scope by attaching data and methods to provide all the features
	 * required to display a filtered and paginated list of documents.
	 *
	 * Inside your *ListController:
	 * <code>var DL = DocumentList.initScope($scope)</code>
	 * A 'DL' object is then created inside the $scope, and is refered to as 'DL' in your controller (see 'var DL =').
	 * In the template, you can then use 'DL':
	 * <code>DL.fiteredDocuments</code>
	 */
	app.service('RbsChange.DocumentList',
			['$routeParams', '$filter', 'RbsChange.Actions', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.Settings', 'RbsChange.FormsManager', '$q', '$location', '$http', 'RbsChange.i18n', '$timeout', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.Breadcrumb', 'RbsChange.NotificationCenter',
			 function ($routeParams, $filter, Actions, ArrayUtils, Utils, Settings, FormsManager, $q, $location, $http, i18n, $timeout, Loading, REST, Breadcrumb, NotificationCenter) {

		var DocumentList = this;

		this.Column = function (id, label, sortable, align, width) {
			var column = {
				'id': id,
				'label': label
			};
			column.sortable = (angular.isUndefined(sortable)) ? true : sortable;
			if (align) {
				column.align = align;
			}
			if (width) {
				column.width = width;
			}
			return column;
		};

		this.NumberColumn = function (id, label, sortable, width) {
			width = width || '90px';
			return new DocumentList.Column(id, label, sortable, 'right', width);
		};


		function DocumentListObject ($scope) {

			this.$scope = $scope;

			this.$scope.is = function (doc, modelName) {
				return Utils.isModel(doc, modelName);
			};

			this.documents = [];

			this.columns = [
				{ id: 'status', label: "État", sortable: true, width: '20px', align: 'center' },
				{ id: 'label', label: "Libellé", sortable: true }
			];

			this.sort =  {
				column: 'label',
				descending: false
			};

			var search = $location.search();
			this.pagination = {
				offset : search.offset || 0,
				limit  : search.limit || Settings.pagingSize,
				total  : 0
			};

			this.viewMode = Settings.documentListViewMode;

			this.thumbnailsInfo = Settings.documentListThumbnailsInfo;

			this.selectedDocuments = [];

			this.allSelected = false;
			this.lastCheckedIndex = -1;

			this.loading = false;

			this.thumbnailsSize = 'normal';
			this.availableViewModes = {
				'list'            : {
					'id'   : 'list',
					'icon' : 'icon-align-justify',
					'label': "Vue en mode liste"
				},
				'thumbnails'      : {
					'id'   : 'thumbnails',
					'icon' : 'icon-th-large',
					'label': "Vue en mode vignettes"
				},
				'small-thumbnails': {
					'id'   : 'small-thumbnails',
					'icon' : 'icon-th-list',
					'label': "Vue en mode petites vignettes"
				}
			};
			this.enabledViewModes = [ 'list' ];
			this.viewMode = this.enabledViewModes[0];

			this.toolbar = {
				actions : []
			};

			this.setActions([
				{
					'actions': ['groupPublishDocument', 'deactivate', 'applyCorrection']
				},
				'delete'
			]);

		}

		DocumentListObject.prototype = {


			isEmpty : function () {
				return this.documents.length === 0;
			},


			// ----------------------------------------------------------------
			//
			// Sort and view modes.
			//
			// ----------------------------------------------------------------


			isSortedOn : function (column) {
				return this.sort.column === column;
			},


			hasColumn : function (column) {
				var i;
				for (i=0 ; i<this.columns.length ; i++) {
					if (this.columns[i].id === column) {
						return true;
					}
				}
				return false;
			},


			toggleSort : function (column, forceAsc) {
				if (this.sort.column === column) {
					if (forceAsc) {
						this.sort.descending = false;
					} else {
						this.sort.descending = ! this.sort.descending;
					}
				} else {
					this.sort.column = column;
					this.sort.descending = false;
				}
				this.reload();
			},


			showThumbnailsView : function () {
				this.viewMode = 'thumbnails';
			},


			showSmallThumbnailsView : function () {
				this.viewMode = 'small-thumbnails';
			},


			isThumbnailsView : function () {
				return this.viewMode === 'thumbnails';
			},


			isSmallThumbnailsView : function () {
				return this.viewMode === 'small-thumbnails';
			},


			showListView : function () {
				this.viewMode = 'list';
			},


			isListView : function () {
				return this.viewMode === 'list';
			},


			enableViewModes : function  (modes) {
				this.enabledViewModes = modes;
				this.viewMode = this.enabledViewModes[0];
			},


			isViewModeEnabled : function (mode) {
				return jQuery.inArray(mode, this.enabledViewModes) > -1;
			},


			setViewMode : function (mode) {
				this.viewMode = mode;
			},


			isViewMode : function (mode) {
				return this.viewMode === mode;
			},


			// ----------------------------------------------------------------
			//
			// Actions on (selected) documents.
			//
			// ----------------------------------------------------------------


			addActions : function (def) {
				var i;
				if (!angular.isArray(def)) {
					throw new Error("Actions definition should be an Array of Arrays (group), Objects (group with label) and/or Strings (separated button).");
				}
				for (i=0 ; i<def.length ; i++) {
					if (angular.isString(def[i])) {
						this.toolbar.actions.push({type: 'single', name: def[i]});
					} else if (angular.isArray(def[i])) {
						this.toolbar.actions.push({type: 'group', actions: def[i]});
					} else if (angular.isObject(def[i])) {
						this.toolbar.actions.push({type: 'group-with-label', label: def[i].label, actions: def[i].actions});
					}
				}
			},

			setActions : function (def) {
				ArrayUtils.clear(this.toolbar.actions);
				this.addActions(def);
			},


			callAction : function (actionName, args) {
				args = this.fillActionParams(args);
				var promise = Actions.execute(actionName, args);
				if (! this.$scope.$$phase) {
					this.$scope.$apply();
				}
				return promise;
			},


			fillActionParams : function (params) {
				if (! angular.isObject(params)) {
					params = {};
				}
				params.$docs = this.selectedDocuments;
				params.$scope = this.$scope;
				params.$DL = this;
				params.$allDocs = this.documents;
				params.$currentTreeNode = Breadcrumb.getCurrentNode();
				return params;
			},


			isActionEnabled : function (actionName) {
				return Actions.isEnabled(actionName, this.selectedDocuments, this);
			},


			remove : function (doc) {
				var promise = Actions.execute('delete', angular.extend(this.fillActionParams(), { $docs: [ doc ] }));
				if (! this.$scope.$$phase) {
					this.$scope.$apply();
				}
				return promise;
			},


			// ----------------------------------------------------------------
			//
			// Selection
			//
			// ----------------------------------------------------------------


			checkDocumentRow : function ($event, index) {
				var start, stop, table = $($event.target).closest('table'), DL = this;
				if ($event.shiftKey) {

					// Skip first line (table header)
					start = 1 + Math.min(index, this.lastCheckedIndex);
					stop  = 1 + Math.max(index, this.lastCheckedIndex);

					// Checking the checkboxes does not reflect the changes in AngularJS, so I need to:
					// 1) Loop through all the concerned checkboxes:
					table.find('tr').slice(start+1, stop).find('td:first :checkbox[data-docid]').each(function () {
						var docId = $(this).data('docid');
						if (docId) {
							// 2) Find the AngularJS Resources with their ID:
							angular.forEach(DL.documents, function (doc) {
								if (doc.id === docId) {
									// 3) Simply select the Resource by changing their "selected" property.
									// AngularJS will then update the UI to reflect the changes.
									doc.selected = true;
								}
							});
						}
					});
				}

				this.lastCheckedIndex = index;
			},


			selectAll : function () {
				this.allSelected = true;
				angular.forEach(this.documents, function (doc) {
					doc.selected = true;
				});
			},


			selectNone : function () {
				this.allSelected = false;
				angular.forEach(this.documents, function (doc) {
					doc.selected = false;
				});
			},


			toggleSelectAll : function () {
				if (this.allSelected) {
					this.selectNone();
				} else {
					this.selectAll();
				}
			},


			isSelectionEmpty : function () {
				return this.selectedDocuments.length === 0;
			},





			go : function (url) {
				$location.path(url);
			},



			// ----------------------------------------------------------------
			//
			// Documents loading.
			//
			// ----------------------------------------------------------------


			documentCollectionLoadedCallback : function (response) {
				this.stopLoading();

				// We are loading a collection, so we can tell the Breadcrumb that there is
				// no end-resource to display.
				Breadcrumb.setResource(null);

				// Available sortable columns
				// FIXME: remove default value here when the server sends this info.
				var sort = response.pagination.availableSort || ['label', 'nodeOrder', 'modificationDate'];
				angular.forEach(this.columns, function (column) {
					column.sortable = angular.isArray(sort) && ArrayUtils.inArray(column.id, sort) !== -1;
				});

				this.documents = response.resources;
				this.pagination.total = response.pagination.count;

				this.$scope.$broadcast('Change:DocumentListChanged', this);
			},


			startLoading : function (msg) {
				this.loading = true;
				Loading.start(msg || i18n.trans('m.rbs.admin.admin.js.loading-list | ucf'));
			},


			stopLoading : function (reason) {
				this.loading = false;
				Loading.stop();
				if (reason) {
					NotificationCenter.error(i18n.trans('m.rbs.admin.admin.js.loading-list-error | ucf'), reason);
				} else {
					NotificationCenter.clear();
				}
			},


			prepareQueryObject : function (query) {
				// Sort by "label" instead of "nodeOrder" when sending a query (search).
				if (this.sort.column === 'nodeOrder') {
					this.sort.column = 'label';
					this.sort.descending = 'asc';
				}
				query.offset = this.pagination.offset;
				query.limit  = this.pagination.limit;
				query.order  = [
					{
						'property' : this.sort.column,
						'order'    : this.sort.descending ? 'desc' : 'asc'
					}
				];
				return query;
			},


			hasFilters : function () {
				return angular.isObject(this.query) && this.query.where;
			},


			reload : function () {
				var DL, promise, params;

				this.documents.length = 0;

				// Is there a query object in there?
				if (this.hasFilters()) {
					this.startLoading();
					promise = REST.query(this.prepareQueryObject(this.query));
				} else {
					params = {
						'offset': this.pagination.offset,
						'limit' : this.pagination.limit,
						'sort'  : this.sort.column,
						'desc'  : this.sort.descending
					};

					// Or may be we are simply loading a Collection...
					if (this.resourceUrl) {
						this.startLoading();
						promise = REST.collection(this.resourceUrl, params);
					// Or may be tree children?
					} else if (this.$scope.currentFolder) {
						this.startLoading();
						promise = REST.treeChildren(this.$scope.currentFolder, params);
					}
				}

				if (promise) {
					this.cbSelectAll = false;
					DL = this;
					promise.then(
						function (response) {
							if (response !== null) {
								DL.documentCollectionLoadedCallback(response);
							} else {
								console.warn('Empty collection');
							}
						},
						function (reason) {
							DL.stopLoading(reason);
						}
					);
					return promise;
				}

				return null;
			},


			setDefaultTreeName : function (treeName) {
				this.defaultTreeName = treeName;
			},


			setTreeName : function (treeName) {
				var self = this;
				REST.treeNode(REST.treeUrl(treeName)).then(function (data) {
					if (data.resources.length) {
						self.setTreeNodeId(data.resources[0].id);
					}
				});
			},


			setTreeNode : function (treeNode) {
				if (treeNode) {
					if (! this.$scope.currentFolder || this.$scope.currentFolder.id !== treeNode.id) {
						this.$scope.currentFolder = treeNode;
						this.reload();
					}
				} else {
					this.$scope.currentFolder = null;
					this.reload();
				}
			},


			setResourceUrl : function (url) {
				this.resourceUrl = url;
			},


			// ----------------------------------------------------------------
			//
			// Helper methods for use in the template to generate URL
			// for pagination and columns headers (sorting).
			//
			// ----------------------------------------------------------------


			headerUrl : function (sortProperty) {
				var search = angular.copy($location.search());
				search.sort = sortProperty;
				if (this.sort.column === sortProperty) {
					search.desc = ! this.sort.descending;
				} else {
					search.desc = false;
				}
				return Utils.makeUrl($location.absUrl(), search);
			},


			pageUrl : function (offset, limit) {
				var search = angular.copy($location.search());
				search.offset = Math.max(0, offset);
				if (angular.isDefined(limit)) {
					search.limit = limit;
				}
				return Utils.makeUrl($location.absUrl(), search);
			},


			preview : function (index) {

				if (this.hasPreview(index)) {
					this.documents.splice(index+1, 1);
					return;
				}

				var	DL = this,
					current = this.documents[index],
					preview = {
					"__DL_preview" : true
				};
				current.__DL_preview_loading = true;

				this.startLoading(i18n.trans('m.rbs.admin.admin.js.loading-preview | ucf'));
				REST.resource(current).then(function (doc) {
					REST.modelInfo(doc).then(function (modelInfo) {
						delete current.__DL_preview_loading;
						preview.document = angular.extend(current, doc);
						preview.modelInfo = modelInfo;
						DL.documents.splice(index+1, 0, preview);
						DL.stopLoading();
					});
				});


			},

			isPreview : function (doc) {
				return doc.__DL_preview === true;
			},

			isPreviewReady : function (doc) {
				return ! doc.__DL_preview_loading;
			},

			hasPreview : function (index) {
				var current, next;
				current = this.documents[index];
				next = (this.documents.length > (index+1)) ? this.documents[index+1] : null;
				return (next && next.__DL_preview && next.document && next.document.id === current.id) ? true : false;
			},


			cancelFilters : function () {
				this.$scope.$broadcast('Change:DocumentList:CancelFilters');
			}

		};



		this.initScopeForTree = function ($scope, instanceName) {
			return this.initScope($scope, null, instanceName);
		};


		this.initScope = function ($scope, resourceUrl, instanceName) {

			instanceName = instanceName || 'DL';

			var DL = new DocumentListObject($scope);
			DL.name = instanceName;

			if (resourceUrl) {
				if (angular.isObject(resourceUrl)) {
					if (angular.isObject(resourceUrl.where) && angular.isString(resourceUrl.model)) {
						DL.query = resourceUrl;
					} else {
						resourceUrl = Utils.makeUrl(resourceUrl.url, resourceUrl.params);
						DL.setResourceUrl(resourceUrl);
					}
				} else {
					DL.setResourceUrl(resourceUrl);
				}
			}

			$scope.$watch(instanceName + '.viewMode', function () {
				Settings.documentListViewMode = DL.viewMode;
			}, true);

			$scope.$watch(instanceName + '.thumbnailsInfo', function () {
				Settings.documentListThumbnailsInfo = DL.thumbnailsInfo;
			}, true);

			$scope.$watch(instanceName + '.documents', function () {
				DL.selectedDocuments = $filter('filter')(DL.documents, {'selected': true});
			}, true);

			$scope.$watch(instanceName + '.selectedDocuments', function () {
				$scope.$broadcast('Change:DocumentListChanged', DL);
			});

			$scope.$watch(instanceName + '.query', function () {
				DL.pagination.offset = 0;
				if (!DL.loading) {
					DL.reload();
				}
			});

			$scope.$on('Change:TreePathChanged', function (event, pathInfo) {
				DL.setTreeNode(pathInfo.currentNode);
			});


			$scope.location = $location;
			$scope.$watch(
				'location.search()',

				function (search) {
					var	offset = parseInt(search.offset || 0, 10),
						limit  = parseInt(search.limit || Settings.pagingSize, 10),
						paginationChanged, sortChanged = false,
						desc = (search.desc === 'true');

					DL.pagination.offset = offset;
					DL.pagination.limit = limit;

					if (search.sort) {
						sortChanged = DL.sort.column !== search.sort;
						DL.sort.column = search.sort;
					}
					if (desc !== DL.sort.descending) {
						sortChanged = true;
					}
					DL.sort.descending = desc;

					if (sortChanged) {
						$scope.$broadcast('Change:DocumentListChanged', DL);
					}

					paginationChanged = DL.pagination.offset !== offset || DL.pagination.limit !== limit;
					if (paginationChanged || sortChanged) {
						DL.reload();
					}
				},

				true
			);

			$scope[instanceName] = DL;

			return $scope[instanceName];

		};

	}]);


})(window.jQuery);