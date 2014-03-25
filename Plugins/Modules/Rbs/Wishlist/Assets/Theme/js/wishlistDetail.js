angular.module('RbsChangeApp').controller('RbsWishlistDetailCtrl', function ($scope, $http)
{
	$scope.changingTitle = false;
	$scope.loading = true;
	$scope.selectedProducts = {};
	$scope.errorMessage = null;

	$scope.$watch('blockId', function (){
		start();
	});

	function start() {
		$scope.loading = false;
		angular.forEach($scope.data.productIds, function (productId) {
			$scope.selectedProducts[productId] = false;
		});
	}

	$scope.openChangeTitle = function () {
		$scope.errorMessage = null;
		$scope.changingTitle = true;
	};

	$scope.changeTitle = function () {
		$scope.errorMessage = null;
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			title: $scope.newTitle,
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId
		}).success(function(data) {
			//TODO refresh the page?
			$scope.changingTitle = false;
			$scope.data = data;
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.removeSelectedProducts = function () {
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

	$scope.changeIsPublic = function () {
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

	$scope.deleteWishlist = function (modalId) {
		jQuery('#' + modalId).modal({});
	};

	$scope.confirmDeleteWishlist = function () {
		$http.post('Action/Rbs/Wishlist/DeleteWishlist', {
			wishlistId: $scope.data.wishlistId,
			userId: $scope.data.userId
		}).success(function(data) {
			window.location.href = $scope.wishlistListUrl;
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};
});