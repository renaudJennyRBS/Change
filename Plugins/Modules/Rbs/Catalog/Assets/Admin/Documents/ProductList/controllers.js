(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for products.
	 * @constructor
	 */
	function ProductsController($scope, Breadcrumb, REST, $routeParams, $http, SelectSession, Navigation) {

		$scope.List = {};

		$scope.$on('Navigation.saveContext', function (event, args) {
			var data = {
				List: $scope.List,
				productsUrl: $scope.productsUrl,
				productList: $scope.productList
			};
			args.context.label($scope.productList.label);
			args.context.savedData('productList', data);
		});

		function initContextData() {
			var currentContext = Navigation.getCurrentContext();
			if (currentContext) {
				var data = currentContext.savedData('productList');
				if (angular.isObject(data)) {
					$scope.List = data.List;
					$scope.productsUrl = data.productsUrl;
					$scope.productList = data.productList;
					Navigation.popContext(currentContext);
					return;
				}
			}
			REST.resource('Rbs_Catalog_ProductList', $routeParams.id).then(function (productList) {
				$scope.productsUrl = productList.META$.links['productListItems'].href;
				$scope.productList = productList;
				$scope.List.isSynchronized = productList.hasOwnProperty('synchronizedSection');
				$scope.List.isCrossSelling = productList.hasOwnProperty('crossSellingType');
			});
		}

		//Init from context
		initContextData();


		REST.action('collectionItems', { code: 'Rbs_Catalog_Collection_ProductSortOrders' }).then(function (data) {
			$scope.List.sortOrders = data.items;
		});
		REST.action('collectionItems', { code: 'Rbs_Generic_Collection_SortDirections' }).then(function (data) {
			$scope.List.sortDirections = data.items;
		});

		$scope.goBack = function () {
			Breadcrumb.goParent();
		};

		$scope.List.toggleHighlight = function (doc) {
			var url = null;
			if (doc.position < 0) {
				url = doc.META$.actions['downplay'].href;
			}
			else {
				url = doc.META$.actions['highlight'].href;
			}
			callActionUrlAndReload(url);
		};

		$scope.List.moveTop = function (doc) {
			callActionUrlAndReload(doc.META$.actions['highlighttop'].href);
		};

		$scope.List.moveUp = function (doc) {
			callActionUrlAndReload(doc.META$.actions['moveup'].href);
		};

		$scope.List.moveDown = function (doc) {
			callActionUrlAndReload(doc.META$.actions['movedown'].href);
		};

		$scope.List.moveBottom = function (doc) {
			callActionUrlAndReload(doc.META$.actions['highlightbottom'].href);
		};

		if (SelectSession.started()) {
			SelectSession.commit($scope.List);
		}

		$scope.addProductsFromPicker = function () {
			var docIds = [];
			for (var i in $scope.List.productsToAdd) {
				docIds.push($scope.List.productsToAdd[i].id);
			}
			var url = REST.getBaseUrl('rbs/catalog/productlistitem/addproducts');
			$http.post(url, {"productListId": $scope.productList.id, "documentIds": docIds}).success(function (data) {
				$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method': 'reload' });

			}).error(function errorCallback(data, status) {
				data.httpStatus = status;
			});
			$scope.List.productsToAdd = [];
		};

		function callActionUrlAndReload(url) {
			if (url) {
				$http.get(url).success(function (data) {
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method': 'reload' });
				}).error(function errorCallback(data, status) {
					data.httpStatus = status;
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method': 'reload' });
				});
			}
		}
	}

	ProductsController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.REST',
		'$routeParams', '$http', 'RbsChange.SelectSession', 'RbsChange.Navigation'];
	app.controller('Rbs_Catalog_ProductList_ProductsController', ProductsController);

	function ProductListController($scope) {
		$scope.params = {};
		$scope.List = {};

		$scope.loadQuery = {
			"model": "Rbs_Catalog_ProductList",
			"where": {
				"or": [
					{
						"op": "eq", // neq, gt, lt, gte, lte
						"lexp": {
							"property": "model" //, "join" : "j0"
						},
						"rexp": {
							"value": "Rbs_Catalog_ProductList"
						}
					},
					{
						"op": "eq",
						"lexp": {
							"property": "model" //, "join" : "j0"
						},
						"rexp": {
							"value": "Rbs_Catalog_SectionProductList"
						}
					}
				]
			}
		};
	}

	ProductListController.$inject = ['$scope'];
	app.controller('Rbs_Catalog_ProductList_ProductListController', ProductListController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions',
			['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function (Actions, REST, $http, i18n) {
				var action = function (ids, $scope, operation) {
					if (operation === 'remove') {
						var url = REST.getBaseUrl('rbs/catalog/productlistitem/delete');
						$http.post(url, {"documentIds": ids}).success(function (data) {
							$scope.refresh();
						})
							.error(function errorCallback(data, status) {
								data.httpStatus = status;
								$scope.refresh();
							});
					}
				};

				Actions.register({
					name: 'Rbs_Catalog_RemoveProductsFromProductList',
					models: '*',
					description: i18n.trans('m.rbs.catalog.adminjs.remove_products_from_list'),
					label: i18n.trans('m.rbs.catalog.adminjs.remove|ucf'),
					selection: "+",
					execute: ['$docs', '$scope', function ($docs, $scope) {
						var ids = [];
						angular.forEach($docs, function (doc) {
							ids.push(doc.id);
						});
						action(ids, $scope, 'remove');
					}]
				});

				return Actions;
			}]);
	}]);
})();