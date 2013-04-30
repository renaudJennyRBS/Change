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
			['$routeParams', '$filter', 'RbsChange.Actions', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.Settings', 'RbsChange.FormsManager', '$q', '$location', '$http', 'RbsChange.Base64', '$timeout', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.Breadcrumb', 'RbsChange.NotificationCenter',
			 function ($routeParams, $filter, Actions, ArrayUtils, Utils, Settings, FormsManager, $q, $location, $http, Base64, $timeout, Loading, REST, Breadcrumb, NotificationCenter) {

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

			this.loading = true;

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
					'label'  : "État",
					'actions': ['groupPublishDocument', 'deactivate', 'applyCorrection']
				},
				{
					'label'  : "Ordre",
					'actions': ["treeMoveBefore", "treeMoveAfter"]
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


			toggleSort : function (column) {
				if (this.sort.column === column) {
					this.sort.descending = ! this.sort.descending;
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
				return Actions.isEnabled(actionName, this.selectedDocuments);
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
				this.documents = response.resources;
				this.pagination.total = response.pagination.count;

				// We are loading a collection, so we can tell the Breadcrumb that there is
				// no end-resource to display.
				Breadcrumb.setResource(null);
			},


			startLoading : function () {
				this.loading = true;
				Loading.start("Chargement de la liste...");
			},


			stopLoading : function (reason) {
				this.loading = false;
				Loading.stop();
				if (reason) {
					NotificationCenter.error("Le chargement de la liste a échoué.", reason);
				} else {
					NotificationCenter.clear();
				}
			},


			prepareQueryObject : function (query) {
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
				var DL, promise;

				// Is there a query object in there?
				if (this.hasFilters()) {

					this.startLoading();
					promise = REST.query(this.prepareQueryObject(this.query));

				// Or may be we are simply loading a Collection...
				} else if (this.resourceUrl) {

					this.startLoading();
					promise = REST.collection(
						this.resourceUrl,
						{
							'offset': this.pagination.offset,
							'limit' : this.pagination.limit,
							'sort'  : this.sort.column,
							'desc'  : this.sort.descending
						}
					);

				// Or may be tree children?
				} else if (this.$scope.currentFolder) {

					this.startLoading();
					promise = REST.treeChildren(this.$scope.currentFolder);

				}

				if (promise) {

					this.cbSelectAll = false;
					DL = this;

					promise.then(
						function (response) {
							DL.documentCollectionLoadedCallback(response);
						},
						function (reason) {
							DL.stopLoading(reason);
						}
					);
					return promise;

				}

				return null;
			},


			setTreeNodeId : function (id) {
				var DL = this;

				if ( ! this.$scope.currentFolder || this.$scope.currentFolder.id !== id ) {

					this.startLoading();

					// Load TreeNode document.
					REST.resource(id).then(

						// Success:
						function (container) {
							DL.$scope.currentFolder = container;
							DL.stopLoading();
							DL.reload();
						},

						// Error:
						function (reason) {
							DL.stopLoading(reason);
						}
					);
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


			pageUrl : function (offset) {
				var search = angular.copy($location.search());
				search.offset = offset;
				return Utils.makeUrl($location.absUrl(), search);
			},


			cancelFilters : function () {
				this.$scope.$broadcast('Change:DocumentList:CancelFilters');
			}

		};



		this.initScopeForTree = function ($scope, instanceName) {

			// Initialize the scope
			var DL = this.initScope($scope, null, instanceName);

			// Install watcher for 'tn' parameter in the URL (tn stands from Tree Node).
			$scope.$watch(
				// $scope.location has been initialized in initScope().
				'location.search()',

				function () {
					var id = parseInt($location.search()['tn'], 10);
					if (isNaN(id)) {
						console.log("Tree node ID (tn) is not a valid ID.");
					} else if (id) {
						DL.setTreeNodeId(id);
					}
				},

				true
			);

			return DL;
		};



		this.initScope = function ($scope, resourceUrl, instanceName) {

			instanceName = instanceName || 'DL';

			var DL = new DocumentListObject($scope);
			DL.name = instanceName;

			if (resourceUrl) {
				if (angular.isObject(resourceUrl)) {
					resourceUrl = Utils.makeUrl(resourceUrl.url, resourceUrl.params);
				}
				DL.setResourceUrl(resourceUrl);
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

			$scope.$watch(instanceName + '.query', function () {
				console.log("QUERY=", DL.query);
				DL.pagination.offset = 0;
				DL.reload();
			});

			$scope.location = $location;
			$scope.$watch('location.search()', function (search) {
				DL.pagination.offset = search.offset || 0;
				DL.pagination.limit = search.limit || Settings.pagingSize;

				if (search.sort) {
					DL.sort.column = search.sort;
				}
				if (search.desc) {
					DL.sort.descending = (search.desc === 'true');
				}

				DL.currentTreeNodeId = search.tn;

				DL.reload();
			}, true);

			$scope[instanceName] = DL;

			return $scope[instanceName];

		};

	}]);


})(window.jQuery);