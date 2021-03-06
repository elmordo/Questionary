var QUESTIONARY = {
		"__EXTENDS__" : function (parent, child) {
			child.prototype = new parent();
			child.prototype.__SUPER__ = parent.prototype;
		},
	
		"__BASE__" : (function __BASE__ (name) {
			// kontrola jmena
			this._name = name;
			
			// nastaveni dotazniku resi trida Questionary
		}),
		
		"Questionary" : (function Questionary () {
			// znovuvytvoreni objektu
			this._itemIndex = {};
			this._items = new Array();
		}),
		
		"Container" : (function Container (name) {
			this._items = new Array();
			
			QUESTIONARY.__BASE__.call(this, name);
			
			this._className = "Container";
		}),
		
		"ChooseInput" : (function ChooseInput (name) {
			this._options = new Array();
			
			QUESTIONARY.__BASE__.call(this, name);
		}),
		
		"SingleInput" : (function SingleInput (name) {
			QUESTIONARY.__BASE__.call(this, name);
			
			this._className = "SingleInput";
		}),
		
		"Label" : (function Label (name) {
			QUESTIONARY.__BASE__.call(this, name);
			
			this._className = "Label";
		}),
		
		"Text" : (function Radio (name) {
			QUESTIONARY.SingleInput.call(this, name);
			
			this._className = "Text";
		}),
		
		"TextArea" : (function TextArea (name) {
			QUESTIONARY.SingleInput.call(this, name);
			
			this._className = "TextArea";
		}),

		"CheckBox" : (function CheckBox (name) {
			QUESTIONARY.SingleInput.call(this, name);

			this._className = "CheckBox";
		}),
		
		"Select" : (function Select (name) {
			QUESTIONARY.ChooseInput.call(this, name);
			
			this._className = "Select";
		}),
		
		"Radio" : (function Radio (name) {
			QUESTIONARY.ChooseInput.call(this, name);
			
			this._className = "Radio";
		}),
		
		"ValueList" : (function ValueList (name) {
			QUESTIONARY.ChooseInput.call(this, name);
			
			this._className = "ValueList";
		}),
		
		"Group" : (function Group (name) {
			QUESTIONARY.Container.call(this, name);
			
			this._className = "Group";
		}),
                
        "GridView" : (function GridView (name) {
            QUESTIONARY.__BASE__.call(this, name);
            this._filledVal = new Array();
            this._default = new Array();
            
            this._className = "GridView";
            
            x = this;
        })
};

/*
 * OBECNY OBJEKT PRVKU
 */

// kontejnerovy prvek (skupina prvku)
QUESTIONARY.__BASE__.prototype._container = null;

QUESTIONARY.__BASE__.prototype._className = "__BASE__";

// vychozi hodnota
QUESTIONARY.__BASE__.prototype._default = "";

// vyplnena hodnota
QUESTIONARY.__BASE__.prototype._filledVal = null;

// prepinac vyplneni
QUESTIONARY.__BASE__.prototype._isFilled = false;

// prepinac uzamceni
QUESTIONARY.__BASE__.prototype._isLocked = false;

// popisek polozky
QUESTIONARY.__BASE__.prototype._label = "";

// jmeno polozky
QUESTIONARY.__BASE__.prototype._name = "";

// reprezentace v DOM (obalena jQuery)
QUESTIONARY.__BASE__.prototype._node = null;

// rodicovsky dotaznik
QUESTIONARY.__BASE__.prototype._questionary = null;

// nastavi nebo vraci hodnotu
QUESTIONARY.__BASE__.prototype._getOrSet = function (valName, value) {
	if (this[valName] === undefined) throw "Value " + valName + " is unknown";
	
	// vyhodnoceni hodnoty
	if (value === undefined) {
		// pozadavek na GET
		return this[valName];
	} else {
		// pozadavek na nastaveni
		this[valName] = value;
		
		return this;
	}
};

// generuje metodu, ktera se vola pri zmene hodnoty
QUESTIONARY.__BASE__.prototype._generateChangeMethod = function () {
	var instance = this;
	
	return function (e) {
		var value = $(this).val();
		
		instance.filledVal(value);
	};
};

// vraci jmeno tridy
QUESTIONARY.__BASE__.prototype.className = function () {
	return this._className;
};

// resetuje nastavena data
QUESTIONARY.__BASE__.prototype.clearData = function () {
	this._filledVal = null;
	this._isFilled = false;
	
	return this;
};

// vraci kontejner. Pokud item neni v kontejneru, vraci NULL
QUESTIONARY.__BASE__.prototype.container = function () {
	return this._container;
};

// nastavi nebo vraci vychozi hodnotu
QUESTIONARY.__BASE__.prototype.defVal = function (value) {
	return this._getOrSet("_default", value);
};

// vraci nebo nastavi vylnenou hodnotu
QUESTIONARY.__BASE__.prototype.filledVal = function (value) {
	// kontrola designModu
	if (value !== undefined && this._questionary.getDesignMode()) return this;
	
	if (value !== undefined) this._isFilled = true;
	
	return this._getOrSet("_filledVal", value);
};

// vraci vyplnenou hodnotu. Pokud neni nastavena, vraci vychoti
QUESTIONARY.__BASE__.prototype.getValue = function () {
	return this._isFilled ? this._filledVal : this._default;
};

// inicializuje prvek
QUESTIONARY.__BASE__.prototype.init = function (node) {
	this._node = $(node);
};

// vraci TRUE, pokud je prvek naplnen
QUESTIONARY.__BASE__.prototype.isFilled = function () {
	return this._isFilled;
};

