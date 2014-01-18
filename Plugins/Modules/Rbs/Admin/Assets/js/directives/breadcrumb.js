(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsBreadcrumb', ['$q', '$location', 'RbsChange.Utils', 'RbsChange.i18n', '$rootScope', 'RbsChange.REST', 'RbsChange.UrlManager', function ($q, $location, Utils, i18n, $rootScope, REST, UrlManager)
	{
		return {
			restrict : 'E',

			scope : true,

			template : '<ul class="rbs-breadcrumb list-unstyled"></ul>',

			replace : true,

			link : function (scope, elm)
			{

				// This function gets called everytime the route changes or is updated.
				function routeChanged (event, route)
				{
					// Skip invalid and redirection routes.
					if (! route.originalPath || route.redirectTo) {
						return;
					}

					var parts = route.originalPath.split(/\//),
						shortModuleName = parts[2],
						fullModuleName = parts[1] + '_' + parts[2],
						documentName,
						modelName,
						eventData = {},
						breadcrumbData = {
							location : [],
							path : [],
							resource : null
						},
						promises = [];

					if (route.originalPath === '/') {
						update(breadcrumbData);
						return;
					}

					// Is the route based on a document?
					// Well, we could also check 'route.params.id' (and 'route.params.LCID').
					if (parts.length >= 4) {
						documentName = parts[3];
						modelName = parts[1] + '_' + parts[2] + '_' + parts[3];
					}

					eventData.moduleName = fullModuleName;
					eventData.route = route;

					// Populate 'location' part of Breadcrumb.
					breadcrumbData.location.push([shortModuleName, UrlManager.getUrl(fullModuleName, null, 'home')]);
					if (modelName) {
						breadcrumbData.location.push([documentName, UrlManager.getUrl(modelName, null, 'list')]);
						eventData.modelName = modelName;
					}

					// Dispatch event so that anyone can update the breadcrumb by populating the
					// 'breadcrumbData' object.
					//
					// An example can be found in the Rbs_Website plugin (Assets/Admin/admin.js.
					// In an 'app.run()' block:
					// - $rootScope.$on('Change:UpdateBreadcrumb')
					// - check eventData (2nd arg) and eventData.modelName to check what has to be done
					// - populate 'breadcrumbData' (3rd arg) (location, path, resource an resourceModifier)
					// - eventually populate the 'promises' array (4th arg) if you have async processes

					$rootScope.$broadcast('Change:UpdateBreadcrumb', eventData, breadcrumbData, promises);

					// If no one has set 'path' and 'resource' properties of the 'breadcrumbData' object,
					// a default process is done here.
					if (breadcrumbData.path.length === 0 && ! breadcrumbData.resource)
					{
						defaultUpdateBreadcrumb(eventData, breadcrumbData, promises);
					}

					if (promises.length) {
						$q.all(promises).then(function () {
							update(breadcrumbData);
						});
					}
					else {
						update(breadcrumbData);
					}
				}


				/**
				 * Default implementation of a Breadcrumb update.
				 *
				 * @param eventData Information about the new route
				 * @param breadcrumbData Object to be populated (location, path and resource)
				 * @param promises An array of Promise objects to be populated if there are async processes
				 */
				function defaultUpdateBreadcrumb (eventData, breadcrumbData, promises)
				{
					var search = $location.search(),
						route = eventData.route,
						p, defer;

					if (route.ruleName === 'new' || (route.params && route.params.id === 'new'))
					{
						breadcrumbData.resource = 'New element'; // FIXME i18n
					}
					else
					{
						if (route.params.id)
						{
							defer = $q.defer();
							if (route.params.LCID) {
								p = REST.resource(route.relatedModelName, route.params.id, route.params.LCID);
							} else {
								p = REST.resource(route.relatedModelName, route.params.id);
							}
							p.then(function (res) {
								breadcrumbData.resource = res;
								loadAncestors(breadcrumbData.resource, breadcrumbData).then(function () {
									defer.resolve();
								});
							});
							promises.push(defer.promise);
						}
						if (route.ruleName !== 'form' && route.ruleName !== 'list') {
							breadcrumbData.resourceModifier = route.ruleName;
						}
					}

					// Properties 'ruleName' and 'relatedModelName' are set by the UrlManager service.
					if (search.hasOwnProperty('tn') && (route.ruleName === 'form' || route.ruleName === 'new'))
					{
						promises.push(loadAncestors(parseInt(search['tn'], 10), breadcrumbData));
					}
				}


				/**
				 * Loads the ancestors of the given treeNode and updates the 'breadcrumbData.path' array.
				 *
				 * @param treeNode
				 * @param breadcrumbData
				 * @returns {promise|*|Function}
				 */
				function loadAncestors (treeNode, breadcrumbData)
				{
					var defer = $q.defer();

					if (Utils.isTreeNode(treeNode)) {
						REST.treeAncestors(treeNode).then(function (ancestors) {
							defer.resolve(ancestors);
						});
					}
					else if (angular.isNumber(treeNode)) {
						REST.resource(treeNode).then(function (tn) {
							if (Utils.isTreeNode(tn)) {
								REST.treeAncestors(tn).then(function (ancestors) {
									defer.resolve(ancestors);
								});
							}
						});
					}
					else {
						defer.resolve();
					}

					defer.promise.then(function (res) {
						if (res) {
							breadcrumbData.path = res.resources;
							// Remove last element because REST.treeAncestors() appends the current tree node
							// (But here the current node is set as 'breadcrumbData.resource'.)
							if (breadcrumbData.resource && breadcrumbData.path.length > 0 && breadcrumbData.path[breadcrumbData.path.length-1].id === breadcrumbData.resource.id)
							{
								breadcrumbData.path.pop();
							}
						}
					});

					return defer.promise;
				}


				$rootScope.$on('$routeChangeSuccess', routeChanged);
				$rootScope.$on('$routeUpdate', routeChanged);


				// -----


				function getLabelSuffix ()
				{
					var search = $location.search(), page;
					if ('offset' in search && 'limit' in search) {
						page = 1 + (search.offset / search.limit);
						return '<em> &mdash; page ' + page + '</em>';
					}
					return '';
				}


				function getEntryHtml (entry, disabled, last, cssClass)
				{
					var item, html, icon, url;

					if (Utils.isDocument(entry)) {
						item = [ entry.label ];
						url = entry.treeUrl();
						if (url && url !== "javascript:;") {
							item.push(entry.treeUrl());
						} else {
							item.push(last ? "javascript:;" : (entry.url() || "javascript:;"));
						}
					} else if (angular.isString(entry)) {
						item = [ entry ];
					} else {
						item = entry;
					}

					icon = item.length === 3 && item[2] ? '<i class="' + item[2] + '"></i> ' : '';

					if (disabled || last) {
						html = '<li class="active';
						if (cssClass) {
							html += ' ' + cssClass;
						}
						html += '"><span>' + icon + item[0];
						if (last) {
							html += getLabelSuffix();
						}
						html += '</span></li>';
					} else {
						html = '<li' + (cssClass ? (' class="' + cssClass + '"') : '') + '><a href="' + item[1] + '">' + icon + item[0] + '</a></li>';
					}

					return html;
				}


				function update (breadcrumbData)
				{
					var html, i;
					html = getEntryHtml(
						[i18n.trans('m.rbs.admin.adminjs.home | ucf'), ""],
						breadcrumbData.disabled,
						// Last element?
						breadcrumbData.location.length === 0 && breadcrumbData.path.length === 0 && ! breadcrumbData.resource,
						'location'
					);

					for (i = 0; i < breadcrumbData.location.length; i++) {
						if (angular.isDefined(breadcrumbData.location[i])) {
							html += getEntryHtml(
								breadcrumbData.location[i],
								breadcrumbData.disabled,
								// Last element?
								i === (breadcrumbData.location.length-1) && breadcrumbData.path.length === 0 && ! breadcrumbData.resource,
								'location'
							);
						}
					}

					for (i = 0; i < breadcrumbData.path.length; i++) {
						if (angular.isDefined(breadcrumbData.path[i])) {
							html += getEntryHtml(
								breadcrumbData.path[i],
								false,
								// Last element?
								i === (breadcrumbData.path.length-1) && ! breadcrumbData.resource
							);
						}
					}

					if (breadcrumbData.resource) {
						html += getEntryHtml(breadcrumbData.resource, breadcrumbData.disabled, ! breadcrumbData.resourceModifier);
					}

					if (breadcrumbData.resourceModifier) {
						html += getEntryHtml(breadcrumbData.resourceModifier, breadcrumbData.disabled, true);
					}

					elm.html(html);
				}

			}

		};

	}]);

})();