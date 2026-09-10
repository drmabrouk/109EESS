<?php
if (!defined('ABSPATH')) exit;

class EESS_Student_Data_Service {

    /**
     * Valid Emirates in the United Arab Emirates
     */
    public static function get_valid_emirates() {
        return array(
            'أبوظبي' => 'أبوظبي',
            'دبي' => 'دبي',
            'الشارقة' => 'الشارقة',
            'عجمان' => 'عجمان',
            'أم القيوين' => 'أم القيوين',
            'رأس الخيمة' => 'رأس الخيمة',
            'الفجيرة' => 'الفجيرة',
            'Abu Dhabi' => 'أبوظبي',
            'Dubai' => 'دبي',
            'Sharjah' => 'الشارقة',
            'Ajman' => 'عجمان',
            'Umm Al Quwain' => 'أم القيوين',
            'Ras Al Khaimah' => 'رأس الخيمة',
            'Fujairah' => 'الفجيرة'
        );
    }

    /**
     * Normalize Emirate string input
     */
    public static function normalize_emirate($input) {
        $clean = trim($input);
        $valid = self::get_valid_emirates();
        foreach ($valid as $key => $official) {
            if (mb_strtolower($clean) === mb_strtolower($key)) {
                return $official;
            }
        }
        return 'أبوظبي';
    }

    /**
     * Normalize Grade input (numeric or string)
     */
    public static function normalize_grade($input) {
        $clean = trim(str_ireplace(array('الصف', 'Grade', 'grade'), '', $input));
        $num = intval($clean);
        if ($num >= 1 && $num <= 12) {
            $grade_map = array(
                1 => 'الصف الأول', 2 => 'الصف الثاني', 3 => 'الصف الثالث', 4 => 'الصف الرابع',
                5 => 'الصف الخامس', 6 => 'الصف السادس', 7 => 'الصف السابع', 8 => 'الصف الثامن',
                9 => 'الصف التاسع', 10 => 'الصف العاشر', 11 => 'الصف الحادي عشر', 12 => 'الصف الثاني عشر'
            );
            return $grade_map[$num];
        }
        return !empty($input) ? sanitize_text_field($input) : 'الصف الأول';
    }

    /**
     * Normalize Section input
     */
    public static function normalize_section($input) {
        $clean = strtoupper(trim($input));
        $section_map = array(
            'A' => 'أ', 'B' => 'ب', 'C' => 'ج', 'D' => 'د',
            'أ' => 'أ', 'ب' => 'ب', 'ج' => 'ج', 'د' => 'د'
        );
        return $section_map[$clean] ?? (!empty($clean) ? $clean : 'أ');
    }

    /**
     * Normalize Special Needs boolean input
     */
    public static function normalize_special_needs($input) {
        $clean = mb_strtolower(trim($input));
        if (in_array($clean, array('نعم', 'yes', '1', 'true'))) {
            return 1;
        }
        return 0;
    }

    /**
     * Normalize multi-select Allergies input
     */
    public static function normalize_allergies($input) {
        if (empty($input)) return '';
        $items = array_map('trim', explode(';', $input));
        $cleaned = array();
        foreach ($items as $item) {
            if (!empty($item) && $item !== 'لا توجد حساسية' && $item !== 'No Known Allergy') {
                $cleaned[] = sanitize_text_field($item);
            }
        }
        return !empty($cleaned) ? implode('; ', array_unique($cleaned)) : 'لا توجد حساسية';
    }

    /**
     * Normalize financial numbers and calculate outstanding balance
     */
    public static function normalize_financials($total, $paid) {
        $total_val = max(0, floatval($total));
        $paid_val  = max(0, floatval($paid));
        $balance   = max(0, $total_val - $paid_val);
        $status    = ($balance <= 0 && $total_val > 0) ? 'Paid' : (($paid_val > 0) ? 'Partial' : 'Unpaid');

        return array(
            'total_tuition_fees'  => $total_val,
            'amount_paid'         => $paid_val,
            'outstanding_balance' => $balance,
            'fee_status'          => $status
        );
    }

