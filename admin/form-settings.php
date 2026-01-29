<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $form_id));
if (!$form) { echo '<div class="notice notice-error"><p>Invalid form.</p></div>'; return; }

$allowed_roles = !empty($form->allowed_roles) ? array_map('trim', explode(',', $form->allowed_roles)) : [];
$roles = wp_roles()->roles;

/**
 * Render Full Designer Settings
 */
function pfb_render_full_designer($prefix, $form) {
    ?>
    <tr style="background:#f0f0f1;"><th colspan="2"><h3>Layout Style</h3></th></tr>
    <tr>
        <th>Column Layout</th>
        <td>
            <select name="<?php echo $prefix; ?>column_layout" style="width:200px;">
                <option value="1-col" <?php selected($form->{$prefix.'column_layout'} ?? '1-col', '1-col'); ?>>Single Column</option>
                <option value="2-col" <?php selected($form->{$prefix.'column_layout'} ?? '1-col', '2-col'); ?>>Double Column</option>
                <option value="3-col" <?php selected($form->{$prefix.'column_layout'} ?? '1-col', '3-col'); ?>>Triple Column</option>
            </select>
        </td>
    </tr>
    <tr>
        <th>Padding & Gaps</th>
        <td class="pfb-design-container">
            <div class="pfb-input-row">
                <label><strong>Form Padding</strong></label>
                <input type="number" name="<?php echo $prefix; ?>form_padding" value="<?php echo esc_attr($form->{$prefix.'form_padding'} ?? 25); ?>"> <span>px</span>
            </div>
            <div class="pfb-input-row">
                <label><strong>Header Gap</strong> (Between Header & Fields)</label>
                <input type="number" name="<?php echo $prefix; ?>header_gap" value="<?php echo esc_attr($form->{$prefix.'header_gap'} ?? 15); ?>"> <span>px</span>
            </div>
            <div class="pfb-input-row">
                <label><strong>Fields Gap</strong> (Between Inputs)</label>
                <input type="number" name="<?php echo $prefix; ?>field_spacing" value="<?php echo esc_attr($form->{$prefix.'field_spacing'} ?? 20); ?>"> <span>px</span>
            </div>
        </td>
    </tr>
    <tr>
        <th>Background Color</th>
        <td><input type="color" name="<?php echo $prefix; ?>input_bg_color" value="<?php echo esc_attr($form->{$prefix.'input_bg_color'} ?? '#ffffff'); ?>"></td>
    </tr>

    <tr style="background:#f0f0f1;"><th colspan="2"><h3>Typography Settings</h3></th></tr>
    <?php 
    $typo_items = ['heading' => 'Heading Style', 'label' => 'Label Style', 'text' => 'Input Text Style'];
    foreach ($typo_items as $key => $title): ?>
    <tr>
        <th><?php echo $title; ?></th>
        <td class="pfb-design-container">
            <div class="pfb-input-row">
                <label><strong>Font Size</strong></label>
                <input type="number" name="<?php echo $prefix.$key; ?>_font_size" value="<?php echo esc_attr($form->{$prefix.$key.'_font_size'} ?? 16); ?>"> <span>px</span>
            </div>
            <div class="pfb-input-row">
                <label><strong>Font Weight</strong></label>
                <select name="<?php echo $prefix.$key; ?>_font_weight">
                    <?php foreach([100,200,300,400,500,600,700,800] as $w) echo '<option value="'.$w.'" '.selected(($form->{$prefix.$key.'_font_weight'} ?? 400), $w, false).'>'.$w.'</option>'; ?>
                </select>
            </div>
            <div class="pfb-input-row">
                <label><strong>Text Color</strong></label>
                <input type="color" name="<?php echo $prefix.$key; ?>_color" value="<?php echo esc_attr($form->{$prefix.$key.'_color'} ?? '#333333'); ?>">
            </div>
            <div class="pfb-input-row">
                <label><strong>Text Alignment</strong></label>
                <select name="<?php echo $prefix.$key; ?>_align">
                    <option value="left" <?php selected(($form->{$prefix.$key.'_align'} ?? 'left'), 'left'); ?>>Left</option>
                    <option value="center" <?php selected(($form->{$prefix.$key.'_align'} ?? 'left'), 'center'); ?>>Center</option>
                    <option value="right" <?php selected(($form->{$prefix.$key.'_align'} ?? 'left'), 'right'); ?>>Right</option>
                </select>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>

    <tr style="background:#f0f0f1;"><th colspan="2"><h3>Media Settings</h3></th></tr>
    <tr>
        <th>Image Preview Size</th>
        <td class="pfb-design-container">
            <div class="pfb-input-row">
                <label><strong>Max Width</strong> (0% to 100%)</label>
                <input type="number" name="<?php echo $prefix; ?>image_preview_width" 
                    value="<?php echo esc_attr($form->{$prefix.'image_preview_width'} ?? 100); ?>" 
                    min="5" max="100"> 
                <span>%</span>
            </div>
            <p class="description">This controls how large images appear on the profile view page.</p>
        </td>
    </tr>

    <tr style="background:#f0f0f1;"><th colspan="2"><h3>Buttons Designer</h3></th></tr>
    <?php foreach(['submit' => 'Submit Button', 'cancel' => 'Cancel Button'] as $b_key => $b_label): ?>
    <tr>
        <th><?php echo $b_label; ?></th>
        <td class="pfb-design-container">
            <div class="pfb-input-row">
                <label><strong>Button Text</strong></label>
                <input type="text" name="<?php echo $prefix.$b_key; ?>_btn_text" value="<?php echo esc_attr($form->{$prefix.$b_key.'_btn_text'} ?? ($b_key === 'submit' ? 'Submit' : 'Cancel')); ?>" class="regular-text">
            </div>
            <div class="pfb-input-row">
                <label><strong>Font Size</strong></label>
                <input type="number" name="<?php echo $prefix.$b_key; ?>_btn_size" value="<?php echo esc_attr($form->{$prefix.$b_key.'_btn_size'} ?? 16); ?>"> <span>px</span>
            </div>
            <div class="pfb-input-row">
                <label><strong>Font Weight</strong></label>
                <select name="<?php echo $prefix.$b_key; ?>_btn_weight">
                    <?php foreach([300,400,500,600,700,800] as $w) echo '<option value="'.$w.'" '.selected(($form->{$prefix.$b_key.'_btn_weight'} ?? 600), $w, false).'>'.$w.'</option>'; ?>
                </select>
            </div>
            <div class="pfb-input-row">
                <label><strong>Background Color</strong></label>
                <input type="color" name="<?php echo $prefix.$b_key; ?>_btn_bg" value="<?php echo esc_attr($form->{$prefix.$b_key.'_btn_bg'} ?? ($b_key === 'submit' ? '#2271b1' : '#eeeeee')); ?>">
            </div>
            <div class="pfb-input-row">
                <label><strong>Text Color</strong></label>
                <input type="color" name="<?php echo $prefix.$b_key; ?>_btn_clr" value="<?php echo esc_attr($form->{$prefix.$b_key.'_btn_clr'} ?? ($b_key === 'submit' ? '#ffffff' : '#333333')); ?>">
            </div>
            <div class="pfb-input-row">
                <label><strong>Border Radius</strong></label>
                <input type="number" name="<?php echo $prefix.$b_key; ?>_btn_radius" value="<?php echo esc_attr($form->{$prefix.$b_key.'_btn_radius'} ?? 6); ?>"> <span>px</span>
            </div>
            <div class="pfb-input-row">
                <label><strong>Button Alignment</strong></label>
                <select name="<?php echo $prefix.$b_key; ?>_btn_align">
                    <option value="flex-start" <?php selected(($form->{$prefix.$b_key.'_btn_align'} ?? 'flex-start'), 'flex-start'); ?>>Left</option>
                    <option value="center" <?php selected(($form->{$prefix.$b_key.'_btn_align'} ?? 'flex-start'), 'center'); ?>>Center</option>
                    <option value="flex-end" <?php selected(($form->{$prefix.$b_key.'_btn_align'} ?? 'flex-start'), 'flex-end'); ?>>Right</option>
                </select>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php
}
?>

