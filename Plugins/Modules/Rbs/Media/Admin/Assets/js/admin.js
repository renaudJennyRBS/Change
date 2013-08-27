(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.routesForLocalizedModels(['Rbs_Media_Image']);
			$delegate.model('Rbs_Media').route('home', 'Rbs/Media', { 'redirectTo': 'Rbs/Media/Image/'});
			return $delegate;
		}]);
	}]);


	/**
	 * Controller for tag-based menu.
	 */
	app.controller('Rbs_Media_Menu_Controller', ['$q', '$scope', '$location', '$filter', 'RbsChange.TagService', 'RbsChange.ArrayUtils', function ($q, $scope, $location, $filter, TagService, ArrayUtils) {

		var tagsLoadedDefered = $q.defer();
		$scope.tags = TagService.getList(tagsLoadedDefered);
		$scope.selectedTags = [];

		function updateFilter () {
			$scope.filteredTags = $filter('orderBy')($filter('filter')($scope.tags, {'label': $scope.filterTags}), 'label');
		}

		$scope.$watch('filterTags', updateFilter, true);
		$scope.$watch('tags', updateFilter, true);

		tagsLoadedDefered.promise.then(function () {
			var filter = $location.search()['filter'];
			if (filter && filter.indexOf('hasTag:') === 0) {
				angular.forEach(filter.substring(7).split(/,/), function (tagId) {
					var tag = getTagById(parseInt(tagId, 10));
					if (tag) {
						$scope.selectTag(tag);
					}
				});
			}
		});

		function getTagById (id) {
			var i;
			for (i=0 ; i<$scope.tags.length ; i++) {
				if ($scope.tags[i].id === id) {
					return $scope.tags[i];
				}
			}
			return null;
		}

		function updateUrl () {
			var ids = [];
			angular.forEach($scope.selectedTags, function (tag) {
				ids.push(tag.id);
			});
			if (ids.length) {
				$location.url('Rbs/Media/Image/?filter=hasTag:' + ids.join(','));
			} else {
				$location.url('Rbs/Media/Image/');
			}
		}

		$scope.selectTag = function (tag) {
			if (ArrayUtils.inArray(tag, $scope.selectedTags) === -1) {
				$scope.selectedTags.push(tag);
			}
			updateUrl();
		};

		$scope.unselectTag = function (tag) {
			if (ArrayUtils.removeValue($scope.selectedTags, tag) !== -1) {
				updateUrl();
			}
		};

		$scope.removeLastSelectedTag = function () {
			if ($scope.selectedTags.length) {
				$scope.selectedTags.pop();
				updateUrl();
			}
		};

		$scope.unselectAll = function () {
			ArrayUtils.clear($scope.selectedTags);
			updateUrl();
		};

	}]);

})();