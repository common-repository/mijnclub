function posttabs () {
	var myForm = document.getElementsByName("chooseoptions")[0];
	var myInput = document.createElement("input");
	myInput.setAttribute("type", "hidden");
	myInput.setAttribute("name", "selectedTab");
	var $tabs = jQuery('#tabs').tabs();
	var selected = $tabs.tabs('option', 'selected');
	myInput.setAttribute("value", selected);
	myForm.appendChild(myInput);
	
	myForm.submit();
}

function posttabs2 () {
	var myForm = document.getElementsByName("chooseperiode")[0];
	var myInput = document.createElement("input");
	myInput.setAttribute("type", "hidden");
	myInput.setAttribute("name", "selectedTab");
	var $tabs = jQuery('#tabs').tabs();
	var selected = $tabs.tabs('option', 'selected');
	myInput.setAttribute("value", selected);
	myForm.appendChild(myInput);
	
	myForm.submit();
}

function posttabs3 () {
	var myForm = document.getElementsByName("choosedag")[0];
	var myInput = document.createElement("input");
	myInput.setAttribute("type", "hidden");
	myInput.setAttribute("name", "selectedTab");
	var $tabs = jQuery('#tabs').tabs();
	var selected = $tabs.tabs('option', 'selected');
	myInput.setAttribute("value", selected);
	myForm.appendChild(myInput);
	
	myForm.submit();
}
