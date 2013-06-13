(function () {

	"use strict";

	var app = angular.module('RbsChange');

	// FIXME This should be loaded with the user's preferences.

	app.service('RbsChange.Settings', ['$rootScope', 'localStorageService', function ($rootScope, localStorageService) {

		try {
			$rootScope.settings = JSON.parse(localStorageService.get('settings'));
		} catch (e) {
			$rootScope.settings = {};
		}
		console.log("Loaded settings: ", $rootScope.settings);

		return angular.extend(
			{
				'pagingSize': 15,
				'documentListViewMode': 'grid',
				'timeZone': {
					'code'  : 'GMT+2',
					'label' : "Paris, Madrid",
					'offset': '+02:00'
				},
				'language': 'fr_FR'
			},
			$rootScope.settings,
			{
				'set' : function (key, value) {
					$rootScope.settings[key] = value;
					localStorageService.add('settings', JSON.stringify($rootScope.settings));
					console.log("Saving setting: ", key, "=", value);
				},

				'get' : function (key, defaultValue) {
					return $rootScope.settings[key] || defaultValue;
				}
			}
		);

	}]);


})();