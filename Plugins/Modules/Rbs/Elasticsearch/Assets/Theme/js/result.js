(function($) {
	"use strict";
	var app = angular.module('RbsChangeApp');

	app.controller('RbsElasticsearchResultController', ['$scope', '$rootScope', function(scope, $rootScope) {
		$rootScope.$broadcast('rbsElasticsearchSetSearchFormAction', {formAction: ''});
	}]);
})(window.jQuery);