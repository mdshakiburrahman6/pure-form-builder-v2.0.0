<?php
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

$all_fields = $wpdb->get_results($wpdb->prepare("SELECT name, label, type, options FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d", $form_id));
?>

<div class="wrap">
    <h1><?php echo $form_id ? 'Edit Form' : 'Create New Form'; ?></h1>

    <?php if (isset($_GET['saved'])): ?><div class="notice notice-success"><p>Form saved successfully!</p></div><?php endif; ?>
    <?php if (isset($_GET['field_added'])): ?><div class="notice notice-success"><p>Field added successfully!</p></div><?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_action', 'pfb_nonce'); ?>
        <input type="hidden" name="action" value="pfb_save_form">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <table class="form-table">
            <tr>
                <th>Form Name</th>
                <td><input type="text" name="form_name" class="regular-text" value="<?php echo esc_attr($form_name); ?>" required></td>
            </tr>
        </table>
        <?php submit_button($form_id ? 'Update Form' : 'Save Form'); ?>
    </form>

    <?php if ($form_id): ?>
        <hr>
        <h2>Form Structure (V2 Nested)</h2>
        <div class="pfb-form-structure">
            <div style="text-align:right; margin-bottom:20px;">
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
                    <div class="pfb-section-card" style="border: 1px solid #ccd0d4; margin-bottom: 25px; background: #fff; border-radius: 4px;">
                        <div class="pfb-section-header" style="display: flex; justify-content: space-between; align-items: center; background: #f6f7f7; padding: 10px 15px; border-bottom: 1px solid #ccd0d4;">
                            <h3 style="margin:0;">Section: <?php echo esc_html($section->label); ?></h3>
                            <div>
                                <a href="?page=pfb-builder&form_id=<?php echo $form_id; ?>&edit_field=<?php echo $section->id; ?>" class="button button-small">Edit Header</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pfb_delete_field&field_id=' . $section->id . '&form_id=' . $form_id), 'pfb_delete_field_' . $section->id); ?>" 
                                   class="button button-small" style="color:red;" onclick="return confirm('Delete this section?');">Delete</a>
                            </div>
                        </div>
                        <div class="pfb-section-body" style="padding: 15px;">
                            <table class="widefat striped" style="border:none; box-shadow:none;">
                                <thead>
                                    <tr>
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
                                            <tr>
                                                <td><?php echo esc_html($f->label); ?></td>
                                                <td><span class="tag"><?php echo esc_html($f->type); ?></span></td>
                                                <td><code><?php echo esc_html($f->name); ?></code></td>
                                                <td>
                                                    <a href="?page=pfb-builder&form_id=<?php echo $form_id; ?>&edit_field=<?php echo $f->id; ?>">Edit</a>
                                                    | 
                                                    <a href="<?php echo wp_nonce_url(
                                                        admin_url('admin-post.php?action=pfb_delete_field&field_id=' . $f->id . '&form_id=' . $form_id), 
                                                        'pfb_delete_field_' . $f->id
                                                    ); ?>" 
                                                    style="color:#d63638;" 
                                                    onclick="return confirm('Are you sure you want to delete this field?');">
                                                        Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else :
                                        echo '<tr><td colspan="4" style="text-align:center; padding:20px; color:#666;">No fields added to this section yet.</td></tr>';
                                    endif; ?>
                                </tbody>
                            </table>
                            <div style="margin-top:10px; text-align:right;">
                                <button type="button" class="button pfb-add-field-to-section" data-section-id="<?php echo $section->id; ?>">+ Add Field here</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: echo '<p>No sections found.</p>'; endif; ?>
            </div>
        </div>

        <hr>
        <h2><?php echo $edit_field ? 'Edit Field' : 'Add Field'; ?></h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="pfb-field-form">
            <?php wp_nonce_field('pfb_add_field_action', 'pfb_field_nonce'); ?>
            <input type="hidden" name="action" value="pfb_add_field">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="field_id" value="<?php echo esc_attr($edit_field->id ?? 0); ?>">
            <input type="hidden" name="fieldset_id" id="pfb-fieldset-id-input" value="<?php echo esc_attr($edit_field->fieldset_id ?? 0); ?>">

            <table class="form-table">
                <tr>
                    <th>Field Type</th>
                    <td>
                        <select name="field_type" id="pfb-field-type">
                            <option value="text" <?php selected($edit_field->type ?? '', 'text'); ?>>Text</option>
                            <option value="textarea" <?php selected($edit_field->type ?? '', 'textarea'); ?>>Textarea</option>
                            <option value="email" <?php selected($edit_field->type ?? '', 'email'); ?>>Email</option>
                            <option value="number" <?php selected($edit_field->type ?? '', 'number'); ?>>Number</option>
                            <option value="url" <?php selected($edit_field->type ?? '', 'url'); ?>>URL</option>
                            <option value="select" <?php selected($edit_field->type ?? '', 'select'); ?>>Select</option>
                            <option value="radio" <?php selected($edit_field->type ?? '', 'radio'); ?>>Radio</option>
                            <option value="file" <?php selected($edit_field->type ?? '', 'file'); ?>>File</option>
                            <option value="image" <?php selected($edit_field->type ?? '', 'image'); ?>>Image</option>
                            <option value="fieldset" <?php selected($edit_field->type ?? '', 'fieldset'); ?>>Fieldset (Section Header)</option>
                        </select>
                    </td>
                </tr>

                <tr class="pfb-fieldset-only">
                    <th>Section Logic</th>
                    <td>
                        <select name="fieldset_display">
                            <option value="show_always" <?php selected($edit_field->fieldset_display ?? '', 'show_always'); ?>>Always Show</option>
                            <option value="hide_if_empty" <?php selected($edit_field->fieldset_display ?? '', 'hide_if_empty'); ?>>Hide if Empty</option>
                        </select>
                    </td>
                </tr>

                <tr><th>Label</th><td><input type="text" name="field_label" value="<?php echo esc_attr($edit_field->label ?? ''); ?>" required></td></tr>
                <tr><th>Field Name</th><td><input type="text" name="field_name" value="<?php echo esc_attr($edit_field->name ?? ''); ?>" required></td></tr>

                <tr class="pfb-standard-field">
                    <th>Required</th>
                    <td><label><input type="checkbox" name="field_required" <?php checked(!empty($edit_field->required)); ?>> This field is required</label></td>
                </tr>

                <tr class="pfb-field-options-row">
                    <th>Options</th>
                    <td><textarea name="field_options" rows="4"><?php if (!empty($edit_field->options)) echo esc_textarea(implode(', ', json_decode($edit_field->options, true))); ?></textarea></td>
                </tr>

                <tr class="pfb-file-only">
                    <th>Allowed Files</th>
                    <td><input type="text" name="file_types" value="<?php echo esc_attr($edit_field->file_types ?? ''); ?>" placeholder="jpg,png"></td>
                </tr>
                <tr class="pfb-file-only">
                    <th>Max Size (MB)</th>
                    <td><input type="number" step="0.1" name="max_size" value="<?php echo esc_attr($edit_field->max_size ?? ''); ?>"></td>
                </tr>

                <tr class="pfb-standard-field">
                    <th>Conditional Logic</th>
                    <td>
                        <label><input type="checkbox" id="enable_condition" <?php echo (!empty($edit_field->rules)) ? 'checked' : ''; ?>> Enable</label>
                        <div id="condition_builder" style="display:none; margin-top:10px; border-left:3px solid #2271b1; padding-left:15px;">
                            <div id="rule_groups"></div>
                            <button type="button" class="button" id="add_rule_group">+ OR Rule Group</button>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button($edit_field ? 'Update Field' : 'Add Field'); ?>
        </form>
    <?php endif; ?>

    <script>
    // JS Logic for Nested UI ebong Conditional Rules
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('pfb-field-type');
        const fieldsetRows = document.querySelectorAll('.pfb-fieldset-only');
        const standardFields = document.querySelectorAll('.pfb-standard-field');
        const optionsRow = document.querySelector('.pfb-field-options-row');
        const fileRows = document.querySelectorAll('.pfb-file-only');
        const fieldsetIdInput = document.getElementById('pfb-fieldset-id-input');

        function toggleUI() {
            const val = typeSelect.value;
            const isFieldset = (val === 'fieldset');
            const isFile = ['file', 'image'].includes(val);
            const isOptions = ['select', 'radio'].includes(val);

            fieldsetRows.forEach(r => r.style.display = isFieldset ? 'table-row' : 'none');
            standardFields.forEach(r => r.style.display = isFieldset ? 'none' : 'table-row');
            if (optionsRow) optionsRow.style.display = isOptions ? 'table-row' : 'none';
            fileRows.forEach(r => r.style.display = isFile ? 'table-row' : 'none');
        }

        typeSelect.addEventListener('change', toggleUI);
        toggleUI();

        // Section/Field Trigger
        document.getElementById('pfb-add-section-trigger')?.addEventListener('click', () => {
            typeSelect.value = 'fieldset';
            fieldsetIdInput.value = '0';
            toggleUI();
            document.getElementById('pfb-field-form').scrollIntoView();
        });

        document.querySelectorAll('.pfb-add-field-to-section').forEach(btn => {
            btn.addEventListener('click', function() {
                fieldsetIdInput.value = this.dataset.sectionId;
                if (typeSelect.value === 'fieldset') typeSelect.value = 'text';
                toggleUI();
                document.getElementById('pfb-field-form').scrollIntoView();
            });
        });
    });
    </script>
</div> 