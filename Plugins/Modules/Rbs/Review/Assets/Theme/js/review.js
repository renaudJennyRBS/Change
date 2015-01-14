(function() {
	"use strict";

	function addAvatarSize(ajaxData, size) {
		if (angular.isArray(ajaxData['avatarSizes'])) {
			ajaxData['avatarSizes'].push(size);
		}
		else {
			ajaxData['avatarSizes'] = [size];
		}
	}

	function addUrlFormat(ajaxParams, format) {
		if (!ajaxParams['urlFormats']) {
			ajaxParams['urlFormats'] = format;
		}
		else if (angular.isArray(ajaxParams['urlFormats'])) {
			ajaxParams['urlFormats'].push(format);
		}
		else if (angular.isString(ajaxParams['urlFormats'])) {
			ajaxParams['urlFormats'] += ',' + format;
		}
	}

	function addDataSetName(ajaxParams, name) {
		if (!ajaxParams['dataSetNames']) {
			ajaxParams['dataSetNames'] = name;
		}
		else if (angular.isArray(ajaxParams['dataSetNames'])) {
			ajaxParams['dataSetNames'].push(name);
		}
		else if (angular.isString(ajaxParams['dataSetNames'])) {
			ajaxParams['dataSetNames'] += ',' + name;
		}
	}

	var app = angular.module('RbsChangeApp');

	function rbsReviewStarRating() {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewStarRating.tpl',
			scope: {
				rating: '=rbsReviewStarRating',
				scale: '@'
			},
			link: function(scope, elm, attrs) {
				scope.stars = [];
				for (var i = 0; i < scope.scale; i++) {
					scope.stars.push(i);
				}

				scope.$watch('rating', function() {
					if (attrs['scaled'] != 'true') {
						scope.scaledRating = Math.floor(scope.rating / (100 / scope.scale));
					}
					else {
						scope.scaledRating = scope.rating;
					}
				});
			}
		}
	}

	app.directive('rbsReviewStarRating', rbsReviewStarRating);

	function rbsReviewInputStarRating() {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewInputStarRating.tpl',
			require: '?ngModel',
			scope: {
				scale: '@'
			},
			link: function(scope, elm, attrs, ngModel) {
				if (!ngModel) {
					return;
				}

				scope.stars = [];
				for (var i = 0; i < scope.scale; i++) {
					scope.stars.push(i + 1);
				}
				scope.scaled = {};

				ngModel.$render = function() {
					scope.scaled.rating = Math.floor(ngModel.$viewValue / (100 / scope.scale));
				};

				scope.$watch('scaled.rating', function(value, oldValue) {
					if (value !== oldValue && !isNaN(value)) {
						ngModel.$setViewValue(Math.ceil(scope.scaled.rating * (100 / scope.scale)));
					}
				});
			}
		}
	}

	app.directive('rbsReviewInputStarRating', rbsReviewInputStarRating);

	function rbsReviewInputStarRatingItem() {
		return {
			restrict: 'A',
			scope: false,
			link: function(scope, elm, attrs) {
				var handlerIn = function handlerIn() {
					scope.scaled.hover = parseInt(attrs['rbsReviewInputStarRatingItem']);
					scope.$digest();
				};
				var handlerOut = function handlerOut() {
					scope.scaled.hover = -1;
					scope.$digest();
				};
				elm.hover(handlerIn, handlerOut);
			}
		}
	}

	app.directive('rbsReviewInputStarRatingItem', rbsReviewInputStarRatingItem);

	function rbsReviewReviewsSummary() {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewReviewsSummary.tpl',
			scope: {
				data: '=rbsReviewReviewsSummary',
				showReviews: '=showReviewsCallback',
				scale: '@'
			},
			link: function(scope, elm, attrs) {
			}
		}
	}

	app.directive('rbsReviewReviewsSummary', rbsReviewReviewsSummary);

	function rbsReviewReviewsDetails(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewReviewsDetails.tpl',
			scope: {
				targetId: '=',
				ajaxData: '=',
				ajaxParams: '=',
				handleVotes: '=',
				reviewsPerPage: '=',
				scale: '='
			},
			link: function(scope, elm, attrs) {
				var ajaxData = angular.isObject(scope.ajaxData) ? scope.ajaxData : {};
				addAvatarSize(ajaxData, '60');

				var ajaxParams = angular.isObject(scope.ajaxParams) ? scope.ajaxParams : {};
				ajaxParams.detailed = true;
				addUrlFormat(ajaxParams, 'canonical');

				var cacheKey = attrs['cacheKey'];
				if (cacheKey && angular.isObject($window['__change']) && $window['__change'][cacheKey]) {
					if (angular.isObject($window['__change'][cacheKey]['stats'])) {
						scope.statsData = $window['__change'][cacheKey]['stats'];
						refreshScaledDistribution();
					}
					else {
						loadStats();
					}

					if (angular.isObject($window['__change'][cacheKey]['list'])) {
						scope.listData = $window['__change'][cacheKey]['list'];
					}
				}
				else {
					loadStats();
				}

				function loadStats() {
					var request = AjaxAPI.getData('Rbs/Review/ReviewsForTarget/' + scope.targetId + '/Stats', ajaxData,
						ajaxParams);
					request.success(function(data) {
						scope.statsData = data.dataSets;
						refreshScaledDistribution();
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
					});
				}

				function refreshScaledDistribution() {
					scope.scaledAverage = scope.statsData.common.rating / (100 / scope.scale);
					scope.scaledDistribution = [];
					for (var i = 0; i <= scope.scale; i++) {
						scope.scaledDistribution.push({
							'rating': i,
							'count': 0,
							'percent': 0
						})
					}

					for (i = 0; i < scope.statsData['distribution'].length; i++) {
						var row = scope.statsData['distribution'][i];
						var rating = Math.floor(row.rating / (100 / scope.scale));
						scope.scaledDistribution[rating].count += row.count;
						scope.scaledDistribution[rating].percent += row.percent;
					}
					scope.scaledDistribution.reverse();

					for (i = 0; i < scope.scaledDistribution.length; i++) {
						scope.scaledDistribution[i].ngStyle = { width: scope.scaledDistribution[i].percent + '%' };
					}
				}
			}
		}
	}

	rbsReviewReviewsDetails.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsReviewReviewsDetails', rbsReviewReviewsDetails);

	function rbsReviewReviewsList(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewReviewsList.tpl',
			scope: {
				targetId: '=',
				listData: '=',
				ajaxData: '=',
				ajaxParams: '=',
				handleVotes: '=',
				reviewsPerPage: '=',
				scale: '@'
			},
			link: function(scope) {
				scope.reviewsPerPage = scope.reviewsPerPage ? scope.reviewsPerPage : 10;

				var ajaxData = angular.isObject(scope.ajaxData) ? scope.ajaxData : {};
				addAvatarSize(ajaxData, '60');

				var ajaxParams = angular.isObject(scope.ajaxParams) ? scope.ajaxParams : {};
				ajaxParams.detailed = true;
				addUrlFormat(ajaxParams, 'canonical');

				if (!angular.isObject(scope.listData)) {
					loadList(0, scope.reviewsPerPage);
				}

				function loadList(offset, limit) {
					scope.loading = true;
					ajaxParams.pagination = { offset: offset, limit: limit };
					var request = AjaxAPI.getData('Rbs/Review/ReviewsForTarget/' + scope.targetId, ajaxData, ajaxParams);
					request.success(function(data) {
						scope.listData = data;
						scope.loading = false;
					});
					request.error(function(data, status) {
						scope.error = data.message;
						scope.loading = false;
						console.log('error', data, status);
					});
				}

				scope.updateListDataOffset = function(offset) {
					loadList(offset, scope.reviewsPerPage);
				}
			}
		}
	}

	rbsReviewReviewsList.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsReviewReviewsList', rbsReviewReviewsList);

	function rbsReviewEdit(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewEdit.tpl',
			scope: {
				data: '=rbsReviewEdit', // Should contain 'editableReview', 'targetId', 'isEditing', 'ajaxData' and 'ajaxParams'.
				scale: '@'
			},
			link: function(scope) {
				scope.identified = false;

				var refreshEditingData = function refreshEditingData(pseudonym) {
					if (angular.isObject(scope.data.editableReview) && angular.isObject(scope.data.editableReview.common)) {
						scope.editingData = {
							pseudonym: scope.data.editableReview.author.pseudonym,
							rating: scope.data.editableReview.common.rating,
							content: {
								raw: scope.data.editableReview.edition.content.raw,
								editor: scope.data.editableReview.edition.content.editor
							}
						};
					}
					else {
						scope.editingData = {
							pseudonym: pseudonym,
							rating: null,
							content: {
								raw: '',
								editor: 'Markdown'
							}
						};
					}
				};

				var ajaxPath;
				if (scope.data.targetId) {
					ajaxPath = 'Rbs/Review/CurrentReviewForTarget/' + scope.data.targetId;
				}
				else if (angular.isObject(scope.data.editableReview) && angular.isObject(scope.data.editableReview.common) &&
					scope.data.editableReview.common.id) {
					ajaxPath = 'Rbs/Review/Review/' + scope.data.editableReview.common.id;
				}
				else {
					console.error('rbsReviewEdit', 'Bad parameters: no target id and no review id');
				}
				var ajaxData = angular.isObject(scope.data.ajaxData) ? angular.copy(scope.data.ajaxData) : {};
				var ajaxParams = angular.isObject(scope.data.ajaxParams) ? angular.copy(scope.data.ajaxParams) : {};
				ajaxParams.detailed = true;
				addDataSetName(ajaxParams, 'edition');

				scope.cancelEdition = function cancelEdition() {
					scope.data.isEditing = false;
				};

				scope.saveReview = function saveReview() {
					AjaxAPI.openWaitingModal();
					scope.error = null;

					var postData = angular.copy(ajaxData);
					postData.setData = scope.editingData;
					var request = AjaxAPI.putData(ajaxPath, postData, ajaxParams);
					request.success(function(data) {
						scope.data.editableReview = data.dataSets;
						scope.data.isEditing = false;
						AjaxAPI.closeWaitingModal();
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
						AjaxAPI.closeWaitingModal();
					});
				};

				scope.deleteReview = function deleteReview(event) {
					if (!confirm(event.target.getAttribute('data-confirm-message'))) {
						return;
					}

					AjaxAPI.openWaitingModal();
					scope.error = null;

					var request = AjaxAPI.deleteData(ajaxPath, ajaxData, ajaxParams);
					request.success(function() {
						scope.data.review = null;
						scope.data.editableReview = { author: scope.data.editableReview.author };
						refreshEditingData(scope.editingData.pseudonym);
						scope.data.isEditing = true;
						AjaxAPI.closeWaitingModal();
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
						AjaxAPI.closeWaitingModal();
					});
				};

				scope.$watch('data.isEditing', function() {
					if (angular.isObject(scope.data.editableReview) && angular.isObject(scope.data.editableReview.author)) {
						refreshEditingData(scope.data.editableReview.author['pseudonym']);
					}
					else {
						refreshEditingData(null);
					}
				});
			}
		}
	}

	rbsReviewEdit.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsReviewEdit', rbsReviewEdit);

	function rbsReviewDisplay($sce, $cookieStore, AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewDisplay.tpl',
			scope: {
				data: '=rbsReviewDisplay',
				handleVotes: '=',
				scale: '@'
			},
			link: function(scope, elm, attrs) {
				scope.number = attrs['number'] ? attrs['number'] : null;
				scope.idPrefix = attrs['idPrefix'] ? attrs['idPrefix'] : 'review';
				scope.voted = false;

				scope.canVote = true;
				var reviewVotes = $cookieStore.get('reviewVotes');
				if (reviewVotes) {
					angular.forEach(reviewVotes, function(reviewVote) {
						if (reviewVote === scope.data.common.id) {
							scope.canVote = false;
						}
					});
				}

				scope.url = null;
				if (angular.isObject(scope.data.common) && angular.isObject(scope.data.common.URL)) {
					scope.url = scope.data.common.URL['contextual'] || scope.data.common.URL['canonical'];
				}

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				scope.vote = function vote(vote) {
					scope.canVote = false;
					var ajaxData = {
						'vote': vote
					};
					var request = AjaxAPI.postData('Rbs/Review/Review/' + scope.data.common.id + '/Votes', ajaxData, []);
					request.success(function(data) {
						scope.voted = true;
						scope.data.votes = data.dataSets.votes;
						if ($cookieStore.get('reviewVotes')) {
							var reviewVotes = $cookieStore.get('reviewVotes');
							reviewVotes.push(scope.data.common.id);
							$cookieStore.put('reviewVotes', reviewVotes);
						}
						else {
							$cookieStore.put('reviewVotes', [scope.data.common.id]);
						}
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
					})
				}
			}
		}
	}

	rbsReviewDisplay.$inject = ['$sce', '$cookieStore', 'RbsChange.AjaxAPI'];
	app.directive('rbsReviewDisplay', rbsReviewDisplay);

	function rbsReviewReviewDetails(AjaxAPI, $window) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewReviewDetails.tpl',
			scope: {
				reviewId: '@',
				ajaxData: '=',
				ajaxParams: '='
			},
			link: function(scope, elm, attrs) {
				scope.loading = true;
				scope.loadingEdition = false;

				var cacheKey = attrs['cacheKey'];
				if (cacheKey) {
					scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				}

				scope.handleVotes = scope.parameters.handleVotes || false;
				scope.scale = scope.parameters.scale || 5;
				addAvatarSize(scope.ajaxData, (scope.parameters.avatarSizes || '60').split(','));

				scope.data = {
					review: null,
					editableReview: null,
					isEditing: false,
					ajaxData: scope.ajaxData,
					ajaxParams: scope.ajaxParams
				};

				scope.editReview = function editReview() {
					scope.data.isEditing = true;
				};

				if (cacheKey && angular.isObject($window['__change']) && $window['__change'][cacheKey]) {
					scope.data.review = $window['__change'][cacheKey];
					loadUserContext();
					scope.loading = false;
				}
				else {
					var ajaxData = angular.isObject(scope.ajaxData) ? scope.ajaxData : {};
					var ajaxParams = angular.isObject(scope.ajaxParams) ? scope.ajaxParams : {};
					ajaxParams.detailed = true;
					addUrlFormat(ajaxParams, 'canonical');

					var request = AjaxAPI.getData('Rbs/Review/Review/' + scope.reviewId, ajaxData, ajaxParams);
					request.success(function(data) {
						scope.data.review = data.dataSets;
						loadUserContext();
						scope.loading = false;
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
						scope.loading = false;
					});
				}

				function loadUserContext() {
					var userContext = AjaxAPI.globalVar('userContext');
					if (angular.isObject(userContext)) {
						refreshDataForEdition(userContext);
					}
					if (!angular.isObject(userContext)) {
						var request = AjaxAPI.getData('Rbs/User/Info', null, null);
						request.success(function(data) {
							userContext = AjaxAPI.globalVar('userContext', data.dataSets.user);
							refreshDataForEdition(userContext);
						});
						request.error(function(data, status) {
							if (status != 403) {
								scope.error = data.message;
								console.log('error', data, status);
							}
							userContext = AjaxAPI.globalVar('userContext', { accessorId: 0 });
						});
					}
				}

				function refreshDataForEdition(userContext) {
					scope.loadingEdition = true;
					var reviewAuthorId = 0;
					if (angular.isObject(scope.data.review) && angular.isObject(scope.data.review.author)) {
						reviewAuthorId = scope.data.review.author.id;
					}

					var accessorId = 0;
					if (angular.isObject(userContext) && userContext.accessorId) {
						accessorId = userContext.accessorId;
					}

					if (reviewAuthorId && reviewAuthorId == accessorId) {
						var ajaxData = angular.isObject(scope.ajaxData) ? scope.ajaxData : {};
						var ajaxParams = angular.isObject(scope.ajaxParams) ? scope.ajaxParams : {};
						ajaxParams.detailed = true;
						addUrlFormat(ajaxParams, 'canonical');
						addDataSetName(ajaxParams, 'edition');

						var request = AjaxAPI.getData('Rbs/Review/Review/' + scope.reviewId, ajaxData, ajaxParams);
						request.success(function(data) {
							scope.data.editableReview = data.dataSets;
							scope.loadingEdition = false;
						});
						request.error(function(data, status) {
							scope.error = data.message;
							console.log('error', data, status);
							scope.loadingEdition = false;
						});
					}
					else {
						scope.data.editableReview = null;
						scope.loadingEdition = false;
					}
				}
			}
		}
	}

	rbsReviewReviewDetails.$inject = ['RbsChange.AjaxAPI', '$window'];
	app.directive('rbsReviewReviewDetails', rbsReviewReviewDetails);

	function rbsReviewCurrentReview(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsReviewCurrentReview.tpl',
			scope: {
				targetId: '=',
				ajaxData: '=',
				ajaxParams: '=',
				scale: '@'
			},
			link: function(scope) {
				scope.identified = false;
				scope.loading = true;
				scope.loadingEdition = false;

				scope.data = {
					review: null,
					editableReview: null,
					targetId: scope.targetId,
					isEditing: false,
					ajaxData: scope.ajaxData,
					ajaxParams: scope.ajaxParams
				};

				scope.editReview = function editReview() {
					scope.data.isEditing = true;
				};

				var userContext = AjaxAPI.globalVar('userContext');
				if (angular.isObject(userContext)) {
					refreshData(userContext);
				}
				if (!angular.isObject(userContext)) {
					var request = AjaxAPI.getData('Rbs/User/Info', null, null);
					request.success(function(data) {
						userContext = AjaxAPI.globalVar('userContext', data.dataSets.user);
						refreshData(userContext);
					});
					request.error(function(data, status) {
						if (status != 403) {
							scope.error = data.message;
							console.log('error', data, status);
						}
						userContext = AjaxAPI.globalVar('userContext', { accessorId: 0 });
						scope.loading = false;
					});
				}

				function refreshData(userContext) {
					var accessorId = 0;
					if (angular.isObject(userContext) && userContext.accessorId) {
						accessorId = userContext.accessorId;
					}

					if (accessorId) {
						scope.identified = true;
						refreshDataForDisplay();
						refreshDataForEdition();
					}
					else {
						scope.data.review = null;
						scope.data.editableReview = null;
						scope.loading = false;
					}
				}

				function refreshDataForDisplay() {
					var ajaxData = angular.isObject(scope.ajaxData) ? angular.copy(scope.ajaxData) : {};
					var ajaxParams = angular.isObject(scope.ajaxParams) ? angular.copy(scope.ajaxParams) : {};
					ajaxParams.detailed = true;

					var request = AjaxAPI.getData('Rbs/Review/CurrentReviewForTarget/' + scope.targetId, ajaxData, ajaxParams);
					request.success(function(data) {
						scope.data.review = data.dataSets;
						scope.loading = false;
					});
					request.error(function(data, status) {
						if (status !== '401') {
							scope.error = data.message;
							console.log('error', data, status);
						}
						scope.loading = false;
					});
				}

				function refreshDataForEdition() {
					scope.loadingEdition = true;
					var ajaxData = angular.isObject(scope.ajaxData) ? scope.ajaxData : {};
					var ajaxParams = angular.isObject(scope.ajaxParams) ? scope.ajaxParams : {};
					ajaxParams.detailed = true;
					addUrlFormat(ajaxParams, 'canonical');
					addDataSetName(ajaxParams, 'edition');

					var request = AjaxAPI.getData('Rbs/Review/CurrentReviewForTarget/' + scope.targetId, ajaxData, ajaxParams);
					request.success(function(data) {
						scope.data.editableReview = data.dataSets;
						if (!angular.isObject(scope.data.editableReview.common)) {
							scope.data.isEditing = true
						}
						scope.loadingEdition = false;
					});
					request.error(function(data, status) {
						scope.error = data.message;
						console.log('error', data, status);
						scope.loadingEdition = false;
					});
				}
			}
		}
	}

	rbsReviewCurrentReview.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsReviewCurrentReview', rbsReviewCurrentReview);
})();