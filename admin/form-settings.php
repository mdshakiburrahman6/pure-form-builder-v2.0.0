<?php
/**
 * admin/form-settings.php
 * Absolute Full Version: Advanced UI Designer + Access Control
 * Includes: Column Layout, Typography, Background Image, and Button Customization.
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $form_id));

if (!$form) {
    echo '<div class="notice notice-error"><p>Invalid form.</p></div>';
    return;
}

$allowed_roles = !empty($form->allowed_roles) ? array_map('trim', explode(',', $form->allowed_roles)) : [];
$roles = wp_roles()->roles;
$pages = get_pages();
?>

<div class="wrap">
    <h1>Form Settings — <?php echo esc_html($form->name); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p>All settings updated successfully!</p></div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#pfb-access-tab" class="nav-tab nav-tab-active" id="tab-access">Access Control</a>
        <a href="#pfb-design-tab" class="nav-tab" id="tab-design">Advanced UI Designer</a>
    </h2>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_settings', 'pfb_settings_nonce'); ?>
        <input type="hidden" name="action" value="pfb_save_form_settings">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">

        <div id="pfb-access-tab" class="pfb-tab-content">
            <table class="form-table">
                <tr>
                    <th>Who can access this form?</th>
                    <td>
                        <select name="access_type" id="pfb-access-type">
                            <option value="all" <?php selected($form->access_type, 'all'); ?>>Everyone</option>
                            <option value="logged_in" <?php selected($form->access_type, 'logged_in'); ?>>Only logged-in users</option>
                            <option value="guest" <?php selected($form->access_type, 'guest'); ?>>Only guests</option>
                        </select>
                    </td>
                </tr>
                <tr id="pfb-role-row">
                    <th>Allowed Roles</th>
                    <td>
                        <?php foreach ($roles as $key => $role): ?>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $allowed_roles)); ?>>
                                <?php echo esc_html($role['name']); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description">Visible only if "Logged-in users" is selected.</p>
                    </td>
                </tr>
                <tr>
                    <th>Entry Management</th>
                    <td>
                        <label>
                            <input type="checkbox" name="allow_user_edit" value="1" <?php checked($form->allow_user_edit); ?>>
                            Allow users to edit their own entries
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div id="pfb-design-tab" class="pfb-tab-content" style="display:none;">
            <table class="form-table">
                <tr style="background:#f6f7f7;"><th colspan="2"><h3>Layout & Spacing</h3></th></tr>
                <tr>
                    <th>Column Layout</th>
                    <td>
                        <select name="column_layout">
                            <option value="1-col" <?php selected($form->column_layout ?? '1-col', '1-col'); ?>>Single Column</option>
                            <option value="2-col" <?php selected($form->column_layout ?? '1-col', '2-col'); ?>>Double Column</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Form Padding</th>
                    <td><input type="number" name="form_padding" value="<?php echo esc_attr($form->form_padding ?? 25); ?>" class="small-text"> px</td>
                </tr>
                <tr>
                    <th>Field Spacing</th>
                    <td><input type="number" name="field_spacing" value="<?php echo esc_attr($form->field_spacing ?? 20); ?>" class="small-text"> px</td>
                </tr>

                <tr style="background:#f6f7f7;"><th colspan="2"><h3>Colors & Background</h3></th></tr>
                <tr>
                    <th>Primary Theme Color</th>
                    <td>
                        <input type="color" name="primary_color" value="<?php echo esc_attr($form->primary_color ?? '#2271b1'); ?>">
                        <p class="description">Applies to buttons and section legends.</p>
                    </td>
                </tr>
                <tr>
                    <th>Label Text Color</th>
                    <td><input type="color" name="label_color" value="<?php echo esc_attr($form->label_color ?? '#333333'); ?>"></td>
                </tr>
                <tr>
                    <th>Input BG Color</th>
                    <td><input type="color" name="input_bg_color" value="<?php echo esc_attr($form->input_bg_color ?? '#ffffff'); ?>"></td>
                </tr>
                <tr>
                    <th>Background Image</th>
                    <td>
                        <div class="pfb-media-upload-wrapper">
                            <input type="text" name="form_bg_image" id="pfb_bg_image_url" value="<?php echo esc_attr($form->form_bg_image ?? ''); ?>" class="regular-text">
                            <button type="button" id="pfb_upload_bg_btn" class="button button-secondary">Select Image</button>
                            <button type="button" id="pfb_remove_bg_btn" class="button button-link-delete" style="<?php echo empty($form->form_bg_image) ? 'display:none;' : ''; ?>">Remove</button>
                        </div>
                        <div id="pfb_bg_preview" style="margin-top:10px;">
                            <?php if (!empty($form->form_bg_image)): ?>
                                <img src="<?php echo esc_url($form->form_bg_image); ?>" style="max-width:150px; border:1px solid #ccc; padding:5px; border-radius:4px;">
                            <?php endif; ?>
                        </div>
                        <p class="description">Select an image from Media Library or paste a URL.</p>
                    </td>
                </tr>

                <tr style="background:#f6f7f7;"><th colspan="2"><h3>Typography & Form Elements</h3></th></tr>
                <tr>
                    <th>Legend Font Size</th>
                    <td><input type="number" name="legend_font_size" value="<?php echo esc_attr($form->legend_font_size ?? 20); ?>" class="small-text"> px</td>
                </tr>
                <tr>
                    <th>Form Border Radius</th>
                    <td><input type="number" name="border_radius" value="<?php echo esc_attr($form->border_radius ?? 8); ?>" class="small-text"> px</td>
                </tr>
                <tr>
                    <th>Overall Text Align</th>
                    <td>
                        <select name="text_align">
                            <option value="left" <?php selected($form->text_align ?? 'left', 'left'); ?>>Left</option>
                            <option value="center" <?php selected($form->text_align ?? 'left', 'center'); ?>>Center</option>
                            <option value="right" <?php selected($form->text_align ?? 'left', 'right'); ?>>Right</option>
                        </select>
                    </td>
                </tr>

                <tr style="background:#f6f7f7;"><th colspan="2"><h3>Button Customization</h3></th></tr>
                <tr>
                    <th>Button Label</th>
                    <td><input type="text" name="button_text" value="<?php echo esc_attr($form->button_text ?? 'Submit'); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Button Text Color</th>
                    <td><input type="color" name="button_text_color" value="<?php echo esc_attr($form->button_text_color ?? '#ffffff'); ?>"></td>
                </tr>
                <tr>
                    <th>Button Width</th>
                    <td>
                        <select name="button_width">
                            <option value="auto" <?php selected($form->button_width ?? 'auto', 'auto'); ?>>Auto (Fits Text)</option>
                            <option value="100%" <?php selected($form->button_width ?? 'auto', '100%'); ?>>Full Width</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button('Save All Form Settings'); ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.pfb-tab-content');

    // Tab Switching Logic
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            contents.forEach(c => c.style.display = 'none');

            this.classList.add('nav-tab-active');
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // Access Control Role Row Visibility
    const accessSelect = document.getElementById('pfb-access-type');
    const roleRow = document.getElementById('pfb-role-row');
    const toggleRoles = () => roleRow.style.display = (accessSelect.value === 'logged_in') ? 'table-row' : 'none';
    
    accessSelect.addEventListener('change', toggleRoles);
    toggleRoles(); // Initialize on load
});


// WordPress Media Uploader Logic
jQuery(document).ready(function($){
    let mediaUploader;

    $('#pfb_upload_bg_btn').click(function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Form Background Image',
            button: {
                text: 'Use this Image'
            },
            multiple: false
        });

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function() {
            let attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#pfb_bg_image_url').val(attachment.url);
            $('#pfb_bg_preview').html('<img src="'+attachment.url+'" style="max-width:150px; border:1px solid #ccc; padding:5px; border-radius:4px;">');
            $('#pfb_remove_bg_btn').show();
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    // Remove background image logic
    $('#pfb_remove_bg_btn').click(function() {
        $('#pfb_bg_image_url').val('');
        $('#pfb_bg_preview').empty();
        $(this).hide();
    });
});

</script>
