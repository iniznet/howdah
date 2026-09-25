<?php
/**
 * The Display screen's guide tab: the declaring feature's own static markup,
 * rendered exactly as shipped. Nothing here is dynamic -- a content section
 * is documentation, not a render path -- and every string is the theme's own,
 * so there is nothing to escape: no user data crosses this file.
 */
?>
<p>The <strong>Footer note</strong> option is a single line of text the theme
renders in the site chrome's colophon, under the footer. When it is empty the
colophon falls back to the site's own description, so the option never leaves
a hole in the page.</p>
<p>Read the value through the field layer's reader, with the option object
reference -- never through <code>get_option()</code> directly, because the
field layer owns the storage and a field's target may move:</p>
<pre><code>$note = $fields-&gt;value('footer_note', \Iniznet\Mahout\Fields\ObjectRef::option());</code></pre>
<p>The theme's site chrome already does this; a child feature that wants the
same line injects its own reader the same way.</p>