// vraci nebo nastavi uzamceni prvku
QUESTIONARY.__BASE__.prototype.locked = function (isLocked) {
	if (isLocked !== undefined) 
		isLocked = Boolean(isLocked);
	
	return this._getOrSet("_isLocked", isLocked);
};

// vraci nebo nastavi popisek prvku
QUESTIONARY.__BASE__.prototype.label = function (label) {
	if (label !== undefined)
		label = String(label);
	
	return this._getOrSet("_label", label);
};

// vraci jmeno prvku
QUESTIONARY.__BASE__.prototype.name = function () {
	return this._name;
};

// vraci prvek, ktery se bude renderovat nasledne
QUESTIONARY.__BASE__.prototype.prev = function () {
	// prvek muze byt umisten v kontejneru - musi se to brat v potaz
	var items = this._getParentsItems();
	
	// nyni se prochazeji prvky a vyhodnocuje se nasledujici prvek
	for (var i in items) {
		if (items[i] === this) {
			// nasli jsme nas prvek, nyni vyhodnotime pozici
			if (i === (items.length - 1)) {
				// pred prvek neni zadny jiny prvek
				return null;
			}
			
			return items[i + 1];
		}
	}
	
	return null;
};

// vraci objekt rodicovskeho dotazniku
QUESTIONARY.__BASE__.prototype.questionary = function () {
	return this._questionary;
};

//vraci predchozi prvek v poradi renderu
QUESTIONARY.__BASE__.prototype.prev = function () {
	// prvek muze byt umisten v kontejneru - musi se to brat v potaz
	var items = this._getParentsItems();
	
	// nyni se prochazeji prvky a vyhodnocuje se nasledujici prvek
	for (var i in items) {
		if (items[i] === this) {
			// nasli jsme nas prvek, nyni vyhodnotime pozici
			if (i === 0) {
				// pred prvek neni zadny jiny prvek
				return null;
			}
			
			return items[i - 1];
		}
	}
	
	return null;
};

QUESTIONARY.__BASE__.prototype.renderItem = function () {
	// vygenerovani wrapperu a zapis labelu
	var retVal = $("<div class='questionary-item'>");
	
	$("<div class='questionary-label'>").text(this._label).appendTo(retVal);
	$("<div class='questionary-content'>").appendTo(retVal);
	$("<br class='questionary-clearer'>").appendTo(retVal);
	$("<input type='hidden' name='className'>").val(this._className).appendTo(retVal);
	$("<input type='hidden' name='itemName'>").val(this._name).appendTo(retVal);
	
	this._node = retVal;
	
	return retVal;
};

QUESTIONARY.__BASE__.prototype.renderEdit = function () {
	throw "Abstract class cant be rendered";
};

// nastavi data z objektu
QUESTIONARY.__BASE__.prototype.setFromArray = function (data) {
	// nastaveni dat
	this._default = data["default"];
	this._filledVal = data["value"];
	this._isFilled = data["isFilled"];
	this._isLocked = Number(data["isLocked"]);
	this._label = data["label"];
	
	return this;
};

// prenese data do objektu
QUESTIONARY.__BASE__.prototype.toArray = function () {
	var retVal = {
		"default" : this._default,
		"value" : this._filledVal,
		"isFilled" : Number(this._isFilled),
		"isLocked" : Number(this._isLocked),
		"label" : this._label,
		"name" : this._name,
		"params" : {},
		"className" : this._className
	};
	
	return retVal;
};

// protected - odebere kontejner
QUESTIONARY.__BASE__.prototype._clearContainer = function () {
	if (this._container) this._container.removeItem(this);
	
	return this;
};

// vraci seznam renderovacich prvku nadrazeneho elementu (dotaznik nebo kontejner)
QUESTIONARY.__BASE__.prototype._getParentsItems = function () {
	if (this.container()) {
		return this.container().getItems();
	} else {
		return this.questionary().getItems();
	}
};

// vypocita velikosti prvku
QUESTIONARY.__BASE__.prototype._recalculateHeight = function () {
	// nacteni prvku
	var label = this._node.find(".questionary-label");
	var content = this._node.find(".questionary-content");
	
	var labelHeight = label.css("height");
	var contentHeight = content.css("height");
	
	labelHeight = Number(labelHeight.substr(0, labelHeight.length - 2));
	contentHeight = Number(contentHeight.substr(0, contentHeight.length - 2));
	
	// vyhodnoceni vyssiho prvku
	if (labelHeight > contentHeight) {
		content.css("height", labelHeight + "px");
	} else {
		label.css("height", contentHeight + "px");
	}
};

// protected - zapise prvek do kontejneru
QUESTIONARY.__BASE__.prototype._setContainer = function (container) {
	// odebrani z kontejneru
	this._clearContainer();
	
	// pridani do kontejneru
	this._container = container;
	
	return this;
};

/*
 * OBJEKT DOTAZNIKU
 */

// prepinac designoveho kodu
QUESTIONARY.Questionary.prototype._designMode = false;

// jmeno dotazniku
QUESTIONARY.Questionary.prototype._name = "";

// prepinac uzamceni dotazniku
QUESTIONARY.Questionary.prototype._isLocked = false;

// index itemu dotazniku
QUESTIONARY.Questionary.prototype._itemIndex = {};

// seznam prvku, ktere se renderuji primo z dotazniku
QUESTIONARY.Questionary.prototype._items = new Array();

