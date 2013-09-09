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
				var and = [];
				if (angular.isObject(property) && angular.isUndefined(value)) {
					angular.forEach(property, function (value, name) {
						and.push({
							'op': 'eq',
							'lexp' : {
								'property' : name
							},
							'rexp' : {
								'value' : value
							}
						});
					});
				}
				else {
					and.push({
						'op': 'eq',
						'lexp' : {
							'property' : property
						},
						'rexp' : {
							'value' : value
						}
					});
				}

				return {
					'model' : model,
					'where' : {
						'and' : and
					}
				};
			}

			return {
				'simpleQuery' : simpleQuery
			};

		};

	});

})();