/**
 * Corporate Web Image, Inc. 
 * jQuery UI Nested Menu - heavily based on:
 *
 * jQuery UI Nested Sortable
 * v 1.4 / 30 dec 2011
 * http://mjsarfatti.com/code/nestedSortable
 *
 * Depends on:
 *	 jquery.ui.sortable.js 1.8+
 *
 * Copyright (c) 2010-2012 Manuele J Sarfatti
 * Licensed under the MIT License
 * http://www.opensource.org/licenses/mit-license.php
 */

(function($) {

	$.widget('cwi.nestedSortable', $.extend({}, $.ui.sortable.prototype, {
		options: {
			tabSize: 20,
			disableNesting: 'cwi-nestedSortable-no-nesting',
			errorClass: 'cwi-nestedSortable-error',
			listType: 'ol',
			items: 'li',
			maxLevels: 0,
			protectRoot: false,
			rootID: 0,
			rtl: false,
			isAllowed: function(parent, item) { return true; }
			/*
			sortable options:
			handle:'' // handle that can be grabbed to move list item - be careful about selecting an element that wraps children elements
			*/
		},


		_create: function() {
			this.element.data('sortable', this.element.data('nestedSortable'));

			if (!this.element.is(this.options.listType))
				throw new Error('nestedSortable: Please check the listType option is set to your actual list type');

			return $.ui.sortable.prototype._create.apply(this, arguments);
		},

		destroy: function() {
			this.element
				.removeData('nestedSortable')
				.unbind('.nestedSortable');
			return $.ui.sortable.prototype.destroy.apply(this, arguments);
		},

		_mouseDrag: function(event) {

			//Compute the helpers position
			this.position = this._generatePosition(event);
			this.positionAbs = this._convertPositionTo('absolute');

			if (!this.lastPositionAbs) {
				this.lastPositionAbs = this.positionAbs;
			}

			//Do scrolling
			if(this.options.scroll) {
				var o = this.options, scrolled = false;
				if(this.scrollParent[0] != document && this.scrollParent[0].tagName != 'HTML') {

					if((this.overflowOffset.top + this.scrollParent[0].offsetHeight) - event.pageY < o.scrollSensitivity)
						this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop + o.scrollSpeed;
					else if(event.pageY - this.overflowOffset.top < o.scrollSensitivity)
						this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop - o.scrollSpeed;

					if((this.overflowOffset.left + this.scrollParent[0].offsetWidth) - event.pageX < o.scrollSensitivity)
						this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft + o.scrollSpeed;
					else if(event.pageX - this.overflowOffset.left < o.scrollSensitivity)
						this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft - o.scrollSpeed;

				} else {

					if(event.pageY - $(document).scrollTop() < o.scrollSensitivity)
						scrolled = $(document).scrollTop($(document).scrollTop() - o.scrollSpeed);
					else if($(window).height() - (event.pageY - $(document).scrollTop()) < o.scrollSensitivity)
						scrolled = $(document).scrollTop($(document).scrollTop() + o.scrollSpeed);

					if(event.pageX - $(document).scrollLeft() < o.scrollSensitivity)
						scrolled = $(document).scrollLeft($(document).scrollLeft() - o.scrollSpeed);
					else if($(window).width() - (event.pageX - $(document).scrollLeft()) < o.scrollSensitivity)
						scrolled = $(document).scrollLeft($(document).scrollLeft() + o.scrollSpeed);

				}

				if(scrolled !== false && $.ui.ddmanager && !o.dropBehaviour)
					$.ui.ddmanager.prepareOffsets(this, event);
			}

			//Regenerate the absolute position used for position checks
			this.positionAbs = this._convertPositionTo('absolute');

			//Set the helper position
			if(!this.options.axis || this.options.axis != 'y') this.helper[0].style.left = this.position.left+'px';
			if(!this.options.axis || this.options.axis != 'x') this.helper[0].style.top = this.position.top+'px';

			//Rearrange
			for (var i = this.items.length - 1; i >= 0; i--) {

				//Cache variables and intersection, continue if no intersection
				var item = this.items[i], itemElement = item.item[0], intersection = this._intersectsWithPointer(item);
				if (!intersection) continue;

				if(itemElement != this.currentItem[0] //cannot intersect with itself
					&&	this.placeholder[intersection == 1 ? 'next' : 'prev']()[0] != itemElement //no useless actions that have been done before
					&&	!$.contains(this.placeholder[0], itemElement) //no action if the item moved is the parent of the item checked
					&& (this.options.type == 'semi-dynamic' ? !$.contains(this.element[0], itemElement) : true)
					//&& itemElement.parentNode == this.placeholder[0].parentNode // only rearrange items within the same container
				) {

					$(itemElement).mouseenter();

					this.direction = intersection == 1 ? 'down' : 'up';

					if (this.options.tolerance == 'pointer' || this._intersectsWithSides(item)) {
						$(itemElement).mouseleave();
						this._rearrange(event, item);
					} else {
						break;
					}

					// Clear emtpy ul's/ol's
					this._clearEmpty(itemElement);

					this._trigger('change', event, this._uiHash());
					break;
				}
			}

			var parentItem = (this.placeholder[0].parentNode.parentNode &&
							 $(this.placeholder[0].parentNode.parentNode).closest('.ui-sortable').length)
				       			? $(this.placeholder[0].parentNode.parentNode)
				       			: null,
			    level = this._getLevel(this.placeholder),
			    childLevels = this._getChildLevels(this.helper),
			    previousItem = this.placeholder[0].previousSibling ? $(this.placeholder[0].previousSibling) : null;

			if (previousItem != null) {
				while (previousItem[0].nodeName.toLowerCase() != 'li' || previousItem[0] == this.currentItem[0]) {
					if (previousItem[0].previousSibling) {
						previousItem = $(previousItem[0].previousSibling);
					} else {
						previousItem = null;
						break;
					}
				}
			}

			var newList = document.createElement(o.listType);

			this.beyondMaxLevels = 0;
			
			// If the item is moved to the left, send it to its parent level
			if (parentItem != null &&
					(o.rtl && (this.positionAbs.left + this.helper.outerWidth() > parentItem.offset().left + parentItem.outerWidth()) ||
					!o.rtl && (this.positionAbs.left < parentItem.offset().left))) {
				parentItem.after(this.placeholder[0]);
				this._clearEmpty(parentItem[0]);
				this._trigger('change', event, this._uiHash());
			}
			// If the item is below another one and is moved to the right, make it a children of it
			else if (previousItem != null &&
						(o.rtl && (this.positionAbs.left + this.helper.outerWidth() < previousItem.offset().left + previousItem.outerWidth() - o.tabSize) ||
						!o.rtl && (this.positionAbs.left > previousItem.offset().left + o.tabSize))) {
				this._isAllowed(previousItem, level, level+childLevels+1);
				if (!previousItem.children(o.listType).length) {
					previousItem[0].appendChild(newList);
				}
				previousItem.children(o.listType)[0].appendChild(this.placeholder[0]);
				this._trigger('change', event, this._uiHash());
			}
			else {
				this._isAllowed(parentItem, level, level+childLevels);
			}

			//Post events to containers
			this._contactContainers(event);

			//Interconnect with droppables
			if($.ui.ddmanager) $.ui.ddmanager.drag(this, event);

			//Call callbacks
			this._trigger('sort', event, this._uiHash());

			this.lastPositionAbs = this.positionAbs;
			return false;

		},

		_mouseStop: function(event, noPropagation) {

			// If the item is in a position not allowed, send it back
			if (this.beyondMaxLevels) {

				this.placeholder.removeClass(this.options.errorClass);

				if (this.domPosition.prev) {
					$(this.domPosition.prev).after(this.placeholder);
				} else {
					$(this.domPosition.parent).prepend(this.placeholder);
				}

				this._trigger('revert', event, this._uiHash());

			}

			// Clean last empty ul/ol
			for (var i = this.items.length - 1; i >= 0; i--) {
				var item = this.items[i].item[0];
				this._clearEmpty(item);
			}

			$.ui.sortable.prototype._mouseStop.apply(this, arguments);

		},
		/**
		 * Exports array
		 **/
		export : function(options) {
		

			var o = $.extend({}, this.options, options);
			var items = this._getItemsAsjQuery(o && o.connected);
			var rtn = [];

			$(items).each(function() {
				
				var res = ($(this).attr('id') || '').match(/(.+)[-=_](.+)/);
				var pid = ($(this).parent(o.listType).parent(o.items).attr('id') || '').match(/(.+)[-=_](.+)/);
				
				if (res) {
					/*
					rtn.push((res[1] + '[' + (o.key && o.expression ? res[1] : res[2]) + ']')
						+ '='
						+ (pid ? (o.key && o.expression ? pid[1] : pid[2]) : o.rootID));
					*/
					/*
					rtn.push(res[0]
						+ '='
						+ (pid ? pid[0] : o.rootID));
					*/
					rtn[res[0]] = (pid ? pid[0] : o.rootID);
				}
			});
			/*
			if(!rtn.length && o.key) {
				rtn.push(o.key + '=');
			}
			*/

			return rtn;//return rtn.join('&');

		},
		
		/**
		 * Removed:
		 *	serialize: function(options)
		 * 	toHierarchy : function(options)
		 * 	toArray : function(options)
		 **/
		_clearEmpty: function(item) {

			var emptyList = $(item).children(this.options.listType);
			if (emptyList.length && !emptyList.children().length) {
				emptyList.remove();
			}

		},

		_getLevel: function(item) {

			var level = 1;

			if (this.options.listType) {
				var list = item.closest(this.options.listType);
				while (!list.is('.ui-sortable')) {
					level++;
					list = list.parent().closest(this.options.listType);
				}
			}

			return level;
		},

		_getChildLevels: function(parent, depth) {
			var self = this,
			    o = this.options,
			    result = 0;
			depth = depth || 0;

			$(parent).children(o.listType).children(o.items).each(function (index, child) {
					result = Math.max(self._getChildLevels(child, depth + 1), result);
			});

			return depth ? result + 1 : result;
		},

		_isAllowed: function(parentItem, level, levels) {
			var o = this.options,
				isRoot = $(this.domPosition.parent).hasClass('ui-sortable') ? true : false;

			// Is the root protected?
			// Are we trying to nest under a no-nest?
			// Are we nesting too deep?
			if (!o.isAllowed(parentItem, this.placeholder) ||
				parentItem && parentItem.hasClass(o.disableNesting) ||
				o.protectRoot && (parentItem == null && !isRoot || isRoot && level > 1)) {
					this.placeholder.addClass(o.errorClass);
					if (o.maxLevels < levels && o.maxLevels != 0) {
						this.beyondMaxLevels = levels - o.maxLevels;
					} else {
						this.beyondMaxLevels = 1;
					}
			} else {
				if (o.maxLevels < levels && o.maxLevels != 0) {
					this.placeholder.addClass(o.errorClass);
					this.beyondMaxLevels = levels - o.maxLevels;
				} else {
					this.placeholder.removeClass(o.errorClass);
					this.beyondMaxLevels = 0;
				}
			}
		}

	}));

	$.cwi.nestedSortable.prototype.options = $.extend({}, $.ui.sortable.prototype.options, $.cwi.nestedSortable.prototype.options);
})(jQuery);