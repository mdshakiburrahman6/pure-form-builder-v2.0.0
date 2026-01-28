<?php
if (!defined('ABSPATH')) exit;

function pfb_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 1. Forms table: Updated with Design and Access Control Columns
    $table_forms = $wpdb->prefix . 'pfb_forms';
    $sql_forms = "CREATE TABLE $table_forms (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(200),
        access_type varchar(50) DEFAULT 'all',
        allowed_roles text NULL,
        redirect_type varchar(50) DEFAULT 'message',
        redirect_page int(11) DEFAULT 0,
        allow_user_edit tinyint(1) DEFAULT 0,
        primary_color varchar(20) DEFAULT '#2271b1',
        button_text_color varchar(20) DEFAULT '#ffffff',
        form_padding int(11) DEFAULT 25,
        border_radius int(11) DEFAULT 8,
        field_spacing int(11) DEFAULT 20,
        legend_font_size int(11) DEFAULT 20,
        label_color varchar(20) DEFAULT '#333333',
        input_bg_color varchar(20) DEFAULT '#ffffff',
        form_bg_image text NULL,
        column_layout varchar(20) DEFAULT '1-col',
        button_text varchar(100) DEFAULT 'Submit',
        text_align varchar(20) DEFAULT 'left',
        button_width varchar(20) DEFAULT 'auto',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_forms);

    // 2. Fields table: Updated for V2 Nested Sections and Conditional Logic
    $table_fields = $wpdb->prefix . 'pfb_fields';
    $sql_fields = "CREATE TABLE $table_fields (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_id bigint(20) NOT NULL,
        type varchar(50) NOT NULL,
        label varchar(255) NOT NULL,
        name varchar(255) NOT NULL,
        options longtext NULL,
        rules longtext NULL,
        required tinyint(1) DEFAULT 0,
        is_fieldset tinyint(1) NOT NULL DEFAULT 0,
        fieldset_id bigint(20) DEFAULT 0,
        fieldset_display varchar(50) DEFAULT 'show_always',
        file_types varchar(255) NULL,
        max_size float DEFAULT 0,
        min_size float DEFAULT 0,
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_fields);

    // 3. Entries table
    $table_entries = $wpdb->prefix . 'pfb_entries';
    $sql_entries = "CREATE TABLE $table_entries (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_id bigint(20),
        user_id bigint(20),
        allow_update tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_entries);

    // 4. Entry meta table
    $table_meta = $wpdb->prefix . 'pfb_entry_meta';
    $sql_meta = "CREATE TABLE $table_meta (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        entry_id bigint(20),
        field_name varchar(255),
        field_value longtext NULL,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_meta);
}