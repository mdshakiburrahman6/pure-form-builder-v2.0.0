<?php
/**
 * admin/form-builder.php
 * Verified Version: Drag-and-Drop, Conditional Logic (with Dynamic Dropdowns), and New Types.
 * No Bengali comments or emojis included.
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'pfb_forms';

$form_id   = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form_name = '';

if ($form_id) {
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $form_id));
    if ($form) $form_name = $form->name;
}

$edit_field_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : 0;
$edit_field = null;
if ($edit_field_id) {
    $edit_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE id=%d", $edit_field_id));
}

// Fetch all non-fieldset fields for conditional logic mapping
$all_fields = $wpdb->get_results($wpdb->prepare(
    "SELECT name, label, options FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d AND is_fieldset = 0", 
    $form_id
));
?>
<style>
    /* Toast Notification Style */
    #pfb-save-toast {
        visibility: hidden;
        min-width: 200px;
        background-color: #008a22;
        color: #fff;
        text-align: center;
        border-radius: 4px;
        padding: 12px;
        position: fixed;
        z-index: 9999;
        right: 30px;
        bottom: 30px;
        font-size: 14px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    #pfb-save-toast.show {
        visibility: visible;
        -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
        animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    @-webkit-keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
    @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
    @-webkit-keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
    @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
</style>

