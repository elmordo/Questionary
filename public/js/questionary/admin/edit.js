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
    
    function writeGridView(container) {
        currentItem._showAdd = container.find("input[name='showAdd']").filter(":checked").length ? true : false;
        currentItem._showDel = container.find("input[name='showDel']").filter(":checked").length ? true : false;
        
        currentItem._minRows = Number(container.find("input[name='minRows']").val());
        
        // zapis definic sloupcu
        currentItem._columns = new Array();
        currentItem._dataRows = new Array();
        
        container.find("ol li").each(function () {
            var el = $(this);
            var name = el.find("input[name='name']").val();
            var caption = el.find("input[name='caption']").val();
            var type = el.find("select").val();
            
            currentItem._columns.push({ name : name, caption : caption, type : type });
        });
    }

    function writeCheckBox(container) {
    	currentItem._checkedVal = container.find("input[name='checkedVal']").val();
    	currentItem._checked = container.find("input[name='defaultChecked']:checked").length;

    	currentItem._filledVal = null;
    	currentItem._isFilled = false;
    }
	
	function writeChanges() {
		// zapis hodnot
		currentItem.label(dialog.find("#common-label").val());
		currentItem.defVal(dialog.find("#common-defval").val());
		currentItem.locked(dialog.find("#common-islocked:checked").length);
		
		// vyhodnoceni prvku
		switch (currentItem.className()) {
		case "CheckBox":
			writeCheckBox(dialog);
			break;

		case "Select":
		case "Radio":
		case "ValueList":
			writeChoose(dialog);
			break;
			
		case "Group":
			writeContainer(dialog);
			break;
        
        case "GridView":
            writeGridView(dialog);
            break;
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

	function writeCheckBoxEdit(item, container) {
		// policko pro zaskrnutou hodnotu
		var checkedVal = $("<input type='text' name='checkedVal'>");
		checkedVal.val(item._checkedVal);

		$("<p>").appendTo(container).append(
			$("<label>").text("Hodnota po zaškrtnutí")
		).append(
			checkedVal
		);

		// policko pro vychozi zaskrtnuti
		var checked = $("<input type='checkbox' name='defaultChecked'>");

		if (item._checked) {
			checked.attr("checked", "checked");
		}

		$("<p>").appendTo(container).append(
			$("<label>").text("Zaškrtnuté")
		).append(
			checked
		);
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
    
    function writeGridViewEdit(item, container) {
        var showDel = $("<input type='checkbox' name='showDel'>");
        var showAdd = $("<input type='checkbox' name='showAdd'>");
        
        if (item._showDel) showDel.attr("checked", "checked");
        if (item._showAdd) showAdd.attr("checked", "checked");
        
        // zapis zmeny viditelnosti tlacitek
        $("<label>Zobrazovat tlačítko přidání:</label>").append(showAdd).appendTo($("<p />").appendTo(container));
        $("<label>Zobrazovat tlačítko smazaní:</label>").append(showDel).appendTo($("<p />").appendTo(container));
        
        // zapis minimalniho poctu radku
        $("<label>Minimální počet řádků:</label>").append(
                $("<input type='text' name='minRows'>").val(item._minRows)
                ).appendTo($("<p />").appendTo(container));
        
        // priprava vyberu sloupcu
        var typeSelect = $("<select name='type'>")
        	.append("<option value='text'>Text</option>")
        	.append("<option value='hidden'>Skryté pole</option>")
        	.append("<option value='const'>Konstantní hodnota</option>")
        	.append("<option value='checkbox'>Zaškrtávací políčko</option>");

        // zapis definice sloupci
        var colContainer = $("<ol>").appendTo(container);
        
        // vygenerovani seznamu definic sloupcu
        for (var i in item._columns) {
            var col = item._columns[i];
            
            var colCont = $("<li>").appendTo(colContainer);
            
            $("<label>Interní jméno: </label>").append($("<input type='text' name='name'>").val(col.name)).appendTo($("<p>").appendTo(colCont));
            $("<label>Popisek: </label>").append($("<input type='text' name='caption'>").val(col.caption)).appendTo($("<p>").appendTo(colCont));
            $("<label>Typ: </label>").append(typeSelect.clone().val(col.type)).appendTo($("<p>").appendTo(colCont));
            $("<input type='button' value='Odebrat'>").click(function () { $(this).parents("li:first").remove(); }).appendTo($("<p>").appendTo(colCont));
        }
        
        colContainer.sortable();
        
        // tlacitko pridani elementu
        $("<p>").append($("<input type='button' value='Přidat sloupec'>").click(function () {
            var colCont = $("<li>").appendTo(colContainer);
            
            $("<label>Interní jméno: </label>").append($("<input type='text' name='name'>")).appendTo($("<p>").appendTo(colCont));
            $("<label>Popisek: </label>").append($("<input type='text' name='caption'>")).appendTo($("<p>").appendTo(colCont));
            $("<label>Typ: </label>").append(typeSelect.clone()).appendTo($("<p>").appendTo(colCont));
            $("<input type='button' value='Odebrat'>").click(function () { $(this).parents("li:first").remove(); }).appendTo($("<p>").appendTo(colCont));
        })).appendTo(container);
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
		case "CheckBox":
			var specContainer = $("<fieldset id='specific'>").append($("<legend>").text("Nastavení zaškrtávacího políčka")).appendTo(container);
			writeCheckBoxEdit(item, specContainer);
			break;

		case "Radio":
		case "Select":
		case "ValueList":
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
        
        case "GridView":
            var specContainer = $("<fieldset id='specific'>").append($("<legend>").text("Nastavení tabulky")).appendTo(container);
            writeGridViewEdit(item, specContainer);
            break;
		}
		
		$("<input type='button' value='Zapsat změny'>").click(writeChanges).appendTo(container);
		$("<br>").appendTo(container);
		$("<input type='button' value='Odstranit'>").click(deleteItem).appendTo(container);
		
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
	
	// snaze prvek
	function deleteItem() {
		if (!confirm("Skutečně odstranit prvek?"))
			return;
		
		questionary.removeItem(currentItem);
		dialog.dialog("close");
		render();
		refreshItems();
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
				content: window.JSON.stringify(arr),
				id : id,
                format : "json"
			}
		};
		
		$.post("/questionary/admin/put.json", data, function (response) {
            confirm("Data byla uložena");
		}, "json");
		
		return false;
	}
	
	$(":button[name='add-item']").click(addItem);
	$("#items-panel").dialog({ position: "top"});
	$("#new-item").dialog({ position: "bottom"});
	$("#form-questionary-edit").submit(submitForm);
	
	questionary.setFromArray(QDATA);
	
	render();
	refreshItems();
});