// vytvori novy prvek (staticka trida)
QUESTIONARY.Questionary.factory = function (name, className, questionary) {
	// kontrola dat
	if (QUESTIONARY[className] === undefined) throw "Item class " + className + " does not exist";
	if (!(questionary instanceof QUESTIONARY.Questionary)) throw "Invalid class of parent questionary";
	
	// kontrola jmena
	if (questionary._itemIndex[name] !== undefined) throw "Item named " + name + "is already existing";
	
	// vytvoreni itemu
	var item = new (QUESTIONARY[className])(name);
	
	// nastaveni dotazniku
	item._questionary = questionary;
	
	questionary._itemIndex[name] = item;
	questionary._items.push(item);
	
	return item;
};

// vytvori novy prvek a prida ho do dotazniku
QUESTIONARY.Questionary.prototype.addItem = function (name, className) {
	var retVal = QUESTIONARY.Questionary.factory(name, className, this);
	
	return retVal;
};

// vraci prvek podle jmena
QUESTIONARY.Questionary.prototype.getByName = function (name) {
	// kontrola existence
	if (this._itemIndex[name] === undefined) throw "Item named " + name + " is not existing in this questionary";
	
	return this._itemIndex[name];
};

// vraci TRUE, pokud je dotaznik v design modu
QUESTIONARY.Questionary.prototype.getDesignMode = function () {
	return this._designMode;
};

// vraci index prvku
QUESTIONARY.Questionary.prototype.getIndex = function () {
	// naklonovani indexu
	var retVal = {};
	
	for (var i in this._itemIndex) {
		retVal[i] = this._itemIndex[i];
	}
	
	return retVal;
};

// vraci seznam prvku primo renderovanych z dotazniku
QUESTIONARY.Questionary.prototype.getItems = function () {
	// naklonovani seznamu itemu
	var retVal = new Array();
	
	for (var i in this._items) {
		retVal[i] = this._items[i];
	}
	
	return retVal;
};

// vraci uzamceni dotazniku
QUESTIONARY.Questionary.prototype.getLocked = function () {
	return this._isLocked;
};

// vraci jmeno dotazniku
QUESTIONARY.Questionary.prototype.getName = function () {
	return this._name;
};

// vraci seznam jmen prvku
QUESTIONARY.Questionary.prototype.getNames = function () {
	// zjisteni jmen
	var retVal = new Array();
	
	for (var i in this._itemIndex) {
		retVal.push(i);
	}
	
	return retVal;
};

// vraci TRUE, pokud je prvek vykreslovan primo z dotazniku
QUESTIONARY.Questionary.prototype.getRenderable = function (item) {
	// vychozi hodnota je false
	var retVal = false;
	
	// iterace nad itemy a kontrola instance
	for (var i in this._items) {
		// kontrola stejne instance
		retVal = item === this._items[i];
		
		// pokud instance byla nalezena, ukonci se pruchod polem
		if (retVal) break;
	}
	
	return retVal;
};

// vraci vsechny hodnoty v Objectu
QUESTIONARY.Questionary.prototype.getValues = function () {
	var retVal = {};
	
	for (var i in this._itemIndex) {
		retVal[i] = this._itemIndex[i].getValue();
	}
	
	return retVal;
};

// nastavi prepinac design modu
QUESTIONARY.Questionary.prototype.setDesingMode = function (mode) {
	this._designMode = Boolean(mode);
	
	return this;
};

// nastavni uzamceni dotazniku
QUESTIONARY.Questionary.prototype.setLocked = function (locked) {
	locked = Boolean(locked);
	
	this._isLocked = locked;
	
	return this;
};

// odebere prvek z dotazniku
QUESTIONARY.Questionary.prototype.removeItem = function (item) {
	// kontrola, jeslti je prvek soucasti tohoto dotazniku
	if (item.questionary() !== this) throw "Item is not part of this questionary";
	
	// kontrola prislusnosti ke kontejneru
	if (item.container()) {
		// odebrani z kontejneru
		item.container().removeItem(item);
	}
	
	// odebrani z _items
	var newItems = new Array();
	var i = null;
	
	for (i in this._items) {
		// pokud zpracovavany objekt neni odebirany, zapise se noveho pole
		if (this._items[i] !== item) {
			newItems.push(this._items[i]);
		}
	}
	
	this._items = newItems;
	
	// odebrani z indexu
	var newIndex = {};
	
	for (i in this._itemIndex) {
		// pokud zpracovavany objekt neni odebirany, zapise se do noveho indexu
		if (this._itemIndex[i] !== item) {
			newIndex[i] = this._itemIndex[i];
		}
	}
	
	this._itemIndex = newIndex;
	
	// odebrani reference itemu
	item._questionary = null;
};

// vyrenderuje dotaznik
QUESTIONARY.Questionary.prototype.render = function () {
	// vygenerovani kontejneru
	var retVal = $("<div class='questionary'>");
	
	// hlavicka
	$("<div class='questionary-head'>").append($("<h2>").text(this._name)).appendTo(retVal);
	
	// prochazeni itemu a jejich rendering
	for (var i in this._items) {
		retVal.append(this._items[i].renderItem());
	}
	
	retVal.appendTo("body");
	
	for (var i in this._items) {
		this._items[i]._recalculateHeight();
	}
	
	return retVal;
};

// vykresli dotaznik pro editaci
QUESTIONARY.Questionary.prototype.renderEdit = function () {
	// vygenerovani kontejneru
	var retVal = $("<div class='questionary'>");
	
	// prochazeni itemu a jejich rendering
	for (var i in this._items) {
		retVal.append(this._items[i].renderEdit());
	}
	
	return retVal;
};

