jQuery(document).ready(function ($) {

	//$("#snimp_joblist").find('input[type="checkbox"]');

	// Usage: $form.find('input[type="checkbox"]').shiftSelectable();
	// replace input[type="checkbox"] with the selector to match your list of checkboxes

	let selected_arr = $(".snimp_selected");
	let count_delete_times = 0;

	$("#snimp-selected-categories").html("<strong>Selected categories:</strong> ");
	$.each(selected_arr, function (index, item) {
		$("#snimp-selected-categories").append("<div class='snimp-selected-infobox'>" + $(this).text() + "</div>");
	});

	$.fn.shiftSelectable = function () {
		var lastChecked,
			$boxes = this;

		$boxes.click(function (evt) {
			//console.log("wat");
			if (!lastChecked) {
				lastChecked = this;
				return;
			}

			if (evt.shiftKey) {
				var start = $boxes.index(this),
					end = $boxes.index(lastChecked);
				$boxes.slice(Math.min(start, end), Math.max(start, end) + 1)
					.attr('checked', lastChecked.checked)
					.trigger('change');
			}

			lastChecked = this;
		});
	};


	$(".snimp_selectbox").on("click", function () {
		let ths = $(this);
		let ths_id = $(this).attr("id");
		let ths_name = $(this).text();

		if (ths.hasClass("snimp_selected"))
			ths.removeClass("snimp_selected");
		else
			ths.addClass("snimp_selected");
		//console.log(ths_name);

		let selected_arr = $(".snimp_selected");
		$("#snimp-selected-categories").html("<strong>Valda kategorier:</strong> ");
		$.each(selected_arr, function (index, item) {
			$("#snimp-selected-categories").append("<div class='snimp-selected-infobox'>" + $(this).text() + "</div>");
		});
	});

	$("#snimp-showallbutton").on("click", function (e) {
		if ($(".snimp_selectmain").hasClass("fullbox")) {
			e.stopPropagation();
			$(".snimp_selectmain").removeClass("fullbox");
			$("#snimp-showallbutton").text("Show All");
		}
		else {
			$(".snimp_selectmain").addClass("fullbox");
			$("#snimp-showallbutton").text("Hide");
		}
	});

	$("#snimp_emptyselected").on("click", function () {
		$(".snimp_selected").removeClass("snimp_selected");
		$("#snimp-selected-categories").html("");
	});

	$("#snimp-favouritesbutton").on("click", function () {
		let selected_arr = $(".snimp_selected");
		let listout = [];
		$.each(selected_arr, function (index, item) {
			//console.log(this.id.split("-")[2]);
			listout.push(this.id.split("-")[2])
			//alert( index + ": " + value );
		});
		var data = { 'action': 'snaf_save_favourites', 'occupations': listout };
		console.log(snillrik_impadmin.ajaxurl);
		$.post(
			snillrik_impadmin.ajaxurl,
			data,
			function (response) {
				alert("Saved");
			}
		);
	});

	$("#delete_expired_jobs").on("click", function () {
		let untildate = $("#delete_expired_jobs_until").val();
		//alert(untildate);
		$("#delete_info").append("Starting delete process.");
		deleteTilDate(untildate);
	});

	$("body").on("click", ".snimp-pagebutton", function () {
		let thisid = $(this).attr("id");
		//console.log(thisid);
		$("#snimp_joblist_offset").val(thisid);
		fetchfrominputs();
	});

	$("#snimp-fetchbutton").on("click", function () {
		$("#snimp_joblist_offset").val(0);
		fetchfrominputs();
	});

	$("#snimp-changeselected").on("click", function () {
		let selected = $("#snaf_change_cats").val();
		//alert(selected);
		let selected_arr = $("#snimp_joblist").find('input[type=checkbox]:checked').parent().parent().find('.snaf_cat_selected');
		selected_arr.val(selected);
	});

	$("#snimp-saveselected").on("click", function () {
		console.log("selected saving");
		let selected_arr = $("#snimp_joblist").find('input[type=checkbox]:checked');
		//let changed_categories = $(".snaf_cat_selected");
		$("#snimp_joblist").slideUp("fast");
		let selected = [];
		let selected_cats = {};
		$.each(selected_arr, function (index, item) {
			let thisid = this.id;
			//console.log(thisid);
			/* 			let category_selected = $("#snaf_cat_selected_"+thisid+" option:selected" );
						selected.push(thisid);
						selected_cats[thisid] = category_selected.val() ? category_selected.text() : ""; */

			let category_selected = $("#snaf_cat_selected_" + thisid + " option:selected");
			selected.push(thisid);
			let all_selected_cats = "";
			category_selected.each(function () {
				//console.log("Val gjorda: "+$(this).val());
				all_selected_cats += ($(this).val() ? $(this).text() : "") + "|";
			});
			selected_cats[thisid] = all_selected_cats;

		});
		console.log(selected_cats);
		let offset = $("#snimp_joblist_offset").val();

		let selected_arr2 = $(".snimp_selected");
		$(".snimp-loader").fadeIn().css("display", "inline-block");
		let listout = [];
		$.each(selected_arr2, function (index, item) {
			//console.log(this.id.split("-")[2]);
			listout.push(this.id.split("-")[2])
			//alert( index + ": " + value );
		});


		//let occupations = $("#snimp_joblist_selected").val();
		let search_string = $("#snillrik-wpjb-q").val();
		var data = {
			'action': 'snaf_save_to_wpjb',
			'selected': selected,
			'selected_cats': selected_cats,
			'occupations': listout,
			'offset': offset,
			'q': search_string
		};
		console.log(data);
		$.post(
			snillrik_impadmin.ajaxurl,
			data,
			function (response) {
				console.log(response);
				$("#snimp_saverespons").html(response);
				$("#snimp_joblist_offset").val(0);
				fetchfrominputs();
				$("#snimp_joblist").slideDown("fast");
			}
		);
	});

	function deleteTilDate(todate) {
		var data = { 'action': 'snaf_delete_to_date', 'todate': todate };
		//console.log(data);

		var currentdate = new Date(); 
		var datetime = "(" + currentdate.getHours() + ":" + currentdate.getMinutes() + ":" + currentdate.getSeconds()+")";


		$.post(
			snillrik_impadmin.ajaxurl,
			data,
			function (response) {
				console.log(response);
				if (response.result != "done") {// && count_delete_times<3){
					//console.log(response.result);
					deleteTilDate(todate);
					$("#delete_info").append("Deleting turn: " + (parseInt(count_delete_times) - 1) + " (" + response.info + ") "+datetime+"<br />");
				}
				else { //if done
					console.log("stopp!");
					$("#delete_info").append("Deleting turn: " + (parseInt(count_delete_times)) + " (" + response.info + ") "+datetime+"<br />");
				}
				//$("#snimp_joblist_offset").val(parseInt(offset)+1);
			}
		);
		count_delete_times++;
	}

	function fetchfrominputs() {
		let selected_arr = $(".snimp_selected");
		$(".snimp-loader").fadeIn().css("display", "inline-block");;
		let offset = $("#snimp_joblist_offset").val();
		let search_string = $("#snillrik-wpjb-q").val();
		let listout = [];
		$.each(selected_arr, function (index, item) {
			//console.log(this.id.split("-")[2]);
			listout.push(this.id.split("-")[2])
			//alert( index + ": " + value );
		});
		$("#snimp_joblist_selected").val(listout);
		var data = { 'action': 'snaf_get_occupations', 'occupations': listout, 'offset': offset, 'q': search_string };
		//console.log(snillrik_impadmin.ajaxurl);
		$.post(
			snillrik_impadmin.ajaxurl,
			data,
			function (response) {
				console.log(response);
				$(".snimp-loader").fadeOut();
				$("#snimp_joblist").html(response.html_out);
				$("#snimp_joblist").find('input[type="checkbox"]').shiftSelectable();

				//$("#snimp_joblist_offset").val(parseInt(offset)+1);
			}
		);
	}

});