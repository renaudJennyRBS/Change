(function ()
{
	"use strict";

	var	app = angular.module('RbsChange'),
		INDEX_FUNCTION_CODE = 'Rbs_Website_Section';


	//-----------------------------------------------------------------------------------------------------------------

	app.directive('rbsRepeatCount', function ()
	{
		return {
			restrict : 'A',

			link : function (scope, iElement, iAttrs)
			{
				iAttrs.$observe('rbsRepeatCount', function (count)
				{
					var i;
					if (angular.isDefined(count))
					{
						count = parseInt(count, 10);
						if (isNaN(count) || count < 0) {
							throw new Error("Invalid value for Directive rbs-repeat-count: must be an integer >= 0");
						}

						if (count === 0) {
							iElement.remove();
						}
						else {
							for (i=0 ; i<(count-1) ; i++) {
								iElement.after(iElement.clone().removeAttr('rbs-repeat-count'));
							}
						}
					}
				});
			}
		};
	});

	function WebsiteSelector ($scope, $route, $location, REST, $filter) {
		REST.collection('Rbs_Website_Website').then(function (collection) {
			var path;
			if (collection.resources.length) {
				var website = collection.resources[0];
				var options = $route.current.$$route.options;
				var view =  (angular.isObject(options) && options.hasOwnProperty('view')) ? options.view : 'structure';
				path = $filter('rbsURL')(website, view);

			} else {
				path = $filter('rbsURL')('Rbs_Website_Website', 'list');
			}
			$location.path(path);
		});
	}

	WebsiteSelector.$inject = ['$scope', '$route', '$location', 'RbsChange.REST', '$filter'];
	app.controller('Rbs_Website_WebsiteSelector', WebsiteSelector);

	function HeaderController ($scope, $route, $routeParams, $location, REST, $filter)
	{
		$scope.currentWebsiteId = $routeParams.id;
		$scope.currentWebsite = null;
		$scope.view = $route.current.$$route.ruleName;
		$scope.websites = [];

		REST.treeChildren('Rbs/Website').then(function (root)
		{
			REST.treeChildren(root.resources[0]).then(function (websites)
			{
				$scope.websites = websites.resources;
				for (var i=0 ; i < websites.resources.length; i++) {
					if (websites.resources[i].id == $scope.currentWebsiteId) {
						$scope.currentWebsite = websites.resources[i];
						break;
					}
				}
			});
		});

		$scope.$watch('currentWebsite', function (website)
		{
			if (website && (website.id != $scope.currentWebsiteId)) {
				var path = $filter('rbsURL')(website, $scope.view);
				$location.path(path);
			}
		});
	}

	HeaderController.$inject = ['$scope', '$route', '$routeParams', '$location', 'RbsChange.REST', '$filter'];
	app.controller('Rbs_Website_HeaderController', HeaderController);

	/**
	 *
	 * @param $scope
	 * @param $q
	 * @param ErrorFormatter
	 * @param i18n
	 * @param REST
	 * @param Query
	 * @param NotificationCenter
	 * @param Utils
	 * @param $cacheFactory
	 * @param Navigation
	 * @constructor
	 */
	function StructureController ($scope, $q, ErrorFormatter, i18n, REST, Query, NotificationCenter, Utils, $cacheFactory, Navigation)
	{
		var cacheKeyPrefix = 'RbsWebsiteStructureData',
			cache, treeDb;

		function initCache (website, reset)
		{
			var cacheKey = cacheKeyPrefix + website.id,
				shouldLoad = false;

			cache = $cacheFactory.get(cacheKey);
			if (angular.isUndefined(cache)) {
				cache = $cacheFactory(cacheKey);
				cache.put('collection', []);
				cache.put('tree', {});
				shouldLoad = true;
			} else if (reset) {
				cache.removeAll();
				cache.put('collection', []);
				cache.put('tree', {});
				shouldLoad = true;
			}
			treeDb = cache.get('tree');
			$scope.browseCollection = cache.get('collection');
			return shouldLoad;
		}

		function selectWebsite(website)
		{
			if (initCache(website, false))
			{
				toggleNode(getNodeInfo($scope.currentWebsite));
			}
		}

		function getNodeIndex (nodeInfo)
		{
			var i, doc, id;
			for (i=0 ; i<$scope.browseCollection.length ; i++)
			{
				doc = $scope.browseCollection[i];
				id = doc.hasOwnProperty('__treeId') ? doc.__treeId : doc.id;
				if (id === nodeInfo.id) {
					return i;
				}
			}
			return -1;
		}


		function loadNode (nodeInfo)
		{
			REST.treeChildren(nodeInfo.document, {limit: 100, column:['functions','title', 'website']}).then(
				// Success
				function (results)
				{
					// Children in tree
					nodeInfo.children = results.resources;
					angular.forEach(nodeInfo.children, function (c) {
						getNodeInfo(c).level = nodeInfo.level + 1;
					});
					expandNode(nodeInfo);
				},

				// Error
				function (error) {
					$scope.loadingFunctions = false;
					NotificationCenter.error("Fonctions", ErrorFormatter.format(error));
				}
			);
		}


		function expandNode (nodeInfo, index)
		{
			var childInfo,
				count = 0;

			if (angular.isUndefined(index)) {
				index = Math.max(0, getNodeIndex(nodeInfo));
			}

			angular.forEach(nodeInfo.children, function (c)
			{
				count++;
				$scope.browseCollection.splice(++index, 0, c);
				childInfo = getNodeInfo(c);
				if (childInfo.open) {
					var excount = expandNode(childInfo, index);
					count += excount;
					index += excount;
				}
			});
			nodeInfo.open = true;
			return count;
		}


		function collapseNode (nodeInfo)
		{
			var index = Math.max(0, getNodeIndex(nodeInfo));
			$scope.browseCollection.splice(index+1, getDescendantsCount(nodeInfo));
			delete nodeInfo.open;
		}


		function getDescendantsCount (nodeInfo)
		{
			var count = 0, childInfo;
			if (nodeInfo.open)
			{
				count = nodeInfo.children.length;
				angular.forEach(nodeInfo.children, function (c)
				{
					childInfo = getNodeInfo(c);
					count += getDescendantsCount(childInfo);
				});
			}
			return count;
		}


		function getNodeInfo (doc)
		{
			var id;

			if (angular.isObject(doc)) {
				id = doc.hasOwnProperty('__treeId') ? doc.__treeId : doc.id;
			} else {
				id = doc;
			}
			if (! treeDb.hasOwnProperty(id)) {
				treeDb[id] = {
					children : null,
					document : doc,
					id : id,
					level : 0
				};
			}
			return treeDb[id];
		}


		function toggleNode (nodeInfo)
		{
			if (nodeInfo.open) {
				collapseNode(nodeInfo);
			}
			else {
				if (nodeInfo.children !== null) {
					expandNode(nodeInfo);
				}
				else {
					loadNode(nodeInfo);
				}
			}
		}


		function getParent (index)
		{
			var i = index - 1,
				level = getNodeInfo($scope.browseCollection[index]).level;

			if (level === 1) {
				return $scope.currentWebsite;
			}

			while (i >= 0) {
				if (getNodeInfo($scope.browseCollection[i]).level < level) {
					return $scope.browseCollection[i];
				}
				i--;
			}

			return null;
		}


		function setListBusy (value)
		{
			$scope.$broadcast('Change:DocumentList:DLRbsWebsiteBrowser:call', {'method':value?'setBusy':'setNotBusy'});
		}


		$scope.reloadNode = function (doc)
		{
			var node = getNodeInfo(doc);
			if (node.level > 0)
			{
				collapseNode(node);
				node.children = null;
				toggleNode(node);
			}
			else
			{
				initCache($scope.currentWebsite, true);
				toggleNode(getNodeInfo($scope.currentWebsite));
			}
		};


		// This object is exposed in the <rbs-document-list/> ('extend' attribute).
		$scope.browser =
		{
			toggleNode : function (doc)
			{
				return toggleNode(getNodeInfo(doc));
			},

			isTopic : function (doc)
			{
				return doc.is && doc.is('Rbs_Website_Topic');
			},

			isPage : function (doc)
			{
				return doc.is && (doc.is('Rbs_Website_StaticPage') || doc.is('Rbs_Website_FunctionalPage'));
			},

			isFunction : function (doc)
			{
				return doc.model === 'Functions';
			},

			hasChildren : function (doc)
			{
				var nodeInfo = getNodeInfo(doc);
				return angular.isArray(nodeInfo.children) ? (nodeInfo.children.length > 0) : doc.nodeHasChildren();
			},

			childrenCount : function (doc)
			{
				var nodeInfo = getNodeInfo(doc);
				return angular.isArray(nodeInfo.children) ? nodeInfo.children.length : doc.nodeChildrenCount();
			},

			showFunctions : {},

			getNodeInfo : getNodeInfo,

			isNodeOpen : function (doc)
			{
				return getNodeInfo(doc).open === true;
			},

			getIndicatorStyle : function (doc, index)
			{
				var lvl = getNodeInfo(doc).level-1,
				    nextDoc = (index+1) < $scope.browseCollection.length ? $scope.browseCollection[index+1] : null,
					nextLvl,
					style;

				if (nextDoc) {
					nextLvl = getNodeInfo(nextDoc).level-1;
					style = { background : 'linear-gradient(to bottom, rgb('+Math.max(0, 245-lvl*19)+','+Math.max(0, 250-lvl*12)+','+Math.max(0, 255-lvl*5)+') 50%, rgb('+Math.max(0, 245-nextLvl*19)+','+Math.max(0, 250-nextLvl*12)+','+Math.max(0, 255-nextLvl*5)+'))' };
				} else {
					style = { background : 'rgb('+Math.max(0, 245-lvl*19)+','+Math.max(0, 250-lvl*12)+','+Math.max(0, 255-lvl*5)+')' };
				}

				if (index === 0) {
					style.top = 0;
				}

				return style;
			},


			getPrimaryCellStyle : function (doc)
			{
				return { paddingLeft: (10 + (getNodeInfo(doc).level-1)*25) + 'px' };
			},


			isIndexPage : function (page)
			{
				return page.functions && page.functions.indexOf(INDEX_FUNCTION_CODE) !== -1;
			},

			setIndexPage : function (page, rowIndex)
			{
				if (this.isIndexPage(page)) {
					return;
				}

				var section = getParent(rowIndex);

				setListBusy(true);
				// Retrieve "index" SectionPageFunction for the current section (if any).
				REST.query(Query.simpleQuery('Rbs_Website_SectionPageFunction', {
					'section' : section.id,
					'functionCode' : INDEX_FUNCTION_CODE
				}), {'column':['page']}).then(

					// Success
					function (spf)
					{
						// SectionPageFunction exists: set new page on it.
						if (spf.resources.length === 1) {
							spf = spf.resources[0];
							// Nothing to do it the index page is the same.
							if (spf.page && spf.page.id === page.id) {
								return;
							}
						}
						// SectionPageFunction does NOT exist: create a new one.
						else {
							spf = REST.newResource('Rbs_Website_SectionPageFunction');
							spf.section = section;
							spf.functionCode = INDEX_FUNCTION_CODE;
						}
						spf.page = page.id;

						REST.save(spf).then(
							// Success
							function () {
								setListBusy(false);
								$scope.reloadNode(section);
							},
							// Error
							function (error)
							{
								setListBusy(false);
								NotificationCenter.error(i18n.trans('m.rbs.website.adminjs.index_page_error | ucf'), error);
							}
						);
					},

					// Error
					function (error)
					{
						setListBusy(false);
						NotificationCenter.error(i18n.trans('m.rbs.website.adminjs.index_page_error | ucf'), error);
					}
				);
			},

			getDocumentErrors : function (doc)
			{
				if (! Utils.isModel(doc, 'Rbs_Website_StaticPage')) {
					return null;
				}
				if (this.isIndexPage(doc) && ! Utils.hasStatus(doc, 'PUBLISHABLE')) {
					return [
						"UNPUBLISHED_INDEX_PAGE_"
					];
				}
				return null;
			}
		};


		$scope.$on('Navigation.saveContext', function (event, args) {
			var label = $scope.currentWebsite.label;
			args.context.label(label);
			var data = {currentWebsite: $scope.currentWebsite, allFunctions: $scope.allFunctions};
			args.context.savedData('Rbs_Website_StructureController', data);
		});

		function getContextData() {
			var currentContext = Navigation.getCurrentContext();
			if (currentContext) {
				var data = currentContext.savedData('Rbs_Website_StructureController');
				if (angular.isObject(data) && data.hasOwnProperty('currentWebsite'))
				{
					return data;
				}
			}
			return null;
		}

		var contextData = getContextData();
		if (contextData) {
			var currentContext = Navigation.getCurrentContext();
			$scope.allFunctions = contextData.allFunctions;
			$scope.currentWebsite = contextData.currentWebsite;
			Navigation.popContext();
			if (currentContext.param('node')) {
				initCache($scope.currentWebsite, false);
				$scope.reloadNode(currentContext.param('node'));
			} else {
				initCache($scope.currentWebsite, true);
				toggleNode(getNodeInfo($scope.currentWebsite));
			}
		} else {
			REST.action('collectionItems', { 'code': 'Rbs_Website_AvailablePageFunctions' }).then(function (result) {
				$scope.allFunctions = result.items;
				$scope.allFunctions['Rbs_Website_Section'] = { "label": i18n.trans('m.rbs.website.adminjs.function_index_page | ucf') };
			});
		}

		$scope.$watch('currentWebsite', function (website) {
			if (website) {
				selectWebsite(website);
			}
		});
	}

	StructureController.$inject = [
		'$scope', '$q',
		'RbsChange.ErrorFormatter', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Query',
		'RbsChange.NotificationCenter', 'RbsChange.Utils', '$cacheFactory', 'RbsChange.Navigation'
	];
	app.controller('Rbs_Website_StructureController', StructureController);




	/**
	 * @param $scope
	 * @param Query
	 * @constructor
	 */
	function MenusController($scope, Query)
	{
		$scope.$watch('currentWebsite', function (website)
		{
			if (website) {
				$scope.listLoadQuery = Query.simpleQuery('Rbs_Website_Menu', 'website', website.id);
			}
		});
	}


	MenusController.$inject = ['$scope', 'RbsChange.Query'];
	app.controller('Rbs_Website_MenusController', MenusController);


})();