// nastavi dotaznik ze serializoveho pole
QUESTIONARY.Questionary.prototype.setFromArray = function (data) {
	this._name = data["name"];
	this._isLocked = data["isLocked"];
	
	for (var i in data["itemList"]) {
		var itemDef = data["itemList"][i];
		
		this.addItem(itemDef["name"], itemDef["className"]);
	}
	
	// deserializace z pole
	for (var i in data["itemList"]) {
		var itemDef = data["itemList"][i];
		
		this._itemIndex[itemDef["name"]].setFromArray(itemDef);
	}
	
	// zapis vykreslovanych prvku
	this._items = new Array();
	
	for (var i in data.items) {
		var itemName = data.items[i];
		
		this.setRenderable(this._itemIndex[itemName], true);
	}
	
	return this;
};

// nastavi jmeno dotazniku
QUESTIONARY.Questionary.prototype.setName = function (name) {
	name = String(name);
	
	this._name = name;
	
	return this;
};

// zaradi item jako renderovatelny primo z dotaznku a zaradi ho na konec fronty
// nebo ho vyradi uplne z renderovani
QUESTIONARY.Questionary.prototype.setRenderable = function (item, renderable) {
	// kontrola, jestli je objekt soucasti tohoto dotazniku
	if (item.questionary() !== this) throw "Item is not part of this questionary";
	
	// pokud je item soucasti kontejneru, odebere se
	if (item.container()) {
		item.container().removeItem(item);
	}
	
	// odebrani itemu z dotazniku
	var newItems = new Array();
	
	for (var i in this._items) {
		// pokud zpracovavany prvek neni nastavovany, prida se do tempu
		if (this._items[i] !== item) {
			newItems.push(this._items[i]);
		}
	}
	
	this._items = newItems;
	
	// pokud je renderable = TRUE, pak se prvek prida na konec seznamu
	if (renderable) {
		this._items.push(item);
	}
	
	return this;
};

// nastavi nove poradi renderovani prvku
QUESTIONARY.Questionary.prototype.setOrder = function(items) {
	// reset seznamu
	var newItems = new Array();
	
	// prochazeni seznamu a razeni
	for (var i in items) {
		var item = items[i];
		
		// kontrola dotazniku
		if (item.questionary() !== this) throw "Item on index " + i + " has wrong instance of questionary";
		
		// kontrola kontejneru a pripadne odebrani z nej
		if (item.container()) item.container().removeItem(item);
		
		// zapis
		newItems.push(item);
	}
	
	// zapis dat
	this._items = newItems;
	
	return this;
};

// serializuje dotznik do pole
QUESTIONARY.Questionary.prototype.toArray = function () {
	var retVal = {
			"name" : this._name,
			"itemList" : new Array(),
			"items" : new Array(),
			"isLocked" : this._isLocked
	};
	
	for (var i in this._items) {
		retVal.items.push(this._items[i].name());
	};
	
	for (var i in this._itemIndex) {
		retVal.itemList.push(this._itemIndex[i].toArray());
	}
	
	return retVal;
};

/*
 * objekt obecneho kontejneru
 */

// dedeni
QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.Container);

// jmeno tridy
QUESTIONARY.Container.prototype._className = "Container";

// seznam prvku
QUESTIONARY.Container.prototype._items = new Array();

// pridani prvku
QUESTIONARY.Container.prototype.addItem = function (item) {
	// odebrani z renderable
	this._questionary.setRenderable(item, false);
	
	// nastaveni noveho kontejneru
	item._setContainer(this);
	
	// zapis na konec
	this._items.push(item);
	
	return this;
};

// odebere vsechny prvky z kontejneru
QUESTIONARY.Container.prototype.clear = function () {
	var items = this._items;
	
	// postupne odebirani prvku
	for (var i in items) {
		this.removeItem(items[i]);
	}
	
	return this;
};

// vraci prvky kontejneru
QUESTIONARY.Container.prototype.getItems = function () {
	var retVal = new Array();
	
	for (var i in this._items) {
		retVal.push(this._items[i]);
	}
	
	return retVal;
};

// odebere prvek z kontejneru
QUESTIONARY.Container.prototype.removeItem = function (item) {
	// nalezeni prvku a vytvoreni noveho seznamu
	var newList = new Array();
	var found = false;
	
	for (var i in this._items) {
		// kontrola jeslti se jedna o spravny prvek
		if (item === this._items[i]) {
			found = true;
		} else {
			newList.push(this._items[i]);
		};
	}
	
	if (!found) throw "Item '" + item._name + "' not found in container '" + this._name + "'";
	
	// zapis novych prvku
	this._items = newList;
	
	// smazani kontejneru prvku
	item._container = null;
	
	return this;
};

// nastavi kontejner ze serializovaneho pole
QUESTIONARY.Container.prototype.setFromArray = function(data) {
	// zavolani predchozi metody
	QUESTIONARY.__BASE__.prototype.setFromArray.call(this, data);
	
	// zapis prvku
	for (var i in data.params.items) {
		var item = this._questionary.getByName(data.params.items[i]);
		
		this.addItem(item);
	}
	
	return this;
};

// nastavi prvky z pole
QUESTIONARY.Container.prototype.setItems = function (items) {
	this.clear();
	
	for (var i in items) {
		this.addItem(items[i]);
	}
	
	return this;
};

// serializuje skupinu do pole
QUESTIONARY.Container.prototype.toArray = function () {
	var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
	
	retVal.params["items"] = new Array();
	
	// zapis hodnot
	for (var i in this._items) {
		retVal.params.items.push(this._items[i].name());
	}
	
	return retVal;
};

/*
 * OBJEKT CHOOSEINPUT
 */

QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.ChooseInput);

QUESTIONARY.ChooseInput.prototype._className = "ChooseInput";

// jmeno tridy
QUESTIONARY.Container.prototype._className = "ChooseInfo";

