#!/bin/bash

FILE="/Users/abdelrahmanhamdy/web/itqan-platform/resources/views/public/quran-teachers/subscription-booking.blade.php"

# Billing Cycle section
sed -i '' 's/دورة الفوترة \*/{{ __('\''public.booking.quran.form.billing_cycle'\'') }}/g' "$FILE"
sed -i '' 's/>شهرياً</> {{ __('\''public.booking.quran.form.monthly'\'') }} </g' "$FILE"
sed -i '' 's/>ربع سنوي</> {{ __('\''public.booking.quran.form.quarterly'\'') }} </g' "$FILE"
sed -i '' 's/>سنوياً</> {{ __('\''public.booking.quran.form.yearly'\'') }} </g' "$FILE"
sed -i '' 's/وفر 10%/{{ __('\''public.booking.quran.package_info.save_10'\'') }}/g' "$FILE"
sed -i '' 's/وفر 20%/{{ __('\''public.booking.quran.package_info.save_20'\'') }}/g' "$FILE"

# Current Level section
sed -i '' 's/المستوى الحالي في تعلم القرآن \*/{{ __('\''public.booking.quran.form.current_level'\'') }}/g' "$FILE"
sed -i '' 's/اختر مستواك/{{ __('\''public.booking.quran.form.select_level'\'') }}/g' "$FILE"
sed -i '' 's/مبتدئ (لا أعرف القراءة)/{{ __('\''public.booking.quran.form.levels.beginner'\'') }}/g' "$FILE"
sed -i '' 's/أساسي (أقرأ ببطء)/{{ __('\''public.booking.quran.form.levels.elementary'\'') }}/g' "$FILE"
sed -i '' 's/متوسط (أقرأ بطلاقة)/{{ __('\''public.booking.quran.form.levels.intermediate'\'') }}/g' "$FILE"
sed -i '' 's/متقدم (أحفظ أجزاء من القرآن)/{{ __('\''public.booking.quran.form.levels.advanced'\'') }}/g' "$FILE"
sed -i '' 's/متمكن (أحفظ أكثر من 10 أجزاء)/{{ __('\''public.booking.quran.form.levels.expert'\'') }}/g' "$FILE"
sed -i '' 's/حافظ (أحفظ القرآن كاملاً)/{{ __('\''public.booking.quran.form.levels.hafiz'\'') }}/g' "$FILE"

# Learning Goals section
sed -i '' 's/أهدافك من تعلم القرآن \*/{{ __('\''public.booking.quran.form.learning_goals'\'') }}/g' "$FILE"
sed -i '' 's/<span class="mr-2">تعلم القراءة الصحيحة<\/span>/<span class="mr-2">{{ __('\''public.booking.quran.form.goals.reading'\'') }}<\/span>/g' "$FILE"
sed -i '' 's/<span class="mr-2">تعلم أحكام التجويد<\/span>/<span class="mr-2">{{ __('\''public.booking.quran.form.goals.tajweed'\'') }}<\/span>/g' "$FILE"
sed -i '' 's/<span class="mr-2">حفظ القرآن الكريم<\/span>/<span class="mr-2">{{ __('\''public.booking.quran.form.goals.memorization'\'') }}<\/span>/g' "$FILE"
sed -i '' 's/<span class="mr-2">تحسين الأداء والإتقان<\/span>/<span class="mr-2">{{ __('\''public.booking.quran.form.goals.improvement'\'') }}<\/span>/g' "$FILE"

# Preferred Schedule section
sed -i '' 's/>الجدول الزمني المفضل</>{{ __('\''public.booking.quran.form.preferred_schedule'\'') }}</g' "$FILE"

# Notes section
sed -i '' 's/>ملاحظات إضافية</>{{ __('\''public.booking.quran.form.notes'\'') }}</g' "$FILE"
sed -i '' 's/placeholder="أي معلومات إضافية تود مشاركتها مع المعلم..."/placeholder="{{ __('\''public.booking.quran.form.notes_placeholder'\'') }}"/g' "$FILE"

