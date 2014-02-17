(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.run(['$templateCache', function ($templateCache) {
			// Template for menu items in pickers.
			$templateCache.put('picker-item-Rbs_Menu_Item.html', '(=item.title=)(=item.titleKey=)');
		}]);
})();