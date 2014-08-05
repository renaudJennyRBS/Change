(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsDocumentListSummary
	 * @function
	 *
	 * @description
	 * Renders an HTML string that summarizes the given list of documents.
	 *
	 * @param {Array} documents Array of Document objects
	 */
	app.filter('rbsDocumentListSummary', ['RbsChange.i18n', function (i18n)
	{
		return function (docs)
		{
			function getLabel (obj)
			{
				if (angular.isObject(obj)) {
					if (obj.hasOwnProperty('label')) {
						return obj.label;
					} else if (obj.hasOwnProperty('title')) {
						return obj.title;
					} else if (obj.hasOwnProperty('name')) {
						return obj.name;
					}
				}
				return '' + obj;
			}

			var out = '',
				msg,
				i;

			if (angular.isArray(docs))
			{
				if (docs.length > 3) {
					out = i18n.trans('m.rbs.admin.adminjs.filter_document_list_summary_more_three', {'count':docs.length, 'element1' : getLabel(docs[0]), 'element2' : getLabel(docs[1]), 'element3' : getLabel(docs[2])});
				} else if (docs.length > 1) {
					msg = [ ];
					for (i=0 ; i<docs.length-1 ; i++) {
						msg.push(getLabel(docs[i]));
					}
					out = i18n.trans('m.rbs.admin.adminjs.filter_document_list_summary_less_three', {'count':docs.length, 'elementsAsHtml' : msg.join('</strong>, <strong class=\"element\">'), 'lastElement': getLabel(docs[docs.length-1])});
				} else {
					out = "<strong class=\"element\">" + getLabel(docs[0]) + "</strong>";
				}
			}
			return out;
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsHighlight
	 * @function
	 *
	 * @description
	 * Highlights the given `needle` in the input string.
	 *
	 * @param {String} string The input string.
	 * @param {String} needle The string to highlight in the input string.
	 */
	app.filter('rbsHighlight', function ()
	{
		return function (input, needle) {
			var regex = new RegExp("(" + needle + ")", "ig");
			return input.replace(regex, '<strong class="highlight">$1</strong>');
		};
	});


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsStripHtmlTags
	 * @function
	 *
	 * @description
	 * Removes all the HTML tags from the input string.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('rbsStripHtmlTags', function ()
	{
		return function (input, allowed) {
			allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
			var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
				commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
			return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
				return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
			});
		};

	});


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsEmptyLabel
	 * @function
	 *
	 * @description
	 * For empty strings, returns a hyphen.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('rbsEmptyLabel', function ()
	{
		return function (input, value, cssClass) {
			value = value || '-';
			if (input === 0) {
				return '0';
			}
			if (input === null || (''+input).trim().length === 0) {
				if (cssClass) {
					return '<span class="' + cssClass + '">' + value + '</span>';
				}
				return value;
			}
			return input;
		};

	});

	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsModelLabel
	 * @function
	 *
	 * @description
	 * Returns the model's label.
	 *
	 * @param {String} string The model's label.
	 */
	app.filter('rbsModelLabel', ['RbsChange.Models', function (Models)
	{
		return function (input) {
			return Models.getModelLabel(input);
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsCapitalize
	 * @function
	 *
	 * @description
	 * Returns the input string with its first letter uppercase and the remaining lowercase.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('rbsCapitalize', function()
	{
		return function(input) {
			return input.substring(0,1).toUpperCase()+input.substring(1).toLowerCase();
		};
	});


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsFileSize
	 * @function
	 *
	 * @description
	 * Returns a formatted and human readable file size from an input value in bytes.
	 *
	 * @param {Number} size The size in bytes.
	 */
	app.filter('rbsFileSize', ['RbsChange.i18n', function (i18n)
	{
		var units = [
			[i18n.trans('c.filesize.bytes')],
			[i18n.trans('c.filesize.kilobytes_abbr'), i18n.trans('c.filesize.kilobytes | ucf')],
			[i18n.trans('c.filesize.megabytes_abbr'), i18n.trans('c.filesize.megabytes | ucf')],
			[i18n.trans('c.filesize.gigabytes_abbr'), i18n.trans('c.filesize.gigabytes | ucf')],
			[i18n.trans('c.filesize.terabytes_abbr'), i18n.trans('c.filesize.terabytes | ucf')]
		];

		return function (bytes) {
			var value = bytes, u = 0;
			while (value >= 1024 && u < units.length) {
				u++;
				value /= 1024.0;
			}
			if (u === 0) {
				return value + ' ' + units[u][0];
			}
			return Math.round(value) + ' <abbr title="' + units[u][1] + '">' + units[u][0] + '</abbr>';
		};

	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsEllipsis
	 * @function
	 *
	 * @description
	 * Truncates the input string to `length` characters and adds an ellipsis at the end.
	 *
	 * @param {String} string The input string.
	 * @param {Number} length The number of characters of string to display before the ellipsis.
	 */
	app.filter('rbsEllipsis', function ()
	{
		return function (input, length, where) {
			where = (where || 'end').toLowerCase();
			if (!angular.isString(input)) {
				return input;
			}
			if (input.length <= length) {
				return input;
			}
			if (where === 'center') {
				return input.substring(0, Math.floor(length/2)-3) + '...' + input.substring(input.length-Math.floor(length/2));
			}
			return input.substring(0, length-3) + '...';
		};

	});


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsBBcode
	 * @function
	 *
	 * @description
	 * Formats a string with basic BBCode.
	 *
	 * For the moment, only the `[b]...[/b]` is supported.
	 *
	 * @param {String} string The input string.
	 */
	app.filter('rbsBBcode', function ()
	{
		return function (input) {
			return input.replace(/\[(\/?)b\]/g,'<$1b>');
		};
	});


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsDiff
	 * @function
	 *
	 * @description
	 * Formats a visual diff between the input string and the first parameter.
	 *
	 * Uses `lib/diff_match_patch` ({@link https://code.google.com/p/google-diff-match-patch/)}
	 *
	 * @param {String} left The input string to be compared to the first parameter.
	 * @param {String=} right The string to be compared to the input string.
	 */
	app.filter('rbsDiff', ['RbsChange.i18n', function (i18n)
	{
		return function (input, match) {
			var output, diffObj, diffs;

			if (angular.isObject(input)) {
				input = JSON.stringify(input);
			}
			if (angular.isObject(match)) {
				match = JSON.stringify(match);
			}

			output = '<span class="diff">';
			diffObj = new diff_match_patch();
			diffs = diffObj.diff_main(match || '', input || '');
			diffObj.diff_cleanupSemantic(diffs);

			angular.forEach(diffs, function (diff) {
				if (diff[0] === -1) {
					output += '<del title="' + i18n.trans('m.rbs.admin.adminjs.deleted | ucf') + '">' + diff[1] + '</del>';
				} else if (diff[0] === 1) {
					output += '<ins title="' + i18n.trans('m.rbs.admin.adminjs.added | ucf') + '">' + diff[1] + '</ins>';
				} else {
					output += diff[1];
				}
			});

			return output + '</span>';
		};

	}]);


	app.filter('rbsStatusLabel', ['RbsChange.i18n', function (i18n)
	{
		return function (input) {
			if (!input) {
				return '';
			}
			return i18n.trans('m.rbs.admin.adminjs.status_' + angular.lowercase(input) + '|ucf');
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsMaxNumber
	 * @function
	 *
	 * @description
	 * For large numbers, displays the `max` value appended with a +
	 *
	 * @param {Number} number The input number.
	 * @param {Number=} max The maximum value to display, defaults to 99.
	 */
	app.filter('rbsMaxNumber', ['$filter', function ($filter)
	{
		return function (input, max) {
			max = max || 99;
			if (input > max) {
				return $filter('number')(max) + '+';
			}
			return $filter('number')(input);
		};

	}]);


	//-------------------------------------------------------------------------
	//
	// Date formats.
	//
	//-------------------------------------------------------------------------


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsDateTime
	 * @function
	 *
	 * @description
	 * Formats a Date object with date and time.
	 *
	 * @param {Date} date The date to format.
	 */
	app.filter('rbsDateTime', ['$filter', function ($filter)
	{
		return function (input) {
			return $filter('date')(input, 'medium');
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsDate
	 * @function
	 *
	 * @description
	 * Formats a Date object with date, without time.
	 *
	 * @param {Date} date The date to format.
	 */
	app.filter('rbsDate', ['$filter', function ($filter)
	{
		return function (input) {
			return $filter('date')(input, 'mediumDate');
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsTime
	 * @function
	 *
	 * @description
	 * Formats a Date object with time, without the date.
	 *
	 * @param {Date} date The date to format.
	 */
	app.filter('rbsTime', ['$filter', function ($filter)
	{
		return function (input) {
			return $filter('date')(input, 'mediumTime');
		};
	}]);


	/**
	 * @ngdoc filter
	 * @name RbsChange.filter:rbsBoolean
	 * @function
	 *
	 * @description
	 * Formats a Boolean value with localized <em>yes</em> or <em>no</em>.
	 *
	 * @param {Boolean} value The boolean value to format.
	 */
	app.filter('rbsBoolean', ['RbsChange.i18n', function (i18n)
	{
		return function (input) {
			return i18n.trans(input ? 'm.rbs.admin.adminjs.yes' : 'm.rbs.admin.adminjs.no');
		};
	}]);

})();