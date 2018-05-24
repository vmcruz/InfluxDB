document.addEventListener('DOMContentLoaded', function(e) {
	var isEmptyElements = ['description', 'param', 'prop', 'return', 'notice', 'warning', 'examples', 'tutorials', 'sourcecode', 'changelog'];
	for(var i = 0; i < isEmptyElements.length; i++) {
		var e = document.querySelector('[data-parent="' + isEmptyElements[i] + '"]');
		if(e) {
			if(e.innerHTML.trim().length == 0) {
				var parent = document.getElementById(isEmptyElements[i]);
				parent.setAttribute('style', 'display: none;');
			}
		}
	}
	
	var addedElements = 0;
	for(var i = 0; i < navBar.length; i++) {
		var span = document.createElement('span');
		span.classList.add('navBar-element');
		if(navBar[i].length > 0) {
			span.innerText = navBar[i];
			document.querySelector('.nav-bar').appendChild(span);
			addedElements++;
			if(i + 2 < navBar.length) {
				var fa = document.createElement('i');
				fa.setAttribute('class', 'fa fa-ellipsis-v fa-icon-16 fa-margin-sides10');
				document.querySelector('.nav-bar').appendChild(fa);
			}
		}
	}
	
	var s = document.querySelectorAll("i.show");
	for(var i = 0; i < s.length; i++) {
		(function(e) {
			e.addEventListener("click", function() {
				var c = document.querySelectorAll("[data-parent='" + e.getAttribute("data-child") + "']");
				var isShowing = false;
				for(var k = 0; k < c.length; k++) {
					if(!c[k].classList.contains("show-div")) {
						c[k].classList.add("show-div");
						isShowing = true;
					} else
						c[k].classList.remove("show-div");
				}
				if(isShowing && !this.classList.contains("fa-bars"))
					this.classList.add("showing");
				else
					this.classList.remove("showing");
			});
		})(s[i]);
	}
	
	if(addedElements == 0)
		document.querySelector('.nav-bar').setAttribute('style', 'display:none');
	
	writemenu(menuItems);
	
	var search = document.getElementById("search");
	search.addEventListener("keyup", function() {
		var a = new Array();
		if(this.value.length >= 3) {
			for(var i = 0; i < menuItems.length; i++) {
				var data = menuItems[i].split('|');
				var dataItem = data[4].replace('●', '.');
				if(dataItem.toLowerCase().indexOf(this.value.toLowerCase()) > -1)
					a.push(menuItems[i]);
			}
		} else
			a = menuItems;
		
		writemenu(a);
	});
});

function writemenu(menuItems) {
	var currentMethod = document.getElementById("title").getAttribute("data-current");
	var html = createMenu(mergeItems(menuItems), {
		namespace: {
			class: 'fancy-namespace-item'
		},
		list: {
			type: 'ul',
			class: 'fancy-menu-list'
		},
		item: {
			class: 'fancy-{TAGTYPE}-item',
			parentclass: 'fancy-{TAGTYPE}-list',
			currentclass: 'fancy-item-selected',
			currentitem: currentMethod
		}
	});
	
	document.querySelector("#nav-menu").innerHTML = html;
	var menuItemList = document.querySelectorAll("[data-url]");
	for(var i = 0; i < menuItemList.length; i++) {
		menuItemList[i].addEventListener('click', function() {
			location.href = this.getAttribute('data-url');
		});
	}
}

function goto(url) {
	location.href = url;
}

function createMenu(obj, config) {		
	var htmlMenu = '<' + config.list.type + ' class="' + config.list.class + '">';
	
	for(var key in obj) {
		if(obj.hasOwnProperty(key)) {
			if(isObject(obj[key])) {
				var dataInfo = "";
				var parentClass = config.item.parentclass;
				
				if(obj.hasOwnProperty(0)) {
					for(var i = 0; i < obj[0].length; i++) {
						var data = obj[0][i].split('|');
						if(data[0] == key) {
							var selectedClass = (config.item.currentitem == data[1] + '.' + data[2]) ? ' ' + config.item.currentclass : '';
							dataInfo = ' data-url="' + data[1] + '.' + data[2] + '" data-item="' + data[4].replace('●', '.') + '"';
							var exposure = (data[3] && data[3] != '') ? data[3] : 'public';
							parentClass = parentClass.replace("{TAGTYPE}", data[5]) + '-' + exposure;
							i = obj[0].length;
						}
					}
				}
					
				if(obj[key].hasOwnProperty(0) && obj[key][0].length > 0)
					htmlMenu += '<li class="' + parentClass + '"><span class="' + selectedClass + '"' + dataInfo + '>' + key + '</span>' + createMenu(obj[key], config) + '</li>';
				else
					htmlMenu += '<li class="' + config.namespace.class + '"><span class="' + selectedClass + '"' + dataInfo + '>' + key + '</span>' + createMenu(obj[key], config) + '</li>';
			} else {
				for(var i = 0; i < obj[key].length; i++) {
					var data = obj[key][i].split('|');
					if(!obj.hasOwnProperty(data[0])) {
						var exposure = (data[3] && data[3] != '') ? data[3] : 'public';					
						var selectedClass = (config.item.currentitem == data[1] + '.' + data[2]) ? ' ' + config.item.currentclass : '';
						var dataItem = data[4].replace('●', '.');
						
						htmlMenu += '<li class="' + config.item.class.replace('{TAGTYPE}', data[5]) + '-' + exposure + selectedClass + '" data-url="' + data[1] + '.' + data[2] + '" data-item="' + dataItem + '"><span>' + data[0] + '</span>' + "</li>";
					}
				}
			}
		}
	}
	
	return htmlMenu + "</ul>";
}

function mergeItems(items) {
	var objs = [];

	for(var i = 0; i < items.length; i++)
		objs.push(makeObject(items[i]));
		
	var obj = {};
	for(var j = 0; j < objs.length; j++)
		obj = deepMerge(obj, objs[j]);

	return obj;
}

function isObject(e) {
	return e && typeof e == 'object' && !Array.isArray(e);
}

function isArray(e) {
	return e && Array.isArray(e);
}

function deepMerge(target, source) {
	if(isArray(target) && isArray(source)) {
		target = target.concat(source);
	} else if(isArray(target) && isObject(source)) {
		if(!source.hasOwnProperty(0))
			source[0] = [];
		
		source[0] = source[0].concat(target);
		target = source;
	} else if(isArray(source) && isObject(target)) {
		if(!target.hasOwnProperty(0))
			target[0] = [];
		
		target[0] = target[0].concat(source);
	} else if(isObject(source)) {
		for(var key in source) {
			if(source.hasOwnProperty(key)) {
				if(!target.hasOwnProperty(key))
					target[key] = source[key];
				else
					target[key] = deepMerge(target[key], source[key]);
			}
		}
	}
	
	return target;
}

function makeObject(a) {
	var obj = {};
	var data = a.split(".");
	var item = data.shift();
	if(data.length > 1)
		obj[item] = makeObject(data.join("."));
	else if(item == a)
		obj = {0 : [a]};
	else
		obj[item] = { 0 : data };
	
	return obj;
}