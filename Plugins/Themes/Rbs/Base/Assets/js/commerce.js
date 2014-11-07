(function(jQuery) {
	"use strict";

	var app = angular.module('RbsChangeApp');

	var DECIMAL_SEP = '.';

	function formatNumber(number, pattern, groupSep, decimalSep, fractionSize) {
		if (number == null || !isFinite(number) || angular.isObject(number)) return '';

		var isNegative = number < 0;
		number = Math.abs(number);
		var numStr = number + '', formatedText = '', parts = [];

		var hasExponent = false;
		if (numStr.indexOf('e') !== -1) {
			var match = numStr.match(/([\d\.]+)e(-?)(\d+)/);
			if (match && match[2] == '-' && match[3] > fractionSize + 1) {
				numStr = '0';
				number = 0;
			} else {
				formatedText = numStr;
				hasExponent = true;
			}
		}

		if (!hasExponent) {
			var fractionLen = (numStr.split(DECIMAL_SEP)[1] || '').length;

			// safely round numbers in JS without hitting imprecisions of floating-point arithmetics
			// inspired by:
			// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round
			number = +(Math.round(+(number.toString() + 'e' + fractionSize)).toString() + 'e' + -fractionSize);

			var fraction = ('' + number).split(DECIMAL_SEP);
			var whole = fraction[0];
			fraction = fraction[1] || '';

			var i, pos = 0, lgroup = pattern.lgSize, group = pattern.gSize;

			if (whole.length >= (lgroup + group)) {
				pos = whole.length - lgroup;
				for (i = 0; i < pos; i++) {
					if ((pos - i)%group === 0 && i !== 0) {
						formatedText += groupSep;
					}
					formatedText += whole.charAt(i);
				}
			}

			for (i = pos; i < whole.length; i++) {
				if ((whole.length - i)%lgroup === 0 && i !== 0) {
					formatedText += groupSep;
				}
				formatedText += whole.charAt(i);
			}

			// format fraction part.
			while(fraction.length < fractionSize) {
				fraction += '0';
			}
			if (fractionSize && fractionSize !== "0") formatedText += decimalSep + fraction.substr(0, fractionSize);
		} else {

			if (fractionSize > 0 && number > -1 && number < 1) {
				formatedText = number.toFixed(fractionSize);
			}
		}

		parts.push(isNegative ? pattern.negPre : pattern.posPre);
		parts.push(formatedText);
		parts.push(isNegative ? pattern.negSuf : pattern.posSuf);
		return parts.join('');
	}

	function getFormats(LCID) {
		var formats;
		switch (LCID) {
			case 'en_US' :
				formats = {
					"DECIMAL_SEP": ".", "GROUP_SEP": ",",
					"PATTERN": {
						"gSize": 3, "lgSize": 3,
						"negPre": "\u00a4-", "negSuf": "",
						"posPre": "\u00a4", "posSuf": ""
					}
				};
				break;
			case 'en_GB' :
				formats = {
					"DECIMAL_SEP": ".", "GROUP_SEP": ",",
					"PATTERN": {
						"gSize": 3, "lgSize": 3,
						"negPre": "\u00a4-", "negSuf": "",
						"posPre": "\u00a4", "posSuf": ""
					}
				};
				break;
			case 'de_DE' :
				formats = {
					"DECIMAL_SEP": ",", "GROUP_SEP": ".",
					"PATTERN": {
						"gSize": 3, "lgSize": 3,
						"negPre": "-", "negSuf": "\u00a0\u00a4",
						"posPre": "", "posSuf": "\u00a0\u00a4"
					}
				};
				break;
			default :
				formats = {
					"DECIMAL_SEP": ",", "GROUP_SEP": "\u00a0",
					"PATTERN": {
						"gSize": 3, "lgSize": 3,
						"negPre": "-", "negSuf": "\u00a0\u00a4",
						"posPre": "", "posSuf": "\u00a0\u00a4"
					}
				}
		}
		return formats;
	}

	app.filter('rbsFormatPrice', ['RbsChange.AjaxAPI', function (AjaxAPI) {

		var formats = getFormats(AjaxAPI.getLCID());

		function filter(amount, currencyCode) {
			var symbol;
			switch (currencyCode) {
				case 'USD' : symbol = '$'; break;
				case 'GBP' : symbol = '£'; break;
				default : symbol = '€'; break;
			}
			return formatNumber(amount, formats.PATTERN, formats.GROUP_SEP, formats.DECIMAL_SEP, 2).replace(/\u00a4/g, symbol);
		}
		return filter;
	}]);

	app.filter('rbsFormatRate', ['RbsChange.AjaxAPI', function (AjaxAPI) {

		var formats = getFormats(AjaxAPI.getLCID());
		function filter(rate, fractionSize) {
			if (!angular.isNumber(fractionSize)) {
				fractionSize = 2;
			}
			var symbol = '%';
			return formatNumber(rate * 100.0 , formats.PATTERN, formats.GROUP_SEP, formats.DECIMAL_SEP, fractionSize).replace(/\u00a4/g, symbol);
		}
		return filter;
	}]);
})(window.jQuery);