<div id="pfb-save-toast">Structure Saved!</div>
<div class="wrap">
    <h1><?php echo $form_id ? 'Edit Form' : 'Create New Form'; ?></h1>

    <?php if (isset($_GET['saved'])): ?><div class="notice notice-success"><p>Form saved successfully!</p></div><?php endif; ?>
    <?php if (isset($_GET['field_added'])): ?><div class="notice notice-success"><p>Structure updated!</p></div><?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_action', 'pfb_nonce'); ?>
        <input type="hidden" name="action" value="pfb_save_form">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <table class="form-table">
            <tr>
                <th>Form Name</th>
                <td><input type="text" name="form_name" class="regular-text" value="<?php echo esc_attr($form_name); ?>" required placeholder="e.g. User Profile Form"></td>
            </tr>
        </table>
        <?php submit_button($form_id ? 'Update Name' : 'Save Form'); ?>
    </form>

    <?php if ($form_id): ?>
        <hr>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Form Structure (Drag to Sort)</h2>
            <button type="button" class="button button-primary" id="pfb-add-section-trigger">+ Add New Section Header</button>
        </div>
        
        <div class="pfb-sections-list">
            <?php 
            $fieldsets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC, id ASC",
                $form_id
            ));

            if ($fieldsets) :
                foreach ($fieldsets as $section) : 
            ?>
                <div class="pfb-section-card" data-id="<?php echo $section->id; ?>" style="border: 1px solid #ccd0d4; margin-bottom: 25px; background: #fff; border-radius: 4px;">
                    <div class="pfb-section-header" style="display: flex; justify-content: space-between; align-items: center; background: #f6f7f7; padding: 10px 15px; border-bottom: 1px solid #ccd0d4; cursor: move;">
                        <h3 style="margin:0;"><span class="dashicons dashicons-move" style="color:#999; margin-right:5px;"></span> Section: <?php echo esc_html($section->label); ?></h3>
                        <div>
                            <a href="?page=pfb-builder&form_id=<?php echo $form_id; ?>&edit_field=<?php echo $section->id; ?>" class="button button-small">Edit Header</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pfb_delete_field&field_id=' . $section->id . '&form_id=' . $form_id), 'pfb_delete_field_' . $section->id); ?>" 
                               class="button button-small" style="color:red;" onclick="return confirm('Delete this section?');">Delete</a>
                        </div>
                    </div>
                    <div class="pfb-section-body" style="padding: 15px;">
                        <table class="widefat striped pfb-fields-table" style="border:none; box-shadow:none;">
                            <thead>
                                <tr>
                                    <th width="30"></th>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $section_fields = $wpdb->get_results($wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
                                    $section->id
                                ));
                                if ($section_fields) :
                                    foreach ($section_fields as $f) : ?>
                                        <tr data-id="<?php echo $f->id; ?>">
                                            <td class="pfb-drag-handle" style="cursor:move; color:#ccc;"><span class="dashicons dashicons-menu"></span></td>
                                            <td><?php echo esc_html($f->label); ?></td>
                                            <td><span class="tag"><?php echo esc_html($f->type); ?></span></td>
                                            <td><code><?php echo esc_html($f->name); ?></code></td>
                                            <td>
                                                <a href="?page=pfb-builder&form_id=<?php echo $form_id; ?>&edit_field=<?php echo $f->id; ?>">Edit</a> | 
                                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pfb_delete_field&field_id=' . $f->id . '&form_id=' . $form_id), 'pfb_delete_field_' . $f->id); ?>" 
                                                style="color:#d63638;" onclick="return confirm('Delete field?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else :
                                    echo '<tr class="no-fields"><td colspan="5" style="text-align:center; padding:20px; color:#666;">No fields in this section.</td></tr>';
                                endif; ?>
                            </tbody>
                        </table>
                        <div style="margin-top:10px; text-align:right;">
                            <button type="button" class="button pfb-add-field-to-section" data-section-id="<?php echo $section->id; ?>">+ Add Field to this Section</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: echo '<p>No sections found.</p>'; endif; ?>
        </div>

        <hr>
        <h2 id="field-editor-title"><?php echo $edit_field ? 'Edit Component' : 'Add Component'; ?></h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="pfb-field-form" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">
            <?php wp_nonce_field('pfb_add_field_action', 'pfb_field_nonce'); ?>
            <input type="hidden" name="action" value="pfb_add_field">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="field_id" value="<?php echo esc_attr($edit_field->id ?? 0); ?>">
            <input type="hidden" name="fieldset_id" id="pfb-fieldset-id-input" value="<?php echo esc_attr($edit_field->fieldset_id ?? 0); ?>">

            <table class="form-table">
                <tr>
                    <th>Type</th>
                    <td>
                        <select name="field_type" id="pfb-field-type" style="width:25em;">
                            <option value="fieldset" <?php selected($edit_field->type ?? '', 'fieldset'); ?>>Section Header (Fieldset)</option>
                            <option value="text" <?php selected($edit_field->type ?? '', 'text'); ?>>Text Input</option>
                            <option value="textarea" <?php selected($edit_field->type ?? '', 'textarea'); ?>>Textarea</option>
                            <option value="email" <?php selected($edit_field->type ?? '', 'email'); ?>>Email</option>
                            <option value="tel" <?php selected($edit_field->type ?? '', 'tel'); ?>>Telephone (Tel)</option>
                            <option value="url" <?php selected($edit_field->type ?? '', 'url'); ?>>URL</option>
                            <option value="number" <?php selected($edit_field->type ?? '', 'number'); ?>>Number</option>
                            <option value="select" <?php selected($edit_field->type ?? '', 'select'); ?>>Dropdown (Select)</option>
                            <option value="radio" <?php selected($edit_field->type ?? '', 'radio'); ?>>Radio Buttons</option>
                            <option value="file" <?php selected($edit_field->type ?? '', 'file'); ?>>File Upload</option>
                            <option value="image" <?php selected($edit_field->type ?? '', 'image'); ?>>Image Upload</option>
                            <option value="gallery" <?php selected($edit_field->type ?? '', 'gallery'); ?>>Image Gallery (Multiple)</option>
                        </select>
                    </td>
                </tr>

                <tr class="pfb-fieldset-only">
                    <th>Section Logic</th>
                    <td>
                        <select name="fieldset_display">
                            <option value="show_always" <?php selected($edit_field->fieldset_display ?? '', 'show_always'); ?>>Show Always</option>
                            <option value="hide_if_empty" <?php selected($edit_field->fieldset_display ?? '', 'hide_if_empty'); ?>>Hide in Profile if Empty</option>
                        </select>
                    </td>
                </tr>
                <tr class="pfb-fieldset-only">
                    <th>Section Background</th>
                    <td>
                        <div id="pfb_section_bg_preview_container" style="margin-bottom:10px;">
                            <?php if (!empty($edit_field->section_bg_image)): ?>
                                <img src="<?php echo esc_url($edit_field->section_bg_image); ?>" id="pfb_section_bg_preview" style="max-width:200px; border:1px solid #ccc; padding:5px; border-radius:4px; display:block;">
                                <button type="button" class="button button-link-delete" id="pfb_remove_section_bg" style="color:red; text-decoration:none; padding:0; margin-top:5px;">Remove Image</button>
                            <?php else: ?>
                                <img id="pfb_section_bg_preview" style="max-width:200px; border:1px solid #ccc; padding:5px; border-radius:4px; display:none;">
                            <?php endif; ?>
                        </div>

                        <input type="text" name="section_bg_image" id="pfb_section_bg_url" value="<?php echo esc_attr($edit_field->section_bg_image ?? ''); ?>" class="regular-text">
                        <button type="button" class="button pfb-section-bg-upload">Select Image</button>
                        
                        <div style="margin-top:10px;">
                            Opacity: <input type="number" name="section_bg_opacity" step="0.1" min="0" max="1" value="<?php echo esc_attr($edit_field->section_bg_opacity ?? 1.0); ?>" class="small-text">
                        </div>
                    </td>
                </tr>
                <tr><th>Label</th><td><input type="text" name="field_label" class="regular-text" value="<?php echo esc_attr($edit_field->label ?? ''); ?>" required></td></tr>
                <tr><th>System Name (ID)</th><td><input type="text" name="field_name" class="regular-text" value="<?php echo esc_attr($edit_field->name ?? ''); ?>" required <?php echo $edit_field ? 'readonly' : ''; ?>></td></tr>

                <tr class="pfb-standard-field">
                    <th>Placeholder</th>
                    <td>
                        <input type="text" name="field_placeholder" class="regular-text" value="<?php echo esc_attr($edit_field->placeholder ?? ''); ?>" placeholder="Enter placeholder text...">
                    </td>
                </tr>

                <tr class="pfb-standard-field">
                    <th>Description</th>
                    <td>
                        <textarea name="field_description" class="large-text" rows="2" placeholder="Enter a small description for this field..."><?php echo esc_textarea($edit_field->description ?? ''); ?></textarea>
                        <p class="description">This text will appear below the input field.</p>
                    </td>
                </tr>

                <!-- <tr class="pfb-standard-field">
                    <th>Required</th>
                    <td><label><input type="checkbox" name="field_required" <?php checked(!empty($edit_field->required)); ?>> Field is mandatory</label></td>
                </tr> -->

                <tr class="pfb-standard-field">
                    <th>Required</th>
                    <td><label><input type="checkbox" name="field_required" <?php checked(!empty($edit_field->required)); ?>> Field is mandatory</label></td>
                </tr>

                <tr class="pfb-field-options-row">
                    <th>Options</th>
                    <td>
                        <textarea name="field_options" rows="3" class="large-text" placeholder="Option 1, Option 2, Option 3"><?php if (!empty($edit_field->options)) echo esc_textarea(implode(', ', json_decode($edit_field->options, true))); ?></textarea>
                        <p class="description">Comma separated list of choices.</p>
                    </td>
                </tr>

                <tr class="pfb-file-settings">
                    <th>Max Size (MB)</th>
                    <td><input type="number" step="0.1" name="max_size" value="<?php echo esc_attr($edit_field->max_size ?? 2); ?>" class="small-text"></td>
                </tr>

                <tr class="pfb-standard-field">
                    <th>Conditional Logic</th>
                    <td>
                        <label><input type="checkbox" id="enable_condition" <?php echo (!empty($edit_field->rules)) ? 'checked' : ''; ?>> Show only if...</label>
                        <div id="condition_builder" style="display:none; margin-top:10px; border:1px solid #ddd; padding:15px; background:#f9f9f9;">
                            <div id="rule_groups"></div>
                            <button type="button" class="button" id="add_rule_group">+ Add OR Rule Group</button>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 20px;">
                <?php submit_button($edit_field ? 'Update Component' : 'Add to Form', 'primary', 'submit', false); ?>
                <?php if ($edit_field): ?>
                    <a href="?page=pfb-builder&form_id=<?php echo $form_id; ?>" class="button" style="margin-left:10px;">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <script>
    jQuery(document).ready(function($) {
        // UI TOGGLE LOGIC
        const typeSelect = $('#pfb-field-type');
        function toggleUI() {
            const val = typeSelect.val();
            $('.pfb-fieldset-only').toggle(val === 'fieldset');
            $('.pfb-standard-field').toggle(val !== 'fieldset');
            $('.pfb-field-options-row').toggle(['select', 'radio'].includes(val));
            $('.pfb-file-settings').toggle(['file', 'image', 'gallery'].includes(val));
        }
        typeSelect.on('change', toggleUI);
        toggleUI();


        /**
         * Initialize Sortable functionality for sections and fields
         * Persists layout changes via AJAX calls
         */
        $(".pfb-sections-list").sortable({
            handle: ".pfb-section-header",
            placeholder: "ui-state-highlight",
            forcePlaceholderSize: true,
            update: function(event, ui) {
                saveOrder(); // Trigger auto-save on section movement
            }
        });

        /**
         * Handle Drag and Drop for fields across multiple section containers
         */
        $(".pfb-fields-table tbody").sortable({
            items: "tr:not(.no-fields)",
            handle: ".pfb-drag-handle",
            placeholder: "ui-state-highlight",
            connectWith: ".pfb-fields-table tbody",
            update: function(event, ui) {
                saveOrder(); // Trigger auto-save on field movement
            }
        });

        /**
         * Capture current DOM structure and sync with Database
         */
        function saveOrder() {
            let order = [];
            // Map current hierarchy of sections and fields
            $(".pfb-section-card").each(function() {
                order.push($(this).data('id')); 
                $(this).find('tbody tr[data-id]').each(function() {
                    order.push($(this).data('id')); 
                });
            });

            // Execute AJAX request to update sort_order in Database
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pfb_update_field_order',
                    order: order
                },
                success: function(res) {
                    if(res.success) {
                        // Show success toast notification
                        const toast = document.getElementById("pfb-save-toast");
                        toast.className = "show";
                        setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
                    }
                }
            });
        }

        // ADD TRIGGERS
        $('#pfb-add-section-trigger').on('click', () => {
            typeSelect.val('fieldset');
            $('#pfb-fieldset-id-input').val('0');
            toggleUI();
            $('#pfb-field-form')[0].scrollIntoView({behavior: "smooth"});
        });

        $(document).on('click', '.pfb-add-field-to-section', function() {
            $('#pfb-fieldset-id-input').val($(this).data('section-id'));
            if (typeSelect.val() === 'fieldset') typeSelect.val('text');
            toggleUI();
            $('#pfb-field-form')[0].scrollIntoView({behavior: "smooth"});
        });

        // CONDITIONAL LOGIC BUILDER
        const allFields = <?php echo wp_json_encode($all_fields); ?>;
        const existingRules = <?php echo !empty($edit_field->rules) ? $edit_field->rules : '[]'; ?>;

        function addRuleRow(groupIndex, ruleData = null) {
            const container = $(`.rule-group[data-index="${groupIndex}"] .rules-list`);
            const ruleIndex = container.children().length;
            
            let fieldOptions = allFields.map(f => `<option value="${f.name}" ${ruleData?.field === f.name ? 'selected' : ''}>${f.label}</option>`).join('');
            
            const html = `
                <div class="rule-row" style="margin-bottom:10px;">
                    IF <select name="rules[${groupIndex}][rules][${ruleIndex}][field]" class="pfb-rule-field-select">${fieldOptions}</select>
                    <select name="rules[${groupIndex}][rules][${ruleIndex}][operator]">
                        <option value="is" ${ruleData?.operator === 'is' ? 'selected' : ''}>is</option>
                        <option value="is_not" ${ruleData?.operator === 'is_not' ? 'selected' : ''}>is not</option>
                    </select>
                    <span class="pfb-rule-value-container">
                        <input type="text" name="rules[${groupIndex}][rules][${ruleIndex}][value]" value="${ruleData?.value || ''}" placeholder="value">
                    </span>
                    <button type="button" class="remove-rule" style="color:red; border:none; background:none; cursor:pointer;">&times;</button>
                </div>`;
            
            const $row = $(html);
            container.append($row);

            // Dynamic value input based on target field type
            $row.find('.pfb-rule-field-select').on('change', function() {
                const selectedFieldName = $(this).val();
                const valueContainer = $(this).siblings('.pfb-rule-value-container');
                const targetField = allFields.find(f => f.name === selectedFieldName);
                
                if (targetField && targetField.options) {
                    try {
                        const options = JSON.parse(targetField.options);
                        if (Array.isArray(options) && options.length > 0) {
                            let selectHtml = `<select name="rules[${groupIndex}][rules][${ruleIndex}][value]">`;
                            options.forEach(opt => {
                                selectHtml += `<option value="${opt}" ${ruleData?.value === opt ? 'selected' : ''}>${opt}</option>`;
                            });
                            selectHtml += `</select>`;
                            valueContainer.html(selectHtml);
                            return;
                        }
                    } catch (e) { console.error("Error parsing options", e); }
                }
                valueContainer.html(`<input type="text" name="rules[${groupIndex}][rules][${ruleIndex}][value]" value="${ruleData?.value || ''}" placeholder="value">`);
            }).trigger('change');
        }

        function addGroup(groupData = null) {
            const index = $('#rule_groups').children().length;
            const html = `
                <div class="rule-group" data-index="${index}" style="background:#fff; border:1px solid #eee; padding:10px; margin-bottom:15px; position:relative;">
                    <div class="rules-list"></div>
                    <button type="button" class="button button-small add-rule">+ AND Rule</button>
                    <button type="button" class="remove-group" style="position:absolute; top:5px; right:5px; color:#999; border:none; background:none; cursor:pointer;">Remove OR Group</button>
                </div>`;
            $('#rule_groups').append(html);
            if (groupData?.rules) groupData.rules.forEach(r => addRuleRow(index, r));
            else addRuleRow(index);
        }

        $('#enable_condition').on('change', function() { $('#condition_builder').toggle(this.checked); });
        if ($('#enable_condition').is(':checked')) {
            $('#condition_builder').show();
            if (existingRules.length) existingRules.forEach(g => addGroup(g));
            else addGroup();
        }

        $('#add_rule_group').on('click', () => addGroup());
        $(document).on('click', '.add-rule', function() { addRuleRow($(this).closest('.rule-group').data('index')); });
        $(document).on('click', '.remove-rule', function() { $(this).closest('.rule-row').remove(); });
        $(document).on('click', '.remove-group', function() { $(this).closest('.rule-group').remove(); });
    });
    </script>
    <script>
        /**
         * Professional Media Uploader for Section Background
         * Logic: Uses delegated events to ensure it works on dynamically added elements.
         */
        jQuery(document).ready(function($) {
            // FIX: Using delegated event $(document).on to target dynamic buttons
            $(document).on('click', '.pfb-section-bg-upload', function(e) {
                e.preventDefault();
                
                // Initialize the WordPress Media Library frame
                let pfb_section_frame = wp.media({ 
                    title: 'Select Section Background', 
                    button: { text: 'Use Image' }, 
                    multiple: false 
                });
                
                // Handle image selection
                pfb_section_frame.on('select', function() {
                    let attachment = pfb_section_frame.state().get('selection').first().toJSON();
                    
                    // Assign image URL to the corresponding input field
                    $('#pfb_section_bg_url').val(attachment.url);
                    
                    // Auto-close the frame after selection for better UX
                    pfb_section_frame.close(); 
                });

                // Display the media uploader
                pfb_section_frame.open();
            });
        });


        /**
         * 
         *  Handle Section Background Removal
         * 
         */
        jQuery(document).ready(function($) {
            /**
             * Handle Section Background Media Upload
             */
            $(document).on('click', '.pfb-section-bg-upload', function(e) {
                e.preventDefault();
                
                let pfb_section_frame = wp.media({ 
                    title: 'Select Section Background', 
                    button: { text: 'Use Image' }, 
                    multiple: false 
                });
                
                pfb_section_frame.on('select', function() {
                    let attachment = pfb_section_frame.state().get('selection').first().toJSON();
                    
                    // Update input and preview image
                    $('#pfb_section_bg_url').val(attachment.url);
                    $('#pfb_section_bg_preview').attr('src', attachment.url).show();
                    
                    // Append remove button if not exists
                    if (!$('#pfb_remove_section_bg').length) {
                        $('#pfb_section_bg_preview').after('<br><button type="button" class="button button-link-delete" id="pfb_remove_section_bg" style="color:red; text-decoration:none; padding:0; margin-top:5px;">Remove Image</button>');
                    }
                    
                    pfb_section_frame.close(); 
                });

                pfb_section_frame.open();
            });

            /**
             * Handle Section Background Removal
             */
            $(document).on('click', '#pfb_remove_section_bg', function(e) {
                e.preventDefault();
                $('#pfb_section_bg_url').val('');
                $('#pfb_section_bg_preview').hide().attr('src', '');
                $(this).remove();
            });
        });
    </script>
    <style>
        .ui-state-highlight { height: 50px; background: #f0faff; border: 1px dashed #2271b1; margin-bottom: 20px; }
        .pfb-drag-handle:hover { color: #2271b1 !important; }
        .pfb-section-header h3 { font-size: 14px; color: #1d2327; }
        .pfb-file-settings { display: none; }
    </style>
</div>
