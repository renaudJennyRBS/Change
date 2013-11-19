(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
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