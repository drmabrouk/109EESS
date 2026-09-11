<?php
if (!defined('ABSPATH')) exit;

class EESS_Org_Helper {

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
     * Standard 9 Departments required inside EVERY Institution
     */
    public static function get_standard_departments() {
        return array(
            'الأقسام الأكاديمية - المواد الدراسية',
            'إدارة الموارد البشرية (HR)',
            'شؤون الطلاب والانضباط السلوكي',
            'الأنشطة المدرسية والفعاليات',
            'المالية والحسابات',
            'الخدمات المساندة والنقل',
            'العيادة المدرسية والرعاية الصحية',
            'الإدارة المدرسية العليا',
            'الدعم الفني والتقني'
        );
    }

    /**
     * Standard 14 Academic Subjects
     */
    public static function get_standard_subjects() {
        return array(
            'التربية الرياضية والصحية',
            'العلوم الصحية',
            'الكيمياء',
            'الرياضيات',
            'التربية الإسلامية',
            'اللغة العربية',
            'اللغة الإنجليزية',
            'الفيزياء',
            'الدراسات الاجتماعية',
            'علوم الحاسوب والتكنولوجيا',
            'العلوم العامة',
            'الأحياء',
            'التربية الموسيقية',
            'الفنون البصرية'
        );
    }

    /**
     * Ensures an Institution has its complete 9 department structure & subjects
     */
    public static function seed_institution_departments($inst_id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();

        $std_depts = self::get_standard_departments();
        $dept_ids = array();

        foreach ($std_depts as $d_name) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eess_departments WHERE institution_id = %d AND name = %s",
                $inst_id, $d_name
            ));
            if (!$existing_id) {
                $code = 'DEPT-' . $inst_id . '-' . substr(md5($d_name), 0, 4);
                $wpdb->insert("{$wpdb->prefix}eess_departments", array(
                    'institution_id' => $inst_id,
                    'code'           => $code,
                    'name'           => $d_name,
                    'status'         => 'active'
                ));
                $existing_id = $wpdb->insert_id;
            }
            $dept_ids[$d_name] = $existing_id;
        }

        // Seed Standard Subjects into the Academic Department
        if (!empty($dept_ids['الأقسام الأكاديمية - المواد الدراسية'])) {
            $acad_dept_id = $dept_ids['الأقسام الأكاديمية - المواد الدراسية'];
            $std_subjects = self::get_standard_subjects();

            foreach ($std_subjects as $s_name) {
                $sub_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}eess_subjects WHERE institution_id = %d AND name = %s",
                    $inst_id, $s_name
                ));
                if (!$sub_exists) {
                    $sub_code = 'SUBJ-' . substr(md5($s_name), 0, 5);
                    $wpdb->insert("{$wpdb->prefix}eess_subjects", array(
                        'institution_id' => $inst_id,
                        'department_id'  => $acad_dept_id,
                        'code'           => $sub_code,
                        'name'           => $s_name,
                        'status'         => 'active'
                    ));
                }
            }
        }
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
     * Seeds initial institutions and schools if none exist
     */
    public static function seed_default_structure() {
        self::seed_mandatory_institutions();
    }

    /**
     * Retrieves the organizational scope for a given user
     */
    public static function get_user_scope($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
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

        return array(
            'unrestricted' => false,
            'institutions' => array_unique(array_filter($institutions)),
            'schools' => array_unique(array_filter($schools)),
            'grades' => array_unique(array_filter($grades)),
            'classes' => array_unique(array_filter($classes)),
            'sections' => array_unique(array_filter($sections)),
            'subjects' => array_unique(array_filter($subjects)),
            'departments' => array_unique(array_filter($departments))
        );
    }

    /**
     * Centralized Assignment Saver
     */
    public static function save_user_assignments($user_id, $data) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));

        $inst_ids = !empty($data['institutions']) ? array_map('intval', (array)$data['institutions']) : array();
        $school_ids = !empty($data['schools']) ? array_map('intval', (array)$data['schools']) : array();
        $grade_ids = !empty($data['grades']) ? array_map('intval', (array)$data['grades']) : array();
        $class_ids = !empty($data['classes']) ? array_map('intval', (array)$data['classes']) : array();
        $subject_ids = !empty($data['subjects']) ? array_map('intval', (array)$data['subjects']) : array();
        $dept_ids = !empty($data['departments']) ? array_map('intval', (array)$data['departments']) : array();

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
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_mandatory_institutions();
        return $wpdb->get_results("SELECT i.*, u.display_name as manager_display_name FROM {$wpdb->prefix}eess_institutions i LEFT JOIN {$wpdb->users} u ON i.manager_id = u.ID WHERE (i.status = 'active' OR i.status IS NULL) ORDER BY CAST(i.code AS UNSIGNED) ASC, i.id ASC");
    }

    public static function get_institution_by_id($id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_institutions WHERE id = %d LIMIT 1", intval($id)));
    }

    public static function get_school_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_schools WHERE id = %d LIMIT 1", intval($id)));
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
        return $wpdb->update("{$wpdb->prefix}eess_institutions", $update, array('id' => intval($id)));
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

        return $wpdb->delete("{$wpdb->prefix}eess_institutions", array('id' => $id));
    }

    // --- DEPARTMENT CRUD METHODS ---
    public static function get_departments_by_institution($inst_id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        self::seed_institution_departments($inst_id);
        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as head_display_name FROM {$wpdb->prefix}eess_departments d LEFT JOIN {$wpdb->users} u ON d.head_user_id = u.ID WHERE d.institution_id = %d AND (d.status = 'active' OR d.status IS NULL) ORDER BY d.id ASC",
            intval($inst_id)
        ));
    }

    public static function add_department($inst_id, $data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
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
        return $wpdb->insert_id;
    }

    public static function update_department($id, $data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
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
        return true;
    }

    public static function delete_department($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_departments", array('id' => intval($id)));
    }

    // --- SUBJECT CRUD METHODS ---
    public static function get_subjects_by_institution($inst_id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, d.name as department_name, u1.display_name as hod_display_name, u2.display_name as coordinator_display_name
             FROM {$wpdb->prefix}eess_subjects s
             LEFT JOIN {$wpdb->prefix}eess_departments d ON s.department_id = d.id
             LEFT JOIN {$wpdb->users} u1 ON s.hod_user_id = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON s.coordinator_user_id = u2.ID
             WHERE s.institution_id = %d AND (s.status = 'active' OR s.status IS NULL)
             ORDER BY s.name ASC",
            intval($inst_id)
        ));
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
        return $wpdb->delete("{$wpdb->prefix}eess_subjects", array('id' => intval($id)));
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
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
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
