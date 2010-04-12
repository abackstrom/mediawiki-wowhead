<?php

//
// wowhead.php -- convert <wowhead>Item Name</wowhead> to a colored wowhead link
//
// Version 1.0 (30 March 2010)
//

// 
// The MIT License
// 
// Copyright (c) 2010 Adam Backstrom <adam@sixohthree.com>
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
// 

$wgHooks['ParserFirstCallInit'][] = 'whParserInit';
$wgHooks['BeforePageDisplay'][] = 'whPageDisplay';
$wgHooks['SkinTemplateSetupPageCss'][] = 'whSetupPageCss';

/**
 * Look for <wowhead>Item Name</wowhead> tags.
 */
function whParserInit( &$parser ) {
	$parser->setHook('wowhead', 'whRender');
	return true;
}//end whParserInit

/**
 * For each <wowhead> tag, link back to wowhead.com and add a "quality" class to the link.
 */
function whRender( $input, $args, $parser, $frame ) {
	global $wgMemc;

	$input_name = $input;
	$input_id = null;

	$key = wfMemcKey( 'wh', $input_name );
	$item = $wgMemc->get( $key );

	// we didn't have this cached by name; try to cache by the name + rel
	if( !is_array($item) && isset($args['rel']) ) {
		parse_str($args['rel'], $rel);

		if( $rel['item'] ) {
			$input_id = $rel['item'];

			$key = wfMemcKey( 'wh', $input_id );
			$item = $wgMemc->get( $key );
		}
	}

	// additional parameters (enchants, random enchants, gems, etc.) per http://static.wowhead.com/widgets/power/demo.html
	if( isset($args['rel']) ) {
		$rel = sprintf(' rel="%s"', $args['rel']);
	} else {
		$rel = '';
	}

	// if we didn't get it from cache (by name or id) try to fetch item by name
	if( !is_array($item) ) {
		$item = whFetch( $input_name );
	}

	// couldn't find by name, try by id
	if( !is_array($item) && $input_id ) {
		$item = whFetch( $input_id );
	}

	// still nothing, bail.
	if( !is_array($item) ) {
		return "[$input_name]";
	}

	// use the input name here, in case there was an item id override in rel (prevents "Dark Band of Agility" from becoming "Dark Band")
	$output = sprintf('<a href="%s" class="q%d"%s>[%s]</a>', $item['link'], $item['quality'], $rel, $input_name);

	return $output;
}//end whRender

/**
 * Fetch by identifier, which could be a name or id
 */
function whFetch( $identifier ) {
	global $wgMemc;

	$url = sprintf('http://www.wowhead.com/?item=%s&xml', urlencode($identifier));

	$raw_xml = Http::get($url);
	$xml = simplexml_load_string($raw_xml);

	if( (string)$xml->error == 'Item not found!' ) {
		return false;
	}

	$item = array(
		'name' => (string)$xml->item->name,
		'link' => (string)$xml->item->link,
		'quality' => (string)$xml->item->quality['id']
	);

	$key = wfMemcKey( 'wh', $identifier );

	$wgMemc->set( $key, $item );

	return $item;
}//end whFetch

/**
 * Hook into page display to add the Wowhead JavaScript file.
 */
function whPageDisplay( &$out, &$sk ) {
	$out->addScript('<script src="http://static.wowhead.com/widgets/power.js"></script>');
	return true;
}//end whPageDisplay

/**
 * Add some styles to the page header to remove a flash of unstyled links before power.js loads.
 */
function whSetupPageCss( &$out ) {
	$out .= '<style type="text/css">
/* copied from http://static.wowhead.com/css/basic.css?699 */
.q{color:#ffd100!important;}.q0,.q0 a{color:#9d9d9d!important;}.q1,.q1 a{color:#fff!important;}.q2,.q2 a{color:#1eff00!important;}.q3,.q3 a{color:#0070dd!important;}.q4,.q4 a{color:#a335ee!important;}.q5,.q5 a{color:#ff8000!important;}.q6,.q6 a{color:#e5cc80!important;}.q7,.q7 a{color:#e5cc80!important;}.q8,.q8 a{color:#ffff98!important;}.q9,.q9 a{color:#71d5ff!important;}.q10,.q10 a{color:#f00!important;}

html body .q1 { color: #000 !important; }
.wowhead-tooltip table { background-color: transparent; }
.wowhead-tooltip .q1 { color: #fff !important; }';

	return true;
}//end whSetupPageCss
