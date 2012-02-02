<?php	##################
	#
	#	rah_textile_bar-plugin for Textpattern
	#	version 0.7
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	#	Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
	#	Licensed under GNU Genral Public License version 2
	#	http://www.gnu.org/licenses/gpl-2.0.html
	#
	###################

	if(@txpinterface == 'admin') {
		rah_textile_bar_install();
		rah_textile_bar_img();
		add_privs('plugin_prefs.rah_textile_bar','1,2');
		register_callback('rah_textile_bar_prefs','plugin_prefs.rah_textile_bar');
		register_callback('rah_textile_bar_install','plugin_lifecycle.rah_textile_bar');
		register_callback('rah_textile_bar','admin_side','head_end');
	}

/**
	Installer
	@param $event string Admin-side event.
	@param $step string Admin-side event, plugin-lifecycle step.
*/

	function rah_textile_bar_install($event='',$step='') {
		
		if($step == 'deleted') {
			
			safe_delete(
				'txp_prefs',
				"name like 'rah_textile_bar_%'"
			);
			
			return;
		}
		
		global $prefs, $event, $textarray;
		
		if($event == 'prefs') {
			
			/*
				Generate language strings if
				not existing
			*/
			
			$strings = 
				array(
					'rah_txtbar' => 'Textile Bar',
					'rah_textile_bar_body' => 'Attach to Body field',
					'rah_textile_bar_excerpt' => 'Attach to Excerpt field'
				);
			
			foreach(rah_textile_bar_buttons() as $att)
				$strings['rah_textile_bar_' . $att[0]] = 'Show ' . $att[0];
			
			foreach(
				$strings as $string => $translation
			)
				if(!isset($textarray[$string]))
					$textarray[$string] = $translation;
		}
		
		$version = '0.7';
		
		$current = 
			isset($prefs['rah_textile_bar_version']) ? 
				$prefs['rah_textile_bar_version'] : '';
		
		if($current == $version)
			return;
			
		$default = 
			array(
				'disable' => '',
				'fields' => ''
			);
		
		if(!$current) {
		
			/*
				Run migration and clean-up if older version was
				installed
			*/
		
			@$rs = 
				safe_rows(
					'name, value',
					'rah_textile_bar',
					'1=1'
				);
		
			if(!empty($rs) && is_array($rs)) {
				foreach($rs as $a)
					if(isset($default[$a['name']]))
						$default[$a['name']] = $a['value'];
			
				@safe_query(
					'DROP TABLE IF EXISTS '.safe_pfx('rah_textile_bar')
				);
			}
		}
		
		/*
			Add preference strings
		*/
		
		$disabled = explode(',', $default['disable']);
		$position = 230;
		$values = rah_textile_bar_buttons();
		
		foreach(array('body','excerpt') as $val)
			$values[] = array($val);
		
		foreach($values as $att) {
			
			$val = 
				$att[0] == 'body' || $att[0] == 'excerpt' ? 
					(
						strpos($default['fields'], $att[0]) !== false || !$default['fields'] ?
							1 : 0
					)
				:
					(
						in_array('#textilebar .'.$att[0], $disabled) ? 
							0 : 1
					);
			
			if($att[0] == 'body' || $att[0] == 'excerpt')
				$position = 225;
			
			$name = 'rah_textile_bar_'.$att[0];
			
			if(!isset($prefs[$name])) {
				safe_insert(
					'txp_prefs',
					"prefs_id=1,
					name='".doSlash($name)."',
					val='".doSlash($val)."',
					type=1,
					event='rah_txtbar',
					html='yesnoradio',
					position=".$position
				);
				
				$prefs[$name] = $val;
			}
			
			$position++;
			
		}
		
		set_pref('rah_textile_bar_version',$version,'rah_txtbar',2,'',0);
		$prefs['rah_textile_bar_version'] = $version;
	}

