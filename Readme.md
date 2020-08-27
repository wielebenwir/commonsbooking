# Installation

1. Checkout Plugin to Wordpress plugin directory (wp-content/plugins)
2. Change to commonsbooking plugin directory
3. Do ``composer install``
4. Do ``npm install && grunt``
5. Install Plugin in Wordpress.

# Tag System

// add this to any template, e.g. booking-publish.php

// echo  
CB::echo('item', 'mymeta');             // this is the item meta  
CB::echo('item', 'mymethod');           // this is a method in class item  
CB::echo('timeframe', 'myproperty');    // this is a property of class timeframe
CB::echo('booking', 'myproperty');      // 'booking' is substited with 'timeframe' in CB.php`

```php
<?php
    // getting
    echo CB::get('item', 'mymeta');   
?>

<?php
    // using a template, useful for mailing
    $template = '
        <p>{{item_mymethod}}</p>
        <i>{{item_testmeta}}</i><br>
        <strong>{{timeframe_myproperty}}</strong>
    ';
    echo \cb_parse_template( $template );
?>

<?php
    // using a shortcode, should be used only in the post editor screen
    do_shortcode( '[cb tag="item_mymeta"]' );
?>
```
