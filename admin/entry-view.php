<?php
/**
 * admin/entry-edit.php
 * Fixed: Added Nested Section Grouping (V2) for Entry View
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$entry_id = intval($_GET['entry_id'] ?? 0);
if (!$entry_id) {
    echo '<div class="notice notice-error"><p>Invalid entry ID</p></div>';
    return;
}

$entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_entries WHERE id=%d", $entry_id));
if (!$entry) {
    echo '<div class="notice notice-error"><p>Entry not found</p></div>';
    return;
}

// 1. Fetch Section Headers (Fieldsets)
$fieldsets = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC, id ASC",
    $entry->form_id
));

// 2. Fetch Entry Meta values
$meta_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d",
    $entry_id
));
$meta_map = [];
foreach ($meta_rows as $m) { $meta_map[$m->field_name] = $m->field_value; }
?>

<div class="wrap">
    <h1 style="margin-bottom:20px">Entry Details — ID: <?php echo $entry_id; ?></h1>

    <div class="pfb-admin-entry-container" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        
        <?php foreach ($fieldsets as $section) : 
            // 3. Fetch fields for this section
            $section_fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
                $section->id
            ));

            // Logic: Section e data thakle-i header dekhabe
            $has_data = false;
            foreach ($section_fields as $sf) { if (!empty($meta_map[$sf->name])) { $has_data = true; break; } }
            if (!$has_data) continue;
        ?>

            <div class="pfb-admin-section-header" style="background: #f1f1f1; padding: 10px 15px; margin-top: 20px; border-left: 4px solid #2271b1; font-weight: bold;">
                <?php echo esc_html($section->label); ?>
            </div>

            <div class="pfb-admin-fields-list">
                <?php foreach ($section_fields as $f) : 
                    $val = $meta_map[$f->name] ?? '';
                    if (empty($val)) continue;
                ?>
                    <div class="pfb-field-card" style="display: flex; border-bottom: 1px solid #eee; padding: 15px 0;">
                        <div class="pfb-field-label" style="width: 250px; font-weight: 600; color: #555;">
                            <?php echo esc_html($f->label); ?>
                        </div>
                        <div class="pfb-field-value" style="flex: 1;">
                            <?php if ($f->type === 'image' || $f->type === 'file'): ?>
                                <div class="pfb-image-box">
                                    <img src="<?php echo esc_url($val); ?>" style="max-width: 150px; border-radius: 5px; border: 1px solid #ddd; display: block; margin-bottom: 10px;">
                                    <a class="button" href="<?php echo esc_url($val); ?>" download>⬇ Download</a>
                                </div>
                            <?php else: ?>
                                <?php echo esc_html($val); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endforeach; ?>

    </div>

    <p style="margin-top:30px;">
        <a class="button button-primary button-large" href="<?php echo admin_url('admin.php?page=pfb-entry-edit&entry_id=' . $entry_id); ?>">
           ✏️ Edit Entry
        </a>
        <a class="button button-secondary button-large" href="<?php echo admin_url('admin.php?page=pfb-entries'); ?>" style="margin-left: 10px;">
           &larr; Back to List
        </a>
    </p>
</div>