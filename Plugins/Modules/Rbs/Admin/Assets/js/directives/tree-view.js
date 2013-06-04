(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('treeView', [
		'$http',
		'RbsChange.REST',
		'RbsChange.ArrayUtils',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu',

		function ($http, REST, ArrayUtils, Breadcrumb, MainMenu) {

			var	TREE_NODE_ID_PREFIX,
				defaultTreeFunctions,
				loadingRootNode;

			TREE_NODE_ID_PREFIX = 'chgTreeNode_';

			defaultTreeFunctions = {

				'buildNodeLabel' : function (rsc, attrs) {
					var label;

					label = loadingRootNode && attrs.rootNodeLabel ? attrs.rootNodeLabel : rsc.document.label;
					if (rsc.childrenCount && attrs.showChildCount === 'true') {
						label += ' (' + rsc.childrenCount + ')';
					}

					return label;
				},


				'buildNodeUrl' : function (rsc, attrs) {
					if (!attrs.urlTemplate) {
						throw new Error("tree: you must define the 'url-template' attribute.");
					}
					return attrs.urlTemplate.replace(/\:(\w+)/g, function (match, property) {
						return rsc[property] || '';
					});
				},


				'buildNodeObject' : function (rsc, attrs) {
					var iconModifier = rsc.childrenCount === 0 ? '/0' : '';
					return {
						// Attributes for "li" DOM node:
						"attr" : {
							"id"  : TREE_NODE_ID_PREFIX + rsc.id,
							"rel" : rsc.document.model + iconModifier
						},

						// Attributes for the link:
						"data" : {
							"title" : this.buildNodeLabel(rsc, attrs),
							"attr"  : {
								"href" : this.buildNodeUrl(rsc, attrs),
								"rel"  : "document",
								"data-model" : rsc.document.model,
								"data-label" : rsc.document.label,
								"data-id"    : rsc.document.id
							}
						},

						// Node state
						"state" : rsc.childrenCount ? "closed" : "",

						"metadata" : angular.extend({
								'NODE$' : {
									'url'           : rsc.link.href,
									'childrenCount' : rsc.childrenCount
								}
							},
							rsc.document)
						};
				},


				'initTree' : function (elm, rootUrl, attrs, loadCallback) {
					var plugins = [ "themes", "json_data" ],
					    allowedModels = null,
					    self = this;

					if (attrs.allowDragNDrop === 'true') {
						plugins.push("dnd");
					}
					if (attrs.allowedModels) {
						if (attrs.allowedModels === '*' || attrs.allowedModels === 'all') {
							attrs.allowedModels = 'all';
						} else {
							allowedModels = attrs.allowedModels.split(/[\s,]+/);
						}
					} else {
						allowedModels = [ 'Rbs_Generic_Folder', 'Rbs_Website_Topic' ];
					}

					if (angular.isFunction(loadCallback)) {
						$(elm).bind("loaded.jstree", loadCallback);
					}

					$(elm).jstree({
						"json_data" : {
							"ajax" : {

								"url" : function (node) {
									if (node === -1) {
										loadingRootNode = true;
										return rootUrl;
									}
									loadingRootNode = false;
									return node.data('NODE$').url + '/';
								},

								"success" : function (data) {
									console.log("success: data=", data);
									var nodes = [];
									if (data.resources.length > 0) {
										angular.forEach(data.resources, function (rsc) {
											if (allowedModels === 'all' || ArrayUtils.inArray(rsc.document.model, allowedModels) !== -1) {
												nodes.push(self.buildNodeObject(rsc, attrs));
											}
										});
									} else {
										nodes.push({
											'attr' : {
												'id'  : TREE_NODE_ID_PREFIX + '0',
												'rel' : "EmptyNode"
											},
											'data' : {
												'title': "Aucun élément",
												'attr' : {
													'href' : 'javascript:;'
												}
											}
										});
									}
									return nodes;
								}

							}
						},

						"core" : {
							"animation" : 100,
							"html_titles" : true
						},

						"plugins" : plugins,

						"themes" : {
							"theme" : "bootstrap",
							"dots"  : false,
							"icons" : attrs.hideIcons === 'true' ? false : true
						}
					});


					var draggedEl;

					$(elm).on({

						'dragstart': function (e) {
							draggedEl = $(this);
							draggedEl.addClass('dragged');
							e.dataTransfer.setData('Rbs/Document', JSON.stringify({
								"model" : draggedEl.data('model'),
								"label" : draggedEl.data('label'),
								"id"    : draggedEl.data('id')
							}));
							e.dataTransfer.effectAllowed = "copyMove";
						},

						'dragend': function () {
							draggedEl.removeClass('dragged');
						}

					}, 'a[rel="document"]');


					return jQuery.jstree._reference($(elm));
				}
			};

			return {
				restrict : 'A',

				link : function (scope, elm, attrs) {
					var treeObject = defaultTreeFunctions,
						rootNode,
						jsTree = null,
						breadcrumbChangedPendingData = null;

					// Check if 'root-node-url' attribute is provided.
					// Checking its value may not lead to a correct result as the Angular expression given as the
					// attribute's value may not have been evaluated yet, and thus return 'undefined' or 'false'.
					if ($(elm).is('[root-node-url]')) {

						// Wait for the Angular expression to be evaluated,
						// and use its value as the root node URL for our tree-view.
						attrs.$observe('rootNodeUrl', function (rootNodeUrl) {

							REST.treeNode(rootNodeUrl).then(

								// Success:
								function (node) {
									rootNode = node;
									rootNode.NODE$ = {
										'url'           : node.META$.treeNode.url,
										'childrenCount' : node.META$.treeNode.childrenCount
									};
									jsTree = treeObject.initTree(elm, rootNodeUrl + '/', attrs, breadcrumbChangedFn);
								},

								// Error:
								function (data) {
									console.log("----- treeView loading error: ", data);
								}
							);

						});

					} else if (attrs.showRootNode !== 'true') {

						// Load Module's root node here, out of the jstree scope.
						// Then initialize the tree-view with the result as the root node.
						$http.get(REST.treeUrl(attrs.treeView))
							.success(
								function (data) {
									rootNode = data.resources[0];
									jsTree = treeObject.initTree(elm, rootNode.link.href + '/', attrs, breadcrumbChangedFn);
								}
							);

					} else {

						// Load Module's whole tree, showing off its root node.
						jsTree = treeObject.initTree(elm, REST.treeUrl(attrs.treeView), attrs, breadcrumbChangedFn);

					}


					function breadcrumbChangedFn () {
						if (breadcrumbChangedPendingData && jsTree) {
							angular.forEach(breadcrumbChangedPendingData.path, function (node) {
								jsTree.open_node(
									$('#' + TREE_NODE_ID_PREFIX + node.id),
									// Callback
									function () { MainMenu.updateLinks(); },
									// Skip animation
									false
								);
							});
							breadcrumbChangedPendingData = null;
						}
					}

					scope.$on('Change:TreePathChanged', function (event, bcData) {
						breadcrumbChangedPendingData = bcData;
						breadcrumbChangedFn();
					});

				}

			};

		}
	]);
})(window.jQuery);