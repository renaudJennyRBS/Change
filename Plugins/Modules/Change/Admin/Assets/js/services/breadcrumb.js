(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.provider('RbsChange.Breadcrumb', function RbsChangeBreadcrumbProvider() {

		this.$get = [
			'$rootScope', '$document', '$location',
			'RbsChange.ArrayUtils',
			'RbsChange.Utils',
			'RbsChange.REST',
			'$log',

			function ($rootScope, $document, $location, ArrayUtils, Utils, REST, $log) {

				var location = [],
				    path = [],
				    fullPath = [],
				    resource = null,
				    disabled = false,
				    frozen = false,
				    breadcrumbService,
				    loading = false,
					currentTreeNodeId = 0;

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
						if ( ! angular.equals(location, loc) && ! this.isFrozen() ) {
							location = loc;
							update();
						}
					},

					resetLocation : function (loc) {
						if ( ! angular.equals(location, loc) && ! this.isFrozen() ) {
							location = loc;
							ArrayUtils.clear(path);
							resource = null;
							update();
						}
					},

					setPath : function (p) {
						if ( ! angular.equals(path, p) && ! this.isFrozen() ) {
							path = p;
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
						return this.getClosest('Change_Website_Website');
					},

					goParent : function () {
						var node = this.getCurrentNode();
						if (node && node.treeUrl()) {
							$location.url(node.treeUrl());
						} else if (location.length) {
							$location.url(location[location.length-1][1]);
						} else {
							history.back();
						}
					},

					setResource : function (res) {
						if ( ! angular.equals(resource, res) ) {
							resource = res;
							update();
						}
					},

					setResourceModifier : function (string) {
						console.warn("Implement Breadcrumb.setResourceModifier() :)");
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

				function broadcastEvent () {
					if (!loading) {
						$rootScope.$broadcast('Change:BreadcrumbChanged', {
							'fullPath' : fullPath,
							'path'     : path,
							'location' : location,
							'resource' : resource,
							'website'  : breadcrumbService.getWebsite(),
							'disabled' : disabled,
							'frozen'   : frozen
						});
					}
				}

				function routeChangeSuccessFn () {
					var treeNodeId = $location.search()['tn'];
					if (! frozen && treeNodeId && ! loading && (treeNodeId !== currentTreeNodeId || path.length === 0)) {
						loading = true;
						REST.resource(treeNodeId).then(

							// Success:
							function (treeNode) {
								// Load tree ancestors of the current TreeNode to update the breadcrumb.
								REST.treeAncestors(treeNode).then(

									// Success:
									function (ancestors) {
										loading = false;
										currentTreeNodeId = treeNodeId;
										breadcrumbService.setPath(ancestors.resources);
										$rootScope.website = breadcrumbService.getWebsite();
									},

									// Error:
									function () {
										loading = false;
									}
								);
							},

							// Error:
							function () {
								loading = false;
							}
						);
					}
				}

				$rootScope.$on('$routeChangeSuccess', routeChangeSuccessFn);
				$rootScope.$on('$routeUpdate', routeChangeSuccessFn);

				return breadcrumbService;

			}
		];

	});

})();