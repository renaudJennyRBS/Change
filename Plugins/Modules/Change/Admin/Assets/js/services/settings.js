(function () {

	var app = angular.module('RbsChange');

	// FIXME This should be loaded with the user's preferences.

	app.service('RbsChange.Settings', ['$rootScope', function ($rootScope) {

		$rootScope.language = 'fr_FR';
		$rootScope.timeZone = {
				'code'  : 'GMT+2',
				'label' : "Paris, Madrid",
				'offset': '+02:00'
			};
		
		return {
			'pagingSize': 15,
			'documentListViewMode': 'thumbnails',
			'documentListThumbnailsInfo': true,
			'language': $rootScope.language,
			'timeZone': $rootScope.timeZone
		};

	}]);


})();