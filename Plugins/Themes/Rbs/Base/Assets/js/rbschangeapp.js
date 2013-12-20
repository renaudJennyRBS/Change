var app = angular.module('RbsChangeApp', ['ngCookies', 'ngAnimate']);
app.config(function ($interpolateProvider) {
	$interpolateProvider.startSymbol('(=').endSymbol('=)');
});