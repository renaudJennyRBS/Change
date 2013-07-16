(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 *
	 */
	app.provider('RbsChange.Query', function RbsChangeQueryProvider () {

		this.$get = function () {

			/**
			 *
			 * @param model
			 * @param property
			 * @param value
			 * @returns {{model: *, where: {and: Array}}}
			 */
			function simpleQuery (model, property, value) {
				return {
					'model' : model,
					'where' : {
						'and' : [
							{
								'op': 'eq',
								'lexp' : {
									'property' : property
								},
								'rexp' : {
									'value' : value
								}
							}
						]
					}
				};
			}

			return {
				'simpleQuery' : simpleQuery
			};

		};

	});

})();