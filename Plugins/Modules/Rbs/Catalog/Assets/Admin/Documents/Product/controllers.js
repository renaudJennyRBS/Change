(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function PricesController($scope, $routeParams, $location, Utils, REST) {
		$scope.params = {};
		$scope.params.webStoreId = $routeParams.webStoreId;
		$scope.params.areaId = $routeParams.areaId;
		$scope.List = {};
		if ($routeParams.startActivation) {
			$scope.params.startActivation = moment($routeParams.startActivation).toDate();
		}

		if ($routeParams.endActivation) {
			$scope.params.endActivation = moment($routeParams.endActivation).toDate();
		}

		if (!$scope.product) {
			REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function(product) {
				$scope.product = product;
				updatePricesURL();
			});
		}

		var updatePricesURL = function() {
			var params = {    'areaId': $scope.params.areaId == '' ? null : $scope.params.areaId,
				'webStoreId': $scope.params.webStoreId == '' ? null : $scope.params.webStoreId,
				'startActivation': $scope.params.startActivation ? moment($scope.params.startActivation).format() : null,
				'endActivation': $scope.params.endActivation ? moment($scope.params.endActivation).format() : null
			};
			if ($scope.product) {
				$scope.pricesURL = Utils.makeUrl($scope.product.META$.links['prices'].href, params);
			}
		};

		$scope.changeWebStore = function(webStoreId) {
			if (webStoreId == '') {
				webStoreId = null;
				$scope.params.areaId = null;
			}
			$location.search('webStoreId', webStoreId);
			updatePricesURL();
		};

		$scope.changeArea = function(areaId) {
			if (areaId == '') {
				areaId = null;
			}
			$location.search('areaId', areaId);
			updatePricesURL();
		};

		$scope.changeStartActivation = function(date) {
			if (date) {
				$location.search('startActivation', moment(date).format());
			}
			else {
				$location.search('startActivation', null);
			}
			updatePricesURL();
		};

		$scope.changeEndActivation = function(date) {
			if (date) {
				$location.search('endActivation', moment(date).format());
			}
			else {
				$location.search('endActivation', null);
			}
			updatePricesURL();
		};
	}
	PricesController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.Utils', 'RbsChange.REST'];
	app.controller('Rbs_Catalog_Product_PricesController', PricesController);

	function CrossSellingController($scope, $routeParams, REST, Query) {
		$scope.params = {};
		$scope.List = {};

		if (!$scope.product) {
			REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function(product) {
				$scope.product = product;
				$scope.loadQuery = Query.simpleQuery('Rbs_Catalog_CrossSellingProductList', 'product', product.id);
			});
		}
	}
	CrossSellingController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.Query'];
	app.controller('Rbs_Catalog_Product_CrossSellingController', CrossSellingController);

	function ProductListsController($scope, $routeParams, $http, $q, REST, NotificationCenter, i18n, Navigation) {
		$scope.DATA = {
			selectedLists: [],
			addBusy: false,
			highlightBusy: {},
			removeBusy: {}
		};

		function loadItems() {
			if ($scope.product.META$.links.hasOwnProperty('productListItems')) {
				REST.collection($scope.product.META$.links['productListItems'].href).then(function(result) {
					if (angular.isObject(result) && result.hasOwnProperty('resources')) {
						$scope.productListItems = result.resources;
						$scope.DATA.removeBusy = {};
					}
				});
			}
			else {
				$scope.productListItems = [];
			}
		}

		$scope.$on('Navigation.saveContext', function(event, args) {
			var label = i18n.trans('m.rbs.catalog.admin.productlist_list | ucf');
			args.context.label(label);
			var data = { product: $scope.product, productListItems: $scope.productListItems, DATA: $scope.DATA };
			args.context.savedData('ProductListsController', data);
		});

		var currentContext = Navigation.getCurrentContext();
		var data = null;
		if (currentContext) {
			data = currentContext.savedData('ProductListsController');
		}
		if (data) {
			$scope.product = data.product;
			$scope.productListItems = data.productListItems;
			$scope.DATA = data.DATA;
			// Done by picker.
			//Navigation.popContext(currentContext);
		}
		else {
			REST.resource($routeParams.id).then(function(product) {
				$scope.product = product;
				loadItems();
			});
		}

		$scope.isHighlightBusy = function(doc) {
			return $scope.DATA.highlightBusy[doc.id] === true;
		};

		$scope.isRemoveBusy = function(doc) {
			return $scope.DATA.removeBusy[doc.id] === true;
		};

		$scope.toggleHighlight = function(doc) {
			var url = doc.META$.actions[doc.isHighlighted ? 'highlight' : 'downplay'].href;
			$scope.DATA.highlightBusy[doc.id] = true;

			if (url) {
				$http.get(url)
					.success(function(data) {
						loadItems();
						delete $scope.DATA.highlightBusy[doc.id];
					})
					.error(function errorCallback(data, status) {
						delete $scope.DATA.highlightBusy[doc.id];
					}
				);
			}
		};

		$scope.deleteProductListItem = function(doc) {
			$scope.DATA.removeBusy[doc.id] = true;
			REST['delete'](doc).then(function() {
				loadItems();
			});
		};

		$scope.addToSelectedLists = function() {
			$scope.DATA.addBusy = true;
			var promises = [];
			angular.forEach($scope.DATA.selectedLists, function(productList) {
				if (!isInList($scope.productListItems, productList)) {
					var item = REST.newResource('Rbs_Catalog_ProductListItem');
					item.product = $scope.product.id;
					item.productList = productList.id;
					promises.push(REST.save(item));
				}
			});
			$q.all(promises).then(function() {
					$scope.DATA.selectedLists = [];
					$scope.DATA.addBusy = false;
					loadItems();
				},
				function(error) {
					NotificationCenter.error(error.code, error.message);
					$scope.DATA.addBusy = false;
					loadItems();
				});
		};

		function isInList(items, list) {
			for (var i = 0; i < items.length; i++) {
				if (items[i].productList.id == list.id) {
					return true;
				}
			}
			return false;
		}
	}
	ProductListsController.$inject = ['$scope', '$routeParams', '$http', '$q', 'RbsChange.REST', 'RbsChange.NotificationCenter',
		'RbsChange.i18n', 'RbsChange.Navigation'];
	app.controller('Rbs_Catalog_Product_ProductListsController', ProductListsController);

	function ProductListController(scope) {
		scope.defaultFilter = {name: "group", operator: "AND", parameters: {},
			filters: [
				{
					name: "variant",
					parameters: {
						propertyName: "variant", operator: "eq", value: false
					}
				}
			]
		};
	}
	ProductListController.$inject = ['$scope'];
	app.controller('Rbs_Catalog_Product_ListController', ProductListController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function($provide) {
		$provide.decorator('RbsChange.Actions',
			['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function(Actions, REST, $http, i18n) {
				Actions.register({
					name: 'Rbs_Catalog_RemoveProductFromProductLists',
					models: '*',
					description: i18n.trans('m.rbs.catalog.adminjs.remove_products_from_list'),
					label: i18n.trans('m.rbs.catalog.adminjs.remove'),
					selection: "+",
					execute: ['$docs', '$scope', function($docs, $scope) {
						var productListIds = [];
						for (var i in $docs) {
							productListIds.push($docs[i].id);
						}
						var conditionId = $scope.data.conditionId;
						var url = REST.getBaseUrl('catalog/product/' + $scope.data.containerId + '/productLists/' + conditionId +
							'/');
						$http.put(url, {"removeProductListIds": productListIds}, REST.getHttpConfig())
							.success(function(data) {
								$scope.refresh();
							})
							.error(function errorCallback(data, status) {
								data.httpStatus = status;
								$scope.refresh();
							});
					}]
				});
				return Actions;
			}]);
	}]);
})();