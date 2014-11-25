(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function RbsWishlistDetailCtrl($scope, $http, $rootScope, AjaxAPI) {
	$scope.changingTitle = false;
	$scope.loading = true;
	$scope.selectedProducts = {};
	$scope.errorMessage = null;

	$scope.$watch('blockId', function() {
		start();
	});

	function start() {
		$scope.loading = false;
		angular.forEach($scope.data.productIds, function(productId) {
			$scope.selectedProducts[productId] = false;
		});
	}

	$scope.openChangeTitle = function() {
		$scope.errorMessage = null;
		$scope.changingTitle = true;
		$scope.oldTitle = $scope.data.title;
	};

	$scope.cancelTitleEdition = function() {
		$scope.errorMessage = null;
		$scope.changingTitle = false;
		$scope.data.title = $scope.oldTitle;
	};

	$scope.changeTitle = function() {
		$scope.errorMessage = null;
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			title: $scope.data.title,
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId
		}).success(function(data) {
			$scope.changingTitle = false;
			$scope.data = data;
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.removeSelectedProducts = function() {
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId,
			productIdsToRemove: $scope.selectedProducts
		}).success(function() {
			window.location.reload();
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.changeIsPublic = function() {
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId,
			changeIsPublic: !$scope.data.isPublic
		}).success(function() {
			window.location.reload();
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.deleteWishlist = function(modalId) {
		jQuery('#' + modalId).modal({});
	};

	$scope.confirmDeleteWishlist = function() {
		$http.post('Action/Rbs/Wishlist/DeleteWishlist', {
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId
		}).success(function(data) {
			window.location.href = $scope.wishlistListUrl;
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.addProductsToCart = function() {
		var addProducts = [];

		angular.forEach($scope.selectedProducts, function(product, productId) {
			addProducts.push({productId: productId});
		});

		if (addProducts.length) {
			var request = AjaxAPI.putData('Rbs/Commerce/Cart', {addProducts:addProducts}, {detailed:false});
			request.success(function(resultData) {
				var cart = resultData.dataSets;
				$rootScope.$broadcast('rbsRefreshCart', {'cart': cart});
			}).error(function(data, status, headers) {
				console.log('error', data, status, headers);
			});
		}
	};
}
	RbsWishlistDetailCtrl.$inject = ['$scope', '$http', '$rootScope', 'RbsChange.AjaxAPI'];
	app.controller('RbsWishlistDetailCtrl', RbsWishlistDetailCtrl);
})();