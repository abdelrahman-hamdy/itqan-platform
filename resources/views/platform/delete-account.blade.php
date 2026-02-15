@extends('components.platform-layout')

@section('title', 'حذف الحساب - منصة إتقان')

@section('content')
  <!-- Page Header -->
  <div class="bg-gradient-to-r from-red-600 to-red-800 text-white py-16 pt-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold mb-4">حذف الحساب</h1>
        <p class="text-xl opacity-90">طلب حذف حسابك وبياناتك من منصة إتقان</p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">

      <div class="prose prose-lg max-w-none text-right" dir="rtl">

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">1. كيفية حذف حسابك</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            يمكنك طلب حذف حسابك من منصة إتقان بإحدى الطرق التالية:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>من خلال التطبيق: انتقل إلى الإعدادات > الحساب > حذف الحساب</li>
            <li>عبر البريد الإلكتروني: أرسل طلب حذف إلى <a href="mailto:support@itqanway.com" class="text-blue-600 hover:underline">support@itqanway.com</a> مع ذكر البريد الإلكتروني المسجل به</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. البيانات التي سيتم حذفها</h2>
          <p class="text-gray-700 leading-relaxed mb-4">عند حذف حسابك، سيتم حذف البيانات التالية نهائياً:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>معلومات الملف الشخصي (الاسم، البريد الإلكتروني، رقم الهاتف، الصورة الشخصية)</li>
            <li>سجل الحضور والغياب</li>
            <li>سجل المحادثات والرسائل</li>
            <li>تقارير التقدم الدراسي</li>
            <li>بيانات الاشتراكات والمدفوعات</li>
            <li>جميع الملفات والمرفقات المرفوعة</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. ملاحظات مهمة</h2>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>حذف الحساب عملية نهائية لا يمكن التراجع عنها</li>
            <li>ستفقد الوصول إلى جميع الدورات والحصص المسجل بها</li>
            <li>لن تتمكن من استرجاع أي بيانات أو محتوى بعد الحذف</li>
            <li>إذا كان لديك اشتراك نشط، يرجى إلغاؤه قبل طلب حذف الحساب</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. التواصل معنا</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            إذا كان لديك أي أسئلة حول عملية حذف الحساب، يرجى التواصل معنا عبر:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 mb-2"><strong>البريد الإلكتروني:</strong> support@itqanway.com</p>
          </div>
        </section>

        <div class="mt-12 pt-8 border-t border-gray-200">
          <p class="text-gray-600 text-sm">
            تاريخ آخر تحديث: {{ date('Y/m/d') }}
          </p>
        </div>

      </div>

    </div>
  </div>
@endsection
