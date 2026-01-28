<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pfb_forms");
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$entries = [];

if ($form_id) {
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d ORDER BY created_at DESC",
        $form_id
    ));
}
?>

<div class="wrap">
    <h1>Form Entries</h1>
    <div class="form-header" style="display: flex; justify-content:space-between">
        <form method="get">
            <input type="hidden" name="page" value="pfb-entries">
            <select name="form_id">
                <option value="">Select Form</option>
                <?php foreach ($forms as $form): ?>
                    <option value="<?php echo esc_attr($form->id); ?>" <?php selected($form_id, $form->id); ?>>
                        <?php echo esc_html($form->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button">Filter</button>
        </form>

        <form method="get" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block;">
            <input type="hidden" name="action" value="pfb_export_entries">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <button class="button button-primary">Export CSV</button>
        </form>
    </div>

    <?php if ($form_id && $entries): ?>
        <table class="widefat striped" style="margin-top:20px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->id); ?></td>
                        <td><?php echo $entry->user_id ? esc_html(get_user_by('id', $entry->user_id)->display_name ?? 'User') : 'Guest'; ?></td>
                        <td><?php echo esc_html($entry->created_at); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=pfb-entry-view&entry_id=' . $entry->id); ?>">View</a> | 
                            <a href="<?php echo admin_url('admin.php?page=pfb-entry-edit&entry_id=' . $entry->id); ?>">Edit</a> | 
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pfb_delete_entry&entry_id=' . $entry->id . '&form_id=' . $form_id), 'pfb_delete_entry_' . $entry->id); ?>" 
                               style="color:red;" onclick="return confirm('Delete this entry?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($form_id): ?>
        <p>No entries found.</p>
    <?php endif; ?>
</div>
<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="notice notice-success is-dismissible">
        <p>Entry deleted successfully!</p>
    </div>
<?php endif; ?>