    /**
     * Normalize and save a complete 30-field student record into the database
     */
    public static function process_and_save_student($data) {
        global $wpdb;
        SM_DB::ensure_student_columns_exist();

        $student_id = intval($data['id'] ?? ($data['student_id'] ?? 0));
        $name       = sanitize_text_field($data['name'] ?? ($data['full_name'] ?? ''));
        $grade      = self::normalize_grade($data['class_name'] ?? ($data['class'] ?? ($data['grade'] ?? '')));
        $section    = self::normalize_section($data['section'] ?? '');
        $national_id= sanitize_text_field($data['national_id'] ?? '');

        if (empty($name)) {
            return new WP_Error('missing_name', 'اسم الطالب حقل إجباري.');
        }

        // De-duplication check during CSV Import / Creation
        $stu_code = sanitize_text_field($data['code'] ?? ($data['student_id_code'] ?? ''));
        if ($student_id == 0) {
            if (!empty($stu_code)) {
                $existing_by_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_id = %s", $stu_code));
                if ($existing_by_code) {
                    $student_id = intval($existing_by_code);
                }
            }
            if ($student_id == 0 && !empty($national_id)) {
                $existing_by_nat = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s", $national_id));
                if ($existing_by_nat) {
                    $student_id = intval($existing_by_nat);
                }
            }
            if ($student_id == 0 && !empty($name) && !empty($grade) && !empty($section)) {
                $existing_by_name = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE name = %s AND class_name = %s AND section = %s", $name, $grade, $section));
                if ($existing_by_name) {
                    $student_id = intval($existing_by_name);
                }
            }
        }

        // Automatic School Recognition
        $school_id = intval($data['school_id'] ?? 0);
        if ($school_id > 0) {
            $sch_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id));
            if (!$sch_exists) {
                return new WP_Error('invalid_school', "معرف المدرسة ($school_id) غير مسجل في الهيكل التنظيمي.");
            }
        }

        $financials = self::normalize_financials($data['total_tuition_fees'] ?? 0, $data['amount_paid'] ?? 0);

        $fields = array(
            'name'                  => $name,
            'class_name'            => $grade,
            'section'               => $section,
            'gender'                => sanitize_text_field($data['gender'] ?? 'ذكر'),
            'dob'                   => !empty($data['dob']) ? sanitize_text_field($data['dob']) : null,
            'nationality'           => sanitize_text_field($data['nationality'] ?? ''),
            'national_id'           => $national_id,
            'school_id'             => $school_id ?: null,
            'guardian_name'         => sanitize_text_field($data['guardian_name'] ?? ($data['parent_name'] ?? '')),
            'guardian_relationship' => sanitize_text_field($data['guardian_relationship'] ?? 'أب'),
            'parent_email'          => sanitize_email($data['parent_email'] ?? ($data['guardian_email'] ?? '')),
            'guardian_phone'        => sanitize_text_field($data['guardian_phone'] ?? ''),
            'student_status'        => sanitize_text_field($data['student_status'] ?? 'Active'),
            'enrollment_status'     => sanitize_text_field($data['enrollment_status'] ?? 'Enrolled'),
            'enrollment_date'       => !empty($data['enrollment_date']) ? sanitize_text_field($data['enrollment_date']) : (!empty($data['registration_date']) ? sanitize_text_field($data['registration_date']) : date('Y-m-d')),
            'registration_date'     => !empty($data['registration_date']) ? sanitize_text_field($data['registration_date']) : date('Y-m-d'),
            'emirate'               => self::normalize_emirate($data['emirate'] ?? 'أبوظبي'),
            'address'               => sanitize_textarea_field($data['address'] ?? ''),
            'academic_level'        => sanitize_text_field($data['academic_level'] ?? 'ممتار'),
            'special_needs'         => self::normalize_special_needs($data['special_needs'] ?? 'لا'),
            'health_status'         => sanitize_textarea_field($data['health_status'] ?? 'سليم'),
            'allergies'             => self::normalize_allergies($data['allergies'] ?? ''),
            'photo_url'             => esc_url_raw($data['photo_url'] ?? ''),
            'fee_status'            => $financials['fee_status'],
            'total_tuition_fees'    => $financials['total_tuition_fees'],
            'amount_paid'           => $financials['amount_paid'],
            'outstanding_balance'   => $financials['outstanding_balance'],
            'payment_status'        => sanitize_text_field($data['payment_status'] ?? 'Pending')
        );

        if (!empty($data['student_code'])) {
            $fields['student_code'] = sanitize_text_field($data['student_code']);
            $fields['student_id']   = sanitize_text_field($data['student_code']);
        }
        if (!empty($data['parent_user_id'])) {
            $fields['parent_user_id'] = intval($data['parent_user_id']);
        }
        if (!empty($data['teacher_id'])) {
            $fields['teacher_id'] = intval($data['teacher_id']);
        }

        if ($student_id > 0) {
            $wpdb->update("{$wpdb->prefix}sm_students", $fields, array('id' => $student_id));
            $final_id = $student_id;
        } else {
            // Resolve institution ID for code generation
            $inst_id = 1;
            if ($school_id > 0) {
                $inst_id = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id)) ?: 1;
            }
            if (empty($fields['student_code']) || empty($fields['student_id'])) {
                if (class_exists('EESS_ID_Code_Service')) {
                    $generated_code = EESS_ID_Code_Service::generate_student_code($inst_id);
                } else {
                    $generated_code = SM_DB::generate_student_code($school_id);
                }
                $fields['student_code'] = $generated_code;
                $fields['student_id']   = $generated_code;
            }
            $wpdb->insert("{$wpdb->prefix}sm_students", $fields);
            $final_id = $wpdb->insert_id;
        }

        if ($final_id > 0) {
            EESS_Org_Helper::resolve_student_org_ids($final_id, $grade, $section);

            // Behavior Record Integration: Create permanent behavior log if behavior observation note provided
            $behavior_note = sanitize_textarea_field($data['student_behavior'] ?? ($data['behavior_note'] ?? ''));
            if (!empty($behavior_note)) {
                $wpdb->insert("{$wpdb->prefix}sm_records", array(
                    'student_id'   => $final_id,
                    'type'         => 'ملاحظة سلوكية',
                    'degree'       => 1,
                    'severity'     => 'low',
                    'action_taken' => 'ملاحظة سلوكية مسجلة عند قيد/تعديل بيانات الطالب',
                    'details'      => $behavior_note,
                    'status'       => 'approved',
                    'created_at'   => current_time('mysql')
                ));
            }

            // Technical WP Account & Role Synchronization for Student
            $student_rec = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
            if ($student_rec && !empty($student_rec->student_code)) {
                $st_code = $student_rec->student_code;
                $user_id = username_exists($st_code);

                if (!$user_id) {
                    $default_pass = $st_code . $st_code;
                    $st_email = !empty($student_rec->parent_email) ? $student_rec->parent_email : ($st_code . '@eess.local');
                    $user_id = wp_create_user($st_code, $default_pass, $st_email);
                }

                if ($user_id && !is_wp_error($user_id)) {
                    $u = new WP_User($user_id);
                    $u->set_role('sm_student');
                    wp_update_user(array(
                        'ID'           => $user_id,
                        'display_name' => $student_rec->name
                    ));

                    update_user_meta($user_id, 'eess_student_id', $final_id);
                    update_user_meta($user_id, 'eess_student_code', $st_code);
                    update_user_meta($user_id, 'eess_school_id', $student_rec->school_id);
                    update_user_meta($user_id, 'eess_user_type', 'student');
                }
            }
        }

        return $final_id;
    }
}
