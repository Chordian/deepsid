
/**
 * DeepSID / styledSelect
 */

var styledCursorScrolled = false, filter = "", filterTimer;

/**
 * Plugin to replace an un-stylable default select drop-down box.
 *
 * @param {string} cls		If specified, class with additional styling.
 */
$.fn.styledSelect = function(cls) {
	return this.each(function() {

		var $this = $(this), numberOfOptions = $(this).children("option").length;

		// Hides the original select element
		$this.addClass("s-hidden");

		// Wrap the select element in a div
		// A parameter can add a class with additional styling (for example absolute positioning)
		$this.wrap('<div class="select '+(typeof cls === 'undefined' ? '' : cls)+' unselectable" unselectable="on"></div>');

		// Insert a styled div to sit over the top of the hidden select element
		$this.after('<div class="styledSelect ellipsis" rel="'+$this.children("option[selected]").val()+'"></div>');

		// Cache the styled div
		var $styledSelect = $this.next("div.styledSelect");

		// Show the selected (if present) or else the first select option in the styled div
		var selected = $this.children("option[selected]").text();
		$styledSelect.text(selected === "" ? $this.children("option").eq(0).text() : selected);

		// Insert an unordered list after the styled div and also cache the list
		var $list = $("<ul />", {
			"class": "options"
		}).insertAfter($styledSelect);

		// Insert a list item into the unordered list for each select option
		var liClass, liDiv, liCluster = "";
		for (var i = 0; i < numberOfOptions; i++) {
			liClass = typeof $this.children('option').eq(i).attr('class') === "undefined" ? '' : ' class="'+$this.children('option').eq(i).attr('class')+'"';
			liDiv = typeof $this.children('option').eq(i).attr('title') === "undefined" ? '' : '<div class="s-icon '+$this.children('option').eq(i).attr('title')+'">'+'</div>';
			liCluster += '<li rel="'+$this.children('option').eq(i).val()+'"'+liClass+'>'+liDiv+$this.children('option').eq(i).text()+'</li>';
		}
		$list.append(liCluster);

		// Show the unordered list when the styled div is clicked (also hides it if the div is clicked again)
		$styledSelect.click(function(event) {
			if (!$(this).hasClass("disabled")) {
				event.stopPropagation();
				$("div.styledSelect").not(this).removeClass("active").next("ul.options").hide();
				$(this).toggleClass("active").next("ul.options").toggle();

				// Mark the LI row corresponding to the current DIV selection
				var $selected = $(this).next("ul.options").children("li[rel='"+$(this).attr("rel")+"']");
				var pixelPosLi = $selected.index() * $selected.height();
				SelectNewLi($selected);
				// Set scroll position of the DIV to get the LI row into view
				if ($list.scrollTop() > pixelPosLi)
					$list.scrollTop(pixelPosLi);
				else if ($list.scrollTop() + ($list.height() - $selected.height()) < pixelPosLi)
					$list.scrollTop(pixelPosLi - ($list.height() - $selected.height()));
			}
		});

		// Hides the unordered list when a list item is clicked and updates the styled div to show the selected list item
		// Also updates the original select element to have the value of the equivalent option
		$list.children("li").click(function(event) {
			event.stopPropagation();
			var $li = $(this);
			if (!$li.hasClass("disabled")) {
				$this.val($li.attr("rel"));
				$styledSelect
					.attr("rel",$li.attr("rel"))
					.text($li.text())
					.removeClass("active")
					.trigger("change");
				$list.hide();
			}
		})

		// Hovering on an LI row to highlight it (leaving the UL will deliberately maintain the highlighting)
		$list.on("mouseenter","li",function() {
			var $li = $(this);
			if (!$li.hasClass("disabled")) {
				// Ignore if UL was scrolled because of cursor up/down keys
				if (styledCursorScrolled)
					styledCursorScrolled = false;
				else
					SelectNewLi($li);
			}
		});

		// Reset old LI row and highlight the new one
		function SelectNewLi($this) {
			// Reset the previously highlighted LI row
			$this.parent("ul").children("li.selected")
				.removeClass("selected")
				.css({
					color:"#444",
					background:"#fff"
				})
				.find("div").css("background-position","0 0");
			// Highlight the new LI row (and change its icon)
			$this.addClass("selected")
				.css({
					color:"#fff",
					background:"#474937"
				})
				.find("div").css("background-position","-16px 0");
		};

		// Hides the unordered list when clicking outside of it
		$(document).click(function() {
			$styledSelect.removeClass("active");
			$list.hide();
		});

		// When using the keyboard in a opened drop-down list
		$(document).keydown(function(event) {
			if ($styledSelect.hasClass("active")) {
				styledCursorScrolled = true; // Avoid interference from the mouse
				var currentLi = $list.children("li.selected").index(),
					$li = $list.children("li");
				switch (event.keyCode) {
					case 27:	// Esc
						event.preventDefault();
						$styledSelect.removeClass("active");
						$list.hide();
						break;
					case 37:	// Cursor left
					case 38:	// Cursor up
						event.preventDefault();
						if (currentLi) {
							$prevLi = $li.eq(--currentLi);
							while ($prevLi.hasClass("disabled")) {
								if (currentLi == 0) return false;
								$prevLi = $li.eq(--currentLi);
							}
							SelectNewLi($prevLi);
							var pixelPosLi = currentLi * $li.height();
							if ($list.scrollTop() > pixelPosLi)
								$list.scrollTop(pixelPosLi);
						}
						break;
					case 39:	// Cursor right
					case 40:	// Cursor down
						event.preventDefault();
						$nextLi = $li.eq(++currentLi);
						while ($nextLi.hasClass("disabled")) {
							if (currentLi == $li.length) return false;
							$nextLi = $li.eq(++currentLi);
						}
						SelectNewLi($nextLi);
						var pixelPosLi = currentLi * $li.height();
						if ($list.scrollTop() + ($list.height() - $li.height()) < pixelPosLi)
							$list.scrollTop(pixelPosLi - ($list.height() - $li.height()));
						break;
					case 36:	// Home
						event.preventDefault();
						currentLi = 0;
						$thisLi = $li.eq(currentLi);
						while ($thisLi.hasClass("disabled")) {
							if (currentLi == $li.length) return false;
							$thisLi = $li.eq(++currentLi);
						}
						SelectNewLi($thisLi);
						$list.scrollTop(0);
						break;
					case 35:	// End
						event.preventDefault();
						currentLi = $li.length - 1;
						$thisLi = $li.eq(currentLi);
						while ($thisLi.hasClass("disabled")) {
							if (currentLi == 0) return false;
							$thisLi = $li.eq(--currentLi);
						}
						SelectNewLi($thisLi);
						$list.scrollTop(($li.length - 1) * $li.height());
						break;
					default:	// Filter typing
						filter += String.fromCharCode(event.keyCode).toLowerCase();

						// Match typing with text contents of an LI (not its 'rel' attribute)
						var filterLi = $list.children("li")
							.filter(function() {
								return $(this).text().toLowerCase().indexOf(filter) == 0;
							}).index();

						$thisLi = $li.eq(filterLi);					
						if (filterLi != -1 && !$thisLi.hasClass("disabled")) {
							var pixelPosLi = filterLi * $li.height();
							SelectNewLi($thisLi);
							// Set scroll position of the DIV to get the filtered LI row into view
							if ($list.scrollTop() > pixelPosLi)
								$list.scrollTop(pixelPosLi);
							else if ($list.scrollTop() + ($list.height() - $li.height()) < pixelPosLi)
								$list.scrollTop(pixelPosLi - ($list.height() - $li.height()));
						}

						clearTimeout(filterTimer);
						filterTimer = setTimeout(function(){
							filter = ""; // Time out - reset building a fast-typing filter string
						},250);
				}
			}
		});

		// Pressing "Enter" needs KeyUp or it won't also work in IE8
		$(document).keyup(function(event) {
			if (event.keyCode == 13 && $styledSelect.hasClass("active")) {
				event.preventDefault();
				$list.children("li").eq($list.children("li.selected").index()).trigger("click");
			}
		});

	});
};

