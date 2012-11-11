$(function() {
	var questionary = new QUESTIONARY.Questionary();
	
	questionary.setName($("#questionary-name").val());
	
	var dialog = null;
	
	var currentItem = null;
	
	// vykrelseni dotazniku
	function render() {
		var target = $("#questionary-item-container");
		
		target.children().remove();
		
		questionary.render().appendTo(target);
	}
	
	function writeChoose(container) {
		// sestaveni seznamu hodnot
		var options = {};
		
		container.find("li").each(function () {
			var item = $(this);
			var name = item.find(":text[name='name']").val();
			var val = item.find(":text[name='value']").val();
			
			options[name] = val;
		});
		
		currentItem.setOptions(options);
	}
	
	function writeContainer(container) {
		// sestaveni seznamu hodnot
		currentItem.clear();
		
		container.find("li :checkbox:checked").each(function () {
			var itemName = $(this).val();
			
			var item = questionary.getByName(itemName);
			currentItem.addItem(item);
		});
	}
	
	function writeChanges() {
		// zapis hodnot
		currentItem.label(dialog.find("#common-label").val());
		currentItem.defVal(dialog.find("#common-defval").val());
		currentItem.locked(dialog.find("#common-islocked:checked").length);
		
		// vyhodnoceni prvku
		switch (currentItem.className()) {
		case "Select":
		case "Radio":
			writeChoose(dialog);
			break;
			
		case "Group":
			writeContainer(dialog);
		}
		
		dialog.dialog("close");
	}
	
	// zmeni pozice prvku
	function changePosition() {
		var source = $("#items").find(":checkbox:checked");
		
		var items = new Array();
		
		source.each(function () {
			items.push(questionary.getByName($(this).attr("name")));
		});
		
		questionary.setOrder(items);
		
		render();
	}
	
	// zapise do kontejneru nastaveni ChooseInput
	function writeChooseEdit(item, container) {
		// vypis existujicich zaznamu
		var ul = $("<ul>").appendTo(container);
		
		var options = item.getOptions();
		
		for (var i in options) {
			var li = $("<li>").appendTo(ul);
			
			// tlacitko odebrani 
			$("<input type='button' value='Odebrat'>").click(function() {
				if (confirm("Skutečně odebrat?"))
					$(this).parents("li:first").remove();
			}).appendTo(li);
			li.append($("<br>"));
			
			li.append($("<label>Hodnota : <input type='text' name='name'></label>").find(":text").val(i).end());
			li.append($("<br>"));
			li.append($("<label>Text : <input type='text' name='value'></label>").find(":text").val(options[i]).end());
		}
		
		ul.sortable();
		
		// tlacitko pridani hodnoty
		$("<input type='button' value='Přidat hodnotu'>").click(function () {
			
			ul.sortable("destroy");
			
			var li = $("<li>").appendTo(ul);
			
			// tlacitko odebrani 
			$("<input type='button' value='Odebrat'>").click(function() {
				if (confirm("Skutečně odebrat?"))
					$(this).parents("li:first").remove();
			}).appendTo(li);
			li.append($("<br>"));
			
			li.append($("<label>Hodnota : <input type='text' name='name'></label>"));
			li.append($("<br>"));
			li.append($("<label>Text : <input type='text' name='value'></label>"));
			
			ul.sortable();
		}).appendTo($("<p>").appendTo(container));
	}
	
	function writeContainerEdit(item, container) {
		// nacteni prvku
		var allItems = questionary.getItems();
		var groupedItems = item.getItems();
		var groupedIndex = {};
		
		// priprava prvku
		var ul = $("<ul>").appendTo(container);
		
		for (var i in groupedItems) {
			var gItem = groupedItems[i];
			
			var li = $("<li>").appendTo(ul);
			
			$("<input type='checkbox' checked='checked'>").val(gItem.name()).appendTo(li);
			$("<span>").text(gItem.name()).appendTo(li);
			
			groupedIndex[gItem.name()] = 1;
		}
		
		// zapis zbytku dat
		for (var i in allItems) {
			var gItem = allItems[i];
			
			if (groupedIndex[gItem.name()] != undefined) continue;
			if (gItem == item) continue;
			
			var li = $("<li>").appendTo(ul);
			
			$("<input type='checkbox'>").val(gItem.name()).appendTo(li);
			$("<span>").text(gItem.name()).appendTo(li);
		}
	}
	
	// zobrazi dialog pro editaci dat
	function showEdit() {
		dialog = $("<div>");
		
		var container = $("<form>").appendTo(dialog);
		var item = questionary.getByName(
			$(this).parents("li:first").find(":checkbox").attr("name")
		);
		
		currentItem = item;
		
		var commonList = $("<fieldset>").append("<legend>").text("Obecné hodnoty").appendTo(container);
		
		// popisek
		$("<p>").append(
				$("<label for='common-label'>").text("Popisek")
		).append(
				$("<input type='text' name='label' id='common-label'>").val(item.label())
		).appendTo(commonList);
		
		// vychozi hodnota
		$("<p>").append(
				$("<label for='common-defval'>").text("Výchozí hodnota")
		).append(
				$("<input type='text' name='defval' id='common-defval'>").val(item.defVal())
		).appendTo(commonList);
		
		// uzamceni
		var locked = $("<input type='checkbox' name='locked' value='1' id='common-islocked'>");
		if (item.locked()) locked.attr("checked", "checked");
		
		$("<p>").append(
				$("<label for='common-islocked'>").text("Uzamčení")
		).append(
				locked
		).appendTo(commonList);
		
		// vyhodnoceni tridy
		switch (item.className()) {
		case "Radio":
		case "Select":
			var specContainer = $("<fieldset id='specific'>").append($("<legend>").text("Nastavení výběru")).appendTo(container);
			writeChooseEdit(item, specContainer);
			break;
			
		case "TextArea":
			container.find(":text[name='defval']").replaceWith($("<textarea name='defval' id='common-defval'>"));
			break;
			
		case "Group":
			var specContainer = $("<fieldset id='specific'>").append($("<legend>").text("Nastavení výběru")).appendTo(container);
			writeContainerEdit(item, specContainer);
			break;
		}
		
		$("<input type='button' value='Zapsat změny'>").click(writeChanges).appendTo(container);
		
		dialog.dialog({
			modal: true,
			width: "800px",
			close : function () {
				render();
				refreshItems();
				dialog.remove();
			}
		});
	}
	
	// zmeni viditelnost prvku
	function changeVisibility() {
		changePosition();
	}
	
	// obnovi seznam prvku
	function refreshItems() {
		var items = questionary.getIndex();
		
		// nastaveni cile
		var target = $("#items");
		
		target.children().remove();
		
		for (var i in items) {
			var item = items[i];
			
			// vygenerovani prvku
			var li = $("<li>");
			
			// data
			var checkbox = $("<input type='checkbox'>").attr("name", item.name());
			
			// vyhodnoceni zaskrtnuti
			if (questionary.getRenderable(item)) {
				checkbox.attr("checked", "checked");
			}
			
			checkbox.appendTo(li).click(changeVisibility);
			$("<input type='hidden' name='className'>").val(item.className()).appendTo(li);
			$("<input type='button' value='E'>").click(showEdit).appendTo(li);
			$("<span>").text(item.name()).appendTo(li);
			
			li.appendTo(target);
		};
		
		target.sortable({
			"stop" : changePosition
		});
	}
	
	// novy prvek
	function addItem() {
		var itemClass = $(this).val();
		
		var name = prompt("Zadejte název prvku");
		
		if (!name.length) return false;
		
		questionary.addItem(name, itemClass);
		
		// obnoveni seznamu objektu a vykresleni
		refreshItems();
		render();
	}
	
	// ulozi asynchronne data
	function submitForm() {
		// serializace celeho dotazniku do pole
		var arr = questionary.toArray();
		var id = $("#questionary-id").val();
		
		var data = {
			questionary : {
				content: arr,
				id : id,
				is_visible: $("#questionary-is_visible:checked").length,
				is_readable: $("#questionary-is_editable:checked").length,
				is_published : $("#questionary-is_published:checked").length
			}
		};
		
		$.post("/admin/putq.json", data, function (response) {
			
		}, "json");
		
		return false;
	}
	
	function updateName() {
		questionary.setName($(this).val());
	}
	
	$(":button[name='add-item']").click(addItem);
	$("#items-panel").dialog({ position: "top"});
	$("#new-item").dialog({ position: "bottom"});
	$("#form-questionary-edit").submit(submitForm);
	$("#questionary-name").change(updateName);
	
	questionary.setFromArray(QDATA);
	
	render();
	refreshItems();
});
