<?php
/**
 * Items list 
 * @TODO: use cb_get_template_part, create template partial as item-summary.php 
 * 
 */
?>

<h3><?php echo __('Items available at this location'); ?></h3>

<?php
    // {% for item in items %}
    foreach($templateData['items'] as $item) {
?>
    <div class="cb-box item-summary">
        <div class="cb-cols col-30-70">
            <div><?php echo get_the_post_thumbnail( $item->ID, array( 100, 100) ); ?></div>
            <div><h4><a href="<?php echo $templateData['postUrl']; ?>&item=<?php echo $item->ID; ?>"><?php echo $item->post_title; ?></a></h4></div>
        </div>
    </div>
<?php
    // {% endfor %}
    }
?>

