angular.module('RbsChangeApp').controller('RbsWishlistButtonCtrl', function ($scope, $http)
{
	$scope.$watch('blockId', function (){
		start();
	});

	function start() {
		$scope.reset();
	}

	$scope.reset = function() {
		$scope.addWishlistSuccess = false;
		$scope.sucessMessage = false;
		$scope.error = false;
		$scope.wishlists = $scope.data.wishlists;
	};

	$scope.addToWishlist = function (wishlist, modalId) {
		if ($scope.data.defaultWishlist) {
			$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
				wishlistId: wishlist.id,
				userId: $scope.data.userId,
				productIdsToAdd: $scope.data.productIds
			}).success(function(data) {
					$scope.successMessage = data.success;
				}).error(function(data) {
					$scope.error = data.error;
				});
		}
		else {
			$scope.addNewWishlist(modalId);
		}
	};

	$scope.addNewWishlist = function (modalId) {
		$scope.newWishlist = {
			'public': true,
			userId: $scope.data.userId,
			storeId: $scope.data.storeId,
			productIds: $scope.data.productIds
		};
		jQuery('#' + modalId).modal({});
	};

	$scope.confirmNewWishlist = function () {
		if (angular.isObject($scope.newWishlist)) {
			$http.post('Action/Rbs/Wishlist/AddWishlist', {
				title: $scope.newWishlist.title,
				'public': $scope.newWishlist.public || false,
				storeId: $scope.newWishlist.storeId,
				userId: $scope.newWishlist.userId,
				productIds: $scope.newWishlist.productIds || null
			}).success(function(data) {
				$scope.successMessage = data.success;
				$scope.addWishlistSuccess = true;
			}).error(function(data) {
				$scope.error = data.error;
			});
		}
	};
});