{{--
    Calendar Event Details Modal Component
    Displays event/session details in a modal dialog
--}}

<!-- Event Details Modal -->
<div id="event-modal" class="modal-overlay" x-data @click="closeModal($event)" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-content" @click.stop>
        <!-- Modal Header with Gradient -->
        <div id="modal-header" class="relative bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-t-xl">
            <button @click="closeModal()" class="absolute top-4 left-4 text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg" aria-label="إغلاق">
                <i class="ri-close-line text-2xl" aria-hidden="true"></i>
            </button>
            <div class="pr-12">
                <!-- Status Badge -->
                <div id="modal-status" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/30 text-white mb-3"></div>
                <!-- Title -->
                <h3 id="modal-title" class="text-2xl font-bold text-white leading-tight"></h3>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <!-- Time & Duration Grid -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="ri-calendar-event-line text-blue-600 text-lg"></i>
                        <span class="text-xs font-semibold text-blue-600 uppercase">التاريخ</span>
                    </div>
                    <p id="modal-date" class="text-sm font-bold text-gray-900"></p>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="ri-time-line text-green-600 text-lg"></i>
                        <span class="text-xs font-semibold text-green-600 uppercase">الوقت</span>
                    </div>
                    <p id="modal-time" class="text-sm font-bold text-gray-900"></p>
                </div>
            </div>

            <!-- Duration -->
            <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ri-hourglass-line text-purple-600 text-lg"></i>
                        <span class="text-xs font-semibold text-purple-600 uppercase">مدة الجلسة</span>
                    </div>
                    <p id="modal-duration" class="text-lg font-bold text-gray-900"></p>
                </div>
            </div>

            <!-- Teacher Info -->
            <div id="modal-teacher-container" class="mb-6 hidden">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">المعلم</h4>
                <div id="modal-teacher" class="flex items-center gap-3 p-4 bg-gradient-to-r from-orange-50 to-orange-100/50 border border-orange-200 rounded-lg"></div>
            </div>

            <!-- Description -->
            <div id="modal-description-container" class="mb-6 hidden">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">وصف الجلسة</h4>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <p id="modal-description" class="text-sm text-gray-700 leading-relaxed"></p>
                </div>
            </div>

            <!-- Participants -->
            <div id="modal-participants-container" class="mb-6 hidden">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">المشاركون في الجلسة</h4>
                <div id="modal-participants" class="space-y-2"></div>
            </div>

            <!-- Action Button -->
            <a id="modal-view-button" href="#" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="ri-external-link-line text-lg ml-2"></i>
                <span>عرض صفحة الجلسة</span>
            </a>
        </div>
    </div>
</div>
