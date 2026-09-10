<?php
if (!defined('ABSPATH')) exit;

/**
 * CENTRALIZED IDENTIFICATION CODE & SERIAL NUMBER ENGINE (EESS)
 * Handles atomic, non-reusable numbering for Institutions, Employees, and Students.
 */
class EESS_ID_Code_Service {

    /**
     * Ensures eess_id_counters table exists
     */
    public static function ensure_counters_table_exists() {
        global $wpdb;
        $table_name = "{$wpdb->prefix}eess_id_counters";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                institution_id bigint(20) NOT NULL,
                counter_type varchar(50) NOT NULL,
                last_sequence bigint(20) DEFAULT 0 NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY inst_counter (institution_id, counter_type)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Retrieves permanent numeric Institution Code
     */
    public static function get_institution_code($inst_id) {
        global $wpdb;
        $inst_id = intval($inst_id);
        if ($inst_id <= 0) $inst_id = 1;

        $code = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $inst_id));
        if (!$code) {
            $code = $inst_id;
        }
        return intval($code);
    }

    /**
     * Atomically increments counter sequence for an institution
     */
    private static function get_next_sequence($inst_id, $counter_type) {
        global $wpdb;
        self::ensure_counters_table_exists();
        $inst_id = intval($inst_id);
        if ($inst_id <= 0) $inst_id = 1;

        $table = "{$wpdb->prefix}eess_id_counters";

        // Insert ON DUPLICATE KEY UPDATE atomic sequence increment
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (institution_id, counter_type, last_sequence)
             VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1",
            $inst_id,
            $counter_type
        ));

        // Fetch incremented sequence
        $seq = $wpdb->get_var($wpdb->prepare(
            "SELECT last_sequence FROM $table WHERE institution_id = %d AND counter_type = %s",
            $inst_id,
            $counter_type
        ));

        return intval($seq);
    }

    /**
     * Generates a unique Employee Code: [Institution Code] + 3-digit sequence (e.g. Inst 2 -> 2001, 2002)
     */
    public static function generate_employee_code($inst_id = 1) {
        global $wpdb;
        $inst_code = self::get_institution_code($inst_id);

        do {
            $seq = self::get_next_sequence($inst_id, 'employee');
            $code = sprintf("%d%03d", $inst_code, $seq);

            // Double-check uniqueness in user_meta
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'eess_employee_number' AND meta_value = %s LIMIT 1",
                $code
            ));
        } while (!empty($exists));

        return $code;
    }

    /**
     * Generates a unique Student Code: [Institution Code] + 4-digit sequence (e.g. Inst 2 -> 20001, 20002)
     */
    public static function generate_student_code($inst_id = 1) {
        global $wpdb;
        $inst_code = self::get_institution_code($inst_id);

        do {
            $seq = self::get_next_sequence($inst_id, 'student');
            $code = sprintf("%d%04d", $inst_code, $seq);

            // Double-check uniqueness in sm_students
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_students WHERE student_id = %s LIMIT 1",
                $code
            ));
        } while (!empty($exists));

        return $code;
    }
}
