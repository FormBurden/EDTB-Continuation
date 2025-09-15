(function () {
	var $section = $('#focal');
	var $panzoom = $section.find(".panzoom").panzoom({
		startTransform: 'scale(1.0)',
		animate: false,
		increment: 0.1,
		disablePan: false,
		disableZoom: true,
		cursor: "/style/img/cursor.svg"
	});

	/*$panzoom.parent().on("mousewheel.focal", function(e)
	 {
	 e.preventDefault();
	 var delta = e.delta || e.originalEvent.wheelDelta;
	 var zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;
	 $panzoom.panzoom('zoom', zoomOut, {
	 animate: false,
	 focal: e
	 });
	 });*/
})();

/**
 * size of the grid
 */
var gridsize = 6;

/**
 * location of body images
 * @type {string}
 */
var bodies = "/SystemMap/bodies";

Array.prototype.clean = function (deleteValue) {
	for (var i = 0; i < this.length; i++) {
		if (this[i] === deleteValue) {
			this.splice(i, 1);
			i--;
		}
	}
	return this;
};

window.onload = function () {
	/**
	 * add bodies according to url parameters
	 */
	var url_vars = (window.__SM_URL_VARS && window.__SM_URL_VARS.length) ? window.__SM_URL_VARS : (typeof getUrlVars === 'function' && getUrlVars().v1);

	if (url_vars) {
		var url_parts = url_vars.split("c").clean("");

		var controls = url_parts[1];

		var show_grid = controls.substr(0, 1);
		var show_bg = controls.substr(1, 1);
		var show_names = controls.substr(2, 1);

		var bodiesArr = url_parts[0].split("l").clean(""),
			i;

		$.getJSON("bodies.json", function (data) {
			for (i = 0; i < bodiesArr.length; i++) {
				var options = [],
					parts = bodiesArr[i].split("i"),
					ia;

				for (ia = 0; ia < parts.length; ia++) {
					var part = parts[ia],
						id = 0,
						value = 0,
						idVal = part.substr(0, 1),
						valVal = part.substr(1);

					if (idVal === "t") { id = 1; }
					if (idVal === "l") { id = 2; }
					if (idVal === "r") { id = 3; }
					if (idVal === "s") { id = 4; }
					if (idVal === "f") { id = 5; }

					options[id] = valVal;
				}

				var bodyid = options[0],
					typeid = options[1],
					landable = options[2],
					ringed = options[3],
					scanned = options[4],
					firstdisc = options[5];

				$.each(data, function (i, item) {
					if (item.id * 1 === bodyid * 1) {
						var imgname = item.name.replace(" ", "_").replace(",", ""),
							width = item.width,
							minvalue = item.min_value,
							maxvalue = item.max_value,
							imgid = typeid,
							bid;

						if (typeid !== "0" && typeid !== "") {
							bid = i + "_" + imgid;
						} else {
							bid = "0";
						}

						var options2 = [];
						options2["id"] = bodyid;
						options2["type"] = item.type;
						options2["name"] = item.name;
						options2["src"] = bodies + "/" + imgname.toLowerCase() + "_" + imgid + ".svg";
						options2["imgid"] = imgid;
						options2["width"] = width;
						options2["min_value"] = minvalue;
						options2["max_value"] = maxvalue;
						options2["bid"] = bid;
						options2["bodyid"] = bodyid;
						options2["landable"] = landable;
						options2["ringed"] = ringed;
						options2["scanned"] = scanned;
						options2["firstdisc"] = firstdisc;
						options2["do_update"] = false;
						options2["source"] = "url";

						add_body(options2);
					}
				});
			}

			if (show_grid === "0") {
				$(".panzoom").css("background-image", "none");
			}

			if (show_bg === "0") {
				$(".rightpanel").css("background-image", "none");
			}

			if (show_names === "1") {
				$("#toggle_names").html("Hide names");
			} else if (show_names === "0") {
				$("#toggle_names").html("Show names");
				$(".bodyname").hide();
			}

			update_price();
		});

		var star = $("#star"),
			planet = $("#planet"),
			other = $("#other");

		$('#star_click').mouseover(function () {
			if (star.is(":hidden")) { star.fadeToggle("fast"); }
			if (planet.is(":visible")) { planet.hide(); }
			if (other.is(":visible")) { other.hide(); }
		});

		$('#planet_click').mouseover(function () {
			if (planet.is(":hidden")) { planet.fadeToggle("fast"); }
			if (star.is(":visible")) { star.hide(); }
			if (other.is(":visible")) { other.hide(); }
		});

		$('#other_click').mouseover(function () {
			if (other.is(":hidden")) { other.fadeToggle("fast"); }
			if (planet.is(":visible")) { planet.hide(); }
			if (star.is(":visible")) { star.hide(); }
		});

		$(".categories").mouseleave(function () {
			if (star.is(":visible")) { star.hide(); }
			if (planet.is(":visible")) { planet.hide(); }
			if (other.is(":visible")) { other.hide(); }
		});

		/**
		 * toggle grid
		 */
		$('#toggle_grid').click(function () {
			var panzoom = $(".panzoom");
			if (panzoom.css("background-image") === "none") {
				panzoom.css("background-image", "repeating-linear-gradient(#333333, #333333 1px, transparent 1px, transparent 20px), repeating-linear-gradient(90deg, transparent, transparent 20px, #333333 20px, #333333 21px)");
			} else {
				panzoom.css("background-image", "none");
			}
			update_url();
		});

		/**
		 * toggle body names
		 */
		var toggle_names = $('#toggle_names');
		toggle_names.click(function () {
			if (toggle_names.html() === "Show names") {
				toggle_names.html("Hide names");
				$(".bodyname").show();
			} else {
				toggle_names.html("Show names");
				$(".bodyname").hide();
			}
			update_url();
		});

		/**
		 * toggle body background
		 */
		$('#toggle_background').click(function () {
			var rightpanel = $(".rightpanel");
			if (rightpanel.css("background-image") === "none") {
				rightpanel.css("background-image", "url(/style/img/backg.jpg)");
			} else {
				rightpanel.css("background-image", "none");
			}
			update_url();
		});
	}
};

$(document).mouseup(function (e) {
	var container = [];
	container.push($(".panzoom").find(".addinfo"));

	$.each(container, function (key, value) {
		if (!$(value).is(e.target) // if the target of the click isn't the container...
			&& $(value).has(e.target).length === 0) // ... nor a descendant of the container
		{
			$(value).fadeOut("fast");
		}
	});
});
