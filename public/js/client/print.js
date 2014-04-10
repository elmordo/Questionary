$(function () {
	var questionary = new QUESTIONARY.Questionary();
	questionary.setFromArray(QDATA);
	
	function render() {
		var target = $("#questionary-content");
		target.children().remove();
		
		questionary.render().appendTo(target);
	}
	
	function setSubmit() {
		$(this).parents("form:first").attr("action", "/client/submit");
		
		return true;
	}
	
	$("#questionary-submit").click(setSubmit);
	render();
	
	// prepsani hodnot na hodnoty vhodne pro tisk
	$(".questionary").find("textarea,input[type='text']").each(function () {
		var obj = $(this);
		var content = obj.val();
		var parent = obj.parent();
		
		obj.remove();
		
		$("<div class='pre'></div>").text(content).appendTo(parent);
		
		var name = parent.parent().find("input[name='itemName']").val();
		
		questionary.getByName(name)._recalculateHeight();
	});
	
	$(".questionary").find(".questionary-item-radio-item").each(function () {
		var container = $(this);
		
		if (!container.find(":checked").length) {
			container.children().css("visibility", "hidden");
		}
	});
});