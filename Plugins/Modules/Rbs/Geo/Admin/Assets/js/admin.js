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
			$delegate.routesForLocalizedModels(['Rbs_Geo_Country', 'Rbs_Geo_TerritorialUnit']);
			$delegate.routesForModels(['Rbs_Geo_Address', 'Rbs_Geo_Zone']);
			$delegate.model(null).route('home', 'Rbs/Geo', { 'redirectTo': 'Rbs/Geo/Zone/'});
			return $delegate;
		}]);
	}]);

})();