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
			$delegate.model(null).route('home', 'Rbs/Media', { 'redirectTo': 'Rbs/Media/Image/'});
			return $delegate;
		}]);
	}]);

	app.controller('Rbs_Media_Menu_Controller', ['$scope', '$location', '$filter', 'RbsChange.TagService', 'RbsChange.ArrayUtils', function ($scope, $location, $filter, TagService, ArrayUtils) {

		$scope.tags = TagService.getList('Rbs_Media');
		$scope.selectedTags = [];

		function updateFilter () {
			$scope.filteredTags = $filter('orderBy')($filter('filter')($scope.tags, {'label': $scope.filterTags}), 'label');
		}

		$scope.$watch('filterTags', updateFilter, true);
		$scope.$watch('tags', updateFilter, true);


		function updateUrl () {
			var ids = [];
			angular.forEach($scope.selectedTags, function (tag) {
				ids.push(tag.id);
			});
			if (ids.length) {
				$location.url('Rbs/Media/Image?filter=hasTag:' + ids.join(','));
			} else {
				$location.url('Rbs/Media/Image');
			}
		}

		$scope.selectTag = function (tag) {
			if (ArrayUtils.inArray(tag, $scope.selectedTags) === -1) {
				$scope.selectedTags.push(tag);
			}
			console.log($scope.selectedTags);
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