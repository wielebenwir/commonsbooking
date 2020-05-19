<?php

/**
 * based on: https://github.com/CMB2/CMB2-Snippet-Library/blob/master/options-and-settings-pages/options-pages-with-tabs-and-submenus.php
 */

/**
 * Hook in and register a metabox to handle a theme options page and adds a menu item.
 */
function commonsbooking_register_main_options_metabox()
{


    //* ---------------- TAB: GENERAL OPTIONS -------------------------*/

    /**
     * Registers main options page menu item and form.
     */
    $args = array(
        'id'           => 'commonsbooking_main_options_page',
        'title'        => 'CommonsBooking',
        'object_types' => array('options-page'),
        'option_key'   => 'commonsbooking_main_options',
        'tab_group'    => 'commonsbooking_main_options',
        'tab_title'    => 'Allgemein',
        'parent_slug'  => "options-general.php"
    );

    // 'tab_group' property is supported in > 2.4.0.
    if (version_compare(CMB2_VERSION, '2.4.0')) {
         $args['display_cb'] = 'commonsbooking_options_display_with_tabs';
     }

    $main_options = new_cmb2_box($args);


    /**
     * group Main Settings 1
     */

    $group_main_options = $main_options->add_field(array(
        'id'      => 'cb_main_options',
        'type'    => 'group',
        'options' => array(
            'group_title' => __('Allgemein', TRANSLATION_CONST),
        ),
        'repeatable' => false
    ));

    /**
     * field example
     */
    $main_options->add_group_field($group_main_options, array(
        'name' => __('XXXX', TRANSLATION_CONST),
        'desc' => 'Write a short description for this entry',
        'id'   => 'xxx',
        'type' => 'text'
    ));



    //* ---------------- TAB: E-MAIL SETTINGS  -------------------------*/


    /**
     * Registers e-mail options page, and set main item as parent.
     */
    $args = array(
        'id'           => 'commonsbooking_email_options_page',
        //'title'        => 'CommonsBooking', // Use menu title, & not title to hide main h2.
        'object_types' => array('options-page'),
        'option_key'   => 'commonsbooking_mail_options',
        'parent_slug'  => 'commonsbooking_main_options',
        'tab_group'    => 'commonsbooking_main_options',
        'tab_title'    => __('E-Mails', TRANSLATION_CONST)
    );

    // 'tab_group' property is supported in > 2.4.0.
    if (version_compare(CMB2_VERSION, '2.4.0')) {
        $args['display_cb'] = 'commonsbooking_options_display_with_tabs';
    }

    $email_options = new_cmb2_box($args);

    /**
     * group e-mail settings: email main options
     */

    $group_email_options = $email_options->add_field(array(
        'id'      => 'email_options',
        'type'    => 'group',
        'options' => array(
            'group_title' => __('E-Mail Grundeinstellungen', TRANSLATION_CONST),
        ),
        'repeatable' => false
    ));

    /**
     * e-mail sender name
     */
    $email_options->add_group_field($group_email_options, array(
        'name' => __('Absender Name', TRANSLATION_CONST),
        'desc' => 'Name der in den gesendeten E-Mails angezeigt wird',
        'id'   => 'email_sender_name',
        'type' => 'text'
    ));

    /**
     * e-mail sender email
     */
    $email_options->add_group_field($group_email_options, array(
        'name' => __('Absender E-Mail', TRANSLATION_CONST),
        'desc' => 'E-Mail Absenderadresse',
        'id'   => 'email_sender_mail',
        'type' => 'text_email'
    ));


    /**
     * group e-mail settings: Approved E-Mail
     */

    $group_email_messages = $email_options->add_field(array(
        'id'      => 'email_messages',
        'type'    => 'group',
        'options' => array(
            'group_title' => __('E-Mails', TRANSLATION_CONST),
        ),
        'repeatable' => false
    ));

    /**
     * e-mail booking confirmed settings
     */
    $email_options->add_group_field($group_email_messages, array(
        'name' => __('Betreff E-Mail Buchungsbestätigung', TRANSLATION_CONST),
        'desc' => 'Write a short description for this entry',
        'id'   => 'email_booking_confirmed_subject',
        'type' => 'text'
    ));

    $email_options->add_group_field($group_email_messages, array(
        'name' => __('E-Mail Text Buchungsbestätigung', TRANSLATION_CONST),
        'description' => 'Write a short description for this entry',
        'id'   => 'email_booking_confirmed_body',
        'type' => 'textarea_small'
    ));


    /**
     * e-mail booking cancelled settings
     */
    $email_options->add_group_field($group_email_messages, array(
        'name' => __('Betreff E-Mail Buchung storniert', TRANSLATION_CONST),
        'desc' => 'Write a short description for this entry',
        'id'   => 'email_booking_cancelled_subject',
        'type' => 'text'
    ));

    $email_options->add_group_field($group_email_messages, array(
        'name' => __('E-Mail Text Buchug storniert', TRANSLATION_CONST),
        'description' => 'Write a short description for this entry',
        'id'   => 'email_booking_cancelled_body',
        'type' => 'textarea_small'
    ));
}


add_action('cmb2_admin_init', 'commonsbooking_register_main_options_metabox');

/**
 * A CMB2 options-page display callback override which adds tab navigation among
 * CMB2 options pages which share this same display callback.
 *
 * @param CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
 */
function commonsbooking_options_display_with_tabs($cmb_options)
{
    $tabs = commonsbooking_options_page_tabs($cmb_options);
?>
    <div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
        <?php if (get_admin_page_title()) : ?>
            <h2><?php echo wp_kses_post(get_admin_page_title()); ?></h2>
        <?php endif; ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $option_key => $tab_title) : ?>
                <a class="nav-tab<?php if (isset($_GET['page']) && $option_key === $_GET['page']) : ?> nav-tab-active<?php endif; ?>" href="<?php menu_page_url($option_key); ?>"><?php echo wp_kses_post($tab_title); ?></a>
            <?php endforeach; ?>
        </h2>
        <form class="cmb-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data" encoding="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo esc_attr($cmb_options->option_key); ?>">
            <?php $cmb_options->options_page_metabox(); ?>
            <?php submit_button(esc_attr($cmb_options->cmb->prop('save_button')), 'primary', 'submit-cmb'); ?>
        </form>
    </div>
<?php
}

/**
 * Gets navigation tabs array for CMB2 options pages which share the given
 * display_cb param.
 *
 * @param CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
 *
 * @return array Array of tab information.
 */
function commonsbooking_options_page_tabs($cmb_options)
{
    $tab_group = $cmb_options->cmb->prop('tab_group');
    $tabs      = array();

    foreach (CMB2_Boxes::get_all() as $cmb_id => $cmb) {
        if ($tab_group === $cmb->prop('tab_group')) {
            $tabs[$cmb->options_page_keys()[0]] = $cmb->prop('tab_title')
                ? $cmb->prop('tab_title')
                : $cmb->prop('title');
        }
    }

    return $tabs;
}


/**
 * Test retrieve options from array 
 */
$option = \Settings::GetOption('commonsbooking_email_options', 'email_booking_confirmed_body');
echo "Option: " . $option;
