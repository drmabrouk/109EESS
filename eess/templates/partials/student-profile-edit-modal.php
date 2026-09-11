<?php if (!defined('ABSPATH')) exit; ?>
<!-- REUSABLE UNIFIED 30-FIELD 5-STEP STUDENT PROFILE WIZARD MODAL -->
<div id="edit-student-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
    <div class="sm-modal-content" style="max-width: 960px; width: 95vw; border-radius: 20px; padding: 28px 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); font-family: 'Cairo', sans-serif;">
        <div class="sm-modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-users" style="color: #881337; font-size: 22px; width: 22px; height: 22px;"></span>
                <span id="edit-modal-title-text">إدارة وسجل الطالب الكامل (30 حقل)</span>
            </h3>
            <button type="button" class="sm-modal-close" onclick="closeUnifiedEditStudentModal()" style="background: none; border: none; font-size: 26px; color: #0f172a; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Wizard Step Progress Indicator (5 Steps) -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; position: relative;">
            <div style="position: absolute; top: 50%; left: 8%; right: 8%; height: 2px; background: #e2e8f0; z-index: 1;"></div>

            <!-- Step 1 Node: Identity & Personal -->
            <div id="eess-wiz-node-1" onclick="goUnifiedEditStep(1)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #881337; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; border: 2px solid #881337; transition: all 0.25s;" title="1. الهوية والبيانات الشخصية">1</div>
            <!-- Step 2 Node: Academic & Org Placement -->
            <div id="eess-wiz-node-2" onclick="goUnifiedEditStep(2)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;" title="2. التنسيق الأكاديمي والتنظيمي">2</div>
            <!-- Step 3 Node: Guardian & Location -->
            <div id="eess-wiz-node-3" onclick="goUnifiedEditStep(3)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;" title="3. ولي الأمر والتواصل والموقع">3</div>
            <!-- Step 4 Node: Financials -->
            <div id="eess-wiz-node-4" onclick="goUnifiedEditStep(4)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;" title="4. السجل المالي والرسوم">4</div>
            <!-- Step 5 Node: Health, Behavior & Account -->
            <div id="eess-wiz-node-5" onclick="goUnifiedEditStep(5)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;" title="5. السجل الصحي والحساب">5</div>
        </div>

        <form id="edit-student-form">
            <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
            <input type="hidden" name="student_id" id="edit_stu_id" value="0">
            <input type="hidden" name="photo_url" id="edit_stu_photo_url_val" value="">

            <!-- STEP 1: Identity & Personal Information -->
            <div id="eess-wiz-step-1" class="eess-wiz-panel" style="display: block;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 1: هويّة الطالب والبيانات الشّخصيّة والصورة</div>

                    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 16px; padding: 14px; background: #ffffff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <div id="edit_stu_photo_preview_box" style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 2px solid #cbd5e1; flex-shrink: 0;">
                            <svg id="edit_stu_default_icon" width="36" height="36" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <img id="edit_stu_photo_img" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;" />
                        </div>
                        <div>
                            <label for="edit_stu_photo_file" class="sm-btn" style="background: #881337; color: #ffffff; height: 32px; padding: 0 14px; font-size: 11.5px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">رفع / تغيير الصورة الشخصية</label>
                            <input type="file" id="edit_stu_photo_file" accept="image/*" style="display: none;" onchange="handleStudentPhotoSelected(this)">
                            <p style="margin: 4px 0 0 0; font-size: 11px; color: #64748b;">صورة شخصية رسمية خلفية بيضاء</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الاسم الكامل للطالب: <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="name" id="edit_stu_name" class="sm-input" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">كود الطالب المركزي (Student Code):</label>
                            <input type="text" name="student_code" id="edit_stu_code" readonly class="sm-input" placeholder="يولد تلقائياً من نظام الترقيم المركزي" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%; background: #f1f5f9; font-weight: 800; color: #881337; cursor: not-allowed;" title="كود معرف الطالب يولد تلقائياً من نظام الترقيم المركزي">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الجنس:</label>
                            <select name="gender" id="edit_stu_gender" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="ذكر">ذكر</option>
                                <option value="أنثى">أنثى</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">تاريخ الميلاد:</label>
                            <input type="date" name="dob" id="edit_stu_dob" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الجنسية المعترف بها:</label>
                            <select name="nationality" id="edit_stu_nationality" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="الإمارات">الإمارات</option>
                                <option value="السعودية">السعودية</option>
                                <option value="مصر">مصر</option>
                                <option value="الأردن">الأردن</option>
                                <option value="سوريا">سوريا</option>
                                <option value="عُمان">عُمان</option>
                                <option value="اليمن">اليمن</option>
                                <option value="السودان">السودان</option>
                                <option value="الكويت">الكويت</option>
                                <option value="البحرين">البحرين</option>
                                <option value="قطر">قطر</option>
                                <option value="فلسطين">فلسطين</option>
                                <option value="لبنان">لبنان</option>
                                <option value="العراق">العراق</option>
                                <option value="تونس">تونس</option>
                                <option value="الجزائر">الجزائر</option>
                                <option value="المغرب">المغرب</option>
                                <option value="أثيوبيا">أثيوبيا</option>
                                <option value="الهند">الهند</option>
                                <option value="باكستان">باكستان</option>
                                <option value="الفلبين">الفلبين</option>
                                <option value="أخرى">أخرى</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">رقم الهوية الوطنية / الإقامة:</label>
                            <input type="text" name="national_id" id="edit_stu_national_id" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Academic & Org Placement -->
            <div id="eess-wiz-step-2" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 2: التنسيق الأكاديمي والتنظيمي (الكود 1 إلى الكود 6)</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المؤسسة / المدرسة التابعة (Code 1 - Code 6): <span style="color: #dc2626;">*</span></label>
                            <select name="school_id" id="edit_stu_school_id" class="sm-select" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="">-- اختر المؤسسة أو المدرسة --</option>
                                <?php foreach (EESS_Org_Helper::get_all_institutions_and_schools() as $org): ?>
                                    <option value="<?php echo $org->id; ?>">Code <?php echo $org->school_code; ?> — <?php echo esc_html($org->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الصف الدراسي (Grade): <span style="color: #dc2626;">*</span></label>
                            <select name="class" id="edit_stu_class" class="sm-select" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="الصف الأول">الصف الأول</option>
                                <option value="الصف الثاني">الصف الثاني</option>
                                <option value="الصف الثالث">الصف الثالث</option>
                                <option value="الصف الرابع">الصف الرابع</option>
                                <option value="الصف الخامس">الصف الخامس</option>
                                <option value="الصف السادس">الصف السادس</option>
                                <option value="الصف السابع">الصف السابع</option>
                                <option value="الصف الثامن">الصف الثامن</option>
                                <option value="الصف التاسع">الصف التاسع</option>
                                <option value="الصف العاشر">الصف العاشر</option>
                                <option value="الصف الحادي عشر">الصف الحادي عشر</option>
                                <option value="الصف الثاني عشر">الصف الثاني عشر</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الشعبة (Section): <span style="color: #dc2626;">*</span></label>
                            <select name="section" id="edit_stu_section" class="sm-select" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="أ">أ</option>
                                <option value="ب">ب</option>
                                <option value="ج">ج</option>
                                <option value="د">د</option>
                                <option value="هـ">هـ</option>
                            </select>
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2; position: relative;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المعلم المربّي / المشرف الأكاديمي (Homeroom Teacher Search):</label>
                            <input type="hidden" name="teacher_id" id="edit_stu_teacher_id">
                            <input type="text" id="edit_stu_teacher_search" autocomplete="off" class="sm-input" placeholder="ابحث باسم المعلم المربّي (أدخل 3 أحرف على الأقل)..." style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                            <div id="edit_stu_teacher_results" style="display: none; position: absolute; top: 100%; right: 0; left: 0; z-index: 9999; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); max-height: 180px; overflow-y: auto; margin-top: 2px;"></div>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المستوى الأكاديمي:</label>
                            <input type="text" name="academic_level" id="edit_stu_acad_level" class="sm-input" placeholder="ممتاز / جيد جداً" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة الطالب (Student Status):</label>
                            <select name="student_status" id="edit_stu_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Active">نشط (Active)</option>
                                <option value="Inactive">غير نشط (Inactive)</option>
                                <option value="Graduated">متخرج (Graduated)</option>
                                <option value="Withdrawn">منسحب (Withdrawn)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة التسجيل (Enrollment Status):</label>
                            <select name="enrollment_status" id="edit_stu_enroll_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Enrolled">مقيد (Enrolled)</option>
                                <option value="Pending">معلق (Pending)</option>
                                <option value="Transferred">منقول (Transferred)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">تاريخ التسجيل (Registration Date):</label>
                            <input type="date" name="registration_date" id="edit_stu_reg_date" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Guardian & Location -->
            <div id="eess-wiz-step-3" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 3: ولي الأمر والتواصل والموقع الجغرافي</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">اسم ولي الأمر:</label>
                            <input type="text" name="guardian_name" id="edit_stu_guardian_name" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">صلة القرابة المعتمدة:</label>
                            <select name="guardian_relationship" id="edit_stu_guardian_rel" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="أب">أب (Father)</option>
                                <option value="أم">أم (Mother)</option>
                                <option value="أخي / أختي">أخي / أختي (Brother / Sister)</option>
                                <option value="أخرى">أخرى (Other)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">البريد الإلكتروني لولي الأمر:</label>
                            <input type="email" name="parent_email" id="edit_stu_email" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">رقم هاتف ولي الأمر (مع مفتاح الدولة):</label>
                            <div style="display: flex; gap: 8px;">
                                <select name="guardian_phone_country" id="edit_stu_phone_country" class="sm-select" style="width: 110px; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 11.5px;">
                                    <option value="+971">+971 (الإمارات)</option>
                                    <option value="+966">+966 (السعودية)</option>
                                    <option value="+20">+20 (مصر)</option>
                                    <option value="+962">+962 (الأردن)</option>
                                    <option value="+963">+963 (سوريا)</option>
                                    <option value="+967">+967 (اليمن)</option>
                                    <option value="+249">+249 (السودان)</option>
                                    <option value="+968">+968 (عمان)</option>
                                    <option value="+965">+965 (الكويت)</option>
                                    <option value="+973">+973 (البحرين)</option>
                                    <option value="+974">+974 (قطر)</option>
                                    <option value="+970">+970 (فلسطين)</option>
                                    <option value="+961">+961 (لبنان)</option>
                                    <option value="+964">+964 (العراق)</option>
                                </select>
                                <input type="text" name="guardian_phone" id="edit_stu_phone" class="sm-input" placeholder="501234567" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; flex: 1;">
                            </div>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الإمارة:</label>
                            <select name="emirate" id="edit_stu_emirate" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="أبوظبي">أبوظبي</option>
                                <option value="دبي">دبي</option>
                                <option value="الشارقة">الشارقة</option>
                                <option value="عجمان">عجمان</option>
                                <option value="أم القيوين">أم القيوين</option>
                                <option value="رأس الخيمة">رأس الخيمة</option>
                                <option value="الفجيرة">الفجيرة</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">عنوان السكن والتفاصيل:</label>
                            <input type="text" name="address" id="edit_stu_address" class="sm-input" placeholder="المنطقة، الشارع، البناية" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Financials & Tuition Fees -->
            <div id="eess-wiz-step-4" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 4: السجل المالي والرسوم المدرسية والدفعات</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">إجمالي الرسوم المدرسية (Total Tuition Fees):</label>
                            <input type="number" step="0.01" name="total_tuition_fees" id="edit_stu_total_fees" onchange="calcStudentBalance()" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المبلغ المدفوع (Amount Paid):</label>
                            <input type="number" step="0.01" name="amount_paid" id="edit_stu_amount_paid" onchange="calcStudentBalance()" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المبلغ المتبقي (Outstanding Balance):</label>
                            <input type="number" step="0.01" readonly id="edit_stu_balance" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%; background: #f8fafc; font-weight: bold; color: #dc2626;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة الدفع (Payment Status):</label>
                            <select name="payment_status" id="edit_stu_payment_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Pending">معلق (Pending)</option>
                                <option value="Paid">مدفوع بالكامل (Paid)</option>
                                <option value="Partial">مدفوع جزئياً (Partial)</option>
                                <option value="Overdue">متأخر (Overdue)</option>
                            </select>
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة الرسوم (Fee Status):</label>
                            <select name="fee_status" id="edit_stu_fee_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Unpaid">غير مدفوع (Unpaid)</option>
                                <option value="Paid">مسدد بالكامل (Paid)</option>
                                <option value="Partial">تسديد جزئي (Partial)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: Health, Special Needs, Behavior & Account Section -->
            <div id="eess-wiz-step-5" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 5: السجل الصحي، أصحاب الهمم، السلوك وإدارة الحساب الأكاديمي</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">من أصحاب الهمم (Special Needs):</label>
                            <select name="special_needs" id="edit_stu_special_needs" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="لا">لا (No)</option>
                                <option value="نعم">نعم (Yes)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الحالة الصحية العامة (Health Status):</label>
                            <input type="text" name="health_status" id="edit_stu_health_status" class="sm-input" placeholder="سليم / مريض سكري" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الحساسية والتنبيهات الطبية (Allergies):</label>
                            <input type="text" name="allergies" id="edit_stu_allergies" class="sm-input" placeholder="الفول السوداني; الحليب" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">تسجيل ملاحظة سلوكية أولية (Behavior Log):</label>
                            <textarea name="student_behavior" id="edit_stu_behavior" class="sm-textarea" rows="2" placeholder="أدخل أي ملاحظة سلوكية لتوليد سجل سلوكي رسمي فوراً للطالب..."></textarea>
                        </div>
                    </div>

                    <!-- Academic Login Account & Credentials Info Section -->
                    <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">
                            <h5 style="margin: 0; font-size: 13px; font-weight: 800; color: #0f172a;">🔐 حساب الدخول الأكاديمي وكلمة المرور</h5>
                            <span style="font-size: 11px; background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-weight: 800;">تلقائي عند الاعتماد</span>
                        </div>
                        <p style="margin: 0 0 10px 0; font-size: 11.5px; color: #64748b;">
                            يتم إنشاء وتفعيل حساب الطالب تلقائياً برتبة <strong>طالب (sm_student)</strong> وبكلمة مرور افتراضية تكون <strong>كود الطالب مكرر مرتين</strong> (مثال: Code: 123456 → Pass: 123456123456).
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px;">
                            <div>اسم المستخدم المعتمد: <strong id="edit_stu_username_display" style="color: #881337;">سيتم توليده من الكود</strong></div>
                            <div>حالة الحساب: <strong style="color: #16a34a;">نشط تلقائياً (Active)</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <button type="button" onclick="closeUnifiedEditStudentModal()" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1;">إلغاء</button>
                <button type="button" id="eess-wiz-prev-btn" onclick="goUnifiedEditStep(currentUnifiedStep - 1)" class="sm-btn" style="background: #e2e8f0; color: #334155; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; display: none;">السابق</button>
                <button type="button" id="eess-wiz-next-btn" onclick="goUnifiedEditStep(currentUnifiedStep + 1)" class="sm-btn" style="background: #0f172a; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800;">التالي</button>
                <button type="submit" id="eess-wiz-submit-btn" class="sm-btn" style="background: #881337; color: #ffffff; height: 38px; padding: 0 24px; border-radius: 8px; font-weight: 800; display: none;">حفظ واعتتماد بيانات الطالب الكاملة (30 حقل)</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentUnifiedStep = 1;

function calcStudentBalance() {
    const total = parseFloat(document.getElementById('edit_stu_total_fees').value) || 0;
    const paid = parseFloat(document.getElementById('edit_stu_amount_paid').value) || 0;
    document.getElementById('edit_stu_balance').value = Math.max(0, total - paid).toFixed(2);
}

function goUnifiedEditStep(step) {
    if (step < 1) step = 1;
    if (step > 5) step = 5;
    currentUnifiedStep = step;

    document.querySelectorAll('.eess-wiz-panel').forEach(p => p.style.display = 'none');
    document.getElementById('eess-wiz-step-' + step).style.display = 'block';

    for (let i = 1; i <= 5; i++) {
        const node = document.getElementById('eess-wiz-node-' + i);
        if (node) {
            if (i === step) {
                node.style.background = '#881337'; node.style.color = '#ffffff'; node.style.borderColor = '#881337';
            } else if (i < step) {
                node.style.background = '#16a34a'; node.style.color = '#ffffff'; node.style.borderColor = '#16a34a';
            } else {
                node.style.background = '#ffffff'; node.style.color = '#64748b'; node.style.borderColor = '#cbd5e1';
            }
        }
    }

    document.getElementById('eess-wiz-prev-btn').style.display = (step > 1) ? 'inline-flex' : 'none';
    document.getElementById('eess-wiz-next-btn').style.display = (step < 5) ? 'inline-flex' : 'none';
    document.getElementById('eess-wiz-submit-btn').style.display = (step === 5) ? 'inline-flex' : 'none';
}

function closeUnifiedEditStudentModal() {
    const modal = document.getElementById('edit-student-modal');
    if (modal) modal.style.display = 'none';
}

function handleStudentPhotoSelected(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const studentId = document.getElementById('edit_stu_id').value;

    if (!studentId || studentId === '0') {
        alert('يرجى تحديد الطالب أو حفظ بياناته أولاً قبل رفع الصورة');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_update_student_photo');
    formData.append('student_id', studentId);
    formData.append('student_photo', file);
    formData.append('sm_photo_nonce', '<?php echo wp_create_nonce("sm_photo_action"); ?>');

    const previewImg = document.getElementById('edit_stu_photo_img');
    const defaultIcon = document.getElementById('edit_stu_default_icon');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.photo_url) {
            const cacheBustedUrl = res.data.photo_url + '?v=' + new Date().getTime();
            previewImg.src = cacheBustedUrl;
            previewImg.style.display = 'block';
            if (defaultIcon) defaultIcon.style.display = 'none';
            document.getElementById('edit_stu_photo_url_val').value = res.data.photo_url;

            const rowPhoto = document.querySelector('#student-row-' + studentId + ' img.student-avatar');
            if (rowPhoto) rowPhoto.src = cacheBustedUrl;

            if (typeof smShowNotification === 'function') smShowNotification('تم تحديث صورة الطالب بنجاح');
        } else {
            alert('فشل رفع الصورة: ' + (res.data || 'خطأ غير معروف'));
        }
    });
}

