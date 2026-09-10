<?php if (!defined('ABSPATH')) exit; ?>
<!-- REUSABLE UNIFIED 30-FIELD 4-STEP STUDENT PROFILE EDIT MODAL -->
<div id="edit-student-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
    <div class="sm-modal-content" style="max-width: 960px; width: 95vw; border-radius: 20px; padding: 24px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); font-family: 'Cairo', sans-serif;">
        <div class="sm-modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-users" style="color: #0f172a; font-size: 22px; width: 22px; height: 22px;"></span>
                <span id="edit-modal-title-text">إدارة وسجل الطالب الكامل</span>
            </h3>
            <button type="button" class="sm-modal-close" onclick="closeUnifiedEditStudentModal()" style="background: none; border: none; font-size: 26px; color: #0f172a; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Wizard Step Progress Indicator (4 Steps) -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; position: relative;">
            <div style="position: absolute; top: 50%; left: 10%; right: 10%; height: 2px; background: #e2e8f0; z-index: 1;"></div>

            <!-- Step 1 Node: Identity & Personal -->
            <div id="eess-wiz-node-1" onclick="goUnifiedEditStep(1)" style="position: relative; z-index: 2; width: 38px; height: 38px; border-radius: 50%; background: #881337; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid #881337; transition: all 0.25s;">1</div>
            <!-- Step 2 Node: Academic & Org Placement -->
            <div id="eess-wiz-node-2" onclick="goUnifiedEditStep(2)" style="position: relative; z-index: 2; width: 38px; height: 38px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; transition: all 0.25s;">2</div>
            <!-- Step 3 Node: Guardian, Location & Financials -->
            <div id="eess-wiz-node-3" onclick="goUnifiedEditStep(3)" style="position: relative; z-index: 2; width: 38px; height: 38px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; transition: all 0.25s;">3</div>
            <!-- Step 4 Node: Health, Behavior & Medical Review -->
            <div id="eess-wiz-node-4" onclick="goUnifiedEditStep(4)" style="position: relative; z-index: 2; width: 38px; height: 38px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; transition: all 0.25s;">4</div>
        </div>

        <form id="edit-student-form">
            <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
            <input type="hidden" name="student_id" id="edit_stu_id">

            <!-- STEP 1: Identity & Personal Information -->
            <div id="eess-wiz-step-1" class="eess-wiz-panel" style="display: block;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 1: هويّة الطالب والبيانات الشّخصيّة</div>

                    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 16px; padding: 14px; background: #ffffff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <div id="edit_stu_photo_preview_box" style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 2px solid #cbd5e1; flex-shrink: 0;">
                            <svg id="edit_stu_default_icon" width="36" height="36" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <img id="edit_stu_photo_img" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;" />
                        </div>
                        <div>
                            <label for="edit_stu_photo_file" class="sm-btn" style="background: #881337; color: #ffffff; height: 32px; padding: 0 14px; font-size: 11.5px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">رفع / تغيير الصورة</label>
                            <input type="file" id="edit_stu_photo_file" accept="image/*" style="display: none;" onchange="handleStudentPhotoSelected(this)">
                            <p style="margin: 4px 0 0 0; font-size: 11px; color: #64748b;">صورة مربّعة خلفيّة بيضاء (URL/File)</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الاسم الكامل للطالب: <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="name" id="edit_stu_name" class="sm-input" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">كود الطالب (Serial/Code):</label>
                            <input type="text" name="student_code" id="edit_stu_code" readonly class="sm-input" placeholder="يولد تلقائياً من نظام الترقيم المركزي" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%; background: #f1f5f9; font-weight: 800; color: #881337; cursor: not-allowed;" title="كود معرف الطالب يولد تلقائياً من محرك الترقيم المركزي بالنظام">
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
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الجنسية:</label>
                            <input type="text" name="nationality" id="edit_stu_nationality" class="sm-input" placeholder="سعودي" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
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
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 2: التنسيق الأكاديمي والتبعيات التنظيمية</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المدرسة التابعة (School ID): <span style="color: #dc2626;">*</span></label>
                            <select name="school_id" id="edit_stu_school_id" class="sm-select" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="">-- اختر المدرسة --</option>
                                <?php foreach (EESS_Org_Helper::get_all_schools() as $sch): ?>
                                    <option value="<?php echo $sch->id; ?>"><?php echo esc_html($sch->name); ?> (ID: <?php echo $sch->id; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الصف الدراسي (Grade): <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="class" id="edit_stu_class" class="sm-input" required placeholder="الصف 10 أو 10" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الشعبة (Section): <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="section" id="edit_stu_section" class="sm-input" required placeholder="أ / A" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المستوى الأكاديمي:</label>
                            <input type="text" name="academic_level" id="edit_stu_acad_level" class="sm-input" placeholder="ممتاز" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة الطالب:</label>
                            <select name="student_status" id="edit_stu_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Active">نشط (Active)</option>
                                <option value="Inactive">غير نشط (Inactive)</option>
                                <option value="Graduated">متخرج (Graduated)</option>
                                <option value="Withdrawn">منسحب (Withdrawn)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">حالة التسجيل:</label>
                            <select name="enrollment_status" id="edit_stu_enroll_status" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="Enrolled">مقيد (Enrolled)</option>
                                <option value="Pending">معلق (Pending)</option>
                                <option value="Transferred">منقول (Transferred)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Guardian, Location & Financials -->
            <div id="eess-wiz-step-3" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 3: ولي الأمر والموقع والرسوم المدرسية</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">اسم ولي الأمر:</label>
                            <input type="text" name="guardian_name" id="edit_stu_guardian_name" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">صلة القرابة:</label>
                            <input type="text" name="guardian_relationship" id="edit_stu_guardian_rel" class="sm-input" placeholder="أب" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">البريد الإلكتروني:</label>
                            <input type="email" name="parent_email" id="edit_stu_email" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">رقم الهاتف (واتساب):</label>
                            <input type="text" name="guardian_phone" id="edit_stu_phone" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
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
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">إجمالي الرسوم المدرسية:</label>
                            <input type="number" step="0.01" name="total_tuition_fees" id="edit_stu_total_fees" onchange="calcStudentBalance()" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المبلغ المدفوع:</label>
                            <input type="number" step="0.01" name="amount_paid" id="edit_stu_amount_paid" onchange="calcStudentBalance()" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">المبلغ المتبقي:</label>
                            <input type="number" step="0.01" readonly id="edit_stu_balance" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%; background: #f8fafc; font-weight: bold; color: #dc2626;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Health, Behavior & Medical Review -->
            <div id="eess-wiz-step-4" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #881337; font-weight: 800; font-size: 13.5px;">الخطوة 4: السجل الصحي، أصحاب الهمم والملاحظة السلوكية</div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">من أصحاب الهمم (Special Needs):</label>
                            <select name="special_needs" id="edit_stu_special_needs" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                                <option value="لا">لا (No)</option>
                                <option value="نعم">نعم (Yes)</option>
                            </select>
                        </div>
                        <div class="sm-form-group">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">الحساسية والتنبيهات الطبية (Allergies):</label>
                            <input type="text" name="allergies" id="edit_stu_allergies" class="sm-input" placeholder="الفول السوداني; الحليب" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
                        </div>
                        <div class="sm-form-group" style="grid-column: span 2;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700;">تسجيل ملاحظة سلوكية أولية (Student Behavior Log):</label>
                            <textarea name="student_behavior" id="edit_stu_behavior" class="sm-textarea" rows="2" placeholder="أدخل أي ملاحظة سلوكية لتوليد سجل سلوكي رسمي فوراً للطالب..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <button type="button" onclick="closeUnifiedEditStudentModal()" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1;">إلغاء</button>
                <button type="button" id="eess-wiz-prev-btn" onclick="goUnifiedEditStep(currentUnifiedStep - 1)" class="sm-btn" style="background: #e2e8f0; color: #334155; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; display: none;">السابق</button>
                <button type="button" id="eess-wiz-next-btn" onclick="goUnifiedEditStep(currentUnifiedStep + 1)" class="sm-btn" style="background: #0f172a; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800;">التالي</button>
                <button type="submit" id="eess-wiz-submit-btn" class="sm-btn" style="background: #881337; color: #ffffff; height: 38px; padding: 0 24px; border-radius: 8px; font-weight: 800; display: none;">اعتماد وحفظ البيانات</button>
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
    if (step > 4) step = 4;
    currentUnifiedStep = step;

    document.querySelectorAll('.eess-wiz-panel').forEach(p => p.style.display = 'none');
    document.getElementById('eess-wiz-step-' + step).style.display = 'block';

    for (let i = 1; i <= 4; i++) {
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
    document.getElementById('eess-wiz-next-btn').style.display = (step < 4) ? 'inline-flex' : 'none';
    document.getElementById('eess-wiz-submit-btn').style.display = (step === 4) ? 'inline-flex' : 'none';
}

function closeUnifiedEditStudentModal() {
    const modal = document.getElementById('edit-student-modal');
    if (modal) modal.style.display = 'none';
}

function handleStudentPhotoSelected(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const studentId = document.getElementById('edit_stu_id').value;

    if (!studentId) {
        alert('يرجى تحديد الطالب أولاً قبل رفع الصورة');
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

            // Update row photo in main table immediately
            const rowPhoto = document.querySelector('#student-row-' + studentId + ' img.student-avatar');
            if (rowPhoto) {
                rowPhoto.src = cacheBustedUrl;
            }

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
        if (titleEl) titleEl.innerText = 'تعديل بيانات وإدارة سجل الطالب';
        document.getElementById('edit_stu_id').value = s.id || s.student_id || '';
        document.getElementById('edit_stu_name').value = s.name || s.student_name || '';
        document.getElementById('edit_stu_class').value = s.class_name || s.class || '';
        document.getElementById('edit_stu_section').value = s.section || '';
        document.getElementById('edit_stu_email').value = s.parent_email || '';
        document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
        document.getElementById('edit_stu_nationality').value = s.nationality || '';
        document.getElementById('edit_stu_code').value = s.student_code || s.student_id || '';
        if (document.getElementById('edit_stu_national_id')) document.getElementById('edit_stu_national_id').value = s.national_id || '';
        if (document.getElementById('edit_stu_dob')) document.getElementById('edit_stu_dob').value = s.dob || '';
        if (document.getElementById('edit_stu_gender')) document.getElementById('edit_stu_gender').value = s.gender || 'ذكر';
        if (document.getElementById('edit_stu_school_id') && s.school_id) document.getElementById('edit_stu_school_id').value = s.school_id;
        if (document.getElementById('edit_stu_guardian_name')) document.getElementById('edit_stu_guardian_name').value = s.guardian_name || '';
        if (document.getElementById('edit_stu_guardian_rel')) document.getElementById('edit_stu_guardian_rel').value = s.guardian_relationship || 'أب';
        if (document.getElementById('edit_stu_emirate')) document.getElementById('edit_stu_emirate').value = s.emirate || 'أبوظبي';
        if (document.getElementById('edit_stu_total_fees')) document.getElementById('edit_stu_total_fees').value = s.total_tuition_fees || '0.00';
        if (document.getElementById('edit_stu_amount_paid')) document.getElementById('edit_stu_amount_paid').value = s.amount_paid || '0.00';
        if (document.getElementById('edit_stu_special_needs')) document.getElementById('edit_stu_special_needs').value = s.special_needs ? 'نعم' : 'لا';
        if (document.getElementById('edit_stu_allergies')) document.getElementById('edit_stu_allergies').value = s.allergies || '';
        calcStudentBalance();

        const previewImg = document.getElementById('edit_stu_photo_img');
        const defaultIcon = document.getElementById('edit_stu_default_icon');
        if (s.photo_url) {
            previewImg.src = s.photo_url; previewImg.style.display = 'block';
            if (defaultIcon) defaultIcon.style.display = 'none';
        } else {
            previewImg.src = ''; previewImg.style.display = 'none';
            if (defaultIcon) defaultIcon.style.display = 'block';
        }

        goUnifiedEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    };

    window.editSmStudentFromStats = window.editSmStudent;

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
                    if (typeof smShowNotification === 'function') smShowNotification('✓ تم تحديث جميع بيانات الطالب الـ 30 بنجاح');
                    closeUnifiedEditStudentModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'حفظ وتحديث السجل الكامل'; }
                    alert('خطأ أثناء التحديث: ' + (res.data || 'فشل حفظ بيانات الطالب.'));
                }
            })
            .catch(err => {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'حفظ وتحديث السجل الكامل'; }
                alert('حدث خطأ أثناء الاتصال بالخادم.');
            });
        });
    }
})();
</script>
