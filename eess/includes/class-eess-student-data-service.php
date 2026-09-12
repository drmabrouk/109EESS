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
        if (class_exists('EESS_Org_Helper')) {
            $resolved = EESS_Org_Helper::normalize_section($input);
            return $resolved['ar'];
        }
        return 'أ';
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

        // Server-Side Role and Institution Scope Validation
        $curr_user_id = get_current_user_id();
        if ($curr_user_id > 0) {
            $user_roles = (array) wp_get_current_user()->roles;
            $can_edit_student = current_user_can('manage_options') || current_user_can('إدارة_الطلاب') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);

            if (!$can_edit_student) {
                return new WP_Error('unauthorized', 'عفواً، لا تمتلك الصلاحية الكافية لإضافة أو تعديل بيانات الطلاب.');
            }

            if (class_exists('EESS_Org_Helper')) {
                $user_scope = EESS_Org_Helper::get_user_scope($curr_user_id);
                if (!$user_scope['unrestricted']) {
                    $allowed_insts = array_map('intval', array_merge((array)($user_scope['institutions'] ?? array()), (array)($user_scope['schools'] ?? array())));
                    if ($student_id > 0) {
                        $existing_stu = $wpdb->get_row($wpdb->prepare("SELECT institution_id, school_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
                        if ($existing_stu) {
                            $st_inst = intval($existing_stu->institution_id ?: $existing_stu->school_id);
                            if ($st_inst > 0 && !empty($allowed_insts) && !in_array($st_inst, $allowed_insts, true)) {
                                return new WP_Error('access_denied', 'عفواً، لا تملك صلاحية تعديل طالب ينتمي لمؤسسة أخرى.');
                            }
                        }
                    }
                }
            }
        }

        if (empty($name)) {
            return new WP_Error('missing_name', 'اسم الطالب حقل إجباري.');
        }

        // De-duplication check during CSV Import / Creation (Resolve student_id before National ID check)
        $stu_code = sanitize_text_field($data['code'] ?? ($data['student_id_code'] ?? ($data['student_code'] ?? '')));
        if ($student_id == 0) {
            if (!empty($stu_code)) {
                $existing_by_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $stu_code));
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

        // Enforce Strict Uniqueness on National ID
        if (!empty($national_id)) {
            $existing_nat_stu = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s AND id != %d",
                $national_id, $student_id
            ));
            if ($existing_nat_stu) {
                return new WP_Error('duplicate_national_id', 'رقم الهوية الوطنية مكرر — لم يتم قبول التسجيل.');
            }

            $existing_nat_user = username_exists($national_id);
            if ($existing_nat_user) {
                $linked_stu_id = get_user_meta($existing_nat_user, 'eess_student_id', true);
                if ($linked_stu_id && intval($linked_stu_id) !== $student_id) {
                    return new WP_Error('duplicate_national_id', 'رقم الهوية الوطنية مكرر — لم يتم قبول التسجيل.');
                }
            }
        }



        // Automatic Institution & School Scope Resolution
        $raw_input_org = intval($data['school_id'] ?? ($data['institution_id'] ?? 0));
        if ($student_id == 0 && $raw_input_org <= 0) {
            $raw_input_org = 2; // Default institution for new students
        }

        $institution_id = $raw_input_org;
        $school_id      = $raw_input_org;

        if ($raw_input_org > 0) {
            $inst_row = $wpdb->get_row($wpdb->prepare("SELECT id, code FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $raw_input_org));
            if (!$inst_row) {
                $inst_row = $wpdb->get_row($wpdb->prepare("SELECT id, code FROM {$wpdb->prefix}eess_institutions WHERE code = %d", $raw_input_org));
            }
            if ($inst_row) {
                $institution_id = $inst_row->id;
                $school_id      = $inst_row->id;
            }
        }

        $financials = self::normalize_financials($data['total_tuition_fees'] ?? 0, $data['amount_paid'] ?? 0);

        // Standardize Guardian Phone Number with Country Code
        $phone_country = sanitize_text_field($data['guardian_phone_country'] ?? '+971');
        $phone_number  = sanitize_text_field($data['guardian_phone'] ?? '');
        $full_phone    = !empty($phone_number) ? (strpos($phone_number, '+') === 0 ? $phone_number : trim($phone_country . ' ' . $phone_number)) : '';

        $fields = array(
            'name'                  => $name,
            'class_name'            => $grade,
            'section'               => $section,
            'gender'                => sanitize_text_field($data['gender'] ?? 'ذكر'),
            'dob'                   => !empty($data['dob']) ? sanitize_text_field($data['dob']) : null,
            'nationality'           => sanitize_text_field($data['nationality'] ?? 'الإمارات العربية المتحدة'),
            'national_id'           => $national_id,
            'institution_id'        => $institution_id ?: null,
            'school_id'             => $school_id ?: null,
            'guardian_name'         => sanitize_text_field($data['guardian_name'] ?? ($data['parent_name'] ?? '')),
            'guardian_relationship' => sanitize_text_field($data['guardian_relationship'] ?? 'أب'),
            'parent_email'          => sanitize_email($data['parent_email'] ?? ($data['guardian_email'] ?? '')),
            'guardian_phone'        => $full_phone,
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
        }
        if (!empty($data['parent_user_id'])) {
            $fields['parent_user_id'] = intval($data['parent_user_id']);
        }
        $fields['teacher_id'] = !empty($data['teacher_id']) ? intval($data['teacher_id']) : null;

        if ($student_id > 0) {
            $updated = $wpdb->update("{$wpdb->prefix}sm_students", $fields, array('id' => $student_id));
            if ($updated === false) {
                return new WP_Error('db_update_failed', 'فشل تحديث بيانات الطالب في قاعدة البيانات: ' . $wpdb->last_error);
            }
            $final_id = $student_id;

            // Database Persistence Verification: Query actual database record to verify institution ID was saved
            if ($institution_id > 0) {
                $saved_inst_id = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
                if (intval($saved_inst_id) !== intval($institution_id)) {
                    return new WP_Error('db_verification_failed', 'فشل التحقق من حفظ المؤسسة في قاعدة البيانات. لم تتم عملية الحفظ بنجاح.');
                }
            }
        } else {
            // Resolve institution ID for code generation
            $inst_id = $institution_id ?: 1;
            if (empty($fields['student_code'])) {
                if (class_exists('EESS_ID_Code_Service')) {
                    $generated_code = EESS_ID_Code_Service::generate_student_code($inst_id);
                } else {
                    $generated_code = SM_DB::generate_student_code($school_id);
                }
                $fields['student_code'] = $generated_code;
            }
            $inserted = $wpdb->insert("{$wpdb->prefix}sm_students", $fields);
            if ($inserted === false || !$wpdb->insert_id) {
                return new WP_Error('db_insert_failed', 'فشل إضافة الطالب في قاعدة البيانات: ' . $wpdb->last_error);
            }
            $final_id = $wpdb->insert_id;

            // Database Persistence Verification for New Record
            if ($institution_id > 0) {
                $saved_inst_id = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
                if (intval($saved_inst_id) !== intval($institution_id)) {
                    return new WP_Error('db_verification_failed', 'فشل التحقق من حفظ المؤسسة في قاعدة البيانات. لم تتم عملية الحفظ بنجاح.');
                }
            }
        }

        // Invalidate transient & object caches to ensure immediate data updates
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");
        wp_cache_flush();

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

            // Technical WP Account & Role Synchronization for Student (National ID as Authoritative Login Username)
            $student_rec = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
            if ($student_rec) {
                $st_code = $student_rec->student_code;
                $login_username = !empty($student_rec->national_id) ? $student_rec->national_id : $st_code;

                if (!empty($login_username)) {
                    $user_id = username_exists($login_username);
                    $default_pass = $login_username;
                    $is_new_account = false;

                    if (!$user_id) {
                        $st_email = !empty($student_rec->parent_email) ? $student_rec->parent_email : ($login_username . '@eess.local');
                        $user_id = wp_create_user($login_username, $default_pass, $st_email);
                        $is_new_account = true;
                    }

                    if ($user_id && !is_wp_error($user_id)) {
                        $u = new WP_User($user_id);
                        $u->set_role('sm_student');
                        wp_update_user(array(
                            'ID'           => $user_id,
                            'display_name' => $student_rec->name
                        ));

                        if ($is_new_account) {
                            update_user_meta($user_id, 'eess_must_change_password', '1');
                            update_user_meta($user_id, 'sm_temp_pass', $default_pass);
                        }

                        update_user_meta($user_id, 'eess_student_id', $final_id);
                        update_user_meta($user_id, 'eess_student_code', $st_code);
                        update_user_meta($user_id, 'eess_national_id', $student_rec->national_id);
                        update_user_meta($user_id, 'eess_school_id', $student_rec->school_id);
                        update_user_meta($user_id, 'eess_institution_id', $student_rec->institution_id);
                        update_user_meta($user_id, 'institution_id', $student_rec->institution_id);
                        update_user_meta($user_id, 'eess_user_type', 'student');
                        update_user_meta($user_id, 'sm_account_status', 'active');
                        update_user_meta($user_id, 'eess_account_status', 'active');
                    }
                }
            }
        }

        return $final_id;
    }
}