/**
 * Plugin to set a styled drop-down box to a value (present in option child).
 *
 * @param {string} value	Value to set.
 */
$.fn.styledSetValue = function(value) {
	return this.each(function() {
		$styledSelect = $(this).next("div.styledSelect");
		$(this).val(value);
		$styledSelect
			.attr("rel",value)
			.text($styledSelect.next("ul.options").children("li[rel='"+value+"']").text());
	});
};

/**
 * Plugin to get current value (as from an option child) from a styled drop-down box.
 *
 * @return {string}			The current value.
 */
$.fn.styledGetValue = function() {
	return $(this).val();
};

/**
 * Plugin to apply a background color to a styled drop-down box depending on
 * its state - reset/default or not.
 *
 * @param {boolean} bool	TRUE for white (reset/default), FALSE for yellow.
 */
$.fn.styledDefaultColor = function(bool) {
	return this.each(function() {
		$(this)
			.next("div.styledSelect")
			.css("background",bool ? "#f4f4f4" : "#f4f4aa");
	});
};

/**
 * Plugin to enable or disable options in the styled drop-down box.
 *
 * @param {string} values	The values of the options, separated by spaces.
 * @param {string} state	Set to "enabled" or "disabled".
 */
$.fn.styledOptionState = function(values, state) {
	return this.each(function() {
		if (typeof values !== "undefined") {
			$.each(values.split(" "), function(i, value) {
				var $option = $(this).next("div.styledSelect").next("ul.options").children("li[rel='"+value+"']");
				$option.removeClass("disabled");
				if (state == "disabled") $option.addClass("disabled");
			}.bind(this));
		}
	});
}

/**
 * Plugin to enable or disable a styled drop-down box.
 *
 * @param {string} state 	Set to "enabled" or "disabled".
 */
$.fn.styledState = function(state) {
	return this.each(function() {
		if (state == "enabled") {
			$(this)
				.next("div.styledSelect")
				.removeClass("disabled")
				.css("color","#444")
				.parents("div.select")
				.css("opacity","1.0");
		} else {
			$(this)
				.next("div.styledSelect")
				.addClass("disabled")
				.css("color","#777")
				.parents("div.select")
				.css("opacity","0.5");
		}
	});
};