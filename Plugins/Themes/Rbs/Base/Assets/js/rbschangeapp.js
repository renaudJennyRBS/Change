var app = angular.module('RbsChangeApp', ['ngCookies']);
app.config(function ($interpolateProvider) {
	$interpolateProvider.startSymbol('(=').endSymbol('=)');
});