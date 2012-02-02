<?php	##################
	#
	#	rah_textile_bar-plugin for Textpattern
	#	version 0.5
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') {
		register_callback('rah_textile_bar','admin_side','head_end');
		register_callback('rah_textile_bar_head','admin_side','head_end');
		add_privs('rah_textile_bar', '1,2');
		register_tab("extensions", "rah_textile_bar", "Textilebar");
		register_callback("rah_textile_bar_page", "rah_textile_bar");
		rah_textile_bar_install();
	} else {
		if(gps('rah_textile_bar_img'))
			rah_textile_bar_img();
		if(gps('rah_textile_bar_js'))
			rah_textile_bar_js();
		if(gps('rah_textile_bar_css'))
			rah_textile_bar_css();
	}

/**
	Adds CSS styles to the <head> for the panel
*/

	function rah_textile_bar_head() {
		global $event;
		
		if($event != 'rah_textile_bar')
			return;
		
		echo
			<<<EOF
				<style type="text/css">
					#rah_textile_bar_container {
						width: 950px;
						margin: 0 auto;
					}
					.rah_textile_bar_items label {
						float: left;
						width: 150px;
					}
					.rah_textile_bar_clear {
						clear: both;
						height: 15px;
					}
				</style>
EOF;
	}

/**
	Installer
*/

	function rah_textile_bar_install() {
		safe_query(
			"CREATE TABLE IF NOT EXISTS ".safe_pfx('rah_textile_bar')." (
				`name` VARCHAR(255) NOT NULL default '',
				`value` LONGTEXT NOT NULL,
				PRIMARY KEY(`name`)
			)"
		);
		if(safe_count('rah_textile_bar', "name='disable'") == 0)
			safe_insert("rah_textile_bar","name='disable', value=''");
		if(safe_count('rah_textile_bar', "name='fields'") == 0)
			safe_insert("rah_textile_bar","name='fields', value='excerpt,body'");
	}

/**
	Delivers the panels
*/
	
	function rah_textile_bar_page() {
		global $step;
		require_privs('rah_textile_bar');
		if($step == 'rah_textile_bar_save')
			rah_textile_bar_save();
		else
			rah_textile_bar_edit();
	}

/**
	Saves the preferences
*/

	function rah_textile_bar_save() {
		rah_textile_bar_update('not','disable');
		rah_textile_bar_update('fields','fields');
		rah_textile_bar_edit('Preferences saved');
	}

/**
	Runs update query
*/

	function rah_textile_bar_update($value='',$field='') {
		$value = ps($value);
		if(is_array($value))
			$value = implode(',',$value);
		$value = doSlash($value);
		safe_update(
			'rah_textile_bar',
			"value='$value'","name='$field'"
		);
	}

