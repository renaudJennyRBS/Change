angular.module('RbsChangeApp').controller('RbsWishlistListCtrl', function ($scope, $http)
{
	$scope.setDefaultWishlist = function (wishlistId) {
		console.log(wishlistId);
	};

	$scope.setWishlistPublic = function (wishlistId) {
		console.log(wishlistId);
	};
});