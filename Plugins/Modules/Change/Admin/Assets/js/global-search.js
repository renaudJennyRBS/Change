(function ($) {

	var GlobalSearch = function ($input, uiSelector) {

		this.$el = $input;
		this.uiSelector = uiSelector;
		this.results = [ ];
		this.$revealed = null;
		this.highlightClass = 'highlighted';
		this.$suggestBox = $('#globalSearchInputDropDown');
		this.$suggestBoxActiveEl = null;

		var me = this;
		this.$suggestBox
		.on('hover', '.globalSearchSuggestion', function (e) {
			if (e.type === 'mouseenter') {
				me.reveal($(this).data('originalElm'));
			} else {
				me.unreveal();
			}
		})
		.on('click.globalsearch', 'a.globalSearchSuggestion', function (e) {
			me.unreveal();
			me.hideResults();
		});

		this.$el.keydown(function (e) {
			if (e.keyCode === 27) {
				me.unreveal();
				me.hideResults();
			} else if (e.keyCode === 13) {
				if (me.$suggestBoxActiveEl) {
					e.preventDefault();
					e.stopPropagation();
					me.hideResults();
					me.unreveal();
					me.$suggestBoxActiveEl.find('a').off('click').click();
					me.$el.val('');
				}
			} else if (e.keyCode === 40) {
				e.stopPropagation();
				e.preventDefault();
				me.focusSuggestion('next');
			} else if (e.keyCode === 38) {
				e.stopPropagation();
				e.preventDefault();
				me.focusSuggestion('prev');
			}
		});

		this.$el.keyup(function (e) {
			if (e.keyCode !== 27 && e.keyCode !== 13 && e.keyCode !== 40 && e.keyCode !== 38) {
				me.search($(this).val());
			}
		});

		$('html').on('click touchstart', function (e) {
			me.unreveal();
			me.hideResults();
		});

		this.$el.focus(function () {
			me.search($(this).val());
		});

	};


	GlobalSearch.prototype = {

		search: function (query) {
			//console.log("Search " + query);
			query = query.trim().toLowerCase();
			this.results = [ ];
			if (query.length > 1) {
				var me = this;
				$(this.uiSelector).each(function () {
					$(this).find('a[href]:not(.globalSearchSuggestion)').each(function () {
						if ($(this).text().toLowerCase().indexOf(query) >= 0 || ($(this).attr('title') && $(this).attr('title').indexOf(query) >= 0) ) {
							me.results.push($(this));
							$(this).data('foundWith', null);
						} else if ($(this).data('uiSearch')) {
							var kw = $(this).data('uiSearch').split(/,/);
							for (var i=0 ; i<kw.length ; i++) {
								if (kw[i].indexOf(query) === 0) {
									$(this).data('foundWith', kw[i]);
									me.results.push($(this));
									break;
								}
							}
						}
					});
					$(this).find('li.nav-header:not(.globalSearchSuggestion)').each(function () {
						if ($(this).text().toLowerCase().indexOf(query) >= 0) {
							me.results.push($(this));
						}
					});
				});
			}

			this.unreveal();
			this.showResults(query);

			return this.results;
		},

		showResults: function (query) {
			query = query.trim().toLowerCase();
			if (query.length > 1) {
				this.$suggestBox.empty();
				this.$suggestBoxActiveEl = null;
				if (this.results.length > 0) {
					this.$suggestBox.append('<li class="nav-header globalSearchSuggestion">Menus</li>');
					for (var i=0 ; i<this.results.length ; i++) {
						var item = '<li><a class="globalSearchSuggestion" href="' + this.results[i].attr('href') + '"';
						if (this.results[i].data('toggle')) {
							item += ' data-toggle="'+this.results[i].data('toggle')+'"';
						}
						if (this.results[i].data('target')) {
							item += ' data-target="' + this.results[i].data('target') + '"';
						}
						var reg = new RegExp("("+query+")", "ig");
						item += '>' + this.results[i].text().replace(reg, "<strong><u>$1</u></strong>");
						if (this.results[i].data('foundWith')) {
							item += ' <em class="muted">- ' + this.results[i].data('foundWith') + '</em>';
						}
						item += '</a></li>';
						this.$suggestBox.append(item);
						this.$suggestBox.find('a[href]').last().data('originalElm', this.results[i]);
					}
				}
				this.$suggestBox.append('<li class="nav-header globalSearchSuggestion">Contenus</li>');
				this.$suggestBox.append('<li><a href="search/' + query + '" class="globalSearchSuggestion"><i class="icon-search"></i> Rechercher ' + query + '</a></li>');
				this.$suggestBox.closest('form').addClass('open');
				this.focusSuggestion('next');
			} else {
				this.hideResults();
			}
		},


		hideResults: function () {
			this.$suggestBox.closest('form').removeClass('open');
		},

		reveal: function ($el) {
			this.unreveal();
			if ($el) {
				this.$revealed = $el;
				this.$revealed.closest('li.dropdown').addClass('open');
				this.$revealed.closest('li').addClass(this.highlightClass);
			}
		},

		unreveal: function () {
			if (this.$revealed) {
				this.$revealed.closest('li.dropdown').removeClass('open');
				this.$revealed.closest('li').removeClass(this.highlightClass);
				this.$revealed = null;
			}
		},

		focusSuggestion: function (prevOrNext) {
			if (this.$suggestBoxActiveEl) {
				var $dest = this.$suggestBoxActiveEl[prevOrNext+'All']('li:not(.nav-header)');
				if ($dest.length >= 1) {
					this.$suggestBoxActiveEl.removeClass('active');
					this.$suggestBoxActiveEl = $dest.first();
					this.$suggestBoxActiveEl.addClass('active');
				}
			} else {
				this.$suggestBoxActiveEl = this.$suggestBox.find('li:not(.nav-header)').first();
				this.$suggestBoxActiveEl.addClass('active');
			}

			this.reveal(this.$suggestBoxActiveEl.find('.globalSearchSuggestion').data('originalElm'));
		}

	};


	$.fn.globalSearch = function (selector) {
		return this.each(function () {
			var gs = $(this).data('globalSearch');
			if (!gs) {
				gs = new GlobalSearch($(this), selector);
				$(this).data('globalSearch', gs);
			}
		});
	};

})( window.jQuery );