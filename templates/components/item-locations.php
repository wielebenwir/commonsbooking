<h3>Items</h3>
<ul>
    <?php
        // {% for item in items %}
        foreach($templateData['locations'] as $location) {
    ?>
        <li><a href="<?php echo $templateData['postUrl']; ?>&item=<?php echo $location->ID; ?>"><?php echo $location->post_title; ?></a></li>
    <?php
        // {% endfor %}
        }
    ?>
</ul>
