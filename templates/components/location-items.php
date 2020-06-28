<h3>Items</h3>
<ul>
    <?php
        // {% for item in items %}
        foreach($templateData['items'] as $item) {
    ?>
        <li>
        <a href="<?php echo esc_url( add_query_arg( 'item', $item->ID ), $templateData['postUrl'] ); ?>"><?php echo $item->post_title; ?></a></li>
    <?php
        // {% endfor %}
        }
    ?>
</ul>
