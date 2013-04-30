(function () {
	
	angular.module('RbsChange').directive('disableOnClick', function () {

		return {
			restrict : 'A',
			
			priority: 100,
			
			link : function (scope, elm, attrs) {
				elm.click(function () {
					elm.attr('disabled', 'disabled');
					elm.addClass('disabled');
				});
			}
		};
	});

})();