// seznam moznosti
QUESTIONARY.ChooseInput.prototype._options = new Array();

// vycisti moznosti
QUESTIONARY.ChooseInput.prototype.clear = function () {
	this._options = new Array();
	
	return this;
};

// vraci klice hodnot
QUESTIONARY.ChooseInput.prototype.getKeys = function () {
	var retVal = new Array();
	
	for (var i in this._options) {
		retVal.push(i);
	}
	
	return retVal;
};

// vraci moznosti nastaveni
QUESTIONARY.ChooseInput.prototype.getOptions = function () {
	var retVal = new Array();
	
	// prekopirovani pole
	for (var i in this._options) {
		retVal[i] = this._options[i];
	}
	
	return retVal;
};

// vraci hodnotu
QUESTIONARY.ChooseInput.prototype.getOption = function (name) {
	// kontrola existence
	if (this._options[name] === undefined) throw "Option named '" + name + "' doesn't exist";
	
	return this._options[name];
};

// vraci TRUE, pokud je moznost definovana
QUESTIONARY.ChooseInput.prototype.isOption = function (name) {
	return (this._options[name] === undefined) ? false : true;
};

// odebere moznost ze seznamu
QUESTIONARY.ChooseInput.prototype.removeOption = function (name) {
	var found = false;
	
	var buffer = {};
	
	for (var i in this._options) {
		// vyhodnoceni, jeslti se jedna o hledany prvek
		if (i !== name) {
			buffer[i] = this._options[i];
		} else {
			found = true;
		}
	}
	
	if (!found) throw "Option named '" + name + "' doesn't exist";
	
	// zapis novych dat
	this._options = buffer;
	
	return this;
};

// deserializuje hodnoty z pole
QUESTIONARY.ChooseInput.prototype.setFromArray = function (data) {
	// zavolani predka
	QUESTIONARY.__BASE__.prototype.setFromArray.call(this, data);
	
	// nastaveni hodnot
	this.setOptions(data.params.options);
	
	return this;
};

// nastavi nabidku hodnot z pole
QUESTIONARY.ChooseInput.prototype.setOptions = function (options) {
	this._options = {};
	
	for (var i in options) {
		this._options[i] = options[i];
	}
	
	return this;
};

// nastavi jednu hodnotu
QUESTIONARY.ChooseInput.prototype.setOption = function (name, value) {
	this._options[name] = value;
	
	return this;
};

QUESTIONARY.ChooseInput.prototype.toArray = function () {
	// metoda predka
	var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
	
	retVal.params.options = {};
	
	for (var i in this._options) {
		retVal.params.options[i] = this._options[i];
	}
	
	return retVal;
};

/*
 * JEDNODUCHY VSTUP
 */

QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.SingleInput);

// jmeno tridy
QUESTIONARY.SingleInput.prototype._className = "SingleInput";

// maximalni delka
QUESTIONARY.SingleInput.prototype._maxLength = 0;

// vraci delku vstupu
QUESTIONARY.SingleInput.prototype.getLength = function () {
	return this._maxLength;
};

// nastavi serializovane hodnoty
QUESTIONARY.SingleInput.prototype.setFromArray = function (data) {
	QUESTIONARY.__BASE__.prototype.setFromArray.call(this, data);
	
	this._maxLength = data.params.maxLength;
	
	return this;
};

// nastavei maximalni delku vstupu
QUESTIONARY.SingleInput.prototype.setLength = function(length) {
	length = Number(length);
	
	if (length === NaN) throw "Length must be number";
	
	this._maxLength = length;
};

// serializuje objekt do pole
QUESTIONARY.SingleInput.prototype.toArray = function () {
	var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
	
	retVal.params.maxLength = this._maxLength;
	
	return retVal;
};