<div class="wrap">
    <h1>Advanced Designer Settings — <?php echo esc_html($form->name); ?></h1>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="notice notice-success is-dismissible"><p><strong>Success!</strong> All settings have been updated.</p></div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#pfb-access-tab" class="nav-tab">Access Control</a>
        <a href="#pfb-view-tab" class="nav-tab">1. View Profile</a>
        <a href="#pfb-edit-tab" class="nav-tab">2. Edit Profile</a>
        <a href="#pfb-submit-tab" class="nav-tab">3. Submit Form</a>
    </h2>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_settings', 'pfb_settings_nonce'); ?>
        <input type="hidden" name="action" value="pfb_save_form_settings">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" name="last_tab" id="pfb_last_tab" value="#pfb-access-tab">

        <div id="pfb-access-tab" class="pfb-tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th>Access Type</th>
                    <td>
                        <select name="access_type" id="pfb-access-type">
                            <option value="all" <?php selected($form->access_type, 'all'); ?>>Everyone</option>
                            <option value="logged_in" <?php selected($form->access_type, 'logged_in'); ?>>Only logged-in users</option>
                        </select>
                    </td>
                </tr>
                <tr id="pfb-role-row">
                    <th>Allowed Roles</th>
                    <td>
                        <?php foreach ($roles as $key => $role): ?>
                            <label style="display:block;"><input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $allowed_roles)); ?>> <?php echo esc_html($role['name']); ?></label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div id="pfb-view-tab" class="pfb-tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th>Profile Background</th>
                    <td>
                        <div id="pfb_bg_preview_container" style="margin-bottom:10px;">
                            <?php if (!empty($form->form_bg_image)): ?>
                                <img src="<?php echo esc_url($form->form_bg_image); ?>" id="pfb_bg_preview" style="max-width:200px; border:1px solid #ccc; padding:5px; border-radius:4px; display:block;">
                                <button type="button" class="button button-link-delete" id="pfb_remove_bg" style="color:red; text-decoration:none; padding:0; margin-top:5px;">Remove Image</button>
                            <?php else: ?>
                                <img id="pfb_bg_preview" style="max-width:200px; border:1px solid #ccc; padding:5px; border-radius:4px; display:none;">
                            <?php endif; ?>
                        </div>

                        <input type="text" name="form_bg_image" id="pfb_bg_url" value="<?php echo esc_attr($form->form_bg_image); ?>" class="regular-text">
                        <button type="button" class="button pfb-media-upload">Upload Image</button>
                    </td>
                </tr>
                <?php pfb_render_full_designer('view_', $form); ?>
            </table>
        </div>

        <div id="pfb-edit-tab" class="pfb-tab-content" style="display:none;">
            <table class="form-table"><?php pfb_render_full_designer('edit_', $form); ?></table>
        </div>

        <div id="pfb-submit-tab" class="pfb-tab-content" style="display:none;">
            <table class="form-table"><?php pfb_render_full_designer('submit_', $form); ?></table>
        </div>

        <?php submit_button('Save All Designer Settings'); ?>
    </form>