(function() {
    window.editSmStudent = function(s) {
        if (!s) return;
        const titleEl = document.getElementById('edit-modal-title-text');
        if (titleEl) titleEl.innerText = 'تعديل بيانات وإدارة سجل الطالب (30 حقل)';

        document.getElementById('edit_stu_id').value = s.id || s.student_id || '0';
        document.getElementById('edit_stu_name').value = s.name || s.student_name || '';
        document.getElementById('edit_stu_class').value = s.class_name || s.class || 'الصف الأول';
        document.getElementById('edit_stu_section').value = s.section || 'أ';
        document.getElementById('edit_stu_email').value = s.parent_email || '';
        document.getElementById('edit_stu_nationality').value = s.nationality || 'الإمارات';
        document.getElementById('edit_stu_code').value = s.student_code || s.student_id || '';
        if (document.getElementById('edit_stu_national_id')) document.getElementById('edit_stu_national_id').value = s.national_id || '';
        if (document.getElementById('edit_stu_dob')) document.getElementById('edit_stu_dob').value = s.dob || '';
        if (document.getElementById('edit_stu_gender')) document.getElementById('edit_stu_gender').value = s.gender || 'ذكر';
        if (document.getElementById('edit_stu_school_id')) {
            document.getElementById('edit_stu_school_id').value = s.institution_id || s.school_id || '';
        }
        if (document.getElementById('edit_stu_teacher_id')) {
            document.getElementById('edit_stu_teacher_id').value = s.teacher_id || '';
        }
        if (document.getElementById('edit_stu_teacher_search')) {
            document.getElementById('edit_stu_teacher_search').value = s.teacher_name || (s.teacher_id ? ('معلم #' + s.teacher_id) : '');
        }
        if (document.getElementById('edit_stu_guardian_name')) document.getElementById('edit_stu_guardian_name').value = s.guardian_name || '';
        if (document.getElementById('edit_stu_guardian_rel')) document.getElementById('edit_stu_guardian_rel').value = s.guardian_relationship || 'أب';

        // Parse Phone & Country Code
        let fullPhone = s.guardian_phone || '';
        let phoneCountry = '+971';
        let phoneNum = fullPhone;
        if (fullPhone.startsWith('+')) {
            let parts = fullPhone.split(' ');
            phoneCountry = parts[0] || '+971';
            phoneNum = parts.slice(1).join('') || parts[0];
        }
        if (document.getElementById('edit_stu_phone_country')) document.getElementById('edit_stu_phone_country').value = phoneCountry;
        if (document.getElementById('edit_stu_phone')) document.getElementById('edit_stu_phone').value = phoneNum;

        if (document.getElementById('edit_stu_emirate')) document.getElementById('edit_stu_emirate').value = s.emirate || 'أبوظبي';
        if (document.getElementById('edit_stu_address')) document.getElementById('edit_stu_address').value = s.address || '';
        if (document.getElementById('edit_stu_status')) document.getElementById('edit_stu_status').value = s.student_status || 'Active';
        if (document.getElementById('edit_stu_enroll_status')) document.getElementById('edit_stu_enroll_status').value = s.enrollment_status || 'Enrolled';
        if (document.getElementById('edit_stu_reg_date')) document.getElementById('edit_stu_reg_date').value = s.registration_date || s.enrollment_date || '';
        if (document.getElementById('edit_stu_acad_level')) document.getElementById('edit_stu_acad_level').value = s.academic_level || 'ممتاز';
        if (document.getElementById('edit_stu_total_fees')) document.getElementById('edit_stu_total_fees').value = s.total_tuition_fees || '0.00';
        if (document.getElementById('edit_stu_amount_paid')) document.getElementById('edit_stu_amount_paid').value = s.amount_paid || '0.00';
        if (document.getElementById('edit_stu_payment_status')) document.getElementById('edit_stu_payment_status').value = s.payment_status || 'Pending';
        if (document.getElementById('edit_stu_fee_status')) document.getElementById('edit_stu_fee_status').value = s.fee_status || 'Unpaid';
        if (document.getElementById('edit_stu_special_needs')) document.getElementById('edit_stu_special_needs').value = s.special_needs ? 'نعم' : 'لا';
        if (document.getElementById('edit_stu_health_status')) document.getElementById('edit_stu_health_status').value = s.health_status || 'سليم';
        if (document.getElementById('edit_stu_allergies')) document.getElementById('edit_stu_allergies').value = s.allergies || '';
        if (document.getElementById('edit_stu_username_display')) document.getElementById('edit_stu_username_display').innerText = s.student_code || 'سيتم توليده تلقائياً';

        calcStudentBalance();

        const previewImg = document.getElementById('edit_stu_photo_img');
        const defaultIcon = document.getElementById('edit_stu_default_icon');
        if (s.photo_url) {
            previewImg.src = s.photo_url; previewImg.style.display = 'block';
            if (defaultIcon) defaultIcon.style.display = 'none';
            document.getElementById('edit_stu_photo_url_val').value = s.photo_url;
        } else {
            previewImg.src = ''; previewImg.style.display = 'none';
            if (defaultIcon) defaultIcon.style.display = 'block';
            document.getElementById('edit_stu_photo_url_val').value = '';
        }

        goUnifiedEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    };

    window.editSmStudentFromStats = window.editSmStudent;

    // Homeroom Teacher Autocomplete Event Listener
    const tSearchInput = document.getElementById('edit_stu_teacher_search');
    const tResultsDiv = document.getElementById('edit_stu_teacher_results');
    let tSearchTimer = null;

    if (tSearchInput && tResultsDiv) {
        tSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (tSearchTimer) clearTimeout(tSearchTimer);

            if (query.length < 3) {
                tResultsDiv.style.display = 'none';
                tResultsDiv.innerHTML = '';
                return;
            }

            tSearchTimer = setTimeout(() => {
                tResultsDiv.style.display = 'block';
                tResultsDiv.innerHTML = '<div style="padding: 10px; font-size: 11.5px; color: #64748b; text-align: center;">جاري البحث عن المعلم... ⏳</div>';

                const formData = new FormData();
                formData.append('action', 'eess_search_teachers_autocomplete');
                formData.append('query', query);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(t => {
                            html += `<div onclick="eessSelectHomeroomTeacher(${t.id}, '${t.name.replace(/'/g, "\\'")}')" style="padding: 9px 12px; font-size: 12px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">` +
                                    `<div>${t.name}</div>` +
                                    `<div style="font-size: 10.5px; color: #64748b; font-weight: 600;">رقم المعلم: ${t.employee_number || t.id}</div>` +
                                    `</div>`;
                        });
                        tResultsDiv.innerHTML = html;
                    } else {
                        tResultsDiv.innerHTML = '<div style="padding: 10px; font-size: 11.5px; color: #94a3b8; text-align: center;">لم يتم العثور على معلم مطابق للبحث.</div>';
                    }
                })
                .catch(() => {
                    tResultsDiv.style.display = 'none';
                });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!tSearchInput.contains(e.target) && !tResultsDiv.contains(e.target)) {
                tResultsDiv.style.display = 'none';
            }
        });
    }

    window.eessSelectHomeroomTeacher = function(tId, tName) {
        document.getElementById('edit_stu_teacher_id').value = tId;
        document.getElementById('edit_stu_teacher_search').value = tName;
        document.getElementById('edit_stu_teacher_results').style.display = 'none';
    };

    const editForm = document.getElementById('edit-student-form');
    if (editForm && !editForm.dataset.listenerAttached) {
        editForm.dataset.listenerAttached = 'true';
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerText = 'جاري الحفظ والتحديث...'; }

            const formData = new FormData(this);
            formData.append('action', 'sm_update_student_ajax');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (typeof smShowNotification === 'function') smShowNotification('✓ تم حفظ واعتتماد جميع بيانات الطالب الـ 30 بنجاح');
                    closeUnifiedEditStudentModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'حفظ واعتد السجل الكامل (30 حقل)'; }
                    alert('خطأ أثناء التحديث: ' + (res.data || 'فشل حفظ بيانات الطالب.'));
                }
            })
            .catch(err => {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'حفظ واعتد السجل الكامل (30 حقل)'; }
                alert('حدث خطأ أثناء الاتصال بالخادم.');
            });
        });
    }
})();
</script>