/**
	Lists buttons
	@return mixed Array of buttons.
*/
	
	function rah_textile_bar_buttons() {
		$b[] = array('strong','strong','*','*');
		$b[] = array('link','url','','');
		$b[] = array('emphasis','em','_','_');
		$b[] = array('ins','ins','+','+');
		$b[] = array('del','del','-','-');
		$b[] = array('h1','h1','h1. ','');
		$b[] = array('h2','h2','h2. ','');
		$b[] = array('h3','h3','h3. ','');
		$b[] = array('h4','h4','h4. ','');
		$b[] = array('h5','h5','h5. ','');
		$b[] = array('h6','h6','h6. ','');
		$b[] = array('image','img','','');
		$b[] = array('codeline','codeline','@','@');
		$b[] = array('ul','ul','* ','\\n');
		$b[] = array('ol','ol','# ','\\n');
		$b[] = array('sup','sup','^','^');
		$b[] = array('sub','sub','~','~');
		$b[] = array('bq','bq','bq. ','\\n\\n');
		$b[] = array('bc','bc','bc. ','\\n\\n');
		$b[] = array('acronym','acronym','','');
		$b[] = array('output_form','output_form','','');
		return $b;
	}

/**
	All the required scripts and styles
*/

	function rah_textile_bar() {
		global $event, $prefs;
		
		if($event != 'article')
			return;
		
		$buttons = array();
		
		foreach(rah_textile_bar_buttons()  as $att)
			if(
				isset($prefs['rah_textile_bar_'.$att[0]]) &&
				$prefs['rah_textile_bar_'.$att[0]] == 1
			)
				$buttons[] = 
					'				rah_textile_bar_theButtons[rah_textile_bar_theButtons.length] = new rah_textile_bar_edButton("'.$att[0].'","'.$att[1].'","'.$att[2].'","'.$att[3].'");';
			
		if(!$buttons)
			return;
		
		$buttons = trim(implode(n,$buttons));
		$f = array();
		$fields = array('body','excerpt');
		
		if(
			isset($prefs['rah_textile_bar_additional_fields']) &&
			!empty($prefs['rah_textile_bar_additional_fields'])
		) {
			foreach(explode(',',$prefs['rah_textile_bar_additional_fields']) as $id)
				$fields[] = $id;
		}
		
		foreach($fields as $key => $field) 
			if(
				isset($prefs['rah_textile_bar_'.$field]) &&
				$prefs['rah_textile_bar_'.$field] == 1
			)
				$f[] = <<<EOF
					$('textarea#{$field}').before('<div class="rah_textile_bar" id="rah_textile_bar_{$key}"></div>');
					rah_textile_bar_addEvent(window, 'load',
						function() {
							rah_textile_bar_initQuicktags('{$field}','rah_textile_bar_{$key}');
						}
					);
EOF;


		
		if(!$f)
			return;	
		
		$f = trim(implode('',$f));
		
		echo <<<EOF
			<script type="text/javascript">
				<!--

				/*
					Event listener
				*/

				function rah_textile_bar_addEvent(obj, evType, fn){
					if (obj.addEventListener){
						obj.addEventListener(evType, fn, true);
						return true;
					} else if (obj.attachEvent){
						var r = obj.attachEvent("on"+evType, fn);
						return r;
					} else {
						return false;
					}
				}

				/*
					Init quicktags
				*/

				function rah_textile_bar_initQuicktags(identifier, textilebarid) {
					var getCanvas = document.getElementsByTagName("textarea");
					for(var i = 0; i < getCanvas.length; i++) {
						if(getCanvas[i].name == identifier  || getCanvas[i].id == identifier) {
							var canvas = getCanvas[i];
						}
						if(canvas) {
							var toolbar = document.getElementById(textilebarid);
							toolbar.style.visibility = "visible";
							var edButtons = new Array();
							edButtons = rah_textile_bar_theButtons;
							for (var i = 0; i < edButtons.length; i++) {
								var thisButton = rah_textile_bar_edShowButton(edButtons[i], canvas);
								toolbar.appendChild(thisButton);
							}
						}
					}
				}

				/*
					Spawn a button and it's actions
				*/

				function rah_textile_bar_edShowButton(button, edCanvas) {
					var theButton = document.createElement("div");
					theButton.id = button.id;
					theButton.title = button.id;
					theButton.className = 'textilebutton';
					theButton.className += ' ' + button.id;
					theButton.tagStart = button.tagStart;
					theButton.tagEnd = button.tagEnd;
					theButton.open = button.open;
					if (button.id == 'image') {
						theButton.onclick = function() { rah_textile_bar_edInsertImage(edCanvas); }
					} else if (button.id == 'link') {
						theButton.onclick = function() { rah_textile_bar_edInsertLink(edCanvas);}
					} else if (button.id == 'output_form') {
						theButton.onclick = function() { rah_textile_bar_edInsertForm(edCanvas);}
					} else if (button.id == 'acronym') {
						theButton.onclick = function() { rah_textile_bar_edInsertAcronym(edCanvas);}
					} else {
						theButton.onclick = function() { rah_textile_bar_edInsertTag(edCanvas,this); }
					}
					theButton.innerHTML = (button.display) + "";
					return theButton;
				}

				/*
					Add tag, active button
				*/

				function rah_textile_bar_edAddTag(button) {
					if(button.tagEnd != '') {
						rah_textile_bar_edOpenTags[rah_textile_bar_edOpenTags.length] = button;
						button.innerHTML = '/' + button.innerHTML;
						button.className = button.className.replace("textilebutton", "active");
					}
				}

				/*
					Close tag, remove active state
				*/

				function rah_textile_bar_edRemoveTag(button) {
					for(i = 0; i < rah_textile_bar_edOpenTags.length; i++) {
						if(rah_textile_bar_edOpenTags[i] == button) {
							rah_textile_bar_edOpenTags.splice(button, 1);
							button.innerHTML = button.innerHTML.replace('/', '');
							button.className = button.className.replace("active", "textilebutton");
						}
					}
				}

				/*
					Check for open tags
				*/

				function rah_textile_bar_edCheckOpenTags(button) {
					var tag = 0;
					for(i = 0; i < rah_textile_bar_edOpenTags.length; i++) {
						if(rah_textile_bar_edOpenTags[i] == button) {
							tag++;
						}
					}
					if(tag > 0) {
						return true;
					} else {
						return false;
					}
				}

				/*
					Close all tags
				*/

				function rah_textile_bar_edCloseAllTags(edCanvas) {
					var count = rah_textile_bar_edOpenTags.length;
					for(o = 0; o < count; o++) {
						rah_textile_bar_edInsertTag(edCanvas, rah_textile_bar_edOpenTags[rah_textile_bar_edOpenTags.length - 1]);
					}
				}

				/*
					Insert tag
				*/

				function rah_textile_bar_edInsertTag(myField, button) {
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						if (sel.text.length > 0) {
							sel.text = button.tagStart + sel.text + button.tagEnd;
						} else {
							if (!rah_textile_bar_edCheckOpenTags(button) || button.tagEnd == '') {
								sel.text = button.tagStart;
								rah_textile_bar_edAddTag(button);
							} else {
								sel.text = button.tagEnd;
								rah_textile_bar_edRemoveTag(button);
							}
						}
						myField.focus();
					} else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = endPos;
						var scrollTop = myField.scrollTop;
						if (startPos != endPos) {
							myField.value = myField.value.substring(0, startPos) + button.tagStart + myField.value.substring(startPos, endPos) + button.tagEnd + myField.value.substring(endPos, myField.value.length);
							cursorPos += button.tagStart.length + button.tagEnd.length;
						}else {
							if (!rah_textile_bar_edCheckOpenTags(button) || button.tagEnd == '') {
								myField.value = myField.value.substring(0, startPos) + button.tagStart + myField.value.substring(endPos, myField.value.length);
								rah_textile_bar_edAddTag(button);
								cursorPos = startPos + button.tagStart.length;
							} else {
								myField.value = myField.value.substring(0, startPos)+ button.tagEnd + myField.value.substring(endPos, myField.value.length);
								rah_textile_bar_edRemoveTag(button);
								cursorPos = startPos + button.tagEnd.length;
							}
						}
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
						myField.scrollTop = scrollTop;
					} else {
						if (!rah_textile_bar_edCheckOpenTags(button) || button.tagEnd == '') {
							myField.value += button.tagStart;
							rah_textile_bar_edAddTag(button);
						} else {
							myField.value += button.tagEnd;
							rah_textile_bar_edRemoveTag(button);
						}
						myField.focus();
					}
				}
				
				/*
					Insert content
				*/
			
				function rah_textile_bar_edInsertContent(myField, myValue) {
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = myValue;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						myField.value = myField.value.substring(0, startPos) + 
						myValue + myField.value.substring(endPos, 
						myField.value.length);
						myField.focus();
						myField.selectionStart = startPos + myValue.length;
						myField.selectionEnd = startPos + myValue.length;
					} else {
						myField.value += myValue;
						myField.focus();
					}
				}
				
				/*
					Insert link
				*/
			
				function rah_textile_bar_edInsertLink(myField) {
					var myValue = prompt('URL:', 'http://');
					var myText = prompt('Text:', '');
					var myTitle = prompt('Title:', '');
					var myRel = prompt('Rel:', '');
					var myValue2 = myValue;
					if (myValue) {
						if(myRel) {
							myValue = '<a rel="' + myRel + '" href="' + myValue2 + '"';
							if(myTitle) {
								myValue += ' title="' + myTitle + '"';
							}
							myValue += '>' + myText + '</a>';
						} else {
							myValue = '"' + myText;
							if(myTitle) {
								myValue += '(' + myTitle + ')';
							}
							myValue += '":' + myValue2 + ' ';
						}
						rah_textile_bar_edInsertContent(myField, myValue);
					}
				}
				
				/*
					Insert acronym
				*/
			
				function rah_textile_bar_edInsertAcronym(myField) {
					var myValue = prompt('Acronym:', '');
					var myTitle = prompt('Comes from:', '');
					var myLanguage = prompt('Language:', '');
					var myValue2 = myValue;
					if (myValue) {
						myValue = '<acronym';
						if(myTitle) {
							myValue += ' title="' + myTitle + '"';
						}
						if(myLanguage) {
							myValue += ' lang="' + myLanguage + '"';
						}
						myValue += '>' + myValue2 + '</acronym>';
						rah_textile_bar_edInsertContent(myField, myValue);
					}
				}
				
				/*
					Insert form
				*/
			
				function rah_textile_bar_edInsertForm(myField) {
					var myValue = prompt('Form:', '');
					if (myValue) {
						myValue = '<txp:output_form form="' + myValue +'" />';
						rah_textile_bar_edInsertContent(myField, myValue);
					}
				}
				
				/*
					Insert image
				*/
			
				function rah_textile_bar_edInsertImage(myField) {
					var myValue = prompt('URL:', '/images/');
					var myTitle = prompt('Alt:', '');
					var myStyle = prompt('Style:', '');
					var myValue2 = myValue;
					if (myValue) {
						myValue = '!';
						if(myStyle) {
							myValue += '{'+ myStyle +'}';
						}
						myValue += myValue2;
						if(myTitle) {
							myValue += '(' + myTitle + ')';
						}
						myValue += '!';
						rah_textile_bar_edInsertContent(myField, myValue);
					}
				}
				
				/*
					Adds button
				*/
			
				function rah_textile_bar_edButton(id, display, tagStart, tagEnd, open) {
					this.id = id;
					this.display = display;
					this.tagStart = tagStart;
					this.tagEnd = tagEnd;
					this.open = open;
				}
			
				var rah_textile_bar_theButtons = new Array();
				var rah_textile_bar_edOpenTags = new Array();
				
				{$buttons}
				
				$(document).ready(function() {
					{$f}
				});
				
				-->
			</script>
			<style type="text/css">
				.rah_textile_bar {
					background: #fff;
					border: 1px solid #ccc;
					border-left: 0;
					padding: 0;
					overflow: hidden;
					margin: 0 0 0 0;
				}
				.rah_textile_bar div {
					display: inline;
					width: auto;
					border-left: 1px solid #ccc;
					color: #333;
					height: 23px;
					width: 23px;
					padding: 0;
					cursor: pointer;
					overflow: hidden;
					outline: 0;
					text-indent: -9000px;
					float: left;
					margin: 0 0 0 0;
					background-color: #fff;
					background-image: url("./?rah_textile_bar_img=image.gif");
					background-repeat: no-repeat;
				}
				.rah_textile_bar .strong {
					background-position: 7px -182px;
				}
				.rah_textile_bar .link {
					background-position: 4px -332px;
				}
				.rah_textile_bar .emphasis {
					background-position: 7px -601px;
				}
				.rah_textile_bar .ins {
					background-position: 7px -512px;
				}
				.rah_textile_bar .del {
					background-position: 4px -540px;
				}
				.rah_textile_bar .h1 {
					background-position: 5px -1px;
				}
				.rah_textile_bar .h2 {
					background-position: 5px -32px;
				}
				.rah_textile_bar .h3 {
					background-position: 5px -61px;
				}
				.rah_textile_bar .h4 {
					background-position: 5px -91px;
				}
				.rah_textile_bar .h5 {
					background-position: 5px -121px;
				}
				.rah_textile_bar .h6 {
					background-position: 5px -151px;
				}
				.rah_textile_bar .image {
					background-position: 5px -362px;
				}
				.rah_textile_bar .codeline {
					background-position: 5px -392px;
				}
				.rah_textile_bar .ul{
					background-position: 5px -302px;
				}
				.rah_textile_bar .ol{
					background-position: 5px -271px;
				}
				.rah_textile_bar .sup {
					background-position: 7px -212px;
				}
				.rah_textile_bar .sub {
					background-position: 7px -242px;
				}
				.rah_textile_bar .bq {
					background-position: 5px -452px;
				}
				.rah_textile_bar .bc {
					background-position: 4px -482px;
				}
				.rah_textile_bar .output_form {
					background-position: 5px -572px;
				}
				.rah_textile_bar .acronym {
					background-position: 4px -422px;
				}
				.rah_textile_bar .active {
					background-color: #ffc;
				}
			</style>
