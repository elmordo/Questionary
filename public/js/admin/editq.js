$(function() {
	
	var foldStatuses = {};
	
	function CHANGE_HISTORY (action, obj, params) {
		
		this.action = action;
		this.obj = obj;
		this.params = this._clone(params);
	};
	
	CHANGE_HISTORY.prototype._clone = function (obj) {
		var retVal = {};
		
		for (var i in obj) {
			retVal[i] = obj[i];
		}
		
		return retVal;
	};
	
	CHANGE_HISTORY.ACTIONS = {
			"new" : "N",
			"delete" : "D",
			"modify" : "M",
			"move" : "V",
			"visibility" : "B"
	};
	
	var questionary = new QUESTIONARY.Questionary();
	q = questionary;
	
	var requestQueue = new Array();
	var inRequest = false;
	
	function writeRequestToQueue(request, revert) {
		requestQueue.push({request : request, revert : revert });
		
		sendNextRequest();
	}
	
	function finishRequest() {
		inRequest = false;
		sendNextRequest();
	}
	
	function sendNextRequest() {
		if (inRequest || !requestQueue.length) return;
		
		var item = requestQueue.shift();
		inRequest = true;
		var url;
		
		if (item.revert) {
			url = "/admin/revert.json";
		} else {
			url = "/admin/add.json";
		}
		
		var data = {
				data : item.request,
				questionaryId : $("#questionary-id").val()
		};
		
		$.post(url, data, finishRequest);
	}
	
	var HISTORY = {
			h: new Array(),	// pole historie
			p : -1,			// ukazatel na aktualni polozku
			l : -1,			// ukazatel na nejzasi polozku, kam se lze dostat pomoci tlacitka vpred
			c : 0			// celkovy pocet zaalokovanych polozek
	};
	
	x = HISTORY;
	
	// prida krok do historie
	function addToHistory(action, obj, params, notSend) {
		var ho = new CHANGE_HISTORY(action, obj, params);
		HISTORY.p++;
		
		if (HISTORY.p === HISTORY.c) {
			HISTORY.h.push(ho);
			HISTORY.c++;
		} else {
			HISTORY.h[HISTORY.p] = ho;
		}
		
		HISTORY.l = HISTORY.p;
		
		$("#undo").removeAttr("disabled");
		$("#redo").attr("disabled", "disabled");
		
		if (!notSend)
			sendAdd(ho, 0);
		
		return ho;
	}
	
	function actualHistoryItem() {
		if (HISTORY.p < 0) return null;
		
		return HISTORY.h[HISTORY.p];
	}
	
	// zapise prvek do seznme pred prvek jmenem prev
	function insertToOrder(item, items, prev, container, pop) {
		var order = new Array();
		
		if (prev) {
			for (var i in items) {
				if (items[i] !== item)
					order.push(items[i]);
				
				if (items[i].name() === prev) {
					order.push(item);
				}
			}
		} else {
			if (container instanceof QUESTIONARY.Questionary && pop)
				items.pop();
			
			order.push(item);
			order = order.concat(items);
		}
		
		return order;
	}
	
	// vrati se o krok v historii
	function revertHistory() {
		if (HISTORY.p < 0) return;
		
		var item = HISTORY.h[HISTORY.p];
		HISTORY.p--;
		
		if (HISTORY.p < 0) $("#undo").attr("disabled", "disabled");
		$("#redo").removeAttr("disabled");
		
		// vyhodnoceni akce
		if (item.action === CHANGE_HISTORY.ACTIONS["new"]) {
			questionary.removeItem(item.obj);
		} else if (item.action === CHANGE_HISTORY.ACTIONS["delete"]) {
			// vytvoreni noveho objektu
			var newItem = questionary.addItem(item.obj.name(), item.obj.className());
			
			newItem.setFromArray(item.obj.toArray());
			
			// vyhodnoceni kontejneru
			var container;
			
			if (item.params.container) {
				container = questionary.getByName(item.params.container);
			} else {
				container = questionary;
			}
			
			// zarazeni na spravne misto
			var items = container.getItems();
			var order = insertToOrder(newItem, items, item.params.prev, container, true);
			
			if (container instanceof QUESTIONARY.Questionary) {
				container.setOrder(order);
			} else {
				container.setItems(order);
			}
			
			// nahrazeni zybtku historie novym elementem
			for (var i in HISTORY.h) {
				if (HISTORY.h[i].obj === item.obj) {
					HISTORY.h[i].obj = newItem;
				}
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["visibility"]) {
			// vyhodnoceni, jestli je prvek vykreslovan
			if (item.obj.container() || questionary.getRenderable(item.obj)) {
				// prvek je aktualne vykreslovat, takze se skryje
				if (item.obj.container()) {
					item.obj.container().removeItem(item.obj);
				} else {
					questionary.setRenderable(item.obj, false);
				}
			} else {
				// prvek je skryt a provede se zobrazeni
				if (item.params.container) {
					var container = questionary.getByName(item.params.container);
					var items = container.getItems();
					var order = insertToOrder(item.obj, items, item.params.prev, container, false);
					
					container.setItems(order);
				} else {
					var items = questionary.getItems();
					
					var order = insertToOrder(item.obj, items, item.params.prev, questionary, false);
					questionary.setOrder(order);
				}
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["move"]) {
			// vygeerovani seznamu prvku
			var order = new Array();
			
			for (var i in item.params.itemsOld) {
				order.push(questionary.getByName(item.params.itemsOld[i]));
			}
			
			// zapis dat
			if (item.obj) {
				// jedna se o kontejner
				item.obj.setItems(order);
			} else {
				questionary.setOrder(order);
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["modify"]) {
			item.obj.setFromArray(item.params.settingsOld);
		}
		
		sendRevert(item);
		
		render();
		refreshItems();
	}
	
	// udela krok vpred v historii
	function redoHistory() {
		if (HISTORY.p === HISTORY.l) return;
		
		HISTORY.p++;
		var item = HISTORY.h[HISTORY.p];
		
		if (HISTORY.p === HISTORY.l) $("#redo").attr("disabled", "disabled");
		$("#undo").removeAttr("disabled");
		
		// vyhodnoceni typu kroku
		if (item.action === CHANGE_HISTORY.ACTIONS["new"]) {
			// vytvoreni noveho objektu
			var newItem = questionary.addItem(item.obj.name(), item.obj.className());
			
			// musime prepsat stare reference na nove
			for (var i in HISTORY.h) {
				if (HISTORY.h[i].obj === item.obj) {
					HISTORY.h[i].obj = newItem;
				}
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["delete"]) {
			// smazani objektu
			questionary.removeItem(item.obj);
		} else if (item.action === CHANGE_HISTORY.ACTIONS["visibility"]) {
			// vyhodnoceni jestli se ma prvek odkryt nebo skryt
			if (item.params.newStatus) {
				// prvek se odkryje
				if (item.obj.container()) {
					// tento pripad teoreticky nemuze ani nastat, ale nechavam zde misto pro necekane situace
				} else {
					var order = questionary.getItems();
					order.push(item.obj);
					
					questionary.setOrder(order);
				}
			} else {
				// prvek se skryje
				if (item.obj.container()) {
					item.obj.container().removeItem(item.obj);
				} else {
					questionary.setRenderable(item.obj, false);
				}
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["move"]) {
			// vygeerovani seznamu prvku
			var order = new Array();
			
			for (var i in item.params.itemsNew) {
				order.push(questionary.getByName(item.params.itemsNew[i]));
			}
			
			// zapis dat
			if (item.obj) {
				// jedna se o kontejner
				item.obj.setItems(order);
			} else {
				questionary.setOrder(order);
			}
		} else if (item.action === CHANGE_HISTORY.ACTIONS["modify"]) {
			item.obj.setFromArray(item.params.settingsNew);
		}
		
		sendAdd(item);
		
		refreshItems();
		render();
	}
	
	// prirpavi delta informaci pro odeslani na server
	function prepareDelta(delta) {
		var data = {
			action : delta.action,
			params : window.JSON.stringify(delta.params)
		};
		
		if (delta.obj) data.obj = delta.obj.name();
		
		return data;
	}
	
	// odesle pridani delta balicku na server
	function sendAdd(item) {
		var data = prepareDelta(item);
		
		writeRequestToQueue(data, 0);
	}
	
	// odesle revert delta balicku na server
	function sendRevert(item) {
		var data = prepareDelta(item);
		
		writeRequestToQueue(data, 1);
	}
	
	// zapise aktualni rozlozeni komponent do historie
	function writeOrderToHistory() {
		var ul = $(this);
		var items = new Array();
		
		ul.find(">li>:checkbox").each(function () {
			items.push(this.name);
		});
		
		// vyhodnoceni kontejneru
		var container = null;
		
		if (!ul.attr("id")) {
			container = questionary.getByName(ul.parent().children(":checkbox").attr("name"));
		}
		
		var params = {
				itemsOld : items
		};
		
		addToHistory(CHANGE_HISTORY.ACTIONS["move"], container, params, true);
	}
	
	questionary.setName($("#questionary-name").val());
	questionary.setDesingMode(true);
	
	var dialog = null;
	
	var currentItem = null;
	
	// vykrelseni dotazniku
	function render() {
		var target = $("#questionary-item-container");
		
		target.children().remove();
		
		questionary.render().appendTo(target);
	}
	
	// zapise zmeny provedene na vyberovem prvku
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
	
	// zapise zmeny provedene na kontejnerovem prvku
	function writeContainer(container) {
		// sestaveni seznamu hodnot
		currentItem.clear();
		
		container.find("li :checkbox:checked").each(function () {
			var itemName = $(this).val();
			
			var item = questionary.getByName(itemName);
			currentItem.addItem(item);
		});
	}
	
	// zapise zmeny provedene na obecnem prvku
	function writeChanges() {
		// zaloha hodnot do historie
		var params = {
				settingsOld : currentItem.toArray()
		};
		
		// zapis hodnot
		currentItem.label(dialog.find("#common-label").val());
		currentItem.defVal(dialog.find("#common-defval").val());
		currentItem.locked(dialog.find("#common-islocked:checked").length);
		
		// vyhodnoceni prvku
		switch (currentItem.className()) {
		case "Select":
		case "Radio":
		case "ValueList":
			writeChoose(dialog);
			break;
			
		case "Group":
			writeContainer(dialog);
		}
		
		params["settingsNew"] = currentItem.toArray();
		
		addToHistory(CHANGE_HISTORY.ACTIONS["modify"], currentItem, params);
		
		dialog.dialog("close");
	}
	
	// zmeni pozice prvku
	function changePosition() {
		var source = $("#items,#unused-items").find(">li>:checkbox:checked");
		
		var items = new Array();
		var names = new Array();
		
		source.each(function () {
			var item = questionary.getByName($(this).attr("name"));
			items.push(item);
			names.push(item.name());
		});
		
		questionary.setOrder(items);
		
		// zapis do historie
		actualHistoryItem().params["itemsNew"] = names;
		
		sendAdd(actualHistoryItem());
		
		render();
		refreshItems();
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
			
			if (groupedIndex[gItem.name()] !== undefined) continue;
			if (gItem === item) continue;
			
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
        
        $("<label>Zobrazovat tlačítko přidání:</label>").append(showAdd).appendTo(container);
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
			},
			draggable : false
		});
	}
	
	// zmeni viditelnost prvku
	function changeVisibility() {
		var name = $(this).attr("name");
		var item = questionary.getByName(name);
		var visibility = Boolean($(this).attr("checked"));
		var prev = item.prev();
		
		// zapis do historie
		var params = {
				prev : prev ? prev.name() : null,
				container : null,
				newStatus : visibility
		};
		
		addToHistory(CHANGE_HISTORY.ACTIONS["visibility"], item, params);
		
		questionary.setRenderable(item, visibility);
		
		refreshItems();
	}
	
	// zmeni viditelnost prvku uvnitr skupiny
	function changeContainerVisibility() {
		var name = $(this).attr("name");
		var item = questionary.getByName(name);
		var prev = item.prev();
		
		var params = {
				prev : prev ? prev.name() : null,
				container : item.container().name(),
				newStatus : false
		};
		
		addToHistory(CHANGE_HISTORY.ACTIONS["visibility"], item, params);
		
		item.container().removeItem(item);
		
		refreshItems();
	}
	
	// nastavi poradi prvku uvnitr skupiny
	function changeContainerPosition() {
		var ul = $(this);
		var checks = ul.find(">li>:checkbox");
		var container = questionary.getByName(ul.parent().find(">:checkbox").attr("name"));
		var names = new Array();
		
		container.clear();
		
		checks.each(function () {
			var item = questionary.getByName($(this).attr("name"));
			container.addItem(item);
			names.push(item.name());
		});
		
		actualHistoryItem().params["itemsNew"] = names;
		sendAdd(actualHistoryItem());
		
		refreshItems();
	}
	
	// odstani prvek daneho klice z pole
	function removeFromArray(arr, index) {
		var retVal = new Array();
		
		for (var i in arr) {
			if (i !== index) {
				retVal[i] = arr[i];
			}
		}
		
		return retVal;
	}
	
	// sbaleni a rozbaleni skupiny
	function toggleFold() {
		var context = $(this);
		var ul = context.parent().children("ul");
		
		if (context.hasClass("fold-plus")) {
			context.removeClass("fold-plus");
			context.addClass("fold-minus");
			
			foldStatuses[context.parent().children(":checkbox").attr("name")] = false;
			
			ul.show("fast");
		} else {
			context.removeClass("fold-minus");
			context.addClass("fold-plus");
			
			foldStatuses[context.parent().children(":checkbox").attr("name")] = true;
			
			ul.hide("fast");
		}
	}
	
	// vygeneruje prvek pro zapis do seznamu prvku
	// pokud je prvek kontejner, pak vygeneruje rekurzivne i jeho prvky
	function generateItemLi(item, checked, changeFnc) {
		var retVal = $("<li class='item-li'>");
		
		var checkbox = $("<input type='checkbox'>").attr("name", item.name());
		checkbox.click(changeFnc ? changeFnc : changeVisibility);
		
		if (checked) checkbox.attr("checked", "checked");
		
		var hidden = $("<input type='hidden' name='className'>").val(item.className());
		var edit = $("<button type='button' class='edit-element-button'>").click(showEdit);
		var span = $("<span>").text(generateLabel(item));
		
		retVal.append(checkbox).append(hidden).append(edit);
		
		// vyhodnoceni, jestli je prvek kontejnerem
		if (item instanceof QUESTIONARY.Container) {
			var folder = $("<button type='button'></button>");
			
			// kontrola existence sbaleni
			if (typeof foldStatuses[item.name()] === "undefined") {
				foldStatuses[item.name()] = false;
			}
			
			retVal.append(folder);
			
			retVal.append(span);
			
			// vygenerovani dalsiho seznamu a vlozeni prvku
			var subUl = $("<ul></ul>");
			
			var items = item.getItems();
			
			for (var i in items) {
				var subItem = items[i];
				
				generateItemLi(subItem, true, changeContainerVisibility).appendTo(subUl);
			}
			
			subUl.appendTo(retVal).sortable({ start: writeOrderToHistory, stop: changeContainerPosition });
			folder.click(toggleFold);
			
			// vyhodnoceni sbaleni prvku
			if (foldStatuses[item.name()]) {
				folder.addClass("fold-plus");
				subUl.hide();
			} else {
				folder.addClass("fold-minus");
			}
			
		} else {
			retVal.append(span);
		}
		
		return retVal;
	}
	
	// vygeneruje nazev provku pro uzivatelske rozhrani
	function generateLabel(item) {
		var label = item.label();
		
		if (label.length === 0) label = item.name();
		
		if (label.length > 63)
			label = label.substr(0, 60) + "...";
		
		return label;
	}
	
	// obnovi seznam prvku
	function refreshItems() {
		var index = questionary.getIndex();
		var items = questionary.getItems();
		
		// aktualizace policka pro vyber caoptionu
		var caption = $("#questionary-caption");
		var subcaption = $("#questionary-subcaption");
		var oldCaption = caption.val();
		var oldSubcaption = subcaption.val();
		
		caption.children().remove();
		
		$("<option></option>").attr("value", "").text("---").appendTo(caption);
		$("<option></option>").attr("value", "").text("---").appendTo(subcaption);
		
		for (var i in index) {
			$("<option></option>").attr("value", i).text(generateLabel(index[i])).appendTo(caption);
			$("<option></option>").attr("value", i).text(generateLabel(index[i])).appendTo(subcaption);
		}
		
		caption.val(oldCaption);
		subcaption.val(oldSubcaption);
		
		// nastaveni cile
		var target = $("#items");
		
		target.children().remove();
		
		// vypsani viditelnych prvku v poradi, jak jsou renderovany
		for (var i in items) {
			var item = items[i];
			index = removeFromArray(index, item.name());
			
			// vygenerovani prvku
			generateItemLi(item, true).appendTo(target);
		}
		
		target.sortable({
			"start" : writeOrderToHistory,
			"stop" : changePosition
		});
		
		target = $("#unused-items");
		target.children().remove();
		
		for (var i in index) {
			var item = index[i];
			
			if (!item.container())
				generateItemLi(item, false).appendTo(target);
		};
	}
	
	// snaze prvek
	function deleteItem() {
		if (!confirm("Skutečně odstranit prvek?"))
			return;
		
		// vygenerovani parametru
		var prev = currentItem.prev();
		
		var params = {
				prev: prev ? prev.name() : prev,
				container: false,
				settings : currentItem.toArray()
		};
		
		if (currentItem.container()) {
			params.container = currentItem.container().name();
		}
		
		// vygenerovani zapisu do historie
		addToHistory(CHANGE_HISTORY.ACTIONS["delete"], currentItem, params);
		
		questionary.removeItem(currentItem);
		dialog.dialog("close");
		render();
		refreshItems();
		currentItem = null;
	}
	
	// novy prvek
	function addItem() {
		var itemClass = $(this).val();
		
		// vygenerovani jmena prvku
		var name;
		var i = 1;
		var found;
		var item = null;
		
		do {
			found = true;
			name = itemClass + String(i);
			
			try {
				item = questionary.addItem(name, itemClass);
			} catch (e) {
				found = false;
				i++;
			}
		} while (!found)
		
		addToHistory(CHANGE_HISTORY.ACTIONS["new"], item, { item : item.toArray() });
		
		// obnoveni seznamu objektu a vykresleni
		refreshItems();
		render();
		
		// zobrazeni dialogu editace
		$("#items>li:last-child>button.edit-element-button").click();
	}
	
	// ulozi asynchronne data
	function submitForm() {
		// serializace celeho dotazniku do pole
		var id = $("#questionary-id").val();
		
		var data = {
			questionary : {
				id : id,
				is_visible: $("#questionary-is_visible:checked").length,
				is_readable: $("#questionary-is_editable:checked").length,
				is_published : $("#questionary-is_published:checked").length,
				name : $("#questionary-name").val(),
				emails : $("#questionary-emails").val(),
				caption : $("#questionary-caption").val(),
				subcaption : $("#questionary-subcaption").val(),
				tags : $("#questionary-tags").val()
			}
		};
		
		$.post("/admin/putq.json", data, function (response) {
			alert("Data byla uložena");
		}, "json");
		
		return false;
	}
	
	function updateName() {
		questionary.setName($(this).val());
	}
	
	function toggleDelete() {
		if ($("#delete-confirm:checked").length) {
			$("#delete-submit").removeAttr("disabled");
		} else {
			$("#delete-submit").attr("disabled", "disabled");
		}
	}
	
	$(":button[name='add-item']").click(addItem);
	$("#items-panel").dialog({ position: "top"});
	$("#new-item").dialog({ position: "bottom"});
	$("#form-questionary-edit").submit(submitForm);
	$("#questionary-name").change(updateName);
	$("#undo").click(revertHistory);
	$("#redo").click(redoHistory);
	$("#delete-confirm").change(toggleDelete);
	
	questionary.setFromArray(QDATA);
	
	render();
	refreshItems();
	
	var defCaption = $("#default-caption");
	$("#questionary-caption").val(defCaption.val());
	defCaption.remove();
	
	defCaption = $("#default-subcaption");
	$("#questionary-subcaption").val(defCaption.val());
	defCaption.remove();
});
