<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم إرسال طلب التسجيل بنجاح</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Success Icon and Title -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    تم إرسال طلب التسجيل بنجاح!
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    شكراً لك على اهتمامك بالانضمام إلى فريق التدريس
                </p>
            </div>

            <!-- Success Message -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-info-circle ml-2 text-blue-600"></i>
                        ما يحدث بعد ذلك؟
                    </h3>
                    
                    <div class="space-y-4 text-sm text-gray-600">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">1</span>
                                </div>
                            </div>
                            <div class="mr-3">
                                <p class="font-medium text-gray-900">مراجعة الطلب</p>
                                <p>سيتم مراجعة طلبك من قبل إدارة الأكاديمية</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">2</span>
                                </div>
                            </div>
                            <div class="mr-3">
                                <p class="font-medium text-gray-900">التواصل معك</p>
                                <p>سنتواصل معك عبر البريد الإلكتروني أو الهاتف</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">3</span>
                                </div>
                            </div>
                            <div class="mr-3">
                                <p class="font-medium text-gray-900">تفعيل الحساب</p>
                                <p>بعد الموافقة، سيتم تفعيل حسابك وإرسال بيانات الدخول</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            ملاحظات مهمة
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>يرجى التأكد من صحة البريد الإلكتروني ورقم الهاتف</li>
                                <li>قد تستغرق عملية المراجعة من 1-3 أيام عمل</li>
                                <li>يمكنك متابعة حالة طلبك عبر البريد الإلكتروني</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}" 
                   class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <i class="fas fa-sign-in-alt ml-2"></i>
                    تسجيل الدخول
                </a>
                
                <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}" 
                   class="w-full flex justify-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <i class="fas fa-home ml-2"></i>
                    العودة للصفحة الرئيسية
                </a>
            </div>

            <!-- Contact Information -->
            <div class="text-center">
                <p class="text-sm text-gray-500">
                    لديك أسئلة؟ 
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                        تواصل معنا
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Auto-hide success message after 5 seconds -->
    <script>
        setTimeout(function() {
            const successMessage = document.querySelector('.bg-green-100');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html> 