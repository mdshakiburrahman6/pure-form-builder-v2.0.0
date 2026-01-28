<?php
/**
 * admin/form-builder.php
 * Final Version: Drag-and-Drop Sorting & Enhanced UI
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

// All fields list for conditional logic builder
$all_fields = $wpdb->get_results($wpdb->prepare("SELECT name, label FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d AND is_fieldset = 0", $form_id));
?>

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
            <?php endforeach; else: echo '<p>No sections found. Start by adding a Section Header.</p>'; endif; ?>
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
                            <option value="number" <?php selected($edit_field->type ?? '', 'number'); ?>>Number</option>
                            <option value="select" <?php selected($edit_field->type ?? '', 'select'); ?>>Dropdown (Select)</option>
                            <option value="radio" <?php selected($edit_field->type ?? '', 'radio'); ?>>Radio Buttons</option>
                            <option value="image" <?php selected($edit_field->type ?? '', 'image'); ?>>Image Upload</option>
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

                <tr><th>Label</th><td><input type="text" name="field_label" class="regular-text" value="<?php echo esc_attr($edit_field->label ?? ''); ?>" required></td></tr>
                <tr><th>System Name (ID)</th><td><input type="text" name="field_name" class="regular-text" value="<?php echo esc_attr($edit_field->name ?? ''); ?>" required <?php echo $edit_field ? 'readonly' : ''; ?>></td></tr>

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
        }
        typeSelect.on('change', toggleUI);
        toggleUI();

        // SORTABLE LOGIC
        $(".pfb-sections-list").sortable({
            handle: ".pfb-section-header",
            placeholder: "ui-state-highlight",
            forcePlaceholderSize: true,
            update: saveOrder
        });

        $(".pfb-fields-table tbody").sortable({
            items: "tr:not(.no-fields)",
            handle: ".pfb-drag-handle",
            placeholder: "ui-state-highlight",
            connectWith: ".pfb-fields-table tbody",
            update: saveOrder
        });

        function saveOrder() {
            let order = [];
            $(".pfb-section-card").each(function() {
                order.push($(this).data('id'));
                $(this).find('tbody tr[data-id]').each(function() {
                    order.push($(this).data('id'));
                });
            });

            $.post(ajaxurl, {
                action: 'pfb_update_field_order',
                order: order
            }, function(res) {
                if(res.success) console.log("Layout saved");
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
                    IF <select name="rules[${groupIndex}][rules][${ruleIndex}][field]">${fieldOptions}</select>
                    <select name="rules[${groupIndex}][rules][${ruleIndex}][operator]">
                        <option value="is" ${ruleData?.operator === 'is' ? 'selected' : ''}>is</option>
                        <option value="is_not" ${ruleData?.operator === 'is_not' ? 'selected' : ''}>is not</option>
                    </select>
                    <input type="text" name="rules[${groupIndex}][rules][${ruleIndex}][value]" value="${ruleData?.value || ''}" placeholder="value">
                    <button type="button" class="remove-rule" style="color:red; border:none; background:none; cursor:pointer;">&times;</button>
                </div>`;
            container.append(html);
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
    <style>
        .ui-state-highlight { height: 50px; background: #f0faff; border: 1px dashed #2271b1; margin-bottom: 20px; }
        .pfb-drag-handle:hover { color: #2271b1 !important; }
        .pfb-section-header h3 { font-size: 14px; color: #1d2327; }
    </style>
</div> 