/**
	The preferences panel
*/

	function rah_textile_bar_edit($message='') {
		pagetop('rah_textile_bar',$message);
		
		$rs = safe_rows('value,name','rah_textile_bar','1=1');
		foreach($rs as $row)
			$a[$row['name']] = $row['value'];
		
		$not = explode(',',$a['disable']);
		$field = explode(',',$a['fields']);
		
		$items = array(
			'#textilebar .link',
			'#textilebar .strong',
			'#textilebar .emphasis',
			'#textilebar .ins',
			'#textilebar .del',
			'#textilebar .h1',
			'#textilebar .h2',
			'#textilebar .h3',
			'#textilebar .h4',
			'#textilebar .h5',
			'#textilebar .h6',
			'#textilebar .image',
			'#textilebar .codeline',
			'#textilebar .ul',
			'#textilebar .ol',
			'#textilebar .sup',
			'#textilebar .sub',
			'#textilebar .bq',
			'#textilebar .bc',
			'#textilebar .output_form',
			'#textilebar .acronym'
		);
		$out[] = 
			n.
			'	<form method="post" action="index.php" id="rah_textile_bar_container">'.n.
			'		<h1><strong>rah_textile_bar</strong> | Simple Textile Inserting Bar</h1>'.n.
			'		<p>&#187; <a href="?event=plugin&amp;step=plugin_help&amp;name=rah_textile_bar">Documentation</a></p>'.n.
			
			'		<p><strong>Add textile bar to</strong></p>'.n.
			'			<p>'.n.
			'				<label><input type="checkbox" name="fields[]" value="excerpt"'.((in_array('excerpt',$field)) ? ' checked="checked"' : '').' /> Excerpt</label>'.n.
			'				<label><input type="checkbox" name="fields[]" value="body"'.((in_array('body',$field)) ? ' checked="checked"' : '').' /> Body</label>'.n.
			'			</p>'.n.
			
			'		<p><strong>Disable and remove items from the Textile bar.</strong> Check a item to disable it.</p>'.n.
			'		<p class="rah_textile_bar_items">'.n;

		foreach($items as $item) 
			$out[] = 
				'		<label>'.n.
				'			<input type="checkbox" id="'.str_replace('#textilebar .','',$item).'" value="'.$item.'" name="not[]"'.((in_array($item,$not)) ? ' checked="checked"' : '').' />'.n.
				'			'.str_replace('#textilebar .','',$item).n.
				'		</label>'.n;
		
		$out[] = 
			'		</p>'.n.
			'		<div class="rah_textile_bar_clear"></div>'.n.
			'		<p><input type="submit" value="Save settings" class="publish" /></p>'.n.
			'		<input type="hidden" name="event" value="rah_textile_bar" />'.n.
			'		<input type="hidden" name="step" value="rah_textile_bar_save" />'.n.
			'	</form>'.n;
		
		echo implode('',$out);
	}

/**
	Adds the required scripts to Write panel's head
*/

	function rah_textile_bar() {
		global $event;
		if($event == 'article')
			echo n.
				'	<script type="text/javascript" src="'.hu.'?rah_textile_bar_js=1"></script>'.n.
				'	<link href="'.hu.'?rah_textile_bar_css=1" rel="Stylesheet" type="text/css" />'.n.n;
	}

/**
	Generates a button
*/

	function rah_textile_bar_button ($class='',$name='',$start='',$close='',$not=array()){
		if(!in_array('#textilebar .'.$class,$not))
			return '	theButtons[theButtons.length] = new edButton('."'".$class."','".$name."','".$start."','".$close."');".n;
	}

/**
	Hooks the JavaScript to certain elements
*/
	
	function rah_textile_bar_load_fields() {
		$out = array();
		$fields = fetch('value','rah_textile_bar','name','fields');
		
		if(empty($fields))
			return;
		
		$fields = explode(',',$fields);
		foreach($fields as $key => $field) 
			$out[] = 
				'		$("textarea#'.$field.'").before(\'<div class="rah_textile_bar" id="rah_textile_bar_'.$key.'"></div>\');'.n.
				'		addEvent(window, "load", function() {initQuicktags("'.$field.'","rah_textile_bar_'.$key.'")});'.n;
		return implode('',$out);
	}

