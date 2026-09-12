<?php
if (!defined('ABSPATH')) exit;

class EESS_Org_Helper {

    private static $cache = array();

    public static function flush_cache() {
        self::$cache = array();
    }

    /**
     * Seeds initial institutions and schools if none exist
     */
    /**
     * Canonical Role-to-Department Mapping Matrix
     */
    public static function get_role_department_mapping() {
        return array(
            'sm_teacher'                   => 'الأقسام الأكاديمية - المواد الدراسية',
            'sm_coordinator'               => 'الأقسام الأكاديمية - المواد الدراسية',
            'sm_hod'                       => 'الأقسام الأكاديمية - المواد الدراسية',
            'sm_hr'                        => 'إدارة الموارد البشرية (HR)',
            'sm_discipline_supervisor'     => 'شؤون الطلاب والانضباط السلوكي',
            'sm_student'                   => 'شؤون الطلاب والانضباط السلوكي',
            'sm_parent'                    => 'شؤون الطلاب والانضباط السلوكي',
            'sm_activities_supervisor'     => 'الأنشطة المدرسية والفعاليات',
            'sm_finance'                   => 'المالية والحسابات',
            'sm_bus_supervisor'            => 'الخدمات المساندة والنقل',
            'sm_transportation_supervisor' => 'الخدمات المساندة والنقل',
            'sm_clinic'                    => 'العيادة المدرسية والرعاية الصحية',
            'sm_principal'                 => 'الإدارة المدرسية العليا',
            'sm_supervisor'                => 'الإدارة المدرسية العليا',
            'sm_system_admin'              => 'الدعم الفني والتقني',
            'subscriber'                   => 'الدعم الفني والتقني',
            'contributor'                  => 'الدعم الفني والتقني',
            'author'                       => 'الدعم الفني والتقني',
            'editor'                       => 'الدعم الفني والتقني'
        );
    }

    /**
     * Official 19 Central Subjects Registry (Code 1 - 19)
     */
    public static function get_official_subjects() {
        return array(
            1  => array('code' => 1,  'name' => 'التربية الإسلامية',          'dept_code' => 13),
            2  => array('code' => 2,  'name' => 'اللغة العربية',              'dept_code' => 7),
            3  => array('code' => 3,  'name' => 'اللغة الإنجليزية',           'dept_code' => 8),
            4  => array('code' => 4,  'name' => 'الرياضيات',                  'dept_code' => 9),
            5  => array('code' => 5,  'name' => 'العلوم',                     'dept_code' => 11),
            6  => array('code' => 6,  'name' => 'الدراسات الاجتماعية',        'dept_code' => 12),
            7  => array('code' => 7,  'name' => 'التربية الأخلاقية',          'dept_code' => 15),
            8  => array('code' => 8,  'name' => 'الحوسبة والتصميم والابتكار', 'dept_code' => 14),
            9  => array('code' => 9,  'name' => 'الفنون',                     'dept_code' => 16),
            10 => array('code' => 10, 'name' => 'التربية البدنية والصحية',    'dept_code' => 10),
            11 => array('code' => 11, 'name' => 'الفيزياء',                   'dept_code' => 11),
            12 => array('code' => 12, 'name' => 'الكيمياء',                    'dept_code' => 11),
            13 => array('code' => 13, 'name' => 'الأحياء',                    'dept_code' => 11),
            14 => array('code' => 14, 'name' => 'العلوم الصحية',              'dept_code' => 19),
            15 => array('code' => 15, 'name' => 'إدارة الأعمال',              'dept_code' => 25),
            16 => array('code' => 16, 'name' => 'ريادة الأعمال',              'dept_code' => 25),
            17 => array('code' => 17, 'name' => 'اللغات الإضافية',            'dept_code' => 8),
            18 => array('code' => 18, 'name' => 'الإرشاد المهني',             'dept_code' => 17),
            19 => array('code' => 19, 'name' => 'الابتكار',                   'dept_code' => 14)
        );
    }

    /**
     * Official 25 Central Departments Registry (Code 1 - 25)
     */
    public static function get_official_departments() {
        return array(
            1  => array('code' => 1,  'name' => 'الإدارة المدرسية'),
            2  => array('code' => 2,  'name' => 'الشؤون الأكاديمية'),
            3  => array('code' => 3,  'name' => 'شؤون الطلبة'),
            4  => array('code' => 4,  'name' => 'الشؤون الإدارية'),
            5  => array('code' => 5,  'name' => 'الموارد البشرية'),
            6  => array('code' => 6,  'name' => 'الشؤون المالية'),
            7  => array('code' => 7,  'name' => 'قسم اللغة العربية'),
            8  => array('code' => 8,  'name' => 'قسم اللغة الإنجليزية'),
            9  => array('code' => 9,  'name' => 'قسم الرياضيات'),
            10 => array('code' => 10, 'name' => 'قسم التربية البدنية والصحية'),
            11 => array('code' => 11, 'name' => 'قسم العلوم'),
            12 => array('code' => 12, 'name' => 'قسم الدراسات الاجتماعية'),
            13 => array('code' => 13, 'name' => 'قسم التربية الإسلامية'),
            14 => array('code' => 14, 'name' => 'قسم الحوسبة والتصميم والابتكار'),
            15 => array('code' => 15, 'name' => 'قسم التربية الأخلاقية'),
            16 => array('code' => 16, 'name' => 'قسم الفنون'),
            17 => array('code' => 17, 'name' => 'قسم الإرشاد الأكاديمي والمهني'),
            18 => array('code' => 18, 'name' => 'قسم الأنشطة المدرسية'),
            19 => array('code' => 19, 'name' => 'قسم الصحة والسلامة المدرسية'),
            20 => array('code' => 20, 'name' => 'قسم تقنية المعلومات'),
            21 => array('code' => 21, 'name' => 'قسم النقل والمواصلات'),
            22 => array('code' => 22, 'name' => 'قسم الأمن والسلامة'),
            23 => array('code' => 23, 'name' => 'قسم الخدمات الطلابية'),
            24 => array('code' => 24, 'name' => 'قسم المرافق والخدمات العامة'),
            25 => array('code' => 25, 'name' => 'قسم الجودة والتطوير المؤسسي')
        );
    }

    /**
     * Official 12 Central Grades Registry (Code 1 - 12)
     */
    public static function get_official_grades() {
        return array(
            1  => array('code' => 1,  'name' => 'الصف الأول'),
            2  => array('code' => 2,  'name' => 'الصف الثاني'),
            3  => array('code' => 3,  'name' => 'الصف الثالث'),
            4  => array('code' => 4,  'name' => 'الصف الرابع'),
            5  => array('code' => 5,  'name' => 'الصف الخامس'),
            6  => array('code' => 6,  'name' => 'الصف السادس'),
            7  => array('code' => 7,  'name' => 'الصف السابع'),
            8  => array('code' => 8,  'name' => 'الصف الثامن'),
            9  => array('code' => 9,  'name' => 'الصف التاسع'),
            10 => array('code' => 10, 'name' => 'الصف العاشر'),
            11 => array('code' => 11, 'name' => 'الصف الحادي عشر'),
            12 => array('code' => 12, 'name' => 'الصف الثاني عشر')
        );
    }

