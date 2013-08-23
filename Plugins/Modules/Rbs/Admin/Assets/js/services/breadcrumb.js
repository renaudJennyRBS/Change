(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.provider('RbsChange.Breadcrumb', function RbsChangeBreadcrumbProvider() {

		this.$get = [
			'$rootScope', '$document', '$location', '$q',
			'RbsChange.ArrayUtils',
			'RbsChange.Utils',
			'RbsChange.REST',

			function ($rootScope, $document, $location, $q, ArrayUtils, Utils, REST) {

				var location = [],
				    path = [],
				    fullPath = [],
				    resource = null,
					resourceModifier = null,
				    disabled = false,
				    frozen = false,
				    breadcrumbService,
				    loading = false,
					currentTreeNodeId = 0,
					readyQ = [];

				breadcrumbService = {

					windowTitleDivider : " / ",
					windowTitlePrefix  : "RBS Change",

					disable : function () {
						disabled = true;
						broadcastEvent();
					},

					enable : function () {
						disabled = false;
						broadcastEvent();
					},

					isDisabled : function () {
						return disabled;
					},

					freeze : function () {
						frozen = true;
					},

					unfreeze : function () {
						frozen = false;
					},

					isFrozen : function () {
						return frozen;
					},

					setLocation : function (loc) {
						if ( ! frozen && ! angular.equals(location, loc) ) {
							location = angular.copy(loc);
							update();
						}
					},

					resetLocation : function (loc) {
						if ( ! frozen && (path.length || ! angular.equals(location, loc) || ! loc) ) {
							location = loc || [];
							ArrayUtils.clear(path);
							resource = null;
							update();
						}
					},

					setPath : function (p) {
						if ( ! frozen && ! angular.equals(path, p) ) {
							path = angular.copy(p);
							update();
						}
					},

					getCurrentNode : function () {
						return path[path.length - 1];
					},

					getClosest : function (modelName) {
						var i;
						for (i=path.length-1 ; i >= 0 ; i--) {
							if (path[i].model === modelName) {
								return path[i];
							}
						}
						return null;
					},

					getWebsite : function () {
						return this.getClosest('Rbs_Website_Website');
					},

					goParent : function () {
						var node = this.getCurrentNode();
						if (angular.isArray(node) && node.length === 2) {
							$location.url(node[1]);
						} else if (node && node.treeUrl && node.treeUrl()) {
							$location.url(node.treeUrl());
						} else if (location.length) {
							$location.url(location[location.length-1][1]);
						} else {
							history.back();
						}
					},

					setResource : function (res) {
						if ( ! angular.equals(resource, res) ) {
							resource = angular.copy(res);
							update();
						}
					},

					setResourceModifier : function (string) {
						resourceModifier = string;
					},

					ready : function () {
						var q = $q.defer();
						if (loading) {
							readyQ.push(q);
						} else {
							q.resolve(buildEventData());
						}
						return q.promise;
					}

				};


				function update () {
					var i,
					    last,
					    module,
					    title;

					fullPath = [];

					if ( ! frozen ) {

						for (i = 0 ; i < location.length ; i++) {
							if (location[i]) {
								fullPath.push(location[i]);
							}
						}

						for (i = 0 ; i < path.length ; i++) {
							if (path[i]) {
								fullPath.push(path[i]);
							}
						}

						if (resource) {
							fullPath.push(resource);
						}

						if (resourceModifier) {
							fullPath.push(resourceModifier);
						}

						// Updates window title:
						// RBS Change / <module's name> / <last path element>
						// RBS Change / <module's name>
						// RBS Change
						title = [ breadcrumbService.windowTitlePrefix ];
						if (fullPath.length > 0) {
							last = fullPath[fullPath.length-1];
							// Grab the module's name if there is more than one element in the path.
							if (fullPath.length > 1) {
								module = fullPath[0];
								title.push(angular.isArray(module) ? module[0] : module);
							}
							title.push(angular.isArray(last) ? last[0] : Utils.isDocument(last) ? last.label : last);
						}
						$document[0].title = title.join(breadcrumbService.windowTitleDivider);

						broadcastEvent();
					}

				}


				function buildEventData () {
					return {
						'fullPath'    : fullPath,
						'path'        : path,
						'currentNode' : breadcrumbService.getCurrentNode(),
						'location'    : location,
						'resource'    : resource,
						'resourceModifier' : resourceModifier,
						'website'     : breadcrumbService.getWebsite(),
						'disabled'    : disabled,
						'frozen'      : frozen
					};
				}


				function broadcastEvent () {
					if (!loading) {
						$rootScope.$broadcast('Change:TreePathChanged', buildEventData());
					}
				}


				function resolvePendingQs () {
					var data = buildEventData();
					while (readyQ.length) {
						readyQ.pop().resolve(data);
					}
				}


				function rejectPendingQs () {
					while (readyQ.length) {
						readyQ.pop().reject();
					}
				}


				function routeChangeSuccessFn (force) {
					var treeNodeId = $location.search()['tn'];
					if (! frozen && ! loading && (force || treeNodeId !== currentTreeNodeId || path.length === 0)) {
						if (treeNodeId) {
							loading = true;
							REST.resource(treeNodeId).then(

								// Success:
								function (treeNode) {

									if (Utils.isTreeNode(treeNode)) {
										// Load tree ancestors of the current TreeNode to update the breadcrumb.
										REST.treeAncestors(treeNode).then(

											// Success:
											function (ancestors) {
												loading = false;
												currentTreeNodeId = treeNodeId;
												breadcrumbService.setPath(ancestors.resources);
												$rootScope.website = breadcrumbService.getWebsite();
												resolvePendingQs();
											},

											// Error:
											function () {
												loading = false;
											}
										);
									} else {
										loading = false;
										breadcrumbService.setPath([treeNode]);
										resolvePendingQs();
									}
								},

								// Error:
								function () {
									rejectPendingQs();
									loading = false;
								}
							);
						} else {
							breadcrumbService.setPath([]);
							resolvePendingQs();
						}
					}
				}

				$rootScope.$on('$routeChangeSuccess', function () {
					// If route changes, we force reloading even if the tree node is the same.
					routeChangeSuccessFn(true);
				});

				$rootScope.$on('$routeUpdate', function () {
					routeChangeSuccessFn(false);
				});

				return breadcrumbService;

			}
		];

	});

})();