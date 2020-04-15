<?php


namespace CommonsBooking\View;


use CommonsBooking\Form\Field;

class Form
{

    /**
     * @param \WP_Post $post
     * @param Field $field
     */
    public static function renderField(\WP_Post $post, Field $field) {
        echo '<div class="form-field form-required">';
        switch ($field->getType()) {
            case "checkbox":
            {
                self::renderCheckbox($post, $field);
                break;
            }
            case "textarea":
            case "wysiwyg":
            {
                self::renderTextarea($post, $field);
                break;
            }
            default:
            {
                self::renderTextField($post, $field);
                break;
            }
        }
        echo '</div>';
    }

    public static function renderTextarea(\WP_Post $post, Field $field) {
        // Text area
        echo '<label for="' . $field->getName() . '"><b>' . $field->getTitle() . '</b></label>';
        echo '<textarea name="' . $field->getName() . '" id="' . $field->getName() . '" columns="30" rows="3">' . htmlspecialchars(get_post_meta($post->ID,
                $field->getName(), true)) . '</textarea>';
        // WYSIWYG
        if ($field->getType() == "wysiwyg") { ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery("<?php echo $field->getName(); ?>").addClass("mceEditor");
                    if (typeof (tinyMCE) == "object" &amp;&amp; typeof (tinyMCE.execCommand) == "function") {
                        tinyMCE.execCommand("mceAddControl", false, "<?php echo $field->getName(); ?>");
                    }
                });
            </script>
        <?php }
    }


    // Checkbox
    public static function renderCheckbox(\WP_Post $post, Field $field) {
        echo '<label for="' . $field->getName() . '" style="display:inline;"><b>' . $field->getTitle() . '</b></label>&amp;nbsp;&amp;nbsp;';
        echo '<input type="checkbox" name="' . $field->getName() . '" id="' . $field->getName() . '" value="yes"';
        if (get_post_meta($post->ID, $field->getName(), true) == "yes") {
            echo ' checked="checked"';
        }
        echo '" style="width: auto;" />';
    }
    
    // Plain text field
    public static function renderTextField(\WP_Post $post, Field $field) {
        echo '<label for="' . $field->getName() . '"><b>' . $field->getTitle() . '</b></label>';
        echo '<input type="text" name="' . $field->getName() . '" id="' . $field->getName() . '" value="' .
            htmlspecialchars(get_post_meta($post->ID, $field->getName(), true)) .
        '" />';
    }

}
