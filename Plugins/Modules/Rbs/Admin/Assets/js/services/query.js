/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc service
	 * @name RbsChange.service:Query
	 * @description Builds Query object to use with {@link RbsChange.service:REST#query `REST.query()`}.
	 */
	app.provider('RbsChange.Query', function RbsChangeQueryProvider ()
	{
		this.$get = function ()
		{
			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Query
			 * @name RbsChange.service:Query#simpleQuery
			 *
			 * @description
			 * Builds a simple Query object used to filter Documents of the given `model` with the
			 * given `property` and `value`.
			 *
			 * @param {String} model The Document Model.
			 * @param {String} property Name of the property to filter on.
			 * @param {*} value Value of the property to filter the Documents.
			 * @returns {Object} Query object.
			 */
			function simpleQuery (model, property, value)
			{
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

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Query
			 * @name RbsChange.service:Query#treeChildrenQuery
			 *
			 * @description
			 * Builds a Query object used to filter Documents of the given `model` that are children of the
			 * given `parentId`.
			 *
			 * @param {String} model The Document Model.
			 * @param {Integer} parentId ID of the parent Document in the tree.
			 * @returns {Object} Query object.
			 */
			function treeChildrenQuery (model, parentId)
			{
				return {
					'model' : model,
					'where' : {
						'and' : [
							{
								"op" : "childOf",
								"node" : parentId
							}
						]
					}
				};

			}

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Query
			 * @name RbsChange.service:Query#treeDescendantsQuery
			 *
			 * @description
			 * Builds a Query object used to filter Documents of the given `model` that are descendants of the
			 * given `parentId`.
			 *
			 * @param {String} model The Document Model.
			 * @param {Integer} parentId ID of the ancestor Document in the tree.
			 * @returns {Object} Query object.
			 */
			function treeDescendantsQuery (model, parentId)
			{
				return {
					'model' : model,
					'where' : {
						'and' : [
							{
								"op" : "descendantOf",
								"node" : parentId
							}
						]
					}
				};
			}

			return {
				'simpleQuery' : simpleQuery,
				'treeChildrenQuery' : treeChildrenQuery,
				'treeDescendantsQuery' : treeDescendantsQuery
			};

		};

	});

})();