angular.module('RbsChangeApp').controller('RbsReviewVoteCtrl', function ($scope, $cookieStore, $http)
{
	$scope.$watch('blockId', function (reviewId){
		start();
	});

	function start(){
		$scope.review = __change[$scope.reviewId];
		var reviewVotes = $cookieStore.get('reviewVotes');
		$scope.review.canVote = true;
		if (reviewVotes)
		{
			angular.forEach(reviewVotes, function (reviewVote){
				if (reviewVote === $scope.review.id)
				{
					$scope.review.canVote = false;
				}
			});
		}

		$scope.vote = function (vote)
		{
			$scope.review.canVote = false;
			$http.post('Action/Rbs/Review/VoteReview', {
				reviewId: $scope.review.id,
				vote: vote
			}).success(function (data){
					$scope.review.upvote = data.upvote;
					$scope.review.downvote = data.downvote;
					$scope.review.voted = true;
					if ($cookieStore.get('reviewVotes'))
					{
						var reviewVotes = $cookieStore.get('reviewVotes');
						reviewVotes.push($scope.review.id);
						$cookieStore.put('reviewVotes', reviewVotes);
					}
					else
					{
						$cookieStore.put('reviewVotes', [ $scope.review.id ]);
					}
				});
		}
	}
}
);