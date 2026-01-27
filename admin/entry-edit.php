<?php
/**
 * admin/entry-edit.php
 * Final Fixed Version: Includes Section Headers (Fieldsets) in Admin Edit
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Security check 
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to edit this entry.');
}

/* =========================
   GET ENTRY AND FORM DATA
========================= */
$entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;
if (!$entry_id) wp_die('Invalid Entry ID');

$entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_entries WHERE id = %d", $entry_id));
if (!$entry) wp_die('Entry not found');

$form_id = $entry->form_id;

/* =========================
   GET ENTRY META
========================= */
$meta_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry_id));
$meta = [];
foreach ($meta_rows as $m) {
    $meta[$m->field_name] = $m->field_value;
}

?>

<?php if (isset($_GET['updated'])): ?>
<div class="notice notice-success is-dismissible">
    <p>Entry updated successfully!</p>
</div>
<?php endif; ?>

<div class="wrap">
    <h1>Edit Entry</h1>

    <p><strong>Entry ID:</strong> <?php echo esc_html($entry_id); ?> | <strong>User:</strong> <?php echo esc_html($entry->user_id ?: 'Guest'); ?> | <strong>Date:</strong> <?php echo esc_html($entry->created_at); ?></p>

    <hr>

    <form method="post" class="pfb-admin-form" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">

        <?php wp_nonce_field('pfb_update_entry', 'pfb_nonce'); ?>

        <input type="hidden" name="action" value="pfb_update_entry">
        <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>">

        <table class="form-table">
            <?php 
            // 🔥 STEP 1: Fetch Fieldsets (Sections)
            $fieldsets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC, id ASC",
                $form_id
            ));

            if ($fieldsets) :
                foreach ($fieldsets as $section) : 
                    // Show Section Header as a Row
                    echo '<tr style="background: #f6f7f7;"><th colspan="2"><h3 style="margin:0; padding:10px 0; color:#2271b1;">' . esc_html($section->label) . '</h3></th></tr>';

                    // 🔥 STEP 2: Fetch input fields for this specific section
                    $section_fields = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
                        $section->id
                    ));

                    foreach ($section_fields as $field) : 
                        $value = $meta[$field->name] ?? '';
                        $value = is_string($value) ? trim($value) : $value;
            ?>
                        <tr class="pfb-field" <?php if (!empty($field->rules)) echo 'data-rules="' . esc_attr($field->rules) . '"'; ?>>
                            <th>
                                <label><?php echo esc_html($field->label); ?></label>
                            </th>
                            <td>
                                <?php switch ($field->type):
                                    case 'text':
                                    case 'number':
                                    case 'email':
                                    case 'url': ?>
                                        <input type="<?php echo esc_attr($field->type); ?>" name="fields[<?php echo esc_attr($field->name); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                    <?php break; ?>

                                    <?php case 'textarea': ?>
                                        <textarea name="fields[<?php echo esc_attr($field->name); ?>]" rows="4" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                                    <?php break; ?>

                                    <?php case 'select':
                                        $options = json_decode($field->options, true) ?: []; ?>
                                        <select name="fields[<?php echo esc_attr($field->name); ?>]">
                                            <option value="">Select</option>
                                            <?php foreach ($options as $opt): ?>
                                                <option value="<?php echo esc_attr($opt); ?>" <?php selected(strtolower(trim($value)), strtolower(trim($opt))); ?>><?php echo esc_html($opt); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php break; ?>

                                    <?php case 'radio':
                                        $options = json_decode($field->options, true) ?: [];
                                        foreach ($options as $opt): ?>
                                            <label><input type="radio" name="fields[<?php echo esc_attr($field->name); ?>]" value="<?php echo esc_attr($opt); ?>" <?php checked($value, $opt); ?>> <?php echo esc_html($opt); ?></label><br>
                                        <?php endforeach; break; ?>

                                    <?php case 'image': ?>
                                        <?php if ($value): ?>
                                            <div style="margin-bottom:10px;">
                                                <img src="<?php echo esc_url($value); ?>" style="max-width:150px; border-radius:8px; border:1px solid #ddd; display:block;">
                                                <label><input type="checkbox" name="delete_image[]" value="<?php echo esc_attr($field->name); ?>"> Remove image</label>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="<?php echo esc_attr($field->name); ?>">
                                    <?php break; ?>

                                    <?php default: ?>
                                        <input type="text" name="fields[<?php echo esc_attr($field->name); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                <?php endswitch; ?>
                            </td>
                        </tr>
            <?php 
                    endforeach; // End fields loop
                endforeach; // End fieldsets loop
            else :
                echo '<tr><td colspan="2">No fields found for this form structure.</td></tr>';
            endif;
            ?>
        </table>

        <?php submit_button('Update Entry'); ?>

    </form>

    <a href="<?php echo admin_url('admin.php?page=pfb-entries'); ?>">
        &larr; Back to Entries
    </a>
</div>

<script>
/**
 * Admin Conditional Logic Handler
 */
function getFormData(form) {
    const data = {};
    form.querySelectorAll('[name^="fields["]').forEach(el => {
        const fieldName = el.name.replace(/^fields\[|\]$/g, '');
        if (el.type === 'radio') {
            if (el.checked) data[fieldName] = el.value;
        } else {
            data[fieldName] = el.value;
        }
    });
    return data;
}

function evaluateRules(ruleGroups, formData) {
    return ruleGroups.some(group => {
        return group.rules.every(rule => {
            let currentValue = String(formData[rule.field] ?? '').toLowerCase().trim();
            const ruleValue = String(rule.value).toLowerCase().trim();
            if (currentValue === '') return false;
            return (rule.operator === 'is') ? (currentValue === ruleValue) : (currentValue !== ruleValue);
        });
    });
}

function applyAdminConditions() {
    const form = document.querySelector('.pfb-admin-form');
    if (!form) return;
    const formData = getFormData(form);

    form.querySelectorAll('.pfb-field').forEach(field => {
        if (!field.dataset.rules) return;
        const show = evaluateRules(JSON.parse(field.dataset.rules), formData);
        field.style.display = show ? '' : 'none';
        field.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !show);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(applyAdminConditions, 50);
    document.querySelector('.pfb-admin-form')?.addEventListener('change', applyAdminConditions);
});
</script>