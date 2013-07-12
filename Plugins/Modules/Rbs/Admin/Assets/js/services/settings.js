(function () {

	"use strict";

	var app = angular.module('RbsChange');

	// FIXME This should be loaded with the user's preferences.

	app.service('RbsChange.Settings', ['$rootScope', 'localStorageService', function ($rootScope, localStorageService) {

		$rootScope.settings = {
			'pagingSize': 15,
			'documentListViewMode': 'list',
			'timeZone': {
				'code'  : 'GMT+2',
				'label' : "Paris, Madrid",
				'offset': '+02:00'
			},
			'language': 'fr_FR'
		};

		var storedSettings;
		try {
			storedSettings = JSON.parse(localStorageService.get('settings'));
		} catch (e) {
		}

		if (angular.isObject(storedSettings)) {
			angular.extend($rootScope.settings, storedSettings);
		}

		return angular.extend(
			{ },
			$rootScope.settings,
			{
				'set' : function (key, value) {
					$rootScope.settings[key] = value;
					localStorageService.add('settings', JSON.stringify($rootScope.settings));
				},

				'get' : function (key, defaultValue) {
					return $rootScope.settings[key] || defaultValue;
				}
			}
		);

	}]);

})();