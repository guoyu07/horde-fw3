--TEST--
Text_Filter_text2html tests
--FILE--
<?php

$_COOKIE[session_name()] = true;
require dirname(__FILE__) . '/../Filter.php';
$text = file_get_contents(dirname(__FILE__) . '/fixtures/text2html.txt');
$params = array('class' => null);
$levels = array(TEXT_HTML_PASSTHRU,
                TEXT_HTML_SYNTAX,
                TEXT_HTML_MICRO,
                TEXT_HTML_MICRO_LINKURL,
                TEXT_HTML_NOHTML,
                TEXT_HTML_NOHTML_NOBREAK);

foreach ($levels as $level) {
    $params['parselevel'] = $level;
    echo Text_Filter::filter($text, 'text2html', $params);
    echo "-------------------------------------------------\n";
}

?>
--EXPECT--
http://www.horde.org/foo/
https://jan:secret@www.horde.org/foo/
mailto:jan@example.com
svn+ssh://jan@svn.example.com/path/
<tag>foo</tag>
<http://css.maxdesign.com.au/listamatic/>
http://www.horde.org/?foo=bar&baz=qux
http://www.<alert>.horde.org/
http://www.&#x32;.horde.org/
-------------------------------------------------
<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a><br />
<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a><br />
mailto:<a href="mailto:jan@example.com" title="New Message to jan@example.com">jan@example.com</a><br />
<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a><br />
&lt;tag&gt;foo&lt;/tag&gt;<br />
&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;<br />
<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a><br />
<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/<br />
<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a><br />
-------------------------------------------------
<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a><br />
<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a><br />
mailto:<a href="mailto:jan@example.com" title="New Message to jan@example.com">jan@example.com</a><br />
<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a><br />
&lt;tag&gt;foo&lt;/tag&gt;<br />
&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;<br />
<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a><br />
<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/<br />
<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a><br />
-------------------------------------------------
<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a><br />
<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a><br />
mailto:jan@example.com<br />
<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a><br />
&lt;tag&gt;foo&lt;/tag&gt;<br />
&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;<br />
<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a><br />
<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/<br />
<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a><br />
-------------------------------------------------
http://www.horde.org/foo/<br />
https://jan:secret@www.horde.org/foo/<br />
mailto:jan@example.com<br />
svn+ssh://jan@svn.example.com/path/<br />
&lt;tag&gt;foo&lt;/tag&gt;<br />
&lt;http://css.maxdesign.com.au/listamatic/&gt;<br />
http://www.horde.org/?foo=bar&amp;baz=qux<br />
http://www.&lt;alert&gt;.horde.org/<br />
http://www.&amp;#x32;.horde.org/<br />
-------------------------------------------------
http://www.horde.org/foo/
https://jan:secret@www.horde.org/foo/
mailto:jan@example.com
svn+ssh://jan@svn.example.com/path/
&lt;tag&gt;foo&lt;/tag&gt;
&lt;http://css.maxdesign.com.au/listamatic/&gt;
http://www.horde.org/?foo=bar&amp;baz=qux
http://www.&lt;alert&gt;.horde.org/
http://www.&amp;#x32;.horde.org/
-------------------------------------------------