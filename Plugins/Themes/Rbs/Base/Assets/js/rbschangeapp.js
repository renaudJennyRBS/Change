var app = angular.module('RbsChangeApp', []);
app.config(function ($interpolateProvider) {
	$interpolateProvider.startSymbol('(=').endSymbol('=)');
});

angular.forEach(window.ChangeBlocksControllers, function(ctrl, name){
	app.controller(name, ctrl)
});
angular.bootstrap(document, ['RbsChangeApp']);