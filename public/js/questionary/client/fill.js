$(function () {
	var questionary = new QUESTIONARY.Questionary();
	questionary.setFromArray(QDATA);
	
	function render() {
		var target = $("#questionary-content");
		target.children().remove();
		
		questionary.render().appendTo(target);
	}
	
	function setSubmit() {
		$(this).parents("form:first").attr("action", "/questionary/client/submit");
		
		return true;
	}
	
	$("#questionary-submit").click(setSubmit);
	render();
});