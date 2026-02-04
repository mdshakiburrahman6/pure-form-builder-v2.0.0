<?php
if (!defined('ABSPATH')) exit;

function la_licenseauth_dashboard() {

    $message = '';

    /* ===============================
     * HANDLE POST (SECURE)
     * =============================== */
    if (
        isset($_POST['la_license_nonce']) &&
        wp_verify_nonce($_POST['la_license_nonce'], 'la_license_action')
    ) {

        // ACTIVATE
        if (isset($_POST['la_verify_license'])) {

            $license_key = sanitize_text_field($_POST['la_license_key']);

            $api = new LA_LicenseAuth_API();
            $init = $api->init();

            if (is_wp_error($init)) {
                $message = '<div class="notice notice-error"><p>' . esc_html($init->get_error_message()) . '</p></div>';
            } else {
                $verify = $api->verify_license($license_key);

                if (is_wp_error($verify)) {
                    update_option('la_license_status', 'inactive');
                    $message = '<div class="notice notice-error"><p>' . esc_html($verify->get_error_message()) . '</p></div>';
                } else {
                    update_option('la_license_status', 'active');
                    update_option('la_license_key', $license_key);
                    update_option('la_license_domain', parse_url(home_url(), PHP_URL_HOST));

                    $message = '<div class="notice notice-success"><p>✅ License activated successfully.</p></div>';
                }
            }
        }

        // DEACTIVATE (LOCAL)
        if (isset($_POST['la_deactivate'])) {
            delete_option('la_license_status');
            delete_option('la_license_key');
            delete_option('la_license_domain');

            $message = '<div class="notice notice-success"><p>🔓 License deactivated.</p></div>';
        }
    }

    /* ===============================
     * STATE
     * =============================== */
    $license_key = get_option('la_license_key', '');
    $status      = get_option('la_license_status', 'inactive');
    $active      = ($status === 'active');
    $domain      = parse_url(home_url(), PHP_URL_HOST);
    ?>

    <style>
        .la-card{background:#fff;border:1px solid #e1e5ea;border-radius:12px;padding:24px;max-width:820px}
        .la-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .la-badge{padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600}
        .la-active{background:#e7f7ef;color:#0a7a3d}
        .la-inactive{background:#fdecec;color:#a60000}
        .la-info{background:#f6f7f9;padding:14px 18px;border-radius:8px;margin-bottom:18px;font-size:14px}
    </style>

    <div class="wrap">
        <h1>LicenseAuth – License Dashboard</h1>

        <?php echo $message; ?>

        <div class="la-card">
            <div class="la-row">
                <h2>🔐 License Status</h2>
                <span class="la-badge <?php echo $active ? 'la-active' : 'la-inactive'; ?>">
                    <?php echo $active ? 'Active' : 'Inactive'; ?>
                </span>
            </div>

            <div class="la-info">
                <strong>Domain:</strong> <?php echo esc_html($domain); ?><br>
                <strong>License:</strong>
                <?php echo $license_key ? esc_html(substr($license_key, 0, 6) . '•••••') : 'Not set'; ?>
            </div>

            <?php if (!$active): ?>
                <form method="post">
                    <?php wp_nonce_field('la_license_action', 'la_license_nonce'); ?>
                    <input type="text" name="la_license_key" class="regular-text"
                           placeholder="XXXX-XXXX-XXXX-XXXX" required>
                    <p>
                        <button class="button button-primary" name="la_verify_license">
                            Activate License
                        </button>
                    </p>
                </form>
            <?php else: ?>
                <form method="post">
                    <?php wp_nonce_field('la_license_action', 'la_license_nonce'); ?>
                    <button class="button" name="la_deactivate">
                        Deactivate License
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
<?php
}




add_action('admin_head', function () {

    // Only for admins
    if (!current_user_can('manage_options')) return;

    $is_active = get_option('la_license_status') === 'active';

    ?>
    <style>
        <?php if ($is_active): ?>
        /* LICENSE ACTIVE → GREEN */
        #adminmenu a[href*="pfb-license"] {
            color: #00c261 !important;
            font-weight: 700;
        }
        #adminmenu a[href*="pfb-license"]:before {
            color: #00c261 !important;
        }
        <?php else: ?>
        /* LICENSE INACTIVE → RED */
        #adminmenu a[href*="pfb-license"] {
            color: #d63638 !important;
            font-weight: 700;
        }
        #adminmenu a[href*="pfb-license"]:before {
            color: #d63638 !important;
        }
        

        <?php endif; ?>
    </style>
    <?php
});