/*
 * OBJEKT LABEL
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.Label);

QUESTIONARY.Label.prototype._className = "Label";

QUESTIONARY.Label.prototype._recalculateHeight = function () {
	
};

QUESTIONARY.Label.prototype.renderItem = function () {
	var retVal = QUESTIONARY.__BASE__.prototype.renderItem.call(this);
	
	retVal.find(".questionary-content").remove();
	retVal.find(".questionary-label").addClass("questionary-item-label");
	return retVal;
};

QUESTIONARY.Label.prototype.toArray = function () {
	var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT TEXT
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.Text);

QUESTIONARY.Text.prototype._className = "Text";

// inicializuje prvek
QUESTIONARY.Text.prototype.init = function (node) {
	QUESTIONARY.SingleInput.prototype.init.call(this, node);
	
	node = $(node);
	node.find(":text.questionary-item-text").change(this._generateChangeMethod());
	
	if (this._questionary._isLocked) {
		node.find(":text.questionary-item-text").attr("readonly", "readonly");
	} else if (this._isLocked) {
		node.find(":text.questionary-item-text").attr("disabled", "disabled");
	}
};

QUESTIONARY.Text.prototype.renderItem = function () {
	var retVal = QUESTIONARY.SingleInput.prototype.renderItem.call(this);
	
	// pripojeni pole
	var content = $("<input type='text' class='questionary-item-text'>").attr("name", this._name);
	
	content.appendTo(retVal.find(".questionary-content"));
	
	var val = this.getValue();
	
	if (val !== null) content.val(val);
	
	this.init(retVal);
	
	return retVal;
};

QUESTIONARY.Text.prototype.toArray = function () {
	var retVal = QUESTIONARY.SingleInput.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT TEXTAREA
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.TextArea);

QUESTIONARY.TextArea.prototype._className = "TextArea";

// inicializuje prvek
QUESTIONARY.TextArea.prototype.init = function (node) {
	QUESTIONARY.SingleInput.prototype.init.call(this, node);
	
	node = $(node);
	node.find("textarea.questionary-item-textarea").change(this._generateChangeMethod());
	
	if (this._questionary._isLocked) {
		node.find("textarea.questionary-item-textarea").attr("readonly", "readonly");
	}else if (this._isLocked) {
		node.find("textarea.questionary-item-textarea").attr("disabled", "disabled");
	}
};

QUESTIONARY.TextArea.prototype.renderItem = function () {
	var retVal = QUESTIONARY.SingleInput.prototype.renderItem.call(this);
	
	// pripojeni pole
	var content = $("<textarea class='questionary-item-textarea'>").attr("name", this._name);
	
	content.appendTo(retVal.find(".questionary-content"));
	
	var val = this.getValue();
	
	if (val !== null) content.val(val);
	
	this.init(retVal);
	
	return retVal;
};

QUESTIONARY.TextArea.prototype.toArray = function () {
	var retVal = QUESTIONARY.SingleInput.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT SELECT
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.ChooseInput, QUESTIONARY.Select);

QUESTIONARY.Select.prototype._className = "Select";

// inicializuje objekt 
QUESTIONARY.Select.prototype.init = function (node) {
	QUESTIONARY.ChooseInput.prototype.init.call(this, node);
	
	node = $(node);
	node.find("select.questionary-item-select").change(this._generateChangeMethod());

	if (this._questionary._isLocked) node.find("select.questionary-item-select").attr("readonly", "readonly");
	else if (this._isLocked) node.find("select.questionary-item-select").attr("disabled", "disabled");
};

QUESTIONARY.Select.prototype.renderItem = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.renderItem.call(this);
	
	// pripojeni pole
	var content = $("<select class='questionary-item-select'>").attr("name", this._name);
	
	// vyhodnoceni vychozi hodnoty
	var val = this.getValue();
	
	// zapis moznosti
	for (var i in this._options) {
		$("<option>").attr("value", i).text(this._options[i]).appendTo(content);
	}
	
	if (val !== null) content.val(val);
	
	content.appendTo(retVal.find(".questionary-content"));

	this.init(retVal);
	
	return retVal;
};

QUESTIONARY.Select.prototype.toArray = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT RADIO
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.ChooseInput, QUESTIONARY.Radio);

QUESTIONARY.Radio.prototype._className = "Radio";

QUESTIONARY.Radio.prototype.init = function (node) {
	// inicializace celeho itemu
	QUESTIONARY.ChooseInput.prototype.init.call(this, node);
	
	node = $(node);
	
	var instance = this;
	
	// inicializace zmeny
	node.find(".questionary-item-radio :radio").each(function () {
		$(this).click(instance._generateChangeMethod());
	});
	
	// vyhodnoceni vypnuti policka
	if (this._questionary._isLocked) node.find(".questionary-item-radio :radio").attr("readonly", "readonly");
	else if (this._isLocked) node.find(".questionary-item-radio :radio").attr("disabled", "disabled");
};

QUESTIONARY.Radio.prototype.renderItem = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.renderItem.call(this);
	
	// pripojeni pole
	var content = $("<div class='questionary-item-radio'>");
	
	// vyhodnoceni vychozi hodnoty
	var val = this.getValue();
	
	// zapis moznosti
	for (var i in this._options) {
		var span = $("<span>").addClass('questionary-item-radio-item').appendTo(content);
		var input = $("<input type='radio'>").attr("name", this._name).val(i)
			.attr("title", this._options[i]).appendTo(span);
		
		// vyhodnoceni zaskrtnuti
		if (i === val) input.attr("checked", "checked");
	}
	
	content.appendTo(retVal.find(".questionary-content"));
	
	this.init(retVal);
	
	return retVal;
};

QUESTIONARY.Radio.prototype.toArray = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT VALUELIST
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.ChooseInput, QUESTIONARY.ValueList);

QUESTIONARY.ValueList.prototype._className = "ValueList";

QUESTIONARY.ValueList.prototype.init = function (node) {
	// inicializace celeho itemu
	QUESTIONARY.ChooseInput.prototype.init.call(this, node);
	
	node = $(node);
};

QUESTIONARY.ValueList.prototype.renderItem = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.renderItem.call(this);
	
	// pripojeni pole
	var content = $("<div class='questionary-item-valuelist'>");
	
	// vyhodnoceni vychozi hodnoty
	var val = this.getValue();
	
	// zapis moznosti
	for (var i in this._options) {
		//var span = $("<span class='questionary-item-valuelist-item'>").text(this._options[i]);
        var span = $("<span>").addClass("questionary-item-valuelist-item").appendTo(content).text(this._options[i]);
                
	}
	
	content.appendTo(retVal.find(".questionary-content"));
	
	this.init(retVal);
	
	return retVal;
};

QUESTIONARY.ValueList.prototype.toArray = function () {
	var retVal = QUESTIONARY.ChooseInput.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * OBJEKT GROUP
 */
 
QUESTIONARY.__EXTENDS__(QUESTIONARY.Container, QUESTIONARY.Group);

QUESTIONARY.Group.prototype._className = "Group";

QUESTIONARY.Group.prototype.init = function (node) {
	// zavolani inicializace predka
	QUESTIONARY.Container.prototype.init.call(this, node);
	
	node = $(node);
	var instance = this;
	
	node.find("span.questionary-item-group-chekbox :checkbox").click(function (e) {
		var check = $(this);
		var checked = check.filter(":checked").length;
		var content = node.find(".questionary-item-group");
		
		if (checked) {
			instance.filledVal(1);
		} else {
			instance.filledVal(0);
		}
		
		// vyhodnoceni schovani
		if (instance.getValue()) {
			content.show();
		} else {
			content.hide();
		}
		
		// rekalkulace zobrazeni
		instance._recalculateHeight();
	});
};

