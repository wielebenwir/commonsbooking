<h3>Items</h3>
<ul>
    <?php
    foreach($templateData['items'] as $item) {
        ?>
        <li><a href="<?php echo get_permalink($item->ID); ?>"><?php echo $item->post_title; ?></a></li>
        <?php
    }
    ?>
</ul>
