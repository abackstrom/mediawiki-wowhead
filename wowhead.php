<?php

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

	$key = wfMemcKey( 'wh', $input );

	$item = $wgMemc->get( $key );

	// used for random enchantments ("of the Boar")
	if( isset($args['rel']) ) {
		$rel = sprintf(' rel="%s"', $args['rel']);
	} else {
		$rel = '';
	}

	if( !is_array($item) ) {
		$url = sprintf('http://www.wowhead.com/?item=%s&xml', urlencode($input));

		$raw_xml = Http::get($url);
		$xml = simplexml_load_string($raw_xml);

		$item = array(
			'name' => (string)$xml->item->name,
			'link' => (string)$xml->item->link,
			'quality' => (string)$xml->item->quality['id']
		);

		$wgMemc->set( $key, $item );
	}

	$output = sprintf('<a href="%s" class="q%d"%s>[%s]</a>', $item['link'], $item['quality'], $rel, $item['name']);

	return $output;
}//end whRender

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
