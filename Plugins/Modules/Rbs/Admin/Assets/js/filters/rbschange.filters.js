(function () {

	var app = angular.module('RbsChange');


	/**
	 * Renders an HTML string that summarizes the given list of documents.
	 *
	 * @param docs List of documents
	 *
	 * @return HTML string
	 */
	app.filter('rbsDocumentListSummary', ['RbsChange.i18n', function (i18n) {
		return function (docs) {

			function getLabel (obj) {
				if (angular.isObject(obj)) {
					if ('label' in obj) {
						return obj.label;
					} else if ('name' in obj) {
						return obj.name;
					}
				}
				return ''+obj;
			}

			var out = '';
			if (angular.isArray(docs)) {

				if (docs.length > 3) {
					out = i18n.trans('m.rbs.admin.adminjs.filter_document_list_summary_more_three', {'count':docs.length, 'element1' : getLabel(docs[0]), 'element2' : getLabel(docs[1]), 'element3' : getLabel(docs[2])});
				} else if (docs.length > 1) {
					var msg = [ ];
					for (var i=0 ; i<docs.length-1 ; i++) {
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
	 * Highlights the given <code>needle</code> in the given <code>input</code>.
	 *
	 * @param needle The string to be highlighted in the filter input.
	 *
	 * @return HTML string (use with ng-bind-html).
	 *
	 * @example
	 * <code>
	 * <span ng-bind-html="product.label | highlight:'polo'"></span>
	 * </code>
	 */
	app.filter('rbsHighlight', function () {

		return function (input, needle) {
			var regex = new RegExp("(" + needle + ")", "ig");
			return input.replace(regex, '<strong class="highlight">$1</strong>');
		};

	});

	/**
	 * @description
	 * Removes all the HTML tags from the input string.
	 *
	 * @example
	 * <code>
	 * <p>{{page.contents | rbsStripHtmlTags}}</p>
	 * </code>
	 */
	app.filter('rbsStripHtmlTags', function () {

		return function (input, allowed) {
			allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
			var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
				commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
			return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
				return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
			});
		};

	});


	function center_text (text, needle, length) {
		var p = text.toLowerCase().indexOf(needle.toLowerCase());
		if (p !== -1) {
			var start = Math.max(0, p - length/2);
			var end = start + length;
			if (start > 0) {
				return '... ' + text.substring(start, end) + '...';
			} else {
				return text.substring(start, end) + '...';
			}
		}
		return text.substring(0, length) + '...';
	}

	/**
	 * Renders an empty label with a hyphen (by default).
	 *
	 * @return HTML string (use with ng-bind-html).
	 */
	app.filter('rbsEmptyLabel', function () {

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
	 * Add first letter of input to uppercase and the rest to lowercase
	 */
	app.filter('rbsCapitalize', function() {
		return function(input) {
			return input.substring(0,1).toUpperCase()+input.substring(1).toLowerCase();
		}
	});

	/**
	 * Returns a formatted and human readable file size from an input value in bytes.
	 *
	 * @return HTML string (use with ng-bind-html).
	 */
	app.filter('rbsFileSize', ['RbsChange.i18n', function (i18n) {

		var units = [
			['octets', i18n.trans('m.rbs.admin.adminjs.octets | ucf')],
			['Ko', i18n.trans('m.rbs.admin.adminjs.kilobytes | ucf')],
			['Mo', i18n.trans('m.rbs.admin.adminjs.megabytes | ucf')],
			['Go', i18n.trans('m.rbs.admin.adminjs.gigabytes | ucf')],
			['To', i18n.trans('m.rbs.admin.adminjs.terabytes | ucf')]
		];

		return function (bytes) {
			var value = bytes, u = 0;
			while (value >= 1024) {
				u++;
				value /= 1024.0;
			}
			if (u === 0) {
				return value + ' ' + units[u][0];
			}
			return Math.round(value) + ' <abbr title="' + units[u][1] + '">' + units[u][0] + '</abbr>';
		};

	}]);

	app.filter('rbsEllipsis', function () {

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


	// TODO Handle other BBcode tags.
	app.filter('rbsBBcode', function () {
		return function (input) {
			return input.replace(/\[(\/?)b\]/g,'<$1b>');
		};
	});


	// See lib/diff_match_patch
	// or https://code.google.com/p/google-diff-match-patch/
	app.filter('rbsDiff', ['RbsChange.i18n', function (i18n) {

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


	app.filter('rbsStatusLabel', ['RbsChange.i18n', function (i18n) {
		return function (input) {
			if (!input) {
				return '';
			}
			return i18n.trans('m.rbs.admin.adminjs.status_' + angular.lowercase(input) + '|ucf');
		};
	}]);


	app.filter('rbsMaxNumber', ['$filter', function ($filter) {

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


	app.filter('rbsDateTime', ['$filter', function ($filter) {
		return function (input) {
			return $filter('date')(input, 'medium');
		};
	}]);

	app.filter('rbsDate', ['$filter', function ($filter) {
		return function (input) {
			return $filter('date')(input, 'mediumDate');
		};
	}]);

	app.filter('rbsTime', ['$filter', function ($filter) {
		return function (input) {
			return $filter('date')(input, 'mediumTime');
		};
	}]);

	app.filter('rbsBoolean', ['RbsChange.i18n', function (i18n) {
		return function (input) {
			return i18n.trans(input ? 'm.rbs.admin.adminjs.yes' : 'm.rbs.admin.adminjs.no');
		};
	}]);

})();