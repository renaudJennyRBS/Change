(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @constructor
	 */
	function SectionFunctionsController($scope, $routeParams, $location, REST)
	{
		var sectionId = parseInt($routeParams.id || $location.search()['website'], 10);
		if (! isNaN(sectionId) )
		{
			REST.resource(sectionId).then(function (section)
			{
				$scope.section = section;
			});
		}
	}

	SectionFunctionsController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST'];
	app.controller('Rbs_Website_SectionFunctionsController', SectionFunctionsController);

})();