QUESTIONARY.Group.prototype._recalculateHeight = function () {
	for (var i in this._items) {
		this._items[i]._recalculateHeight();
	}
};

QUESTIONARY.Group.prototype.renderItem = function () {
	var retVal = QUESTIONARY.Container.prototype.renderItem.call(this);
	
	// vytvoreni noveho labelu
	var labelContainer = $("<div class='questionary-item-group-label'>");
	
	$("<input type='hidden' value='0'>").attr("name", this._name).appendTo(labelContainer);
	
	var check = $("<input type='checkbox' value='1'>").appendTo(
		$("<span class='questionary-item-group-chekbox'>").appendTo(labelContainer)
	).attr("name", this._name).attr("checked", "checked");
	
	$("<span class='questionary-item-group-label'>").text(this._label).appendTo(labelContainer);
	
	retVal.find(".questionary-label").replaceWith(labelContainer);
	
	// uprava labelu a odstraneni kontejneru
	retVal.find(".questionary-label").removeClass("questionary-label").addClass("questionary-item-group-label");
	
	
	var target = retVal.find(".questionary-content").removeClass("questionary-content").addClass("questionary-item-group-content");
	
	// pripojeni pole
	var content = $("<div class='questionary-item-group'>");
	
	// zapis moznosti
	for (var i in this._items) {
		this._items[i].renderItem().appendTo(content);
	}
	
	// udalost sbaleni
	this.init(retVal);
	
	// nastaveni hodnoty
	var val = this.getValue();
	
	if (val !== null && Number(val) === 0) {
		check.removeAttr("checked");
		content.hide();
	}
	
	content.appendTo(target);
	
	return retVal;
};

QUESTIONARY.Group.prototype.toArray = function () {
	var retVal = QUESTIONARY.Container.prototype.toArray.call(this);
	
	return retVal;
};

/*
 * PRVEK GridView
 */

QUESTIONARY.__EXTENDS__(QUESTIONARY.__BASE__, QUESTIONARY.GridView);

// minimalni pocet radku, ktere se zobrazi
QUESTIONARY.GridView.prototype._minRows = 1;

// definice sloupcu
QUESTIONARY.GridView.prototype._columns = new Array(
        { caption : "Hodnota", name : "value", type : "text"}
);

// prepinac umoznujici zobrazit / skryt tlactko pridani radku
QUESTIONARY.GridView.prototype._showAdd = true;

// prepinac umoznujici zobrazit / skrit tlacitko odebrani radku
QUESTIONARY.GridView.prototype._showDel = true;

QUESTIONARY.GridView.prototype.init = function (node) {
    QUESTIONARY.__BASE__.prototype.init.call(this, node);
    
    // event smazani dat
    var context = this;
    
    node.find("table button").click(function () {
        var element = $(this);
        var index = element.parents("tr:first").index();
        
        $(this).parents("tr:first").remove();
        
        // odebrani indexu z dat
        var newList = new Array();
        
        for (var i in context._rowData) {
            if (i !== index) {
                newList.push(context._filledVal[i]);
            }
        }
        
        context._filledVal = newList;
    });
    
    node.find("table").find("input[type='text']").change(this._changeEvent());
    
    node.find("button[name='add']").click(function () {
        var row = context._generateEmptyRow();
        var index = context._filledVal.length;
        context._filledVal.push(row);
        
        var rowNode = context._generateRow(row, index);
        rowNode.find("input[type='text']").change(context._changeEvent());
        
        context._node.find("table").append(rowNode);
    });
};

QUESTIONARY.GridView.prototype.renderItem = function () {
    var retVal = QUESTIONARY.__BASE__.prototype.renderItem.call(this);
    
    // prepis trid
    retVal.find(".questionary-label").addClass("questionary-item-gw-label");
    
    var contentWrapper = retVal.find(".questionary-content");
    contentWrapper.addClass("questionary-item-gw-content");
    
    // vygenerovani tabulky pro append
    var table = $("<table />").appendTo(contentWrapper);
    var headRow = $("<tr />").appendTo($("<thead />").appendTo(table));
    
    for (var i in this._columns) {
        var col = this._columns[i];

        // kontrola, zda se nejdena o skrytou hodnotu
        if (col.type !== "hidden") {
	        headRow.append(
	            $("<th>").text(col.caption)
	        );
    	}
    }
    
    if (this._showDel) {
        headRow.append($("<th class='questionary-item-gw-delcol'>").html("&nbsp;"));
    }
    
    // doplneni prazdnych hodnot, pokud neni dost dat
    for (var i = this._filledVal.length; i < this._minRows; i++) {
        this._filledVal.push(this._generateEmptyRow());
    }
    
    // vygenerovani datovych radku
    for (var i in this._filledVal) {
        var row = this._filledVal[i];
        
        table.append(this._generateRow(row, i));
    }
    
    if (this._showAdd) {
        // pridani tlacika k vytvoreni noveho radku
        $("<button type='button' name='add'>Přidat řádek</button>").appendTo(contentWrapper);
    }
    
    this.init(retVal);
    
    return retVal;
};

QUESTIONARY.GridView.prototype.setFromArray = function (data) {
    QUESTIONARY.__BASE__.prototype.setFromArray.call(this, data);
    
    if (this._filledVal === null) {
        this._filledVal = this._default;
    }
    
    this._minRows = Number(data.params.minRows);
    this._columns = data.params.columns;
    this._showDel = data.params.showDel;
    this._showAdd = data.params.showAdd;
};

