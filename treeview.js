/*
 * This one is adapted from Chris Hunt's version at Tec Tips:
 * http://www.tek-tips.com/viewthread.cfm?qid=1094852&page=1
 * by W-Mark Kubacki; kubacki@hurrikane.de
 */

function toggle (e) {
	var list = this.nextSibling;

	if(list.style.display == "none") {
		this.parentNode.className = "expanded";
		list.style.display = "block";
	}
	else {
		this.parentNode.className = "collapsed";
		list.style.display = "none";
	}
}

/* Cross-browser event adder, from http://weblogs.asp.net/asmith/archive/2003/10/06/30744.aspx */
function XBrowserAddHandler(target,eventName,handlerName) {
	if(target.addEventListener) {
		target.addEventListener(eventName, function(e){target[handlerName](e);}, false);
	}
	else if(target.attachEvent) {
		target.attachEvent("on" + eventName, function(e){target[handlerName](e);});
	}
	else {
		var originalHandler = target["on" + eventName];
		if(originalHandler) {
			target["on" + eventName] = function(e){originalHandler(e);target[handlerName](e);};
		}
		else {
			target["on" + eventName] = target[handlerName];
		}
	}
}

function init_tree() {
	/* Find our TreeView, which resides a div with id "folder_acl_tree". */
	var divs = document.getElementsByTagName("div");
	for (var i = 0; i < divs.length; i++) {
		if(divs[i].id == "folder_acl_tree") {
			/* Every SPAN followed by a UL has to be a container. */
			var links = divs[i].getElementsByTagName("span");
			for(var j = 0; j < links.length; j++) {
				if(links[j].nextSibling != null) {
					links[j].toggle = toggle;
					XBrowserAddHandler(links[j],"click","toggle");
					/* New or active entries shall be visible. */
					if(links[j].className == "ina_mbox") {
					links[j].parentNode.className = "collapsed";
					}
				}
			}
			/* By default all trees are collapsed and thus not displayed... */
			var lists = divs[i].getElementsByTagName("ul");
			for(var j = 0; j < lists.length; j++) {
				if(lists[j].parentNode.className == "collapsed") {
					lists[j].style.display = "none";
				}
			}
			/* ...except for the root tree. */
			try {
				divs[i].firstChild.style.display = "block";
				divs[i].firstChild.className = "tree_root";
			} catch (e) {
			}
			break; /* We assume there is only one tree. */
		}
	}
}
