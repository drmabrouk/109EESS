<?php
if (!defined('ABSPATH')) exit;

/**
 * CENTRALIZED IDENTIFICATION CODE & SERIAL NUMBER ENGINE (EESS)
 * Handles atomic, non-reusable numbering for Institutions, Employees, and Students.
 */
class EESS_ID_Code_Service {

    /**
     * Get Central Numbering Configuration
     */
    public static function get_numbering_config() {
        $default = array(
            'student_prefix'   => '',
            'student_digits'   => 4,
            'student_format'   => '{inst_code}{seq}',
            'employee_prefix'  => '',
            'employee_digits'  => 3,
            'employee_format'  => '{inst_code}{seq}'
        );
        return wp_parse_args(get_option('eess_central_numbering_config', array()), $default);
    }

    /**
     * Save Central Numbering Configuration
     */
    public static function save_numbering_config($data) {
        $clean = array(
            'student_prefix'  => sanitize_text_field($data['student_prefix'] ?? ''),
            'student_digits'  => max(3, min(8, intval($data['student_digits'] ?? 4))),
            'student_format'  => sanitize_text_field($data['student_format'] ?? '{inst_code}{seq}'),
            'employee_prefix' => sanitize_text_field($data['employee_prefix'] ?? ''),
            'employee_digits' => max(2, min(6, intval($data['employee_digits'] ?? 3))),
            'employee_format' => sanitize_text_field($data['employee_format'] ?? '{inst_code}{seq}')
        );
        update_option('eess_central_numbering_config', $clean);
        return $clean;
    }

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
     * Retrieves permanent numeric Institution Code dynamically from Org Structure
     */
    public static function get_institution_code($inst_id) {
        global $wpdb;
        $inst_id = intval($inst_id);
        if ($inst_id <= 0) $inst_id = 1;

        // Try lookup in eess_institutions first
        $code = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}eess_institutions WHERE id = %d AND status = 'active'", $inst_id));
        if (!$code) {
            // Try lookup as school ID in eess_schools
            $school_rec = $wpdb->get_row($wpdb->prepare("SELECT school_code, institution_id FROM {$wpdb->prefix}eess_schools WHERE id = %d", $inst_id));
            if ($school_rec) {
                if (!empty($school_rec->school_code)) {
                    return intval($school_rec->school_code);
                } elseif (!empty($school_rec->institution_id)) {
                    $inst_code = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $school_rec->institution_id));
                    if ($inst_code) return intval($inst_code);
                }
            }
            $code = $inst_id;
        }
        return intval($code);
    }

    /**
     * Atomically increments counter sequence for an institution
     */
    public static function get_next_sequence($inst_id, $counter_type, $set_val = null) {
        global $wpdb;
        self::ensure_counters_table_exists();
        $inst_id = intval($inst_id);
        if ($inst_id <= 0) $inst_id = 1;

        $table = "{$wpdb->prefix}eess_id_counters";

        if ($set_val !== null) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table (institution_id, counter_type, last_sequence)
                 VALUES (%d, %s, %d)
                 ON DUPLICATE KEY UPDATE last_sequence = %d",
                $inst_id, $counter_type, intval($set_val), intval($set_val)
            ));
            return intval($set_val);
        }

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (institution_id, counter_type, last_sequence)
             VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1",
            $inst_id,
            $counter_type
        ));

        $seq = $wpdb->get_var($wpdb->prepare(
            "SELECT last_sequence FROM $table WHERE institution_id = %d AND counter_type = %s",
            $inst_id,
            $counter_type
        ));

        return intval($seq);
    }

    /**
     * Generates a unique Employee Code
     */
    public static function generate_employee_code($inst_id = 1) {
        global $wpdb;
        $inst_code = self::get_institution_code($inst_id);
        $config = self::get_numbering_config();

        do {
            $seq = self::get_next_sequence($inst_id, 'employee');
            $seq_str = sprintf("%0" . $config['employee_digits'] . "d", $seq);

            $code = $config['employee_prefix'] . str_replace(
                array('{inst_code}', '{seq}', '{year}'),
                array($inst_code, $seq_str, date('Y')),
                $config['employee_format']
            );

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'eess_employee_number' AND meta_value = %s LIMIT 1",
                $code
            ));
        } while (!empty($exists));

        return $code;
    }

    /**
     * Generates a unique Student Code based on System Administrator Central Configuration
     */
    public static function generate_student_code($inst_id = 1) {
        global $wpdb;
        $inst_code = self::get_institution_code($inst_id);
        $config = self::get_numbering_config();

        do {
            $seq = self::get_next_sequence($inst_id, 'student');
            $seq_str = sprintf("%0" . $config['student_digits'] . "d", $seq);

            $code = $config['student_prefix'] . str_replace(
                array('{inst_code}', '{seq}', '{year}'),
                array($inst_code, $seq_str, date('Y')),
                $config['student_format']
            );

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_students WHERE student_id = %s OR student_code = %s LIMIT 1",
                $code, $code
            ));
        } while (!empty($exists));

        return $code;
    }
}
