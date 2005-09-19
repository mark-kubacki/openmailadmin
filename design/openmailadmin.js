function admin_panel_showhide (e) {
    switch(this.id) {
	case "admin_show":
	    this.style.display = "none";
	    document.getElementById("admin_panel").style.display = "block";
	    break;
	case "admin_hide":
	    document.getElementById("admin_panel").style.display = "none";
	    document.getElementById("admin_show").style.display = "block";
	    break;
    }
}

function newsletter_showhide (e) {
    /* Is this the show or hide button? */
    if(this.nextSibling != null && this.nextSibling.tagName.toLowerCase() == "div") {
	/* show */
	this.style.display = "none";
	this.nextSibling.style.display = "block";
    }
    else {
	/* hide */
	this.parentNode.style.display = "none";
	this.parentNode.previousSibling.style.display = "block";
    }
}

function check_corresponding_box (e) {
    /* (ascending) me -> dd -> dl (descending) -> dt -> input [checkbox] */
    this.parentNode.parentNode.firstChild.firstChild.checked=true;
}

function init_oma() {
    /* Initialise admin-panel buttons. */
    if(document.getElementById("admin_hide") != null) {
	document.getElementById("admin_hide").admin_panel_showhide = admin_panel_showhide;
	document.getElementById("admin_show").admin_panel_showhide = admin_panel_showhide;
	XBrowserAddHandler(document.getElementById("admin_hide"),"click","admin_panel_showhide");
	XBrowserAddHandler(document.getElementById("admin_show"),"click","admin_panel_showhide");
	document.getElementById("admin_panel").style.display = "none";
    }
    /* newsletter quasi-buttons */
    var spans = document.getElementsByTagName("span");
    for (var i = 0; i < spans.length; i++) {
	if(spans[i].className == "quasi_btn") {
	    if(spans[i].id == "" && spans[i].nextSibling != null
	       && spans[i].nextSibling.tagName.toLowerCase() == "div") {
		/* We have found our newsletter buttons. */
		spans[i].newsletter_showhide = newsletter_showhide;
		XBrowserAddHandler(spans[i],"click","newsletter_showhide");
		spans[i].nextSibling.firstChild.newsletter_showhide = newsletter_showhide;
		XBrowserAddHandler(spans[i].nextSibling.firstChild,"click","newsletter_showhide");
		spans[i].nextSibling.style.display = "none";
	    }
	}
    }
    /* textfields whose visible neighbours are checkboxes */
    var tinp = document.getElementsByTagName("input");
    for (var i = 0; i < tinp.length; i++) {
	try {
	    if(tinp[i].parentNode.parentNode.firstChild.firstChild != null
		    && tinp[i].parentNode.parentNode.firstChild.firstChild.getAttribute("type", "false") == "checkbox") {
		tinp[i].check_corresponding_box = check_corresponding_box;
		XBrowserAddHandler(tinp[i],"change","check_corresponding_box");
	    }
	} catch (e) {
	}
    }
}
