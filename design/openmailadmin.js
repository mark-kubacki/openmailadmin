/******************************************************************************
 * aux functions
 ******************************************************************************/
function sieve_by(arr_elemts, what, only_accepted_value) {
	var result = new Array();
	for(var i = 0; i < arr_elemts.length; i++) {
		if(arr_elemts[i].getAttribute(what, "false") == only_accepted_value) {
			result.push(arr_elemts[i]);
		}
	}
	return result;
}

function does_admin_panel_exists() {
	var panel = document.getElementById("admin_panel");
	if(panel != null) {
		return true;
	} else {
		return false;
	}
}

function get_all_inputs_of_admin_panel() {
	try {
		var panel = document.getElementById("admin_panel");
		var boxes = panel.getElementsByTagName("input");
		return boxes;
	} catch(e) {
		return new Array();
	}
}

function get_all_checkboxes_in_admin_panel() {
	var boxes = get_all_inputs_of_admin_panel();
	boxes = sieve_by(boxes, "type", "checkbox");
	return boxes;
}

function get_admin_panels_action_options() {
	var boxes = get_all_inputs_of_admin_panel();
	boxes = sieve_by(boxes, "type", "radio");
	boxes = sieve_by(boxes, "name", "action");
	return boxes;
}

function set_style_display(elements, to) {
	for (var i = 0; i < elements.length; i++) {
		elements[i].style.display = to;
	}
}

function get_admin_panels_form_fields_container(input_name) {
	var inp = get_all_inputs_of_admin_panel();
	inp = sieve_by(inp, "name", input_name);
	return inp[0].parentNode.parentNode;
}

function get_admin_panel_fields(arr_names) {
	var tmp = new Array();
	for (var i = 0; i < arr_names.length; i++) {
		try {
		tmp.push(get_admin_panels_form_fields_container(arr_names[i]));
		} catch(e) {
		}
	}
	return tmp;
}

function admin_panel_fields_show_xor_hide(arr_show, arr_hide) {
	set_style_display(get_admin_panel_fields(arr_show), "");
	set_style_display(get_admin_panel_fields(arr_hide), "none");
}

function get_admin_panel_owner() {
	var inputs = get_all_inputs_of_admin_panel();
	inputs = sieve_by(inputs, "name", "frm");
	return inputs[0].getAttribute("value", "false");
}

/******************************************************************************
 * now come action handler
 ******************************************************************************/
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

function hide_all_checkboxes_in_admin_panel (e) {
	set_style_display(get_all_checkboxes_in_admin_panel(), "none");
}

function show_all_checkboxes_in_admin_panel (e){
	set_style_display(get_all_checkboxes_in_admin_panel(), "");
}

function get_current_show_xor_hide_table(panel_owner, current_action) {
	var tbl = new Object();
	tbl['virtual']			= new Object();
	tbl['virtual']['new']		= new Array(new Array('dest_is_mbox', 'alias'), new Array());
	tbl['virtual']['delete']	= new Array(new Array(), new Array('dest_is_mbox', 'alias'));
	tbl['virtual']['dest']		= new Array(new Array('dest_is_mbox'), new Array('alias'));
	tbl['virtual']['active']	= tbl['virtual']['delete'];
	tbl['ACL']			= new Object();
	tbl['ACL']['new']		= new Array(new Array('subname'), new Array('dummy'));
	tbl['ACL']['delete']		= new Array(new Array(), new Array('subname', 'dummy'));
	tbl['ACL']['rights']		= new Array(new Array('dummy'), new Array('subname'));
	tbl['virtual_regexp']		= new Object();
	tbl['virtual_regexp']['new']	= new Array(new Array('reg_exp', 'dest_is_mbox'), new Array('probe'));
	tbl['virtual_regexp']['delete']	= new Array(new Array(), new Array('probe', 'reg_exp', 'dest_is_mbox'));
	tbl['virtual_regexp']['dest']	= new Array(new Array('dest_is_mbox'), new Array('reg_exp', 'probe'));
	tbl['virtual_regexp']['active']	= tbl['virtual_regexp']['delete']
	tbl['virtual_regexp']['probe']	= new Array(new Array('reg_exp', 'probe'), new Array('dest_is_mbox'));
	tbl['domains']			= new Object();
	tbl['domains']['new']		= new Array(new Array('domain', 'owner', 'a_admin', 'categories'), new Array());
	tbl['domains']['delete']	= new Array(new Array(), new Array('domain', 'owner', 'a_admin', 'categories'));
	tbl['domains']['change']	= tbl['domains']['new'];
	tbl['user']			= new Object();
	tbl['user']['new']		= new Array(new Array('mbox', 'dummy2', 'person', 'canonical', 'domains', 'quota', 'max_alias', 'max_regexp', 'dummy'), new Array());
	tbl['user']['change']		= tbl['user']['new']
	tbl['user']['delete']		= new Array(new Array(), new Array('mbox', 'dummy2', 'person', 'canonical', 'domains', 'quota', 'max_alias', 'max_regexp', 'dummy'));
	tbl['user']['active']		= tbl['user']['delete']

	return tbl[panel_owner][current_action];
}

function show_only_necessary_fields (e) {
	var cur	= get_current_show_xor_hide_table(get_admin_panel_owner(),
						  this.getAttribute("value", "false"));
	admin_panel_fields_show_xor_hide(cur[0], cur[1]);
}

/******************************************************************************
 * for initialization
 ******************************************************************************/
function get_inputs_with_nearby_checkboxes(root) {
	var result = new Array();
	var tinp = root.getElementsByTagName("input");
	for (var i = 0; i < tinp.length; i++) {
		// If this is already a checkbox there is no need of checking another one.
		if(tinp[i].getAttribute("type", "false") == "checkbox") {
			continue;
		}
		try {
			if(tinp[i].parentNode.parentNode.firstChild.firstChild != null
			   && tinp[i].parentNode.parentNode.firstChild.firstChild.getAttribute("type", "false") == "checkbox") {
				result.push(tinp[i]);
			}
		} catch (e) {
		}
	}
	return result;
}

function does_admin_panel_hold_change_option() {
	var tmp = get_admin_panels_action_options()
	tmp = sieve_by(tmp, "type", "radio");
	tmp = sieve_by(tmp, "name", "action");
	tmp = sieve_by(tmp, "value", "change");
	return tmp.length > 0;
}

/******************************************************************************
 * initialization
 ******************************************************************************/
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
	/* inputs whose visible neighbours are checkboxes */
	var tinp = get_inputs_with_nearby_checkboxes(document);
	for (var i = 0; i < tinp.length; i++) {
		tinp[i].check_corresponding_box = check_corresponding_box;
		XBrowserAddHandler(tinp[i],"change","check_corresponding_box");
	}

	if(does_admin_panel_exists()) {
		if(does_admin_panel_hold_change_option()) {
			hide_all_checkboxes_in_admin_panel(null);
			var tmp = get_admin_panels_action_options();
			for (var i = 0; i < tmp.length; i++) {
				if(tmp[i].getAttribute("value", "false") == "change") {
					tmp[i].show_action = show_all_checkboxes_in_admin_panel;
				} else {
					tmp[i].show_action = hide_all_checkboxes_in_admin_panel;
				}
				XBrowserAddHandler(tmp[i], "click", "show_action");
			}
		}

		var tmp = get_admin_panels_action_options();
		for (var i = 0; i < tmp.length; i++) {
			tmp[i].req_fields_action = show_only_necessary_fields;
			XBrowserAddHandler(tmp[i], "click", "req_fields_action");
		}
	}
}
