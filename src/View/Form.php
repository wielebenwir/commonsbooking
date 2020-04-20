<?php

namespace CommonsBooking\View;

use CommonsBooking\Form\Field;

class Form
{

    /**
     * @param \WP_Post $post
     * @param Field $field
     */
    public static function renderField(\WP_Post $post, Field $field)
    {
        echo '<div class="form-field form-required">';
        switch ($field->getType()) {
            case "selectbox":
            {
                self::renderSelectbox($post, $field);
                break;
            }
            case "date":
            {
                self::renderDateInput($post, $field);
                break;
            }
            case "datetime":
            {
                self::renderDateInput($post, $field, true);
                break;
            }
            case "checkbox":
            {
                self::renderCheckbox($post, $field);
                break;
            }
            case "checkboxes":
            {
                self::renderCheckboxes($post, $field);
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

    public static function renderDateInput(\WP_Post $post, Field $field, $hasTime = false)
    {
        echo '<label for="' . $field->getName() . '"><b>' . $field->getTitle() . '</b></label>';
        $type = $hasTime ? "datetime-local" : "date";
        echo '<input type="' . $type . '" name="' . $field->getName() . '" id="' . $field->getName() . '" value="' .
             htmlspecialchars(get_post_meta($post->ID, $field->getName(), true)) .
             '" />';
    }

    public static function renderSelectbox(\WP_Post $post, Field $field)
    {
        echo '<label for="' . $field->getName() . '"><b>' . $field->getTitle() . '</b></label>';
        echo '<select name="' . $field->getName() . '" id="' . $field->getName() . '">';

        echo '<option value="none" ';
        if (get_post_meta($post->ID, $field->getName(), true) == "none") {
            echo ' selected="selected"';
        }
        echo '>' . __("-", TRANSLATION_CONST) . '</option>';

        foreach ($field->getOptions() as $key => $item) {

            if($item instanceof \WP_Post) {
                $key = $item->ID;
                $label = $item->post_title;
            } else {
                $label = $item;
            }
            echo '<option value="' . $key . '" ';
            if (get_post_meta($post->ID, $field->getName(), true) == $key) {
                echo ' selected="selected"';
            }
            echo '>' . $label . '</option>';
        }
        echo '</select>';
    }

    public static function renderCheckboxes(\WP_Post $post, Field $field)
    {
        echo '<label><b>' . $field->getTitle() . '</b></label>';
        foreach ($field->getOptions() as $key => $label) {
            echo '<label for="' . $field->getName() . '-' . $key . '">';
            echo '<input style="margin-top:0.1em" type="checkbox" name="' . $field->getName() . '-' . $key . '" id="' . $field->getName() . '-' . $key . '" value="yes"';
            if (get_post_meta($post->ID, $field->getName() . '-' . $key, true) == "yes") {
                echo ' checked="checked"';
            }
            echo '" style="width: auto;float:left;" />';
            echo '<b>' . $label . '</b></label>';

        }
    }

    public static function renderTextarea(\WP_Post $post, Field $field)
    {
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
    public static function renderCheckbox(\WP_Post $post, Field $field)
    {
        echo '<label for="' . $field->getName() . '" style="display:inline;"><b>' . $field->getTitle() . '</b></label>&amp;nbsp;&amp;nbsp;';
        echo '<input type="checkbox" name="' . $field->getName() . '" id="' . $field->getName() . '" value="yes"';
        if (get_post_meta($post->ID, $field->getName(), true) == "yes") {
            echo ' checked="checked"';
        }
        echo '" style="width: auto;" />';
    }

    // Plain text field
    public static function renderTextField(\WP_Post $post, Field $field)
    {
        echo '<label for="' . $field->getName() . '"><b>' . $field->getTitle() . '</b></label>';
        echo '<input type="text" name="' . $field->getName() . '" id="' . $field->getName() . '" value="' .
             htmlspecialchars(get_post_meta($post->ID, $field->getName(), true)) .
             '" />';
    }

}