EOF;
	}

/**
	The image containing all the buttons graphs
*/

	function rah_textile_bar_img() {
		
		if(gps('rah_textile_bar_img') != 'image.gif')
			return;
		
		ob_start();
		ob_end_clean();
		header('Content-type: image/gif');
		$code = 'R0lGODlhEAB6AocAAAAAAAwMDBQUFBwcHCUlJSsrKzMzMzk5OUREREdHSEtLS01WTFNTU1tbW011S1FiUFdwV1xtdWF+VGNjY2pqamVrdW53aGpxenZ+bnNzc3J0enZ+dXh8dXt7e218i0t811J81zuCNz/Rd0CGPEWDQk+cSFGUTFC8WVepe2eSRWCRWHaCXmOLYWSJdmuZaGejWmCwVnamS3q6T368VWysZWu/YHqoaHeucny4dErVZ13TbGrbdnTGaH7Lc3fWdl+In16RikyArUeCsFmIuF+pr2aYlXeBhmqXpHiqn2e8vH+zoU2K3FSJ3FOZxleS3FmZ4l2nyF6w01ql5mSJ3G2WwGOV3nKawnie1mWa4XWa4WmlwWWl32u2yXyhxn2i2WWn5mm163am43S06m/DgmzRkHPegnHWmm3EtGDYpn/FvXrRr3XJy4RzTYJ9dYaMQoCDVoC+Uoe5aIS9fojBV5DFXY/FY4nEdJjJZpHHeqDNfajSdIKDgoaHioKOgYKIi4qHgouLi42RjJOTk5ubm4u7g5emiJaolIC1poe1t6SkpKqrqqi7p7S0tLq6uoepzIao3I200JaszpWr0Jmy0YWk4oKu5oqs5IK15oOw6IO76Ii054y26Yi454266pS045Sz6JS765m05Zm06J255p2866W41KW96orDhIPYgZLIiZDThJvZk4TLtYrbu43qnZzivaTIiKHLk6XThaXWlbXemKzXpq/VvbTeoLfXvKLim6fvsazwtbvhpbTvtY7ByYvL15DN25jV2YnB6pfE66rC2qjXy6bZ17/CybPG3bTaxbXe1qPC7aLN76zD7KrM7qfW8bLH67XO8b7Q47rV8qnuybrmw7fo1MLbpcLcv83nr83ns9bst9zwvsPDw8DFysrKysfexM7T09TU1NXX2Nzc3MvX5sfZ89Hd88rp0NPtztbt09ryw9zx2cXk9trk79jm+uX0xuTk5Orq6uXt+OXw/uzx9uvx+/Pz9PH1+/X69fT4/fj89/v8/P///wAAAAAAACH5BAMAAP0ALAAAAAAQAHoCAAj/APsJHEiwoMGDCBMqXMiwocOHECMeVAQoULd+3fZ0GIRxT0EKfAZ2UNAvHAUDBSeEFNghgcBBBVJmUJQoUQMELwkUbOBRYIYDAgXpJMigZ7+TQQcUZNBhIIWY/QIpHTioQ4ZG/RhN4KloAoMM5CSKHUu2rNmzaNOqXcu2rVu1FC123EiuA4WLTlf2aymvQaAGBeINVCnyACPB3wrg7TdhZs2bgwvcG8hz4E+WDcYRNYp0Y797ggUydUrgm4EGDAy/tIpVawMKDGJPeEu7tu3buHPr3v0w7sWMdO0uPqp3JGNBgAkSZulScCIGyh3bxBlvAgFBBCv7BNovngICjAYW/3VqYFyi7gMAiW8qkMJ1At3EDcBb9WrWrRz2NA7Pu7///wAGKGCAvmHExx4c9SNPIARRoJ5Ae0D32QSzOfXgXiRhWKFAjSni4QQ47fHNVsoZ9dM3CExggAFGadfPT/d8840CCYhDGXtHoSQQagMl0gEHrVEwQU8aDWjkkUgmqWSSBQI3yCAURIlVe8WRlEGUE0zGYXEuNaBIaINJB5kBBRCAYz8uXtbPOIIMoIh4nJUnyGQZXDhae+9hxQB9rN3XAAcZTNDBlEsWauihiCbqUJMaJRjOHmCCJBJJ91CQgZZbitQlBQZ1+NgB3RSQgQIUYJpmATChl2A/47VXgCBQpf+3nlMFiAPfOPOtZp9rezRCIX+KBivssMT6940gGXTAiCAUDIJpQY1QwGA/gzDA6UGMSNqPIgrghG1l9zSQgFEFMfJaIhQgcO1EDHAA4QEKPNujAhkIdA8BqB6USALrDlCAXgQlckADOQ0A5kCMBMrTSB2EVezDEEd8mz2ddLIMQ6DYswwmDlUSBkGDCCJIIPJoVC8pYbxDkDiBnqcIH/GYYwk+5sxD0DhbKVJvP2FggUUVBiVCo7wH3ZMAAhcmNNMBBgxX0D2AvMmHAQTYeNCVgIzTgAI0hiPx12CHLTbIIpNsMkIsT+AyzAnh7OXOCgmdANEGGY00Q0s33Q/FFhP/BLXUVIuT8TKVEIS11lwn4LXHDaGs8kLmUKJPzQv1/PPYmGc+kMYOLXNx5wJ9k8HoUTbAo0Cef64WKaTQ45A9mzQ0jyYCNbJ1t0yXORAmjK8Vxu8OpezQPB8rUuYAAggQQAAADBRGFp9oLv30EE+zzDQNLRPNxaKTTuHp/ViP/X/TkBJNQ6Bs3I/tXCNwwAG6CxRNJc0A2EwWqisUxibFH588880TiCnCYArqGfCAbWFHLnrAAx70YBXpMAg/csEDXmijGsmwRS1qkIp9DIQfqpAFPNiRDmsYwxjBsEUqcJAPgcxiFdxYhzrQYQxrAOMXvrAFHuTQD3bwgBu92EU1/6wRjGBwgQusWEU2SgCOVdBCG7qgBjpuGIUkmKEMZZAFLG7Qg23kwhXWQMcR17CDHORABzi4hgl+iIodvKIYUEhCK9AgghycgAbZGMEPe0CGV0AhCk3gghrOQAQUvCAbIehBNlTRijT8wA+DMEIQhGCEQfyhECRIBS1uMYYWkIwc3TCCB8gBSkFAQB0w2IYKMhCOCChgAoLgwAQUgIFuNOUUdrDAHsKhBQQ0IhzdUAQCbNCNP/QjHzSQACsjgAAEMIBGCHhAN3a2DxcsIBCKWFYGOCAIRiiCWQRZxAImECUHsOBKQnIYAtdZqHKUYhKRkIQk4hlPeUaiHAIphTzugf+PfNxDH/f45z3qUY9JCGQS9/AEJA4RC1nIIhVF6MIV5mHQfkgiH6FARCz0cIeOEuIIVyioQC4aCiXI4g50mMMc8AAEL9QjEiPFxyiUkIeUwgEOcQDCIygqkEjIFBGpqAMcZDCDGxxhpxWVRD1IMQkkxOGpNojoI14aU1OMAhIrcIMb3uAIS0w1qfWIgVjZ8IdBtCEFKWjDIADRiH0oFRvY6MMnu8GHQIyjlIN4qyE6EI4NkApZFFDABWypT3wAgg/hkEcCfhnMBESCruUgxiQ8QIFwVAABCXjm0TIwzYGQgwLdVMQgMqCBQWQTnJ4lp6UawbIrTUAz7IytbCGiD4f/6EMMYBAIGMRQW4PgQwxi+IJugYuPgkwDDGD4guv2tlspnG8gXxCDFB4nkHdIQQxP4MdAoiGFL2BhufT4wheeAI2C4AMLXwBaP8SLheL6tgpOEMgTsNDb2dr3vorSxzAyMYz9ZsJmCGnHMNrRjkxQ9yDCeEYmhHEJeyTkGdPIxDkYbI7gincLH+vHPITBjHO8gxlhMMcluguGLMSuH5x4hjOGcb1KzGMLUnjCFirBD344wxmXaAY0QkGKSpBCCliYxhMo0Y93XGIY56juI8LwhCeYIxpPyEI/OjEMTXiiH+cgYBWkYIl+QHkKCGlGd5/AhPlOwb0EOQeZsyAKUUwB/wtTOLBAsiAFyQkEGiCIc5pBwYQlQEO786DEB0BQwIEs4dBLmEJtQfCBRn8Av5CONEPwIYkhWPrSlnYEgAUCCWLU+NOflgYVtCuQIfBDH/RIdarf8Q5+dGHTo7bHqunB6lY7Ap8CGbWqac3qdvDDEUkuNT9kXet3tKPVXXDHQBzh6U/Tmh78QIYVSN0PfEzi0pOoMT5KYYVNG6TbpRgCMRTyziFIYyH1oMK5Jc1uYcVDRvCOtzpDtw9Qf/oe3Zj3N/gR0H0E9B7y4Ec8ugGmfe8DoP6WR8D9ne/Q9ZMfB/dnwD/9DYF4A7f82K09AqrwePCj4viI7hcyDtyNc/zjx/9NLrTnEV3n/vvjLT/Hp62L3Xvw++Pc9e48+EEPLIDhCc24+cer7YT08gO9WLBHjW3OD7x4A778mC/T613vivdj3/bOutWxnnVQb73r9rb6u2XUjXiXXUYHa7faMfeNPWQAWMgBFsjg9hm6EwRZBXHXQVBrGYQAYkPtQQgfCKYchERoJwgZhAIW03aEyEMBDUBMAtJOkHh0wCu7RIv/lMe8tXs+tvlQRDcaYUmFKEsgjJDQQbpBgTexpG1bEURYrC742CRgArQfyDQ5oAFATeAAAwCA8AGQtM8MRBEJMECGDMKI8wxkANxh/gEwRYBpGUQRB2CAIBIhiDN9/vvgV8vFPRLxJXkoZPyJGEQi2IqQ8TNiEJqpzkH2kQhvWi0e6xpIPkzLCHHwI2qD9zSDwAiM8A1/AyXPcg8DyAjhsA8dwH0NYH4D4X6NMA780AHfxAASaC+C8CaJsAetJwgMIC/kMCcCIXqCkCUGUTJaEg+BMAEbSBD7EDLxIA4UEIEJkQ/hkAFeQjfh94OxlTANICh40wCEkhDxwACEtxDVUnwIMQEJ4DQHQQ5H0xCCMC4NwQAGcIQIEQ5kwhCDYDoMAHhAWIa3ERAAOw==';
		echo base64_decode($code);
		exit();
	}

/**
	Redirects to the preferences panel
*/

	function rah_textile_bar_prefs() {
		header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_textile_bar_body');
		echo 
			'<p id="message">'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_textile_bar_body">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
?>