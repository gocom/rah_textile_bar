<?php

/**
 * Rah_textile_bar plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_textile_bar
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		rah_textile_bar_install();
		rah_textile_bar_img();
		add_privs('plugin_prefs.rah_textile_bar', '1,2');
		register_callback('rah_textile_bar_prefs', 'plugin_prefs.rah_textile_bar');
		register_callback('rah_textile_bar_install', 'plugin_lifecycle.rah_textile_bar');
		register_callback('rah_textile_bar', 'admin_side', 'head_end');
	}

/**
 * Installer
 * @param string $event Admin-side event.
 * @param string $step Admin-side event, plugin-lifecycle step.
 */

	function rah_textile_bar_install($event='', $step='') {
		
		global $prefs;
		
		if($step == 'deleted') {
			
			safe_delete(
				'txp_prefs',
				"name like 'rah\_textile\_bar\_%'"
			);
			
			return;
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
 * Lists buttons
 * @return array Array of buttons.
 */

	function rah_textile_bar_buttons() {
		$b[] = array('strong','strong','*','*');
		$b[] = array('emphasis','em','_','_');
		$b[] = array('h1','h1','h1. ','');
		$b[] = array('h2','h2','h2. ','');
		$b[] = array('h3','h3','h3. ','');
		$b[] = array('h4','h4','h4. ','');
		$b[] = array('h5','h5','h5. ','');
		$b[] = array('h6','h6','h6. ','');
		$b[] = array('ins','ins','+','+');
		$b[] = array('del','del','-','-');
		$b[] = array('link','url','','');
		$b[] = array('image','img','','');
		$b[] = array('ul','ul','* ','\\n');
		$b[] = array('ol','ol','# ','\\n');
		$b[] = array('sup','sup','^','^');
		$b[] = array('sub','sub','~','~');
		$b[] = array('bq','bq','bq. ','\\n\\n');
		$b[] = array('bc','bc','bc. ','\\n\\n');
		$b[] = array('codeline','codeline','@','@');
		$b[] = array('acronym','acronym','','');
		$b[] = array('output_form','output_form','','');
		return $b;
	}

/**
 * All the required scripts and styles
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
					margin: 0;
					padding: 0;
					overflow: hidden;
					background: #fff;
				}
				.rah_textile_bar div {
					display: inline;
					float: left;
					border: 1px solid #ccc;
					margin: 0;
					padding: 0;
					width: 24px;
					height: 24px;
					color: #333;
					cursor: pointer;
					outline: 0;
					text-indent: 110%;
					white-space: nowrap;
					overflow: hidden;
					background: #fff url("./?rah_textile_bar_img=image.png") no-repeat;
				}
				.rah_textile_bar .strong {
					background-position: 0 0;
				}
				.rah_textile_bar .emphasis {
					background-position: -24px 0;
				}
				.rah_textile_bar .h1 {
					background-position: -48px 0;
				}
				.rah_textile_bar .h2 {
					background-position: -72px 0;
				}
				.rah_textile_bar .h3 {
					background-position: -96px 0;
				}
				.rah_textile_bar .h4 {
					background-position: -120px 0;
				}
				.rah_textile_bar .h5 {
					background-position: -144px 0;
				}
				.rah_textile_bar .h6 {
					background-position: -168px 0;
				}
				.rah_textile_bar .ins {
					background-position: -192px 0;
				}
				.rah_textile_bar .del {
					background-position: -216px 0;
				}
				.rah_textile_bar .link {
					background-position: -240px 0;
				}
				.rah_textile_bar .image {
					background-position: -264px 0;
				}
				.rah_textile_bar .ul {
					background-position: -288px 0;
				}
				.rah_textile_bar .ol {
					background-position: -312px 0;
				}
				.rah_textile_bar .sup {
					background-position: -336px 0;
				}
				.rah_textile_bar .sub {
					background-position: -360px 0;
				}
				.rah_textile_bar .bq {
					background-position: -384px 0;
				}
				.rah_textile_bar .bc {
					background-position: -408px 0;
				}
				.rah_textile_bar .codeline {
					background-position: -432px 0;
				}
				.rah_textile_bar .acronym {
					background-position: -456px 0;
				}
                .rah_textile_bar .output_form {
                    background-position: -480px 0;
				}
				.rah_textile_bar .active {
					background-color: #fff6d3;
				}
			</style>
EOF;
	}

/**
 * The image containing all the buttons glyphs
 */

	function rah_textile_bar_img() {
		
		if(gps('rah_textile_bar_img') != 'image.png')
			return;
		
		ob_start();
		ob_end_clean();
		header('Content-type: image/png');
		$code = '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%3D';
		echo base64_decode($code);
		exit();
	}

/**
 * Redirects to the preferences panel
 */

	function rah_textile_bar_prefs() {
		header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_textile_bar_body');
		echo 
			'<p id="message">'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_textile_bar_body">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
?>