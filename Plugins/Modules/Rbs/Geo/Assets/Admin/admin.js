(function () {

	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Geo_Country');
	__change.createEditorForModel('Rbs_Geo_TerritorialUnit');
	__change.createEditorForModel('Rbs_Geo_Zone');
	__change.createEditorsForLocalizedModel('Rbs_Geo_AddressField');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.routesForLocalizedModels(['Rbs_Geo_Country', 'Rbs_Geo_AddressField']);
			$delegate.routesForModels(['Rbs_Geo_Address', 'Rbs_Geo_TerritorialUnit', 'Rbs_Geo_Zone', 'Rbs_Geo_AddressFields', 'Rbs_Geo_Address']);
			$delegate.model('Rbs_Geo').route('home', 'Rbs/Geo', { 'redirectTo': 'Rbs/Geo/Zone/'});
			return $delegate;
		}]);
	}]);
})();