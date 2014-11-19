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
	 * @name RbsChange.service:i18n
	 * @description UI localization service.
	 */
	app.service('RbsChange.i18n', ['$filter', function i18nServiceFn ($filter)
	{
		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:i18n
		 * @name RbsChange.service:i18n#trans
		 *
		 * @description Translates a localization string.
		 *
		 * @param {String} string The localization string.
		 * @param {Object=} params Replacements parameters.
		 */
		this.trans = function (string, params) {
			var p, path, key, filters = null;

			p = string.indexOf('|');
			if (p !== -1) {
				filters = string.substr(p+1).trim().split('|');
				string = string.substring(0, p).trim();
			}
			string = string.toLowerCase();

			p = string.lastIndexOf('.');
			path = string.substring(0, p);
			key = string.substr(p + 1);

			// Search for the key in the global object (comes from "Change/Admin/i18n.js").
			if (__change.i18n[path] && __change.i18n[path][key]) {
				string = __change.i18n[path][key];
				// Replace parameters (if any).
				angular.forEach(params, function (value, key) {
					string = string.replace(new RegExp('\\$' + key + '\\$', 'gi'), value);
				});
			}

			if (filters) {
				angular.forEach(filters, function (filterName) {
					string = $filter(filterName.trim())(string);
				});
			}

			return string;
		};
	}]);

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:i18n
	 *
	 * @description
	 * Localization filter.
	 *
	 * @param {String} string Localization string.
	 */
	app.filter('i18n', ['RbsChange.i18n', function (i18n)
	{
		return function i18nFilterFn (string, params) {
			return i18n.trans(string, params);
		};
	}]);

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:ucf
	 *
	 * @description
	 * Filter to make the first letter of a string uppercase.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('ucf', function ()
	{
		return function ucfFilterFn (input) {
			if (input && input.length > 0) {
				return angular.uppercase(input.substr(0, 1)) + input.substr(1);
			}
			return input;
		};
	});

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:etc
	 *
	 * @description
	 * Appends an ellipsis to the input string.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('etc', function ()
	{
		return function etcFilterFn (input) {
			return input + '…';
		};
	});

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:lab
	 *
	 * @description
	 * Appends ` :` (double dots) at the end of the input string.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('lab', function ()
	{
		return function labFilterFn (input) {
			// FIXME Remove space before ':' according to current language.
			return input + ' :';
		};
	});

})();