# Cost Summary section
sed -i '' 's/>ملخص التكلفة</>{{ __('\''public.booking.quran.form.cost_summary'\'') }}</g' "$FILE"
sed -i '' 's/x-show="billingCycle === '\''monthly'\''">سعر الباقة (شهرياً)/x-show="billingCycle === '\''monthly'\''">'{{ __('\''public.booking.quran.form.package_price_monthly'\'') }}/g' "$FILE"
sed -i '' 's/x-show="billingCycle === '\''quarterly'\''" x-cloak>سعر الباقة (ربع سنوي)/x-show="billingCycle === '\''quarterly'\''" x-cloak>{{ __('\''public.booking.quran.form.package_price_quarterly'\'') }}/g' "$FILE"
sed -i '' 's/x-show="billingCycle === '\''yearly'\''" x-cloak>سعر الباقة (سنوياً)/x-show="billingCycle === '\''yearly'\''" x-cloak>{{ __('\''public.booking.quran.form.package_price_yearly'\'') }}/g' "$FILE"
sed -i '' 's/<span>رسوم الخدمة<\/span>/<span>{{ __('\''public.booking.quran.form.service_fee'\'') }}<\/span>/g' "$FILE"
sed -i '' 's/<span>المجموع<\/span>/<span>{{ __('\''public.booking.quran.form.total'\'') }}<\/span>/g' "$FILE"

# Payment Terms section
sed -i '' 's/>شروط الدفع والاشتراك:</>{{ __('\''public.booking.quran.form.payment_terms.title'\'') }}</g' "$FILE"
sed -i '' 's/<li>• سيتم تحصيل الرسوم في بداية كل دورة فوترة<\/li>/<li>• {{ __('\''public.booking.quran.form.payment_terms.term_1'\'') }}<\/li>/g' "$FILE"
sed -i '' 's/<li>• يمكنك إلغاء الاشتراك في أي وقت قبل التجديد التلقائي<\/li>/<li>• {{ __('\''public.booking.quran.form.payment_terms.term_2'\'') }}<\/li>/g' "$FILE"
sed -i '' 's/<li>• سيقوم المعلم بالتواصل معك خلال 24 ساعة لتحديد مواعيد الجلسات<\/li>/<li>• {{ __('\''public.booking.quran.form.payment_terms.term_3'\'') }}<\/li>/g' "$FILE"
sed -i '' 's/<li>• يمكن إعادة جدولة الجلسات بتنسيق مسبق مع المعلم<\/li>/<li>• {{ __('\''public.booking.quran.form.payment_terms.term_4'\'') }}<\/li>/g' "$FILE"

# Buttons
sed -i '' 's/>المتابعة للدفع</>{{ __('\''public.booking.quran.form.submit'\'') }}</g' "$FILE"
sed -i '' 's/<span>إلغاء<\/span>/<span>{{ __('\''public.booking.quran.form.cancel'\'') }}<\/span>/g' "$FILE"

# JavaScript validation messages
sed -i '' 's/خطأ في النموذج:/{{ __('\''public.booking.quran.errors.form_error'\'') }}/g' "$FILE"
sed -i '' "s/'يجب اختيار دورة الفوترة'/'{{ __(\\'public.booking.quran.errors.billing_cycle\\') }}'/g" "$FILE"
sed -i '' "s/'يجب اختيار المستوى الحالي في تعلم القرآن'/'{{ __(\\'public.booking.quran.errors.current_level\\') }}'/g" "$FILE"
sed -i '' "s/'يجب اختيار هدف واحد على الأقل من أهداف التعلم'/'{{ __(\\'public.booking.quran.errors.learning_goals\\') }}'/g" "$FILE"

echo "Quran subscription booking view updated successfully!"
