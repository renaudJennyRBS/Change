angular.module('RbsChangeApp').controller('RbsWishlistListCtrl', function ($scope, $http)
{
	$scope.setDefaultWishlist = function (wishlistId) {
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			wishlistId: wishlistId,
			userId: $scope.data.userId,
			setDefault: true
		}).success(function() {
			window.location.reload();
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};

	$scope.changeIsPublic = function (wishlistId, isPublic) {
		$http.post('Action/Rbs/Wishlist/UpdateWishlist', {
			wishlistId: wishlistId,
			userId: $scope.data.userId,
			changeIsPublic: isPublic
		}).success(function() {
			window.location.reload();
		}).error(function(data) {
			$scope.errorMessage = data.error;
		});
	};
});