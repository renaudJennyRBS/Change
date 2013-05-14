(function () {

	var app = angular.module('RbsChange');


	/**
	 * Renders an HTML string that summarizes the given list of documents.
	 *
	 * @param docs List of documents
	 *
	 * @return HTML string
	 */
	app.filter('documentListSummary', function () {
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
					out = "<strong>" + docs.length + " éléments</strong> dont " +
					"<strong class=\"element\">" + getLabel(docs[0]) + "</strong>, " +
					"<strong class=\"element\">" + getLabel(docs[1]) + "</strong> et " +
					"<strong class=\"element\">" + getLabel(docs[2]) + "</strong>";
				} else if (docs.length > 1) {
					var msg = [ ];
					for (var i=0 ; i<docs.length-1 ; i++) {
						msg.push(getLabel(docs[i]));
					}
					out = "les <strong>" + docs.length + " éléments</strong> suivants : " +
					'<strong class="element">' + msg.join('</strong>, <strong class=\"element\">') + '</strong> et ' +
					'<strong class="element">' + getLabel(docs[docs.length-1]) + '</strong>';
				} else {
					out = "<strong class=\"element\">" + getLabel(docs[0]) + "</strong>";
				}

			}

			return out;
		};
	});


	/**
	 * Highlights the given <code>needle</code> in the given <code>input</code>.
	 *
	 * @param needle The string to be highlighted in the filter input.
	 *
	 * @return HTML string (use with ng-bind-html-unsafe).
	 *
	 * @example
	 * <code>
	 * <span ng-bind-html-unsafe="product.label | highlight:'polo'"></span>
	 * </code>
	 */
	app.filter('highlight', function () {

		return function (input, needle) {
			var regex = new RegExp("(" + needle + ")", "ig");
			return input.replace(regex, '<strong class="highlight">$1</strong>');
		};

	});


	/**
	 * Returns the model's readable name from its system name.
	 */
	app.filter('modelLabel', ['RbsChange.Modules', function (Modules) {

		return function (model) {
			if (angular.isObject(model) && model.hasOwnProperty('model')) {
				model = model.model;
			}
			return (model in Modules.models) ? Modules.models[model] : model;
		};

	}]);


	app.filter('documentSummary', ['$filter', function ($filter) {

		return function (doc, needle, length) {
			length = length || 70;
			if ('contents' in doc && doc.contents) {
				if (needle) {
					return $filter('highlight')(center_text($filter('stripHtmlTags')(doc.contents), needle, length), needle);
				} else {
					return $filter('stripHtmlTags')(doc.contents).substring(0, length) + '...';
				}
			}
			return '';
		};

	}]);


	/**
	 * @description
	 * Removes all the HTML tags from the input string.
	 *
	 * @example
	 * <code>
	 * <p>{{page.contents | stripHtmlTags}}</p>
	 * </code>
	 */
	app.filter('stripHtmlTags', function () {

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
	 * Renders the label of a document, with additionnal labels such as "correction", "draft" (if any).
	 *
	 * @param doc Document instance.
	 *
	 * @return HTML string (use with ng-bind-html-unsafe).
	 */
	app.filter('documentSystemLabels', function () {

		return function (doc) {
			var out = '';

			if (doc.status === 'DRAFT') {
				out += '<span style="margin-left:3px; margin-top:2px" class="label pull-right">Brouillon</span>';
			}
			if (doc.tags.length === 1) {
				out += '<span style="margin-left:3px; margin-top:2px" class="label pull-right">' + doc.tags.length + ' <i class="icon-tag icon-white"></i></span>';
			}
			if (doc.tags.length >= 2) {
				out += '<span style="margin-left:3px; margin-top:2px" class="label pull-right">' + doc.tags.length + ' <i class="icon-tags icon-white"></i></span>';
			}

			out += doc.label;
			return out;
		};

	});


	app.filter('documentInfoLabels', function () {

		return function (doc) {
			var out = '';

			if (doc.tags) {
				if (doc.tags.length === 1) {
					out += '<span class="label" title="Un tag : ' + doc.tags[0].label + '">' + doc.tags.length + ' <i class="icon-tag icon-white"></i></span>';
				}
				if (doc.tags.length >= 2) {
					out += '<span class="label" title="' + doc.tags.length + ' tags : ';
					for (var i=0 ; i<doc.tags.length ; i++) {
						if (i > 0) {
							out += ', ';
						}
						out += doc.tags[i].label;
					}

					out += '">' + doc.tags.length + ' <i class="icon-tags icon-white"></i></span>';
				}
			}

			return out;
		};

	});


	/**
	 * Renders an empty label with a hyphen (by default).
	 *
	 * @return HTML string (use with ng-bind-html-unsafe).
	 */
	app.filter('emptyLabel', function () {

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
	 * Given a mediaId (and optionnal width and height), returns the URL of the thumbnail.
	 */
	app.filter('thumbnail', function () {

		return function (mediaId, maxWidth, maxHeight) {
			maxWidth = maxWidth || 100;
			maxHeight = maxHeight || 100;
			return 'thumbnail.php?maxWidth=' + maxWidth + '&maxHeight=' + maxHeight + '&media=' + mediaId;
		};

	});


	app.filter('underscores', function () {

		return function (input) {
			return input.replace(/[^a-z0-9]/ig, '_');
		};

	});


	/**
	 * Returns a formatted and human readable file size from an input value in bytes.
	 *
	 * @return HTML string (use with ng-bind-html-unsafe).
	 */
	app.filter('fileSize', function () {

		// TODO i18n
		var units = [
			['octets', 'octets'],
			['Ko', "Kilo-octets"],
			['Mo', "Mega-octets"],
			['Go', "Giga-octets"],
			['To', "Tera-octets"]
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

	});


	app.filter('percentage', ['$filter', function ($filter) {

		return function (input) {
			return $filter('number')(Math.round(input)) + ' %';
		};

	}]);


	app.filter('ellipsis', function () {

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
	app.filter('BBcode', function () {
		return function (input) {
			return input.replace(/\[(\/?)b\]/g,'<$1b>');
		};
	});


	// See lib/diff_match_patch
	// or https://code.google.com/p/google-diff-match-patch/
	app.filter('diff', function () {

		return function (input, match) {
			var output, diffObj, diffs;

			output = '<span class="diff">';
			diffObj = new diff_match_patch();
			diffs = diffObj.diff_main(match || '', input || '');
			diffObj.diff_cleanupSemantic(diffs);

			angular.forEach(diffs, function (diff) {
				if (diff[0] === -1) {
					output += '<del title="Supprimé">' + diff[1] + '</del>';
				} else if (diff[0] === 1) {
					output += '<ins title="Ajouté">' + diff[1] + '</ins>';
				} else {
					output += diff[1];
				}
			});

			return output + '</span>';
		};

	});


	app.filter('statusLabel', function () {

		// FIXME Put this somewhere else (i18n)
		var statuses = {
			'DRAFT'       : "Brouillon",
			'PUBLISHABLE' : "Publiable",
			'DEACTIVATED' : "Désactivé",
			'VALIDATION'  : "À valider",
			'ACTIVE'      : "Activé"
		};

		return function (input) {
			return statuses[input] || input;
		};

	});


	app.filter('documentURL', ['RbsChange.Breadcrumb', 'RbsChange.Utils', function (Breadcrumb, Utils) {

		return function (doc, urlName) {
			if (!Utils.isDocument(doc)) {
				return 'javascript:;';
			}
			var node = Breadcrumb.getCurrentNode();
			if (node && urlName !== 'tree') {
				return doc.url(urlName) + '?tn=' + node.id;
			}
			return doc.url(urlName);
		};

	}]);


	app.filter('maxNumber', ['$filter', function ($filter) {

		return function (input, max) {
			max = max || 99;
			if (input > max) {
				return $filter('number')(max) + '+';
			}
			return $filter('number')(input);
		};

	}]);


})();