/**
	Delivers the JS
*/

	function rah_textile_bar_js() {
		ob_start();
		ob_end_clean();
		header('Content-type: application/x-javascript');
		$not = @fetch('value','rah_textile_bar','name','disable');
		$not = explode(',',$not);
		$js = 
			base64_decode(
				'CWZ1bmN0aW9uIGFkZEV2ZW50KG9iaiwgZXZUeXBlLCBmbil7DQoJCWlmIChvYmouYWRkRXZlbnRMaXN0ZW5lcil7DQoJCQlvYmouYWRkRXZlbnRMaXN0ZW5lcihldlR5cGUsIGZuLCB0cnVlKTsNCgkJCXJldHVybiB0cnVlOw0KCQl9IGVsc2UgaWYgKG9iai5hdHRhY2hFdmVudCl7DQoJCQl2YXIgciA9IG9iai5hdHRhY2hFdmVudCgib24iK2V2VHlwZSwgZm4pOw0KCQkJcmV0dXJuIHI7DQoJCX0gZWxzZSB7DQoJCQlyZXR1cm4gZmFsc2U7DQoJCX0NCgl9DQoNCglmdW5jdGlvbiBpbml0UXVpY2t0YWdzKGlkZW50aWZpZXIsIHRleHRpbGViYXJpZCkgew0KCQkJdmFyIGdldENhbnZhcyA9IGRvY3VtZW50LmdldEVsZW1lbnRzQnlUYWdOYW1lKCJ0ZXh0YXJlYSIpOw0KCQkJZm9yICh2YXIgaSA9IDA7IGkgPCBnZXRDYW52YXMubGVuZ3RoOyBpKyspIHsNCgkJCQlpZiAoZ2V0Q2FudmFzW2ldLm5hbWUgPT0gaWRlbnRpZmllciAgfHwgZ2V0Q2FudmFzW2ldLmlkID09IGlkZW50aWZpZXIpIHsNCgkJCQkJdmFyIGNhbnZhcyA9IGdldENhbnZhc1tpXTsNCgkJCQl9DQoJCQkJaWYgKGNhbnZhcykgew0KCQkJCQl2YXIgdG9vbGJhciA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKHRleHRpbGViYXJpZCk7IC8vIGhhcmRjb2RlZA0KCQkJCQl0b29sYmFyLnN0eWxlLnZpc2liaWxpdHkgPSAidmlzaWJsZSI7DQoJCQkJCXZhciBlZEJ1dHRvbnMgPSBuZXcgQXJyYXkoKTsNCgkJCQkJZWRCdXR0b25zID0gdGhlQnV0dG9uczsNCgkJCQkJZm9yICh2YXIgaSA9IDA7IGkgPCBlZEJ1dHRvbnMubGVuZ3RoOyBpKyspIHsNCgkJCQkJCXZhciB0aGlzQnV0dG9uID0gZWRTaG93QnV0dG9uKGVkQnV0dG9uc1tpXSwgY2FudmFzKTsNCgkJCQkJCXRvb2xiYXIuYXBwZW5kQ2hpbGQodGhpc0J1dHRvbik7DQoJCQkJCX0NCgkJCQl9DQoJCQl9DQoJfQ0KDQoJZnVuY3Rpb24gZWRTaG93QnV0dG9uKGJ1dHRvbiwgZWRDYW52YXMpIHsNCgkJdmFyIHRoZUJ1dHRvbiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoImRpdiIpOw0KCQl0aGVCdXR0b24uaWQgPSBidXR0b24uaWQ7DQoJCXRoZUJ1dHRvbi50aXRsZSA9IGJ1dHRvbi5pZDsNCgkJdGhlQnV0dG9uLmNsYXNzTmFtZSA9ICd0ZXh0aWxlYnV0dG9uJzsNCgkJdGhlQnV0dG9uLmNsYXNzTmFtZSArPSAnICcgKyBidXR0b24uaWQ7DQoJCXRoZUJ1dHRvbi50YWdTdGFydCA9IGJ1dHRvbi50YWdTdGFydDsNCgkJdGhlQnV0dG9uLnRhZ0VuZCA9IGJ1dHRvbi50YWdFbmQ7DQoJCXRoZUJ1dHRvbi5vcGVuID0gYnV0dG9uLm9wZW47DQoJCWlmIChidXR0b24uaWQgPT0gJ2ltYWdlJykgew0KCQkJdGhlQnV0dG9uLm9uY2xpY2sgPSBmdW5jdGlvbigpIHsgZWRJbnNlcnRJbWFnZShlZENhbnZhcyk7IH0NCgkJfSBlbHNlIGlmIChidXR0b24uaWQgPT0gJ2xpbmsnKSB7DQoJCQl0aGVCdXR0b24ub25jbGljayA9IGZ1bmN0aW9uKCkgeyBlZEluc2VydExpbmsoZWRDYW52YXMpO30NCgkJfSBlbHNlIGlmIChidXR0b24uaWQgPT0gJ291dHB1dF9mb3JtJykgew0KCQkJdGhlQnV0dG9uLm9uY2xpY2sgPSBmdW5jdGlvbigpIHsgZWRJbnNlcnRGb3JtKGVkQ2FudmFzKTt9DQoJCX0gZWxzZSBpZiAoYnV0dG9uLmlkID09ICdhY3JvbnltJykgew0KCQkJdGhlQnV0dG9uLm9uY2xpY2sgPSBmdW5jdGlvbigpIHsgZWRJbnNlcnRBY3JvbnltKGVkQ2FudmFzKTt9DQoJCX0gZWxzZSB7DQoJCQl0aGVCdXR0b24ub25jbGljayA9IGZ1bmN0aW9uKCkgeyBlZEluc2VydFRhZyhlZENhbnZhcyx0aGlzKTsgfQ0KCQl9DQoJCXRoZUJ1dHRvbi5pbm5lckhUTUwgPSAoYnV0dG9uLmRpc3BsYXkpICsgIiI7DQoJCXJldHVybiB0aGVCdXR0b247DQoJfQ0KDQoJZnVuY3Rpb24gZWRBZGRUYWcoYnV0dG9uKSB7DQoJCWlmIChidXR0b24udGFnRW5kICE9ICcnKSB7DQoJCQllZE9wZW5UYWdzW2VkT3BlblRhZ3MubGVuZ3RoXSA9IGJ1dHRvbjsNCgkJCWJ1dHRvbi5pbm5lckhUTUwgPSAnLycgKyBidXR0b24uaW5uZXJIVE1MOw0KCQkJYnV0dG9uLmNsYXNzTmFtZSA9IGJ1dHRvbi5jbGFzc05hbWUucmVwbGFjZSgidGV4dGlsZWJ1dHRvbiIsICJhY3RpdmUiKTsNCgkJfQ0KCX0NCg0KCWZ1bmN0aW9uIGVkUmVtb3ZlVGFnKGJ1dHRvbikgew0KCQlmb3IgKGkgPSAwOyBpIDwgZWRPcGVuVGFncy5sZW5ndGg7IGkrKykgew0KCQkJaWYgKGVkT3BlblRhZ3NbaV0gPT0gYnV0dG9uKSB7DQoJCQkJZWRPcGVuVGFncy5zcGxpY2UoYnV0dG9uLCAxKTsNCgkJCQlidXR0b24uaW5uZXJIVE1MID0gYnV0dG9uLmlubmVySFRNTC5yZXBsYWNlKCcvJywgJycpOw0KCQkJCWJ1dHRvbi5jbGFzc05hbWUgPSBidXR0b24uY2xhc3NOYW1lLnJlcGxhY2UoImFjdGl2ZSIsICJ0ZXh0aWxlYnV0dG9uIik7DQoJCQl9DQoJCX0NCgl9DQoNCglmdW5jdGlvbiBlZENoZWNrT3BlblRhZ3MoYnV0dG9uKSB7DQoJCXZhciB0YWcgPSAwOw0KCQlmb3IgKGkgPSAwOyBpIDwgZWRPcGVuVGFncy5sZW5ndGg7IGkrKykgew0KCQkJaWYgKGVkT3BlblRhZ3NbaV0gPT0gYnV0dG9uKSB7DQoJCQkJdGFnKys7DQoJCQl9DQoJCX0NCgkJaWYgKHRhZyA+IDApIHsNCgkJCXJldHVybiB0cnVlOw0KCQl9IGVsc2Ugew0KCQkJcmV0dXJuIGZhbHNlOw0KCQl9DQoJfQ0KDQoJZnVuY3Rpb24gZWRDbG9zZUFsbFRhZ3MoZWRDYW52YXMpIHsNCgkJdmFyIGNvdW50ID0gZWRPcGVuVGFncy5sZW5ndGg7DQoJCWZvciAobyA9IDA7IG8gPCBjb3VudDsgbysrKSB7DQoJCQllZEluc2VydFRhZyhlZENhbnZhcywgZWRPcGVuVGFnc1tlZE9wZW5UYWdzLmxlbmd0aCAtIDFdKTsNCgkJfQ0KCX0NCg0KCWZ1bmN0aW9uIGVkSW5zZXJ0VGFnKG15RmllbGQsIGJ1dHRvbikgew0KCQlpZiAoZG9jdW1lbnQuc2VsZWN0aW9uKSB7DQoJCQlteUZpZWxkLmZvY3VzKCk7DQoJCQlzZWwgPSBkb2N1bWVudC5zZWxlY3Rpb24uY3JlYXRlUmFuZ2UoKTsNCgkJCWlmIChzZWwudGV4dC5sZW5ndGggPiAwKSB7DQoJCQkJc2VsLnRleHQgPSBidXR0b24udGFnU3RhcnQgKyBzZWwudGV4dCArIGJ1dHRvbi50YWdFbmQ7DQoJCQl9IGVsc2Ugew0KCQkJCWlmICghZWRDaGVja09wZW5UYWdzKGJ1dHRvbikgfHwgYnV0dG9uLnRhZ0VuZCA9PSAnJykgew0KCQkJCQlzZWwudGV4dCA9IGJ1dHRvbi50YWdTdGFydDsNCgkJCQkJZWRBZGRUYWcoYnV0dG9uKTsNCgkJCQl9IGVsc2Ugew0KCQkJCQlzZWwudGV4dCA9IGJ1dHRvbi50YWdFbmQ7DQoJCQkJCWVkUmVtb3ZlVGFnKGJ1dHRvbik7DQoJCQkJfQ0KCQkJfQ0KCQkJbXlGaWVsZC5mb2N1cygpOw0KCQl9IGVsc2UgaWYgKG15RmllbGQuc2VsZWN0aW9uU3RhcnQgfHwgbXlGaWVsZC5zZWxlY3Rpb25TdGFydCA9PSAnMCcpIHsNCgkJCXZhciBzdGFydFBvcyA9IG15RmllbGQuc2VsZWN0aW9uU3RhcnQ7DQoJCQl2YXIgZW5kUG9zID0gbXlGaWVsZC5zZWxlY3Rpb25FbmQ7DQoJCQl2YXIgY3Vyc29yUG9zID0gZW5kUG9zOw0KCQkJdmFyIHNjcm9sbFRvcCA9IG15RmllbGQuc2Nyb2xsVG9wOw0KCQkJaWYgKHN0YXJ0UG9zICE9IGVuZFBvcykgew0KCQkJCW15RmllbGQudmFsdWUgPSBteUZpZWxkLnZhbHVlLnN1YnN0cmluZygwLCBzdGFydFBvcykgKyBidXR0b24udGFnU3RhcnQgKyBteUZpZWxkLnZhbHVlLnN1YnN0cmluZyhzdGFydFBvcywgZW5kUG9zKSArIGJ1dHRvbi50YWdFbmQgKyBteUZpZWxkLnZhbHVlLnN1YnN0cmluZyhlbmRQb3MsIG15RmllbGQudmFsdWUubGVuZ3RoKTsNCgkJCQljdXJzb3JQb3MgKz0gYnV0dG9uLnRhZ1N0YXJ0Lmxlbmd0aCArIGJ1dHRvbi50YWdFbmQubGVuZ3RoOw0KCQkJfWVsc2Ugew0KCQkJCWlmICghZWRDaGVja09wZW5UYWdzKGJ1dHRvbikgfHwgYnV0dG9uLnRhZ0VuZCA9PSAnJykgew0KCQkJCQlteUZpZWxkLnZhbHVlID0gbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoMCwgc3RhcnRQb3MpICsgYnV0dG9uLnRhZ1N0YXJ0ICsgbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoZW5kUG9zLCBteUZpZWxkLnZhbHVlLmxlbmd0aCk7DQoJCQkJCWVkQWRkVGFnKGJ1dHRvbik7DQoJCQkJCWN1cnNvclBvcyA9IHN0YXJ0UG9zICsgYnV0dG9uLnRhZ1N0YXJ0Lmxlbmd0aDsNCgkJCQl9IGVsc2Ugew0KCQkJCQlteUZpZWxkLnZhbHVlID0gbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoMCwgc3RhcnRQb3MpKyBidXR0b24udGFnRW5kICsgbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoZW5kUG9zLCBteUZpZWxkLnZhbHVlLmxlbmd0aCk7DQoJCQkJCWVkUmVtb3ZlVGFnKGJ1dHRvbik7DQoJCQkJCWN1cnNvclBvcyA9IHN0YXJ0UG9zICsgYnV0dG9uLnRhZ0VuZC5sZW5ndGg7DQoJCQkJfQ0KCQkJfQ0KCQkJbXlGaWVsZC5mb2N1cygpOw0KCQkJbXlGaWVsZC5zZWxlY3Rpb25TdGFydCA9IGN1cnNvclBvczsNCgkJCW15RmllbGQuc2VsZWN0aW9uRW5kID0gY3Vyc29yUG9zOw0KCQkJbXlGaWVsZC5zY3JvbGxUb3AgPSBzY3JvbGxUb3A7DQoJCX0gZWxzZSB7DQoJCQlpZiAoIWVkQ2hlY2tPcGVuVGFncyhidXR0b24pIHx8IGJ1dHRvbi50YWdFbmQgPT0gJycpIHsNCgkJCQlteUZpZWxkLnZhbHVlICs9IGJ1dHRvbi50YWdTdGFydDsNCgkJCQllZEFkZFRhZyhidXR0b24pOw0KCQkJfSBlbHNlIHsNCgkJCQlteUZpZWxkLnZhbHVlICs9IGJ1dHRvbi50YWdFbmQ7DQoJCQkJZWRSZW1vdmVUYWcoYnV0dG9uKTsNCgkJCX0NCgkJCW15RmllbGQuZm9jdXMoKTsNCgkJfQ0KCX0NCg0KCWZ1bmN0aW9uIGVkSW5zZXJ0Q29udGVudChteUZpZWxkLCBteVZhbHVlKSB7DQoJCWlmIChkb2N1bWVudC5zZWxlY3Rpb24pIHsNCgkJCW15RmllbGQuZm9jdXMoKTsNCgkJCXNlbCA9IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpOw0KCQkJc2VsLnRleHQgPSBteVZhbHVlOw0KCQkJbXlGaWVsZC5mb2N1cygpOw0KCQl9DQoJCWVsc2UgaWYgKG15RmllbGQuc2VsZWN0aW9uU3RhcnQgfHwgbXlGaWVsZC5zZWxlY3Rpb25TdGFydCA9PSAnMCcpIHsNCgkJCXZhciBzdGFydFBvcyA9IG15RmllbGQuc2VsZWN0aW9uU3RhcnQ7DQoJCQl2YXIgZW5kUG9zID0gbXlGaWVsZC5zZWxlY3Rpb25FbmQ7DQoJCQlteUZpZWxkLnZhbHVlID0gbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoMCwgc3RhcnRQb3MpICsgDQoJCQlteVZhbHVlICsgbXlGaWVsZC52YWx1ZS5zdWJzdHJpbmcoZW5kUG9zLCANCgkJCW15RmllbGQudmFsdWUubGVuZ3RoKTsNCgkJCW15RmllbGQuZm9jdXMoKTsNCgkJCW15RmllbGQuc2VsZWN0aW9uU3RhcnQgPSBzdGFydFBvcyArIG15VmFsdWUubGVuZ3RoOw0KCQkJbXlGaWVsZC5zZWxlY3Rpb25FbmQgPSBzdGFydFBvcyArIG15VmFsdWUubGVuZ3RoOw0KCQl9IGVsc2Ugew0KCQkJbXlGaWVsZC52YWx1ZSArPSBteVZhbHVlOw0KCQkJbXlGaWVsZC5mb2N1cygpOw0KCQl9DQoJfQ0KDQoJZnVuY3Rpb24gZWRJbnNlcnRMaW5rKG15RmllbGQpIHsNCgkJdmFyIG15VmFsdWUgPSBwcm9tcHQoJ1VSTDonLCAnaHR0cDovLycpOw0KCQl2YXIgbXlUZXh0ID0gcHJvbXB0KCdUZXh0OicsICcnKTsNCgkJdmFyIG15VGl0bGUgPSBwcm9tcHQoJ1RpdGxlOicsICcnKTsNCgkJdmFyIG15UmVsID0gcHJvbXB0KCdSZWw6JywgJycpOw0KCQl2YXIgbXlWYWx1ZTIgPSBteVZhbHVlOw0KCQlpZiAobXlWYWx1ZSkgew0KCQkJaWYobXlSZWwpIHsNCgkJCQlteVZhbHVlID0gJzxhIHJlbD0iJyArIG15UmVsICsgJyIgaHJlZj0iJyArIG15VmFsdWUyICsgJyInOw0KCQkJCWlmKG15VGl0bGUpIHsNCgkJCQkJbXlWYWx1ZSArPSAnIHRpdGxlPSInICsgbXlUaXRsZSArICciJzsNCgkJCQl9DQoJCQkJbXlWYWx1ZSArPSAnPicgKyBteVRleHQgKyAnPC9hPic7DQoJCQl9IGVsc2Ugew0KCQkJCW15VmFsdWUgPSAnIicgKyBteVRleHQ7DQoJCQkJaWYobXlUaXRsZSkgew0KCQkJCQlteVZhbHVlICs9ICcoJyArIG15VGl0bGUgKyAnKSc7DQoJCQkJfQ0KCQkJCW15VmFsdWUgKz0gJyI6JyArIG15VmFsdWUyICsgJyAnOw0KCQkJfQ0KCQkJZWRJbnNlcnRDb250ZW50KG15RmllbGQsIG15VmFsdWUpOw0KCQl9DQoJfQ0KDQoJZnVuY3Rpb24gZWRJbnNlcnRBY3JvbnltKG15RmllbGQpIHsNCgkJdmFyIG15VmFsdWUgPSBwcm9tcHQoJ0Fjcm9ueW06JywgJycpOw0KCQl2YXIgbXlUaXRsZSA9IHByb21wdCgnQ29tZXMgZnJvbTonLCAnJyk7DQoJCXZhciBteUxhbmd1YWdlID0gcHJvbXB0KCdMYW5ndWFnZTonLCAnJyk7DQoJCXZhciBteVZhbHVlMiA9IG15VmFsdWU7DQoJCWlmIChteVZhbHVlKSB7DQoJCQlteVZhbHVlID0gJzxhY3JvbnltJzsNCgkJCWlmKG15VGl0bGUpIHsNCgkJCQlteVZhbHVlICs9ICcgdGl0bGU9IicgKyBteVRpdGxlICsgJyInOw0KCQkJfQ0KCQkJaWYobXlMYW5ndWFnZSkgew0KCQkJCW15VmFsdWUgKz0gJyBsYW5nPSInICsgbXlMYW5ndWFnZSArICciJzsNCgkJCX0NCgkJCW15VmFsdWUgKz0gJz4nICsgbXlWYWx1ZTIgKyAnPC9hY3JvbnltPic7DQoJCQllZEluc2VydENvbnRlbnQobXlGaWVsZCwgbXlWYWx1ZSk7DQoJCX0NCgl9DQoNCglmdW5jdGlvbiBlZEluc2VydEZvcm0obXlGaWVsZCkgew0KCQl2YXIgbXlWYWx1ZSA9IHByb21wdCgnRm9ybTonLCAnJyk7DQoJCWlmIChteVZhbHVlKSB7DQoJCQlteVZhbHVlID0gJzx0eHA6b3V0cHV0X2Zvcm0gZm9ybT0iJyArIG15VmFsdWUgKyciIC8+JzsNCgkJCWVkSW5zZXJ0Q29udGVudChteUZpZWxkLCBteVZhbHVlKTsNCgkJfQ0KCX0NCg0KCWZ1bmN0aW9uIGVkSW5zZXJ0SW1hZ2UobXlGaWVsZCkgew0KCQl2YXIgbXlWYWx1ZSA9IHByb21wdCgnVVJMOicsICcvaW1hZ2VzLycpOw0KCQl2YXIgbXlUaXRsZSA9IHByb21wdCgnQWx0OicsICcnKTsNCgkJdmFyIG15U3R5bGUgPSBwcm9tcHQoJ1N0eWxlOicsICcnKTsNCgkJdmFyIG15VmFsdWUyID0gbXlWYWx1ZTsNCgkJaWYgKG15VmFsdWUpIHsNCgkJCW15VmFsdWUgPSAnISc7DQoJCQlpZihteVN0eWxlKSB7DQoJCQkJbXlWYWx1ZSArPSAneycrIG15U3R5bGUgKyd9JzsNCgkJCX0NCgkJCW15VmFsdWUgKz0gbXlWYWx1ZTI7DQoJCQlpZihteVRpdGxlKSB7DQoJCQkJbXlWYWx1ZSArPSAnKCcgKyBteVRpdGxlICsgJyknOw0KCQkJfQ0KCQkJbXlWYWx1ZSArPSAnISc7DQoJCQllZEluc2VydENvbnRlbnQobXlGaWVsZCwgbXlWYWx1ZSk7DQoJCX0NCgl9DQoNCglmdW5jdGlvbiBlZEJ1dHRvbihpZCwgZGlzcGxheSwgdGFnU3RhcnQsIHRhZ0VuZCwgb3Blbikgew0KCQl0aGlzLmlkID0gaWQ7DQoJCXRoaXMuZGlzcGxheSA9IGRpc3BsYXk7DQoJCXRoaXMudGFnU3RhcnQgPSB0YWdTdGFydDsNCgkJdGhpcy50YWdFbmQgPSB0YWdFbmQ7DQoJCXRoaXMub3BlbiA9IG9wZW47DQoJfQ0KDQoJdmFyIHRoZUJ1dHRvbnMgPSBuZXcgQXJyYXkoKTsNCgl2YXIgZWRPcGVuVGFncyA9IG5ldyBBcnJheSgpOw=='
			)
		;
		echo 
			$js.n.
			rah_textile_bar_button('strong','strong','*','*',$not).
			rah_textile_bar_button('link','url','','',$not).
			rah_textile_bar_button('emphasis','em','_','_',$not).
			rah_textile_bar_button('ins','ins','+','+',$not).
			rah_textile_bar_button('del','del','-','-',$not).
			rah_textile_bar_button('h1','h1','h1. ','',$not).
			rah_textile_bar_button('h2','h2','h2. ','',$not).
			rah_textile_bar_button('h3','h3','h3. ','',$not).
			rah_textile_bar_button('h4','h4','h4. ','',$not).
			rah_textile_bar_button('h5','h5','h5. ','',$not).
			rah_textile_bar_button('h6','h6','h6. ','',$not).
			rah_textile_bar_button('image','img','','',$not).
			rah_textile_bar_button('codeline','codeline','@','@',$not).
			rah_textile_bar_button('ul','ul','* ','\n',$not).
			rah_textile_bar_button('ol','ol','# ','\n',$not).
			rah_textile_bar_button('sup','sup','^','^',$not).
			rah_textile_bar_button('sub','sub','~','~',$not).
			rah_textile_bar_button('bq','bq','bq. ','\n\n',$not).
			rah_textile_bar_button('bc','bc','bc. ','\n\n',$not).
			rah_textile_bar_button('acronym','acronym','','',$not).
			rah_textile_bar_button('output_form','output_form','','',$not).
			'	$(document).ready (function() {'.n.
			rah_textile_bar_load_fields().
			'	});';
		exit();
	}

/**
	Delivers the CSS
*/

	function rah_textile_bar_css() {
		ob_start();
		ob_end_clean();
		header('Content-type: text/css');
		echo '
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
				background-image: url("'.hu.'?rah_textile_bar_img=image");
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
			}';
		exit();
	}

/**
	Delivers the image
*/

	function rah_textile_bar_img() {
		ob_start();
		ob_end_clean();
		header('Content-type: image/gif');
		$code = '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';
		echo base64_decode($code);
		exit();
	}