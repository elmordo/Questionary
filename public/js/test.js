var q;

$(function () {
 q = new QUESTIONARY.Questionary();

var i = q.addItem("prdel", "Label");
var ti = q.addItem("text", "Text").defVal("ahoj dvěte");
var tt = q.addItem("textarea", "TextArea").defVal("ahoj světe");
var tt2 = q.addItem("textarea2", "TextArea").defVal("kunda");
var ts = q.addItem("select", "Select");
var tr = q.addItem("radio", "Radio");
var g = q.addItem("group", "Group").label("Toto je skupina").defVal(0);

g.addItem(i);
tt.filledVal("Die bitch!!");

i.label("Ahoj nazdar už jsme tu, vracíme se z výletu, všichni nám tu chcípli a vezeme domů mrtvoly");
ti.label("Jméno posledního zemřelého");
tt.label("Popis smrti");
ts.setOptions({
	"prdel" : "Prdel",
	"kunda" : "Kunda",
	"pica" : "Píča",
	"kokot" : "Kokot"
}).label("Vyber pohlaví:").defVal("kokot");

tr.setOptions({
	"prdel" : "Prdel",
	"kunda" : "Kunda",
	"pica" : "Píča",
	"kokot" : "Kokot"
}).label("Vyber způsob smrti:").defVal("kunda");

q.render().appendTo($("#content"));

x = q.toArray();

b = new QUESTIONARY.Questionary();

b.setFromArray(x).render().appendTo("#content2");

i.label("prdel");

$("#content").children().remove().end().append(q.render());

b.render();
$("#content2").children().remove().end().append(b.render());
});
