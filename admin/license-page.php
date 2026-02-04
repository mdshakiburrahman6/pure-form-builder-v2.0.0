<?php
if (!defined('ABSPATH')) exit;

$status = get_option('pfb_license_status', 'inactive');
$key    = get_option('pfb_license_key', '');
$domain = site_url();
?>

<div class="wrap">
    <h1>🔐 License Dashboard</h1>

    <div style="background:#fff;padding:20px;border-radius:8px;max-width:600px;">
        <p><strong>Status:</strong>
            <?php if ($status === 'active'): ?>
                <span style="color:green;font-weight:bold;">Active</span>
            <?php else: ?>
                <span style="color:red;font-weight:bold;">Inactive</span>
            <?php endif; ?>
        </p>

        <p><strong>Domain:</strong> <?php echo esc_html($domain); ?></p>

        <form method="post">
            <?php wp_nonce_field('pfb_license_action'); ?>

            <input type="text" name="pfb_license_key"
                   placeholder="Enter License Key"
                   value="<?php echo esc_attr($key); ?>"
                   style="width:100%;padding:8px;">

            <br><br>

            <?php if ($status !== 'active'): ?>
                <button class="button button-primary" name="pfb_activate_license">
                    Activate License
                </button>
            <?php else: ?>
                <button class="button" name="pfb_deactivate_license">
                    Deactivate License
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>