QUESTIONARY.GridView.prototype.toArray = function () {
    var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
    
    retVal.default = this._filledVal;
    retVal.params.columns = this._columns;
    retVal.params.minRows = this._minRows;
    retVal.params.showDel = this._showDel;
    retVal.params.showAdd = this._showAdd;
    
    return retVal;
};

// vygeneruje radek na zaklade dodanych dat
QUESTIONARY.GridView.prototype._generateRow = function (data, index) {
    // priprava navratove hodnoty
    var retVal = $("<tr />");
    
    // pokud nejsou nastavena data, vygeneruje se prazdne pole
    if (data === undefined) {
        data = new Array();
        
        for (i in this._columns) {
        	var col = this._columns[i];
            data[col.name] = "";
        }
    }
    
    // vygenerovani dat
    for (var i in this._columns) {
        // vygenerovani prvku
        var element = null;
        var col = this._columns[i];
        var visible=true;

        switch (col.type) {
            case "text":
                element = $("<input type='text' />").attr("name", this._name + "[" + index + "][" + col.name + "]");
                break;
            
            case "hidden":
            	element = $("<input type='hidden' />").attr("name", this._name + "[" + index + "][" + col.name + "]");
            	visible = false;
            	break;

            case "const":
            	element = $("<span>").text(data[col.name]);
            	var helper = $("<input type='hidden' />").attr("name", this._name + "[" + index + "][" + col.name + "]");
            	helper.val(data[col.name]);
            	element.append(helper);
            	break;

            case "checkbox":
            	var chBox = $("<input type='checkbox' value='1'>").attr("name", this._name + "[" + index + "][" + col.name + "]");

            	// kontrola zaskrtnuti
            	if (Number(data[col.name]) == 1) chBox.attr("checked", "checked");

            	element = $("<span>").append(
            		// hodnota pri nezaskrtnuti
            		$("<input type='hidden' value='0' />").attr("name", this._name + "[" + index + "][" + col.name + "]")
            	).append(
            		chBox
            	);
            	break;

            default:
                throw "Unsupported column type";
        }

        // zapis dat a zarazeni do radku
        element.val(data[col.name]);

        var td = $("<td>").append(element).appendTo(retVal);

        if (!visible) td.css("display", "none");
    }
    
    // kontrola, zda se ma pridat tlacitko smazani
    if (this._showDel) {
        var btn = $("<button type='button'>Smazat</button>").appendTo(retVal);
    }
    
    return retVal;
};

// vygeneruje prazdna data radku
QUESTIONARY.GridView.prototype._generateEmptyRow = function () {
    var retVal = new Array();
    
    for (var i in this._columns) {
        retVal.push("");
    }
    
    return retVal;
};

// prepisuje funkci nastaveni vysky popisku
QUESTIONARY.GridView.prototype._recalculateHeight = function () {
    
};

QUESTIONARY.GridView.prototype._changeEvent = function () {
    var context = this;
    
    return function (e) {
        // zjisteni pozice
        context._isFilled = true;
        
        var element = $(this);
        var colIndex = element.parents("td:first").index();
        var rowIndex = element.parents("tr:first").index();
        
        // zapis informace
        context._filledVal[rowIndex][colIndex] = element.val();
    };
};

/**
 * PRVEK CheckBox
 */

QUESTIONARY.__EXTENDS__(QUESTIONARY.SingleInput, QUESTIONARY.CheckBox);

/**
 * prepinac, zda bude tlacitko po vykresleni zaskrtnuto
 */
QUESTIONARY.CheckBox.prototype._checked=0;

/**
 * hodnota zaskrtnuteho tlacitka
 */
QUESTIONARY.CheckBox.prototype._checkedVal="1";

/**
 * inicializuje prvek
 */
QUESTIONARY.CheckBox.prototype.init = function (node) {
	var context = this;

	node.find(":checkbox").click(function () {
		if ($(this).filter(":checked").length) {
			// policko je zaskrtnute
			context._filledVal = context._checkedVal;
		} else {
			// policko neni zaskrtnute
			context._filledVal = context._default;
		}

		context._isFilled = true;
	});
}

/**
 * metoda vykresleni
 */
QUESTIONARY.CheckBox.prototype.renderItem = function () {
	this._default = "0";
	var retVal = this.__SUPER__.renderItem.call(this);

	// vygenerovani zaskrtavaciho policka a jeho vychozi hodnoty
	var def = $("<input type='hidden'>").attr("name", this._name).val(this._default);
	var box = $("<input type='checkbox'>").attr("name", this._name).val(this._checkedVal);

	// vyhodnoceni vychozi hodnoty
	if (this._filledVal === this._checkedVal) box.attr("checked", "checked");
	
	retVal.find(".questionary-content").append(def).append(box);

	// inicializace prvku
	this.init(retVal);

	return retVal;
}

/**
 * deserializace
 */
QUESTIONARY.CheckBox.prototype.setFromArray = function (data) {
    QUESTIONARY.__BASE__.prototype.setFromArray.call(this, data);

    this._default = "0";
    
    this._checked = Number(data.params.checked);
    this._checkedVal = data.params.checkedVal;
};

/**
 * serializace
 */
QUESTIONARY.CheckBox.prototype.toArray = function () {
    var retVal = QUESTIONARY.__BASE__.prototype.toArray.call(this);
    
    retVal.params.checked = this._checked;
    retVal.params.checkedVal = this._checkedVal;
    
    return retVal;
};