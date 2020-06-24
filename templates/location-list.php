<h3>Locations</h3>
<ul>
    <?php
    // {% for item in items %}
    foreach($templateData['locations'] as $location) {
        ?>
        <li><a href="<?php echo get_permalink($location->ID); ?>"><?php echo $location->post_title; ?></a></li>
        <?php
        // {% endfor %}
    }
    ?>
</ul>
