(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editor for 'Rbs_Store_WebStore' model.
	__change.createEditorForModel('Rbs_Store_WebStore');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Store').route('home', 'Rbs/Store', { 'redirectTo': 'Rbs/Store/WebStore/'});
			$delegate.routesForModels(['Rbs_Store_WebStore']);
			return $delegate;
		}]);
	}]);

})();