</div>

<style>
    .pfb-design-container { display: flex; flex-direction: column; gap: 12px; padding: 10px 0 !important; }
    .pfb-input-row { display: flex; align-items: center; gap: 10px; }
    .pfb-input-row label { width: 220px; display: inline-block; color: #555; }
    .pfb-input-row input[type="number"] { width: 70px; }
    .pfb-tab-content h3 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 10px; color: #2271b1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.pfb-tab-content');
    const lastTabInput = document.getElementById('pfb_last_tab');
    const accessSelect = document.getElementById('pfb-access-type');
    const roleRow = document.getElementById('pfb-role-row');

    // 1. Tab Switching & Persistence
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabId = urlParams.get('tab') || '#pfb-access-tab';

    function activateTab(targetId) {
        tabs.forEach(t => t.classList.remove('nav-tab-active'));
        contents.forEach(c => c.style.display = 'none');

        const activeLink = document.querySelector(`[href="${targetId}"]`);
        if (activeLink) activeLink.classList.add('nav-tab-active');

        const activeDiv = document.getElementById(targetId.substring(1));
        if (activeDiv) activeDiv.style.display = 'block';
        
        lastTabInput.value = targetId;
    }

    activateTab(activeTabId);

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            activateTab(this.getAttribute('href'));
        });
    });

    // 2. Access Control Role Toggling (Old Logic)
    const toggleRoles = () => { if(roleRow) roleRow.style.display = (accessSelect.value === 'logged_in') ? 'table-row' : 'none'; };
    if(accessSelect) accessSelect.addEventListener('change', toggleRoles);
    toggleRoles();

    // 3. Media Uploader Logic (Old Logic)
    jQuery('.pfb-media-upload').click(function(e) {
        e.preventDefault();
        let frame = wp.media({ title: 'Select Background', button: { text: 'Use Image' }, multiple: false });
        frame.on('select', function() {
            let attachment = frame.state().get('selection').first().toJSON();
            jQuery('#pfb_bg_url').val(attachment.url);
        }).open();
    });
});


/**
 * Handle Media Upload and Image Preview logic for Form Settings
 */
jQuery(document).ready(function($) {
    
    // Trigger WordPress Media Uploader
    $('.pfb-media-upload').on('click', function(e) {
        e.preventDefault();
        
        // Initialize the media frame
        let frame = wp.media({ 
            title: 'Select Background Image', 
            button: { text: 'Use Image' }, 
            multiple: false 
        });

        // Event handler for image selection
        frame.on('select', function() {
            // Get attachment details from the frame
            let attachment = frame.state().get('selection').first().toJSON();
            
            // Update hidden input and image preview src
            $('#pfb_bg_url').val(attachment.url);
            $('#pfb_bg_preview').attr('src', attachment.url).show();
            
            // Append 'Remove Image' button if it doesn't already exist
            if (!$('#pfb_remove_bg').length) {
                $('#pfb_bg_preview').after('<br><button type="button" class="button button-link-delete" id="pfb_remove_bg" style="color:red; text-decoration:none; padding:0; margin-top:5px;">Remove Image</button>');
            }

            // Automatically close the media library after selection
            frame.close(); 
        });

        frame.open();
    });

    /**
     * Handle Image Removal
     * Resets the input value and hides the preview element
     */
    $(document).on('click', '#pfb_remove_bg', function(e) {
        e.preventDefault();
        
        // Clear the URL input and hide the preview container
        $('#pfb_bg_url').val('');
        $('#pfb_bg_preview').hide().attr('src', '');
        
        // Remove the button itself from the DOM
        $(this).remove();
    });
});
</script>