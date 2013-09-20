angular.module('RbsChangeApp').controller('RbsReviewVoteCtrl', function ($scope, $cookies, $http)
{
	$scope.$watch('blockId', function (reviewId){
		start();
	});

	function start(){
		$scope.review = __change[$scope.reviewId];
		var reviewVotes = $cookies.reviewVotes;
		if ($scope.review.reviewVotes)
		{
			angular.forEach(reviewVotes, function (reviewVote){
				console.log(reviewVote);
			});
		}
		else
		{
			$scope.review.canVote = true;
		}

		$scope.vote = function (vote)
		{
			console.log(vote);
			$scope.review.canVote = false;
			$http.post('Action/Rbs/Review/VoteReview', {
				reviewId: $scope.review.id,
				vote: vote
			}).success(function (data){
					$scope.review.upvote = data.upvote;
					$scope.review.downvote = data.downvote;
					$scope.review.voted = true;
				});
		}
	}
}
);