    /**
     * Official 26 Central Sections Registry (Code 1 - 26)
     */
    public static function get_official_sections() {
        return array(
            1  => array('code' => 1,  'ar' => 'أ',  'en' => 'A'),
            2  => array('code' => 2,  'ar' => 'ب',  'en' => 'B'),
            3  => array('code' => 3,  'ar' => 'ج',  'en' => 'C'),
            4  => array('code' => 4,  'ar' => 'د',  'en' => 'D'),
            5  => array('code' => 5,  'ar' => 'هـ', 'en' => 'E'),
            6  => array('code' => 6,  'ar' => 'و',  'en' => 'F'),
            7  => array('code' => 7,  'ar' => 'ز',  'en' => 'G'),
            8  => array('code' => 8,  'ar' => 'ح',  'en' => 'H'),
            9  => array('code' => 9,  'ar' => 'ط',  'en' => 'I'),
            10 => array('code' => 10, 'ar' => 'ي',  'en' => 'J'),
            11 => array('code' => 11, 'ar' => 'ك',  'en' => 'K'),
            12 => array('code' => 12, 'ar' => 'ل',  'en' => 'L'),
            13 => array('code' => 13, 'ar' => 'م',  'en' => 'M'),
            14 => array('code' => 14, 'ar' => 'ن',  'en' => 'N'),
            15 => array('code' => 15, 'ar' => 'س',  'en' => 'O'),
            16 => array('code' => 16, 'ar' => 'ع',  'en' => 'P'),
            17 => array('code' => 17, 'ar' => 'ف',  'en' => 'Q'),
            18 => array('code' => 18, 'ar' => 'ص',  'en' => 'R'),
            19 => array('code' => 19, 'ar' => 'ق',  'en' => 'S'),
            20 => array('code' => 20, 'ar' => 'ر',  'en' => 'T'),
            21 => array('code' => 21, 'ar' => 'ش',  'en' => 'U'),
            22 => array('code' => 22, 'ar' => 'ت',  'en' => 'V'),
            23 => array('code' => 23, 'ar' => 'ث',  'en' => 'W'),
            24 => array('code' => 24, 'ar' => 'خ',  'en' => 'X'),
            25 => array('code' => 25, 'ar' => 'ذ',  'en' => 'Y'),
            26 => array('code' => 26, 'ar' => 'ض',  'en' => 'Z')
        );
    }

    /**
     * Ensures an Institution has its department structure initialized
     */
    public static function seed_institution_departments($inst_id) {
        // Department & Subject seeding is centrally managed via seed_and_migrate_central_org_structure()
        self::ensure_institutions_columns_exist();
    }

