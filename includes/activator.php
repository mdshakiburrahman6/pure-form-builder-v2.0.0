<?php
/**
 * includes/activator.php
 * Final Optimized Version: Strict SQL Syntax with Full 3-Tab Support
 */

if (!defined('ABSPATH')) exit;

function pfb_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 1. Forms table: Updated with prefixed columns for View, Edit, and Submit tabs
    $table_forms = $wpdb->prefix . 'pfb_forms';
    $sql_forms = "CREATE TABLE $table_forms (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        access_type varchar(50) DEFAULT 'all',
        allowed_roles text NULL,
        allow_user_edit tinyint(1) DEFAULT 0,
        form_bg_image text NULL,
        view_column_layout varchar(20) DEFAULT '1-col',
        view_form_padding int(11) DEFAULT 25,
        view_header_gap int(11) DEFAULT 15,
        view_field_spacing int(11) DEFAULT 20,
        view_input_bg_color varchar(20) DEFAULT '#ffffff',
        view_heading_font_size int(11) DEFAULT 22,
        view_heading_font_weight int(11) DEFAULT 600,
        view_heading_color varchar(20) DEFAULT '#2271b1',
        view_heading_align varchar(20) DEFAULT 'left',
        view_label_font_size int(11) DEFAULT 14,
        view_label_font_weight int(11) DEFAULT 600,
        view_label_color varchar(20) DEFAULT '#333333',
        view_label_align varchar(20) DEFAULT 'left',
        view_text_font_size int(11) DEFAULT 14,
        view_text_font_weight int(11) DEFAULT 400,
        view_text_color varchar(20) DEFAULT '#000000',
        view_text_align varchar(20) DEFAULT 'left',
        view_submit_btn_text varchar(100) DEFAULT 'Edit Profile',
        view_submit_btn_bg varchar(20) DEFAULT '#2271b1',
        view_submit_btn_clr varchar(20) DEFAULT '#ffffff',
        view_submit_btn_radius int(11) DEFAULT 6,
        view_submit_btn_align varchar(20) DEFAULT 'flex-start',
        view_cancel_btn_text varchar(100) DEFAULT 'Back',
        view_cancel_btn_bg varchar(20) DEFAULT '#eeeeee',
        view_cancel_btn_clr varchar(20) DEFAULT '#333333',
        view_cancel_btn_radius int(11) DEFAULT 6,
        view_cancel_btn_align varchar(20) DEFAULT 'flex-start',
        edit_column_layout varchar(20) DEFAULT '1-col',
        edit_form_padding int(11) DEFAULT 25,
        edit_header_gap int(11) DEFAULT 15,
        edit_field_spacing int(11) DEFAULT 20,
        edit_input_bg_color varchar(20) DEFAULT '#ffffff',
        edit_heading_font_size int(11) DEFAULT 22,
        edit_heading_font_weight int(11) DEFAULT 600,
        edit_heading_color varchar(20) DEFAULT '#2271b1',
        edit_heading_align varchar(20) DEFAULT 'left',
        edit_label_font_size int(11) DEFAULT 14,
        edit_label_font_weight int(11) DEFAULT 600,
        edit_label_color varchar(20) DEFAULT '#333333',
        edit_label_align varchar(20) DEFAULT 'left',
        edit_text_font_size int(11) DEFAULT 14,
        edit_text_font_weight int(11) DEFAULT 400,
        edit_text_color varchar(20) DEFAULT '#000000',
        edit_text_align varchar(20) DEFAULT 'left',
        edit_submit_btn_text varchar(100) DEFAULT 'Update',
        edit_submit_btn_bg varchar(20) DEFAULT '#2271b1',
        edit_submit_btn_clr varchar(20) DEFAULT '#ffffff',
        edit_submit_btn_radius int(11) DEFAULT 6,
        edit_submit_btn_align varchar(20) DEFAULT 'flex-start',
        edit_cancel_btn_text varchar(100) DEFAULT 'Cancel',
        edit_cancel_btn_bg varchar(20) DEFAULT '#eeeeee',
        edit_cancel_btn_clr varchar(20) DEFAULT '#333333',
        edit_cancel_btn_radius int(11) DEFAULT 6,
        edit_cancel_btn_align varchar(20) DEFAULT 'flex-start',
        submit_column_layout varchar(20) DEFAULT '1-col',
        submit_form_padding int(11) DEFAULT 25,
        submit_header_gap int(11) DEFAULT 15,
        submit_field_spacing int(11) DEFAULT 20,
        submit_input_bg_color varchar(20) DEFAULT '#ffffff',
        submit_heading_font_size int(11) DEFAULT 22,
        submit_heading_font_weight int(11) DEFAULT 600,
        submit_heading_color varchar(20) DEFAULT '#2271b1',
        submit_heading_align varchar(20) DEFAULT 'left',
        submit_label_font_size int(11) DEFAULT 14,
        submit_label_font_weight int(11) DEFAULT 600,
        submit_label_color varchar(20) DEFAULT '#333333',
        submit_label_align varchar(20) DEFAULT 'left',
        submit_text_font_size int(11) DEFAULT 14,
        submit_text_font_weight int(11) DEFAULT 400,
        submit_text_color varchar(20) DEFAULT '#000000',
        submit_text_align varchar(20) DEFAULT 'left',
        submit_submit_btn_text varchar(100) DEFAULT 'Submit',
        submit_submit_btn_bg varchar(20) DEFAULT '#2271b1',
        submit_submit_btn_clr varchar(20) DEFAULT '#ffffff',
        submit_submit_btn_radius int(11) DEFAULT 6,
        submit_submit_btn_align varchar(20) DEFAULT 'flex-start',
        submit_cancel_btn_text varchar(100) DEFAULT 'Cancel',
        submit_cancel_btn_bg varchar(20) DEFAULT '#eeeeee',
        submit_cancel_btn_clr varchar(20) DEFAULT '#333333',
        submit_cancel_btn_radius int(11) DEFAULT 6,
        submit_cancel_btn_align varchar(20) DEFAULT 'flex-start',
        view_image_preview_width int(11) DEFAULT 100,
        edit_image_preview_width int(11) DEFAULT 100,
        submit_image_preview_width int(11) DEFAULT 100,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_forms);

    // 2. Fields table, 3. Entries table, 4. Entry meta table remains same as your code
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
        section_bg_image text NULL,
        section_bg_opacity float DEFAULT 1.0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_fields);

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