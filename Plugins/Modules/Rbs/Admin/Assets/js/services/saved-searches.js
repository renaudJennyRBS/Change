(function () {

	"use strict";

	var	cfgLocalStorageKeyName = 'SavedSearches',
		forEach = angular.forEach;

	function SavedSearchesService (localStorageService, Utils, ArrayUtils) {

		var data, searches;

		data = localStorageService.get(cfgLocalStorageKeyName);
		searches = data ? JSON.parse(data) : [];


		this.save = function (label, queryObject) {
			queryObject.meta = queryObject.meta || {};
			queryObject.meta.label = label;
			searches.push(queryObject);
			localStorageService.add(cfgLocalStorageKeyName, JSON.stringify(searches));
		};


		this.getSearches = function (moduleName) {
			var result = [];

			forEach(searches, function (queryObject) {
				if (Utils.startsWith(queryObject.model, moduleName)) {
					result.push(queryObject);
				}
			});

			return result;
		};


		this.remove = function (queryObject) {
			ArrayUtils.removeValue(searches, queryObject);
		};

	}

	angular.module('RbsChange').service(
		'RbsChange.SavedSearches',
		[
			'localStorageService',
			'RbsChange.Utils',
			'RbsChange.ArrayUtils',
			SavedSearchesService
		]
	);

})();