
<div>
    <h3><a href="<?php echo get_permalink( $post->ID); ?>"><?php echo $post->post_title; ?></a></h3>
    <?php echo get_the_post_thumbnail( $post->ID, 'thumbnail' ); ?>
    <br>
</div>
