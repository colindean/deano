<p>This is the about page.</p>
<p><?php
echo sprintf('<a href="%s">This should link to root</a>', url_for('root'));
?></p>
<p><a href="error">This should cause a 404</a>.</p>