    /**
     * Seeds initial institutions and schools if none exist
     */
    /**
     * Seeds and normalizes the 6 mandatory institutions and hierarchy
     */
    public static function seed_mandatory_institutions() {
        global $wpdb;
        self::ensure_institutions_columns_exist();

        $mandatory_list = array(
            1 => array('name' => 'مؤسسة الشعلة للتعليم والتطوير', 'parent_id' => null, 'type' => 'مؤسسة إدارية'),
            2 => array('name' => 'مدرسة الشعلة الخاصة - الصناعية', 'parent_id' => null, 'type' => 'مدرسة'),
            3 => array('name' => 'مدرسة الشعلة الخاصة - الفلاح', 'parent_id' => null, 'type' => 'مدرسة'),
            4 => array('name' => 'مدرسة منارة الشارقة الخاصة', 'parent_id' => null, 'type' => 'مدرسة'),
            5 => array('name' => 'مدرسة الشعلة الخاصة - عجمان', 'parent_id' => null, 'type' => 'مدرسة'),
            6 => array('name' => 'مدرسة الشعلة الأمريكية', 'parent_id' => null, 'type' => 'مدرسة')
        );

        // 1. Ensure Parent Institution (Code 1) exists first
        $parent_db_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = 1 OR name = %s LIMIT 1", $mandatory_list[1]['name']));
        if (!$parent_db_id) {
            $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                'code'      => 1,
                'parent_id' => null,
                'name'      => $mandatory_list[1]['name'],
                'type'      => 'مؤسسة إدارية',
                'status'    => 'active'
            ));
            $parent_db_id = $wpdb->insert_id;
        } else {
            $wpdb->update("{$wpdb->prefix}eess_institutions", array(
                'code'      => 1,
                'parent_id' => null,
                'name'      => $mandatory_list[1]['name'],
                'status'    => 'active'
            ), array('id' => $parent_db_id));
        }
        self::seed_institution_departments($parent_db_id);

        // 2. Ensure Child Schools (Codes 2-6) exist and link to Parent (Code 1)
        for ($code = 2; $code <= 6; $code++) {
            $info = $mandatory_list[$code];
            $child_db_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = %d OR name = %s LIMIT 1", $code, $info['name']));

            if (!$child_db_id) {
                $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                    'code'      => $code,
                    'parent_id' => $parent_db_id,
                    'name'      => $info['name'],
                    'type'      => 'مدرسة',
                    'status'    => 'active'
                ));
                $child_db_id = $wpdb->insert_id;
            } else {
                $wpdb->update("{$wpdb->prefix}eess_institutions", array(
                    'code'      => $code,
                    'parent_id' => $parent_db_id,
                    'name'      => $info['name'],
                    'status'    => 'active'
                ), array('id' => $child_db_id));
            }
            self::seed_institution_departments($child_db_id);

            // Sync with eess_schools table
            $school_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE school_code = %d OR name = %s LIMIT 1", $code, $info['name']));
            if (!$school_exists) {
                $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                    'institution_id' => $child_db_id,
                    'school_code'    => $code,
                    'name'           => $info['name'],
                    'status'         => 'active'
                ));
            } else {
                $wpdb->update("{$wpdb->prefix}eess_schools", array(
                    'institution_id' => $child_db_id,
                    'school_code'    => $code,
                    'name'           => $info['name'],
                    'status'         => 'active'
                ), array('id' => $school_exists));
            }
        }

        // 3. Ensure mandatory institutions remain active without archiving custom institutions
        $wpdb->query("UPDATE {$wpdb->prefix}eess_institutions SET status = 'active' WHERE code IN (1,2,3,4,5,6)");
        $wpdb->query("UPDATE {$wpdb->prefix}eess_schools SET status = 'active' WHERE school_code IN (2,3,4,5,6)");
    }

    /**
     * Seeds and migrates Central Organizational Structure (19 Subjects, 25 Departments, 12 Grades, 26 Sections)
     */
    public static function seed_and_migrate_central_org_structure() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_mandatory_institutions();

        // 1. Migrate & Ensure 25 Official Departments (Codes 1 - 25)
        $official_depts = self::get_official_departments();
        $dept_code_to_id = array();

        foreach ($official_depts as $d_code => $d_info) {
            // Find existing department by code or name
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, code, name FROM {$wpdb->prefix}eess_departments WHERE code = %s OR name = %s ORDER BY id ASC",
                (string)$d_code, $d_info['name']
            ));

            $authoritative_id = null;
            if (!empty($rows)) {
                $authoritative_id = $rows[0]->id;
                // Update authoritative record
                $wpdb->update("{$wpdb->prefix}eess_departments", array(
                    'code'   => (string)$d_code,
                    'name'   => $d_info['name'],
                    'status' => 'active'
                ), array('id' => $authoritative_id));

                // Handle duplicates if more than 1 record matches
                for ($i = 1; $i < count($rows); $i++) {
                    $obsolete_id = $rows[$i]->id;
                    // Migrate dependent relationships from obsolete_id to authoritative_id
                    $wpdb->update("{$wpdb->prefix}eess_user_assignments", array('department_id' => $authoritative_id), array('department_id' => $obsolete_id));
                    $wpdb->update("{$wpdb->prefix}eess_subjects", array('department_id' => $authoritative_id), array('department_id' => $obsolete_id));
                    $wpdb->update("{$wpdb->prefix}sm_students", array('department_id' => $authoritative_id), array('department_id' => $obsolete_id));
                    $wpdb->delete("{$wpdb->prefix}eess_departments", array('id' => $obsolete_id));
                }
            } else {
                $wpdb->insert("{$wpdb->prefix}eess_departments", array(
                    'institution_id' => 1,
                    'code'           => (string)$d_code,
                    'name'           => $d_info['name'],
                    'status'         => 'active'
                ));
                $authoritative_id = $wpdb->insert_id;
            }
            $dept_code_to_id[$d_code] = $authoritative_id;
        }

        // Clean up any remaining legacy/obsolete departments outside 1-25
        $valid_dept_ids = array_values($dept_code_to_id);
        if (!empty($valid_dept_ids)) {
            $in_clause = implode(',', array_map('intval', $valid_dept_ids));
            $obsolete_depts = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_departments WHERE id NOT IN ($in_clause) AND code NOT IN (" . implode(',', range(1, 25)) . ")");
            foreach ($obsolete_depts as $obs_dept_id) {
                // Migrate to fallback department (Code 2 - الشؤون الأكاديمية)
                $fallback_dept_id = $dept_code_to_id[2] ?? 2;
                $wpdb->update("{$wpdb->prefix}eess_user_assignments", array('department_id' => $fallback_dept_id), array('department_id' => $obs_dept_id));
                $wpdb->update("{$wpdb->prefix}eess_subjects", array('department_id' => $fallback_dept_id), array('department_id' => $obs_dept_id));
                $wpdb->update("{$wpdb->prefix}sm_students", array('department_id' => $fallback_dept_id), array('department_id' => $obs_dept_id));
                $wpdb->delete("{$wpdb->prefix}eess_departments", array('id' => $obs_dept_id));
            }
        }

        // 2. Migrate & Ensure 19 Official Subjects (Codes 1 - 19)
        $official_subjs = self::get_official_subjects();
        $subj_code_to_id = array();

        foreach ($official_subjs as $s_code => $s_info) {
            $target_dept_id = $dept_code_to_id[$s_info['dept_code']] ?? $s_info['dept_code'];

            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, code, name FROM {$wpdb->prefix}eess_subjects WHERE code = %s OR name = %s ORDER BY id ASC",
                (string)$s_code, $s_info['name']
            ));

            $authoritative_id = null;
            if (!empty($rows)) {
                $authoritative_id = $rows[0]->id;
                $wpdb->update("{$wpdb->prefix}eess_subjects", array(
                    'department_id' => $target_dept_id,
                    'code'          => (string)$s_code,
                    'name'          => $s_info['name'],
                    'status'        => 'active'
                ), array('id' => $authoritative_id));

                for ($i = 1; $i < count($rows); $i++) {
                    $obsolete_id = $rows[$i]->id;
                    $wpdb->update("{$wpdb->prefix}eess_user_assignments", array('subject_id' => $authoritative_id), array('subject_id' => $obsolete_id));
                    $wpdb->update("{$wpdb->prefix}eess_subject_grades", array('subject_id' => $authoritative_id), array('subject_id' => $obsolete_id));
                    $wpdb->update("{$wpdb->prefix}eess_subject_schools", array('subject_id' => $authoritative_id), array('subject_id' => $obsolete_id));
                    $wpdb->delete("{$wpdb->prefix}eess_subjects", array('id' => $obsolete_id));
                }
            } else {
                $wpdb->insert("{$wpdb->prefix}eess_subjects", array(
                    'institution_id' => 1,
                    'department_id'  => $target_dept_id,
                    'code'           => (string)$s_code,
                    'name'           => $s_info['name'],
                    'status'         => 'active'
                ));
                $authoritative_id = $wpdb->insert_id;
            }
            $subj_code_to_id[$s_code] = $authoritative_id;
        }

        // Clean up obsolete subjects outside 1-19
        $valid_subj_ids = array_values($subj_code_to_id);
        if (!empty($valid_subj_ids)) {
            $in_clause_sub = implode(',', array_map('intval', $valid_subj_ids));
            $obsolete_subjs = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_subjects WHERE id NOT IN ($in_clause_sub) AND code NOT IN (" . implode(',', range(1, 19)) . ")");
            foreach ($obsolete_subjs as $obs_sub_id) {
                $fallback_sub_id = $subj_code_to_id[1] ?? 1;
                $wpdb->update("{$wpdb->prefix}eess_user_assignments", array('subject_id' => $fallback_sub_id), array('subject_id' => $obs_sub_id));
                $wpdb->delete("{$wpdb->prefix}eess_subject_grades", array('subject_id' => $obs_sub_id));
                $wpdb->delete("{$wpdb->prefix}eess_subject_schools", array('subject_id' => $obs_sub_id));
                $wpdb->delete("{$wpdb->prefix}eess_subjects", array('id' => $obs_sub_id));
            }
        }

        // 3. Migrate & Ensure 12 Official Grades (Codes 1 - 12)
        $official_grades = self::get_official_grades();
        foreach ($official_grades as $g_code => $g_info) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eess_grades WHERE id = %d OR name = %s LIMIT 1",
                $g_code, $g_info['name']
            ));

            if (!$existing_id) {
                $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                    'id'        => $g_code,
                    'school_id' => 1,
                    'name'      => $g_info['name']
                ));
            } else {
                $wpdb->update("{$wpdb->prefix}eess_grades", array(
                    'id'        => $g_code,
                    'name'      => $g_info['name'],
                    'school_id' => 1
                ), array('id' => $existing_id));
            }
        }
        // Remove orphan grades above ID 12 with controlled relationship migration
        $obsolete_grades = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_grades WHERE id > 12");
        foreach ($obsolete_grades as $obs_gid) {
            $wpdb->update("{$wpdb->prefix}sm_students", array('grade_id' => 1), array('grade_id' => $obs_gid));
            $wpdb->update("{$wpdb->prefix}eess_user_assignments", array('grade_id' => 1), array('grade_id' => $obs_gid));
            $wpdb->update("{$wpdb->prefix}eess_subject_grades", array('grade_id' => 1), array('grade_id' => $obs_gid));
            $wpdb->delete("{$wpdb->prefix}eess_grades", array('id' => $obs_gid));
        }

        // 4. Ensure Sections Table Exists & Migrate 26 Official Sections (Codes 1 - 26)
        $table_sections = "{$wpdb->prefix}eess_sections";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_sections'") !== $table_sections) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_sections (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                code int(11) NOT NULL,
                name_ar varchar(50) NOT NULL,
                name_en varchar(50) NOT NULL,
                status varchar(20) DEFAULT 'active',
                PRIMARY KEY  (id),
                UNIQUE KEY code (code)
            ) $charset_collate;";
            if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            }
            dbDelta($sql);
        }

        $official_sections = self::get_official_sections();
        foreach ($official_sections as $sec_code => $sec_info) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eess_sections WHERE code = %d LIMIT 1",
                $sec_code
            ));

            if (!$existing_id) {
                $wpdb->insert("{$wpdb->prefix}eess_sections", array(
                    'code'    => $sec_code,
                    'name_ar' => $sec_info['ar'],
                    'name_en' => $sec_info['en'],
                    'status'  => 'active'
                ));
            } else {
                $wpdb->update("{$wpdb->prefix}eess_sections", array(
                    'name_ar' => $sec_info['ar'],
                    'name_en' => $sec_info['en'],
                    'status'  => 'active'
                ), array('id' => $existing_id));
            }
        }
    }

    /**
     * Centralized Section Normalization Resolver (maps Arabic 'أ', English 'A', or numeric code '1' to authoritative section)
     */
    public static function normalize_section($input) {
        $clean = trim((string)$input);
        if ($clean === '') return array('code' => 1, 'ar' => 'أ', 'en' => 'A');

        $sections = self::get_official_sections();

        // 1. Check numeric code (1-26)
        if (is_numeric($clean)) {
            $num = intval($clean);
            if (isset($sections[$num])) {
                return $sections[$num];
            }
        }

        // 2. Check Arabic or English letter match
        $upper_clean = mb_strtoupper($clean, 'UTF-8');
        foreach ($sections as $s) {
            if (mb_strtoupper($s['ar'], 'UTF-8') === $upper_clean || mb_strtoupper($s['en'], 'UTF-8') === $upper_clean) {
                return $s;
            }
        }

        // Default fallback to Section 1 ('أ' / 'A')
        return array('code' => 1, 'ar' => 'أ', 'en' => 'A');
    }

    /**
     * Creates and seeds official Employee & Staff Evaluation Models (General & Specialty)
     */
    public static function seed_official_evaluation_models() {
        global $wpdb;

        $table_models = "{$wpdb->prefix}eess_eval_models";
        $table_questions = "{$wpdb->prefix}eess_eval_questions";
        $charset_collate = $wpdb->get_charset_collate();

        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_models'") !== $table_models) {
            $sql1 = "CREATE TABLE $table_models (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                type varchar(50) NOT NULL DEFAULT 'general',
                scope varchar(50) NOT NULL DEFAULT 'teachers',
                role_key varchar(50) NOT NULL DEFAULT 'sm_teacher',
                subject_code int(11) DEFAULT NULL,
                dept_code int(11) DEFAULT NULL,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                total_questions int(11) NOT NULL DEFAULT 10,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            dbDelta($sql1);
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_questions'") !== $table_questions) {
            $sql2 = "CREATE TABLE $table_questions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                model_id bigint(20) NOT NULL,
                question_order int(11) NOT NULL DEFAULT 1,
                question_text text NOT NULL,
                max_score int(11) NOT NULL DEFAULT 10,
                status varchar(20) DEFAULT 'active',
                PRIMARY KEY  (id),
                KEY model_id (model_id)
            ) $charset_collate;";
            dbDelta($sql2);
        }

        // Purge legacy evaluation categories ("تقييم الالتزام", "التواصل")
        $wpdb->query("DELETE FROM {$wpdb->prefix}sm_evaluations WHERE category_name LIKE '%الالتزام%' OR category_name LIKE '%التواصل%'");

        // 4 Official Models Seeding Data
        $official_models = array(
            1 => array(
                'title' => 'تقييم الانضباط والسلوك',
                'type' => 'general',
                'questions' => array(
                    'ما مدى التزام المعلم بالحضور والانصراف والالتزام بالمواعيد الرسمية؟',
                    'ما مدى التزام المعلم بأداء المناوبات المكلف بها في أوقاتها وأماكنها المحددة؟',
                    'ما مدى التزام المعلم بالانضباط والوجود الفعلي أثناء فترات المناوبة؟',
                    'ما مدى التزام المعلم بالتعامل باحترام ومهنية مع الطلبة؟',
                    'ما مدى قدرة المعلم على ضبط سلوك الطلبة والتعامل مع المواقف السلوكية بحكمة؟',
                    'ما مدى التزام المعلم بالأنظمة واللوائح والسياسات المعتمدة في المدرسة؟',
                    'ما مدى التزام المعلم بالمظهر والسلوك المهني المناسب داخل المدرسة؟',
                    'ما مدى تعاونه واحترامه للإدارة والزملاء وأعضاء المجتمع المدرسي؟',
                    'ما مدى حرص المعلم على سلامة الطلبة ومتابعتهم أثناء الحصص والمناوبات والأنشطة؟',
                    'ما مدى التزام المعلم العام بالانضباط والسلوك المهني وتحمل المسؤولية الوظيفية؟'
                )
            ),
            2 => array(
                'title' => 'التقييم التربوي والمهني',
                'type' => 'general',
                'questions' => array(
                    'ما مدى التزام المعلم بالتخطيط الجيد للدروس وإعدادها وفق المنهج ونواتج التعلم؟',
                    'ما مدى فاعلية المعلم في استخدام استراتيجيات تدريس متنوعة ومناسبة لمستويات الطلبة؟',
                    'ما مدى قدرة المعلم على تحقيق نواتج التعلم ورفع مستوى تحصيل الطلبة؟',
                    'ما مدى كفاءة المعلم في إدارة الصف وتهيئة بيئة تعليمية إيجابية ومحفزة؟',
                    'ما مدى استخدام المعلم لأساليب التقويم المناسبة وقياس تقدم الطلبة بصورة مستمرة؟',
                    'ما مدى مراعاة المعلم للفروق الفردية وااحتياجات الطلبة التعليمية؟',
                    'ما مدى توظيف المعلم للتكنولوجيا والموارد التعليمية بما يدعم عملية التعلم؟',
                    'ما مدى التزام المعلم بالتطوير المهني المستمر وتطبيق التغذية الراجعة لتحسين أدائه؟',
                    'ما مدى تعاون المعلم مع الإدارة والزملاء وأولياء الأمور بما يخدم مصلحة الطلبة؟',
                    'ما مدى كفاءة المعلم التربوية والمهنية والتزامه بتحقيق معايير الأداء التعليمي المعتمدة؟'
                )
            ),
            3 => array(
                'title' => 'تقييم الأداء الوظيفي',
                'type' => 'general',
                'questions' => array(
                    'ما مدى التزام المعلم بأداء المهام والمسؤوليات الوظيفية المكلف بها؟',
                    'ما مدى الالتزام بالحضور والانصراف والمواعيد الرسمية للعمل؟',
                    'ما مدى إنجاز المعلم للمهام المطلوبة منه في الوقت المحدد وبالجودة المطلوبة؟',
                    'ما مدى التزام المعلم بالأنظمة واللوائح والسياسات والإجراءات المعتمدة في المدرسة؟',
                    'ما مدى دقة المعلم في تنفيذ الأعمال وتوثيق السجلات والبيانات المطلوبة؟',
                    'ما مدى تحمله للمسؤولية والمبادرة في أداء واجباته الوظيفية؟',
                    'ما مدى تعاونه مع الإدارة والزملاء في تنفيذ الأعمال والمهام المشتركة؟',
                    'ما مدى استجابته للتوجيهات والتعليمات والملاحظات الإدارية؟',
                    'ما مدى كفاءة المعلم في إدارة وقته وترتيب أولويات مهامه الوظيفية؟',
                    'ما مدى كفاءة المعلم في أداء واجباته الوظيفية وتحقيق متطلبات العمل المدرسي؟'
                )
            ),
            4 => array(
                'title' => 'تقييم التربية البدنية والصحية',
                'type' => 'specialty',
                'subject_code' => 10,
                'dept_code' => 10,
                'questions' => array(
                    'ما مدى جودة تخطيط المعلم للحصة وفق المنهج ونواتج التعلم المعتمدة؟',
                    'ما مدى فاعلية المعلم في تنفيذ التدريس وتحفيز مشاركة الطلبة؟',
                    'ما مدى تحقيق المعلم لنواتج التعلم البدنية والمهارية والصحية المستهدفة؟',
                    'ما مدى كفاءة المعلم في إدارة الحصة وتنظيم الوقت والمرافق والأدوات؟',
                    'ما مدى مراعاة المعلم للفروق الفردية واحتياجات وقدرات الطلبة؟',
                    'ما مدى التزام المعلم بمعايير الأمن والسلامة والوقاية من الإصابات أثناء الأنشطة؟',
                    'ما مدى دقة المعلم في تقييم أداء الطلبة وقياس تقدمهم وتوثيق النتائج؟',
                    'ما مدى إسهام المعلم في تعزيز اللياقة البدنية والصحة ونمط الحياة الصحي لدى الطلبة؟',
                    'ما مدى التزام المعلم بالتعاون المهني والمشاركة في الأنشطة والفعاليات المدرسية؟',
                    'ما مدى التزام المعلم بالتطوير المهني المستمر وتطبيق الممارسات الحديثة في التربية البدنية والصحية؟'
                )
            )
        );

        foreach ($official_models as $m_id => $m_data) {
            $existing_m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_models WHERE id = %d OR title = %s LIMIT 1", $m_id, $m_data['title']));
            if (!$existing_m_id) {
                $wpdb->insert($table_models, array(
                    'id' => $m_id,
                    'title' => $m_data['title'],
                    'type' => $m_data['type'],
                    'scope' => 'teachers',
                    'role_key' => 'sm_teacher',
                    'subject_code' => $m_data['subject_code'] ?? null,
                    'dept_code' => $m_data['dept_code'] ?? null,
                    'is_active' => 1,
                    'total_questions' => 10,
                    'created_at' => current_time('mysql')
                ));
                $existing_m_id = $wpdb->insert_id ?: $m_id;
            } else {
                $wpdb->update($table_models, array(
                    'title' => $m_data['title'],
                    'type' => $m_data['type'],
                    'subject_code' => $m_data['subject_code'] ?? null,
                    'dept_code' => $m_data['dept_code'] ?? null,
                    'is_active' => 1,
                    'total_questions' => 10
                ), array('id' => $existing_m_id));
            }

            // Sync 10 Questions
            $wpdb->delete($table_questions, array('model_id' => $existing_m_id));
            foreach ($m_data['questions'] as $q_idx => $q_text) {
                $wpdb->insert($table_questions, array(
                    'model_id' => $existing_m_id,
                    'question_order' => $q_idx + 1,
                    'question_text' => $q_text,
                    'max_score' => 10,
                    'status' => 'active'
                ));
            }
        }
    }

    /**
     * Ensures all student records have a unique verification token
     */
    public static function ensure_student_verification_tokens() {
        global $wpdb;
        $tbl_stu = "{$wpdb->prefix}sm_students";

        $check_vt = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tbl_stu' AND COLUMN_NAME = 'verification_token'");
        if (empty($check_vt)) {
            $wpdb->query("ALTER TABLE {$tbl_stu} ADD COLUMN verification_token varchar(100) DEFAULT NULL, ADD KEY verification_token (verification_token)");
        }

        $untokened = $wpdb->get_results("SELECT id FROM {$tbl_stu} WHERE verification_token IS NULL OR verification_token = ''");
        foreach ($untokened as $s) {
            $token = 'EESS-VER-' . strtoupper(bin2hex(random_bytes(8)));
            $wpdb->update($tbl_stu, array('verification_token' => $token), array('id' => $s->id));
        }
    }

    /**
     * Seeds initial institutions and central structure
     */
    public static function seed_default_structure() {
        self::seed_mandatory_institutions();
        self::seed_and_migrate_central_org_structure();
        self::seed_official_evaluation_models();
        self::ensure_student_verification_tokens();
    }

    /**
     * Retrieves the organizational scope for a given user
     */
    public static function get_user_scope($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        if (isset(self::$cache['scope_' . $user_id])) {
            return self::$cache['scope_' . $user_id];
        }
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) return array('unrestricted' => false, 'institutions' => array(), 'schools' => array(), 'grades' => array(), 'classes' => array(), 'sections' => array(), 'subjects' => array(), 'departments' => array());

        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || in_array('sm_system_admin', $roles);

        if ($is_admin) {
            // Unrestricted access for System Admin
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            $all_insts   = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE status='active'");
            return array(
                'unrestricted' => true,
                'institutions' => $all_insts,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'sections' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        // Check if user is assigned to Institution Code 1 (Parent/Owner Institution)
        $meta_inst_id = get_user_meta($user_id, 'eess_institution_id', true) ?: get_user_meta($user_id, 'institution_id', true);
        $user_inst_id = intval($meta_inst_id);

        if ($user_inst_id === 1) {
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            $all_insts   = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE status='active'");
            return array(
                'unrestricted' => true,
                'institutions' => $all_insts,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'sections' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        // Fetch user assignments
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d",
            $user_id
        ));

        $institutions = array();
        $schools = array();
        $grades = array();
        $classes = array();
        $sections = array();
        $subjects = array();
        $departments = array();

        if ($user_inst_id > 0) {
            $institutions[] = $user_inst_id;
        }

        foreach ($assignments as $asn) {
            if ($asn->institution_id) {
                $institutions[] = intval($asn->institution_id);
                if (!$asn->school_id || $asn->school_id == 0) {
                    $child_schools = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE institution_id = %d AND status='active'", $asn->institution_id));
                    if (!empty($child_schools)) {
                        foreach ($child_schools as $csid) $schools[] = intval($csid);
                    }
                }
            }
            if ($asn->school_id) {
                $schools[] = intval($asn->school_id);
            }
            if ($asn->grade_id) $grades[] = intval($asn->grade_id);
            if ($asn->class_id) $classes[] = intval($asn->class_id);
            if ($asn->subject_id) $subjects[] = intval($asn->subject_id);
            if ($asn->department_id) $departments[] = intval($asn->department_id);
        }

        // Fallback to user_meta if assignments table is empty
        if (empty($schools)) {
            $meta_school_id = get_user_meta($user_id, 'eess_school_id', true) ?: get_user_meta($user_id, 'sm_school_id', true);
            if ($meta_school_id) {
                $schools[] = intval($meta_school_id);
            }
        }

        // Teacher-specific grade & section assignment mapping
        if (in_array('sm_teacher', $roles)) {
            $raw_grades = get_user_meta($user_id, 'sm_assigned_grades', true) ?: (get_user_meta($user_id, 'eess_assigned_grades', true) ?: (get_user_meta($user_id, 'sm_grade_level', true) ?: ''));
            if (is_array($raw_grades)) {
                $grades_list = $raw_grades;
            } elseif (is_string($raw_grades) && !empty($raw_grades)) {
                $decoded = json_decode($raw_grades, true);
                if (is_array($decoded)) {
                    $grades_list = $decoded;
                } else {
                    $grades_list = array_map('trim', explode(',', $raw_grades));
                }
            } else {
                $grades_list = array();
            }

            foreach ($grades_list as $g_item) {
                $clean_g = trim(preg_replace('/^(الصف|صف|Grade|grade)\s*:?\s*/u', '', (string)$g_item));
                if (!empty($clean_g)) $grades[] = $clean_g;
            }

            $raw_sections = get_user_meta($user_id, 'sm_assigned_sections', true) ?: (get_user_meta($user_id, 'eess_assigned_sections', true) ?: (get_user_meta($user_id, 'sm_class_section', true) ?: ''));
            if (is_array($raw_sections)) {
                $sections_list = $raw_sections;
            } elseif (is_string($raw_sections) && !empty($raw_sections)) {
                $sections_list = array_map('trim', explode(',', $raw_sections));
            } else {
                $sections_list = array();
            }

            foreach ($sections_list as $s_item) {
                $clean_s = trim(preg_replace('/^(الشعبة|شعبة|Section|section)\s*:?\s*/u', '', (string)$s_item));
                if (!empty($clean_s)) $sections[] = $clean_s;
            }
        }

        $scope_res = array(
            'unrestricted' => false,
            'institutions' => array_unique(array_filter($institutions)),
            'schools' => array_unique(array_filter($schools)),
            'grades' => array_unique(array_filter($grades)),
            'classes' => array_unique(array_filter($classes)),
            'sections' => array_unique(array_filter($sections)),
            'subjects' => array_unique(array_filter($subjects)),
            'departments' => array_unique(array_filter($departments))
        );
        self::$cache['scope_' . $user_id] = $scope_res;
        return $scope_res;
    }

    /**
     * Resolves Department Code automatically from Subject Code or Subject Name
     */
    public static function resolve_department_from_subject($subject_input) {
        $clean = trim((string)$subject_input);
        if ($clean === '') return 2; // Default to 'الشؤون الأكاديمية'

        $subjects = self::get_official_subjects();

        // 1. Check numeric subject code
        if (is_numeric($clean)) {
            $code = intval($clean);
            if (isset($subjects[$code])) {
                return $subjects[$code]['dept_code'];
            }
        }

        // 2. Check subject name
        foreach ($subjects as $s) {
            if ($s['name'] === $clean || mb_strpos($s['name'], $clean) !== false || mb_strpos($clean, $s['name']) !== false) {
                return $s['dept_code'];
            }
        }

        return 2; // Default fallback to الشؤون الأكاديمية
    }

    /**
     * Centralized Assignment Saver
     */
    public static function save_user_assignments($user_id, $data) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
        self::flush_cache();

        $inst_ids = !empty($data['institutions']) ? array_map('intval', (array)$data['institutions']) : array();
        $school_ids = !empty($data['schools']) ? array_map('intval', (array)$data['schools']) : array();
        $grade_ids = !empty($data['grades']) ? array_map('intval', (array)$data['grades']) : array();
        $class_ids = !empty($data['classes']) ? array_map('intval', (array)$data['classes']) : array();
        $subject_ids = !empty($data['subjects']) ? array_map('intval', (array)$data['subjects']) : array();
        $dept_ids = !empty($data['departments']) ? array_map('intval', (array)$data['departments']) : array();

        // Auto-resolve Department Codes from assigned Subject Codes
        if (!empty($subject_ids)) {
            foreach ($subject_ids as $sub_code) {
                $auto_dept_code = self::resolve_department_from_subject($sub_code);
                if ($auto_dept_code && !in_array($auto_dept_code, $dept_ids, true)) {
                    $dept_ids[] = $auto_dept_code;
                }
            }
        }

        $max_count = max(count($inst_ids), count($school_ids), count($grade_ids), count($class_ids), count($subject_ids), count($dept_ids), 1);

        for ($i = 0; $i < $max_count; $i++) {
            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                'user_id' => $user_id,
                'institution_id' => $inst_ids[$i] ?? ($inst_ids[0] ?? null),
                'school_id' => $school_ids[$i] ?? ($school_ids[0] ?? null),
                'grade_id' => $grade_ids[$i] ?? ($grade_ids[0] ?? null),
                'class_id' => $class_ids[$i] ?? ($class_ids[0] ?? null),
                'subject_id' => $subject_ids[$i] ?? ($subject_ids[0] ?? null),
                'department_id' => $dept_ids[$i] ?? ($dept_ids[0] ?? null)
            ));
        }

        clean_user_cache($user_id);
        wp_cache_flush();
    }

    /**
     * Standardized SQL Filter Injector for any table querying students
     */
    public static function filter_students_query($query_alias = '') {
        global $wpdb;
        $scope = self::get_user_scope();
        if ($scope['unrestricted']) return " 1=1 ";

        $prefix = !empty($query_alias) ? $query_alias . '.' : '';
        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        $inst_ids = !empty($scope['institutions']) ? implode(',', array_map('intval', $scope['institutions'])) : '0';
        $school_ids = !empty($scope['schools']) ? implode(',', array_map('intval', $scope['schools'])) : '0';

        // Base institution / school boundary
        $clause = " ({$prefix}institution_id IN ($inst_ids) OR {$prefix}school_id IN ($school_ids)) ";

        // Teacher strict class / grade / section scoping
        if (in_array('sm_teacher', $roles)) {
            $sub_clauses = array();

            if (!empty($scope['grades'])) {
                $escaped_grades = array_map(function($g) use ($wpdb) { return "'" . esc_sql($g) . "'"; }, $scope['grades']);
                $sub_clauses[] = "{$prefix}class_name IN (" . implode(',', $escaped_grades) . ")";
            }

            if (!empty($scope['sections'])) {
                $escaped_sections = array_map(function($s) use ($wpdb) { return "'" . esc_sql($s) . "'"; }, $scope['sections']);
                $sub_clauses[] = "{$prefix}section IN (" . implode(',', $escaped_sections) . ")";
            }

            if (!empty($sub_clauses)) {
                $clause .= " AND (" . implode(' AND ', $sub_clauses) . ") ";
            }
        }

        return $clause;
    }

    public static function resolve_student_org_ids($student_id, $class_name, $section, $school_name = '') {
        global $wpdb;
        if (empty($school_name)) {
            $school_info = SM_Settings::get_school_info();
            $school_name = $school_info['school_name'] ?? 'مدرسة الأمل للتعليم الأساسي والثانوي';
        }

        // 1. Find or create School
        $school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $school_name));
        if (!$school_id) {
            $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                'institution_id' => 1,
                'name' => $school_name,
                'status' => 'active'
            ));
            $school_id = $wpdb->insert_id;
        }

        // 2. Find or create Grade
        $grade_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_grades WHERE school_id = %d AND name = %s", $school_id, $class_name));
        if (!$grade_id) {
            $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                'school_id' => $school_id,
                'name' => $class_name
            ));
            $grade_id = $wpdb->insert_id;
        }

        // 3. Find or create Class
        $class_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d AND name = %s", $grade_id, $section));
        if (!$class_id) {
            $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                'grade_id' => $grade_id,
                'name' => $section
            ));
            $class_id = $wpdb->insert_id;
        }

        // 4. Update the student table row (preserve existing institution_id and school_id without overwriting)
        $curr_row = $wpdb->get_row($wpdb->prepare("SELECT institution_id, school_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
        $inst_to_set = ($curr_row && intval($curr_row->institution_id) > 0) ? intval($curr_row->institution_id) : 1;
        $sch_to_set  = ($curr_row && intval($curr_row->school_id) > 0) ? intval($curr_row->school_id) : $inst_to_set;

        $wpdb->update("{$wpdb->prefix}sm_students", array(
            'institution_id' => $inst_to_set,
            'school_id'      => $sch_to_set,
            'grade_id'       => $grade_id,
            'class_id'       => $class_id
        ), array('id' => $student_id));

        return array(
            'school_id' => $school_id,
            'grade_id' => $grade_id,
            'class_id' => $class_id
        );
    }

    public static function ensure_all_students_resolved() {
        global $wpdb;
        $unresolved = $wpdb->get_results("SELECT id, class_name, section FROM {$wpdb->prefix}sm_students WHERE school_id IS NULL OR school_id = 0 OR class_id IS NULL OR class_id = 0");
        foreach ($unresolved as $row) {
            self::resolve_student_org_ids($row->id, $row->class_name, $row->section);
        }
    }

    /**
     * Ensures eess_institutions table has all single-level model columns
     */
    public static function ensure_institutions_columns_exist() {
        global $wpdb;
        $table = "{$wpdb->prefix}eess_institutions";

        $cols = array(
            'code' => "INT(11) DEFAULT 1 NOT NULL",
            'parent_id' => "BIGINT(20) DEFAULT NULL",
            'type' => "VARCHAR(100) DEFAULT 'مؤسسة إدارية' NOT NULL",
            'logo_url' => "VARCHAR(255) DEFAULT '' NOT NULL",
            'country' => "VARCHAR(100) DEFAULT 'الإمارات العربية المتحدة' NOT NULL",
            'address' => "TEXT DEFAULT NULL",
            'phone' => "VARCHAR(50) DEFAULT '' NOT NULL",
            'email' => "VARCHAR(100) DEFAULT '' NOT NULL",
            'manager_id' => "BIGINT(20) DEFAULT NULL",
            'deputy_manager_id' => "BIGINT(20) DEFAULT NULL",
            'director_name' => "VARCHAR(255) DEFAULT '' NOT NULL"
        );

        foreach ($cols as $col => $def) {
            $check = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$col'");
            if (empty($check)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $col $def");
            }
        }

        // Ensure eess_schools table has all hierarchical & manager columns
        $sch_table = "{$wpdb->prefix}eess_schools";
        $sch_cols = array(
            'school_code' => "INT(11) DEFAULT 1 NOT NULL",
            'school_logo' => "VARCHAR(255) DEFAULT '' NOT NULL",
            'address' => "TEXT DEFAULT NULL",
            'phone' => "VARCHAR(50) DEFAULT '' NOT NULL",
            'email' => "VARCHAR(100) DEFAULT '' NOT NULL",
            'manager_id' => "BIGINT(20) DEFAULT NULL",
            'deputy_manager_id' => "BIGINT(20) DEFAULT NULL",
            'discipline_supervisor_id' => "BIGINT(20) DEFAULT NULL"
        );

        foreach ($sch_cols as $scol => $sdef) {
            $check_s = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$sch_table' AND COLUMN_NAME = '$scol'");
            if (empty($check_s)) {
                $wpdb->query("ALTER TABLE $sch_table ADD COLUMN $scol $sdef");
            }
        }
    }

    // --- ORGANIZATIONAL CRUD METHODS ---
    public static function get_institutions() {
        if (isset(self::$cache['institutions'])) {
            return self::$cache['institutions'];
        }
        global $wpdb;
        $res = $wpdb->get_results("SELECT i.*, u.display_name as manager_display_name FROM {$wpdb->prefix}eess_institutions i LEFT JOIN {$wpdb->users} u ON i.manager_id = u.ID WHERE (i.status = 'active' OR i.status IS NULL) ORDER BY CAST(i.code AS UNSIGNED) ASC, i.id ASC");
        self::$cache['institutions'] = $res;
        return $res;
    }

    public static function get_institution_by_id($id) {
        $id = intval($id);
        if (isset(self::$cache['inst_' . $id])) {
            return self::$cache['inst_' . $id];
        }
        global $wpdb;
        $res = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_institutions WHERE id = %d LIMIT 1", $id));
        self::$cache['inst_' . $id] = $res;
        return $res;
    }

    public static function get_school_by_id($id) {
        $id = intval($id);
        if (isset(self::$cache['sch_' . $id])) {
            return self::$cache['sch_' . $id];
        }
        global $wpdb;
        $res = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_schools WHERE id = %d LIMIT 1", $id));
        self::$cache['sch_' . $id] = $res;
        return $res;
    }

    public static function add_institution($data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        if (is_string($data)) {
            $data = array('name' => $data);
        }
        $insert = array(
            'code'          => !empty($data['code']) ? intval($data['code']) : 1,
            'parent_id'     => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
            'name'          => sanitize_text_field($data['name'] ?? ''),
            'type'          => sanitize_text_field($data['type'] ?? 'مدرسة'),
            'logo_url'      => esc_url_raw($data['logo_url'] ?? ''),
            'country'       => sanitize_text_field($data['country'] ?? 'الإمارات العربية المتحدة'),
            'address'       => sanitize_textarea_field($data['address'] ?? ''),
            'phone'         => sanitize_text_field($data['phone'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? intval($data['manager_id']) : null,
            'director_name' => sanitize_text_field($data['director_name'] ?? ''),
            'status'        => 'active'
        );
        $wpdb->insert("{$wpdb->prefix}eess_institutions", $insert);
        self::flush_cache();
        return $wpdb->insert_id;
    }

    public static function update_institution($id, $data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        if (is_string($data)) {
            $data = array('name' => $data);
        }
        $update = array(
            'code'          => !empty($data['code']) ? intval($data['code']) : intval($id),
            'parent_id'     => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
            'name'          => sanitize_text_field($data['name'] ?? ''),
            'type'          => sanitize_text_field($data['type'] ?? 'مدرسة'),
            'logo_url'      => esc_url_raw($data['logo_url'] ?? ''),
            'country'       => sanitize_text_field($data['country'] ?? 'الإمارات العربية المتحدة'),
            'address'       => sanitize_textarea_field($data['address'] ?? ''),
            'phone'         => sanitize_text_field($data['phone'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? intval($data['manager_id']) : null,
            'director_name' => sanitize_text_field($data['director_name'] ?? '')
        );
        $res = $wpdb->update("{$wpdb->prefix}eess_institutions", $update, array('id' => intval($id)));
        self::flush_cache();
        return $res;
    }

    public static function delete_institution($id) {
        global $wpdb;
        // Check dependencies before deletion
        $school_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}eess_schools WHERE institution_id = %d AND status = 'active'", $id));
        if ($school_count > 0) {
            return new WP_Error('has_schools', 'لا يمكن حذف المؤسسة لوجود مدارس/فروع تابعة لها. يرجى نقل أو حذف المدارس أولاً.');
        }

        $user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}eess_user_assignments WHERE institution_id = %d", $id));
        if ($user_count > 0) {
            return new WP_Error('has_users', 'لا يمكن حذف المؤسسة لوجود مستخدمين/كوادر مكلفة بها.');
        }

        $res = $wpdb->delete("{$wpdb->prefix}eess_institutions", array('id' => $id));
        self::flush_cache();
        return $res;
    }

    // --- DEPARTMENT CRUD METHODS ---
    public static function get_departments_by_institution($inst_id) {
        $inst_id = intval($inst_id);
        if (isset(self::$cache['depts_' . $inst_id])) {
            return self::$cache['depts_' . $inst_id];
        }
        global $wpdb;
        $res = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as head_display_name FROM {$wpdb->prefix}eess_departments d LEFT JOIN {$wpdb->users} u ON d.head_user_id = u.ID WHERE (d.institution_id = %d OR d.institution_id = 1) AND (d.status = 'active' OR d.status IS NULL) ORDER BY d.id ASC",
            $inst_id
        ));
        self::$cache['depts_' . $inst_id] = $res;
        return $res;
    }

    public static function add_department($inst_id, $data) {
        global $wpdb;
        $name = sanitize_text_field($data['name'] ?? '');
        if (empty($name)) return new WP_Error('empty_name', 'اسم القسم مطلوب');

        $code = !empty($data['code']) ? preg_replace('/[^0-9]/', '', $data['code']) : intval($wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}eess_departments") + 101);

        if (empty($code)) {
            return new WP_Error('invalid_code', 'كود القسم يجب أن يحتوي على أرقام فقط');
        }

        $existing_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_departments WHERE code = %s", $code));
        if ($existing_code) {
            return new WP_Error('duplicate_code', 'كود القسم الرقمي مُستخدم بالفعل، يرجى اختيار كود آخر');
        }

        $wpdb->insert("{$wpdb->prefix}eess_departments", array(
            'institution_id' => 1,
            'code'           => $code,
            'name'           => $name,
            'status'         => 'active'
        ));
        self::flush_cache();
        return $wpdb->insert_id;
    }

    public static function update_department($id, $data) {
        global $wpdb;
        $update = array();
        if (isset($data['name'])) $update['name'] = sanitize_text_field($data['name']);
        if (isset($data['code'])) {
            $code = preg_replace('/[^0-9]/', '', $data['code']);
            if (empty($code)) {
                return new WP_Error('invalid_code', 'كود القسم يجب أن يحتوي على أرقام فقط');
            }
            $existing_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_departments WHERE code = %s AND id != %d", $code, $id));
            if ($existing_code) {
                return new WP_Error('duplicate_code', 'كود القسم الرقمي مُستخدم بالفعل في قسم آخر');
            }
            $update['code'] = $code;
        }

        if (!empty($update)) {
            $wpdb->update("{$wpdb->prefix}eess_departments", $update, array('id' => intval($id)));
        }
        self::flush_cache();
        return true;
    }

    public static function delete_department($id) {
        global $wpdb;
        $res = $wpdb->delete("{$wpdb->prefix}eess_departments", array('id' => intval($id)));
        self::flush_cache();
        return $res;
    }

    // --- SUBJECT CRUD METHODS ---
    public static function get_subjects_by_institution($inst_id) {
        $inst_id = intval($inst_id);
        if (isset(self::$cache['subjs_' . $inst_id])) {
            return self::$cache['subjs_' . $inst_id];
        }
        global $wpdb;
        $res = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, d.name as department_name, u1.display_name as hod_display_name, u2.display_name as coordinator_display_name
             FROM {$wpdb->prefix}eess_subjects s
             LEFT JOIN {$wpdb->prefix}eess_departments d ON s.department_id = d.id
             LEFT JOIN {$wpdb->users} u1 ON s.hod_user_id = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON s.coordinator_user_id = u2.ID
             WHERE (s.institution_id = %d OR s.institution_id = 1) AND (s.status = 'active' OR s.status IS NULL)
             ORDER BY s.name ASC",
            $inst_id
        ));
        self::$cache['subjs_' . $inst_id] = $res;
        return $res;
    }

    public static function get_subject_assigned_grades($subject_id) {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare("SELECT grade_id FROM {$wpdb->prefix}eess_subject_grades WHERE subject_id = %d", intval($subject_id)));
    }

    public static function get_subject_assigned_schools($subject_id) {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare("SELECT school_id FROM {$wpdb->prefix}eess_subject_schools WHERE subject_id = %d", intval($subject_id)));
    }

    public static function save_subject($inst_id, $data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();

        $sub_id = !empty($data['id']) ? intval($data['id']) : 0;
        $name   = sanitize_text_field($data['name'] ?? '');
        if (empty($name)) return new WP_Error('empty_name', 'اسم المادة الدراسية مطلوب');

        $dept_id             = !empty($data['department_id']) ? intval($data['department_id']) : null;
        $code                = !empty($data['code']) ? sanitize_text_field($data['code']) : ('SUBJ-' . substr(md5($name), 0, 5));
        $hod_user_id         = !empty($data['hod_user_id']) ? intval($data['hod_user_id']) : null;
        $coordinator_user_id = !empty($data['coordinator_user_id']) ? intval($data['coordinator_user_id']) : null;
        $status              = !empty($data['status']) ? sanitize_text_field($data['status']) : 'active';

        if ($sub_id > 0) {
            $wpdb->update("{$wpdb->prefix}eess_subjects", array(
                'institution_id'      => intval($inst_id),
                'department_id'       => $dept_id,
                'code'                => $code,
                'name'                => $name,
                'status'              => $status,
                'hod_user_id'         => $hod_user_id,
                'coordinator_user_id' => $coordinator_user_id
            ), array('id' => $sub_id));
        } else {
            $wpdb->insert("{$wpdb->prefix}eess_subjects", array(
                'institution_id'      => intval($inst_id),
                'department_id'       => $dept_id,
                'code'                => $code,
                'name'                => $name,
                'status'              => $status,
                'hod_user_id'         => $hod_user_id,
                'coordinator_user_id' => $coordinator_user_id
            ));
            $sub_id = $wpdb->insert_id;
        }

        // Sync Grade M2M Relationships
        $wpdb->delete("{$wpdb->prefix}eess_subject_grades", array('subject_id' => $sub_id));
        if (!empty($data['grade_ids']) && is_array($data['grade_ids'])) {
            foreach ($data['grade_ids'] as $gid) {
                $wpdb->insert("{$wpdb->prefix}eess_subject_grades", array(
                    'subject_id' => $sub_id,
                    'grade_id'   => intval($gid)
                ));
            }
        }

        // Sync School M2M Relationships
        $wpdb->delete("{$wpdb->prefix}eess_subject_schools", array('subject_id' => $sub_id));
        if (!empty($data['school_ids']) && is_array($data['school_ids'])) {
            foreach ($data['school_ids'] as $sid) {
                $wpdb->insert("{$wpdb->prefix}eess_subject_schools", array(
                    'subject_id' => $sub_id,
                    'school_id'  => intval($sid)
                ));
            }
        }

        self::flush_cache();
        return $sub_id;
    }

    public static function delete_subject($id) {
        global $wpdb;
        $user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}eess_user_assignments WHERE subject_id = %d", intval($id)));
        if ($user_count > 0) {
            return new WP_Error('has_users', 'لا يمكن حذف المادة لوجود معلميم أو كادر مكلف بها حالياً.');
        }

        $wpdb->delete("{$wpdb->prefix}eess_subject_grades", array('subject_id' => intval($id)));
        $wpdb->delete("{$wpdb->prefix}eess_subject_schools", array('subject_id' => intval($id)));
        $res = $wpdb->delete("{$wpdb->prefix}eess_subjects", array('id' => intval($id)));
        self::flush_cache();
        return $res;
    }

    public static function get_schools() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_mandatory_institutions();
        return $wpdb->get_results("SELECT s.*, i.name as institution_name, u1.display_name as manager_display_name, u2.display_name as deputy_manager_display_name FROM {$wpdb->prefix}eess_schools s LEFT JOIN {$wpdb->prefix}eess_institutions i ON s.institution_id = i.id LEFT JOIN {$wpdb->users} u1 ON s.manager_id = u1.ID LEFT JOIN {$wpdb->users} u2 ON s.deputy_manager_id = u2.ID WHERE (s.status = 'active' OR s.status IS NULL) ORDER BY s.name ASC");
    }

    public static function get_schools_by_institution($inst_id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_results($wpdb->prepare("SELECT s.*, u1.display_name as manager_display_name FROM {$wpdb->prefix}eess_schools s LEFT JOIN {$wpdb->users} u1 ON s.manager_id = u1.ID WHERE s.institution_id = %d AND (s.status = 'active' OR s.status IS NULL) ORDER BY s.name ASC", intval($inst_id)));
    }

    public static function get_all_schools() {
        return self::get_schools();
    }

    public static function get_all_institutions_and_schools() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_mandatory_institutions();
        return $wpdb->get_results("SELECT id, code as school_code, name, type FROM {$wpdb->prefix}eess_institutions WHERE (status = 'active' OR status IS NULL) ORDER BY CAST(code AS UNSIGNED) ASC, id ASC");
    }

    /**
     * Deterministically repair student institution_id and school_id references across the database
     */
    public static function repair_student_institution_references() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_mandatory_institutions();

        // Map code -> institution_id
        $inst_code_map = $wpdb->get_results("SELECT id, code, name FROM {$wpdb->prefix}eess_institutions WHERE status = 'active'", OBJECT_K);
        $code_to_id = array();
        foreach ($inst_code_map as $item) {
            $code_to_id[intval($item->code)] = intval($item->id);
        }

        // Repair students with 0 or null institution_id by checking school_id or setting default Institution Code 1
        $students = $wpdb->get_results("SELECT id, institution_id, school_id FROM {$wpdb->prefix}sm_students WHERE institution_id IS NULL OR institution_id = 0 OR school_id IS NULL OR school_id = 0");

        $repaired_count = 0;
        foreach ($students as $s) {
            $new_inst_id = intval($s->institution_id);
            $new_sch_id  = intval($s->school_id);

            if ($new_inst_id > 0 && isset($code_to_id[$new_inst_id])) {
                $new_inst_id = $code_to_id[$new_inst_id];
            }

            if ($new_inst_id <= 0 && $new_sch_id > 0) {
                if (isset($code_to_id[$new_sch_id])) {
                    $new_inst_id = $code_to_id[$new_sch_id];
                } else {
                    $sch_inst = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}eess_schools WHERE id = %d", $new_sch_id));
                    if ($sch_inst) $new_inst_id = intval($sch_inst);
                }
            }

            if ($new_inst_id <= 0) {
                $new_inst_id = $code_to_id[1] ?? 1;
            }

            if ($new_sch_id <= 0) {
                $sch_row = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE institution_id = %d LIMIT 1", $new_inst_id));
                $new_sch_id = $sch_row ? intval($sch_row) : $new_inst_id;
            }

            if ($new_inst_id !== intval($s->institution_id) || $new_sch_id !== intval($s->school_id)) {
                $wpdb->update(
                    "{$wpdb->prefix}sm_students",
                    array('institution_id' => $new_inst_id, 'school_id' => $new_sch_id),
                    array('id' => $s->id)
                );
                $repaired_count++;
            }
        }

        if ($repaired_count > 0) {
            SM_Logger::log('معالجة مرجعيات المؤسسات', "تم إصلاح وتحديث مرجعيات المؤسسة لعدد ($repaired_count) من الطلاب بنجاح.");
        }

        return $repaired_count;
    }

    public static function add_school($inst_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_schools", array('institution_id' => $inst_id, 'name' => $name, 'status' => 'active'));
    }

    public static function update_school($id, $name, $inst_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_schools", array('name' => $name, 'institution_id' => $inst_id), array('id' => $id));
    }

    public static function delete_school($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_schools", array('id' => $id));
    }

    public static function get_grades($school_id = null) {
        global $wpdb;
        if ($school_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_grades WHERE school_id = %d ORDER BY name ASC", $school_id));
        }
        return $wpdb->get_results("SELECT g.*, s.name as school_name FROM {$wpdb->prefix}eess_grades g LEFT JOIN {$wpdb->prefix}eess_schools s ON g.school_id = s.id ORDER BY g.name ASC");
    }

    public static function add_grade($school_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_grades", array('school_id' => $school_id, 'name' => $name));
    }

    public static function update_grade($id, $name, $school_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_grades", array('name' => $name, 'school_id' => $school_id), array('id' => $id));
    }

    public static function delete_grade($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_grades", array('id' => $id));
    }

    public static function get_classes($grade_id = null) {
        global $wpdb;
        if ($grade_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d ORDER BY name ASC", $grade_id));
        }
        return $wpdb->get_results("SELECT c.*, g.name as grade_name, s.name as school_name FROM {$wpdb->prefix}eess_classes c LEFT JOIN {$wpdb->prefix}eess_grades g ON c.grade_id = g.id LEFT JOIN {$wpdb->prefix}eess_schools s ON g.school_id = s.id ORDER BY c.name ASC");
    }

    public static function add_class($grade_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_classes", array('grade_id' => $grade_id, 'name' => $name));
    }

    public static function update_class($id, $name, $grade_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_classes", array('name' => $name, 'grade_id' => $grade_id), array('id' => $id));
    }

    public static function delete_class($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_classes", array('id' => $id));
    }

    public static function ensure_divisions_table_exists() {
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}eess_divisions'");
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$wpdb->prefix}eess_divisions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                school_id bigint(20) NOT NULL,
                name varchar(255) NOT NULL,
                status varchar(50) DEFAULT 'active' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            }
            dbDelta($sql);
        }

        // Add division_id column to eess_grades table if not exists
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}eess_grades' AND COLUMN_NAME = 'division_id'");
        if (empty($row)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eess_grades ADD COLUMN division_id bigint(20) DEFAULT NULL");
        }

        // Add division_id column to eess_user_assignments table if not exists
        $row_assign = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}eess_user_assignments' AND COLUMN_NAME = 'division_id'");
        if (empty($row_assign)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eess_user_assignments ADD COLUMN division_id bigint(20) DEFAULT NULL");
        }
    }

    public static function get_divisions() {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->get_results("SELECT d.*, s.name as school_name FROM {$wpdb->prefix}eess_divisions d LEFT JOIN {$wpdb->prefix}eess_schools s ON d.school_id = s.id ORDER BY d.name ASC");
    }

    public static function add_division($school_id, $name) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->insert("{$wpdb->prefix}eess_divisions", array('school_id' => $school_id, 'name' => $name, 'status' => 'active'));
    }

    public static function update_division($id, $name, $school_id) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->update("{$wpdb->prefix}eess_divisions", array('name' => $name, 'school_id' => $school_id), array('id' => $id));
    }

    public static function delete_division($id) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->delete("{$wpdb->prefix}eess_divisions", array('id' => $id));
    }
}
