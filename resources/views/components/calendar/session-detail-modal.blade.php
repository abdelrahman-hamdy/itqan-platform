{{--
    Shared Session Detail + Homework Modal for Teacher & Supervisor calendars.

    Props:
    - $sessionDetailRoute   (string) — URL for fetching session details
    - $updateSessionRoute   (string) — URL for updating session
    - $quranHomeworkRoute    (string) — URL for saving Quran homework
    - $academicHomeworkRoute (string) — URL for saving academic homework
    - $teacherId            (int|null) — null for teacher view, set for supervisor
    - $calendarVarName      (string) — JS window var to refetch ('teacherCalendar' | 'supervisorCalendar')
--}}
@props([
    'sessionDetailRoute',
    'updateSessionRoute',
    'quranHomeworkRoute',
    'academicHomeworkRoute',
    'teacherId' => null,
    'calendarVarName' => 'teacherCalendar',
])

<!-- Session Detail Modal -->
<div x-data="sessionDetailModal()" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" class="fixed inset-0 bg-black/50 z-40" @click="close()"></div>

    <!-- Modal -->
    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="close()">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <!-- Header -->
            <div class="relative p-5 rounded-t-xl" :class="{
                'bg-gradient-to-br from-blue-500 to-blue-600': session?.status === 'scheduled',
                'bg-gradient-to-br from-indigo-500 to-indigo-600': session?.status === 'ready',
                'bg-gradient-to-br from-amber-500 to-amber-600': session?.status === 'ongoing' || session?.status === 'live',
                'bg-gradient-to-br from-green-500 to-green-600': session?.status === 'completed',
                'bg-gradient-to-br from-red-500 to-red-600': session?.status === 'cancelled',
                'bg-gradient-to-br from-gray-500 to-gray-600': !session?.status
            }">
                <button @click="close()" class="absolute top-3 rtl:left-3 ltr:right-3 text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 cursor-pointer">
                    <i class="ri-close-line text-xl"></i>
                </button>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white/20 text-white" x-text="session?.status_label || ''"></span>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-white/10 text-white/90" x-text="getSourceLabel(session?.source)"></span>
                </div>
                <h3 class="text-lg font-bold text-white leading-tight" x-text="session?.title || ''"></h3>
            </div>

            <!-- Loading -->
            <template x-if="loading">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </template>

            <!-- Content (view mode) -->
            <template x-if="!loading && session && !editMode">
                <div class="p-5 space-y-4">
                    <!-- Date/Time -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                            <p class="text-[10px] font-semibold text-blue-600 uppercase mb-1"><i class="ri-calendar-event-line me-1"></i>{{ __('teacher.calendar.modal_date') }}</p>
                            <p class="text-sm font-bold text-gray-900" x-text="formatModalDate(session.scheduled_at)"></p>
                        </div>
                        <div class="bg-green-50 border border-green-100 rounded-lg p-3">
                            <p class="text-[10px] font-semibold text-green-600 uppercase mb-1"><i class="ri-time-line me-1"></i>{{ __('teacher.calendar.modal_time') }}</p>
                            <p class="text-sm font-bold text-gray-900" x-text="formatModalTime(session.scheduled_at)"></p>
                        </div>
                    </div>

                    <!-- Duration -->
                    <div class="bg-purple-50 border border-purple-100 rounded-lg p-3 flex items-center justify-between">
                        <span class="text-xs font-semibold text-purple-600"><i class="ri-hourglass-line me-1"></i>{{ __('teacher.calendar.modal_duration') }}</span>
                        <span class="text-sm font-bold text-gray-900" x-text="(session.duration_minutes || 60) + ' {{ __('teacher.calendar.minutes_short') }}'"></span>
                    </div>

                    <!-- Info rows -->
                    <div class="space-y-2">
                        <template x-if="session.student_name">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-user-line text-gray-400 w-5"></i>
                                <span class="text-gray-500">{{ __('teacher.calendar.student_label') }}</span>
                                <span class="font-medium text-gray-900" x-text="session.student_name"></span>
                            </div>
                        </template>
                        <template x-if="session.circle_name">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-group-line text-gray-400 w-5"></i>
                                <span class="text-gray-500">{{ __('teacher.calendar.circle_label') }}</span>
                                <span class="font-medium text-gray-900" x-text="session.circle_name"></span>
                            </div>
                        </template>
                        <template x-if="session.subject_name">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-book-line text-gray-400 w-5"></i>
                                <span class="text-gray-500">{{ __('teacher.calendar.subject_label') }}</span>
                                <span class="font-medium text-gray-900" x-text="session.subject_name"></span>
                            </div>
                        </template>
                        <template x-if="session.course_title">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-presentation-line text-gray-400 w-5"></i>
                                <span class="text-gray-500">{{ __('teacher.calendar.course_label') }}</span>
                                <span class="font-medium text-gray-900" x-text="session.course_title"></span>
                            </div>
                        </template>
                        <template x-if="session.meeting_link">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-video-line text-gray-400 w-5"></i>
                                <a :href="session.detail_url || '#'" class="text-blue-600 hover:underline text-sm">{{ __('teacher.calendar.join_meeting') }}</a>
                            </div>
                        </template>
                        <template x-if="session.teacher_notes">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-2">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1">{{ __('teacher.calendar.notes_label') }}</p>
                                <p class="text-sm text-gray-700" x-text="session.teacher_notes"></p>
                            </div>
                        </template>
                        <template x-if="session.has_homework">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-task-line text-green-500 w-5"></i>
                                <span class="text-green-600 font-medium">{{ __('teacher.calendar.has_homework') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-col gap-2 pt-2 border-t border-gray-100">
                        <a :href="session.detail_url || '#'" class="w-full text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                            <i class="ri-external-link-line me-1"></i> {{ __('teacher.calendar.view_full_session') }}
                        </a>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-if="session.can_edit">
                                <button @click="editData = { scheduled_at: utcToAcademyLocal(session.scheduled_at), duration_minutes: session.duration_minutes || 60, teacher_notes: session.teacher_notes || '' }; editMode = true" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                    <i class="ri-edit-line me-1"></i> {{ __('teacher.calendar.edit_session') }}
                                </button>
                            </template>
                            <button @click="openHomeworkModal()" class="px-4 py-2 bg-amber-50 text-amber-700 text-sm font-medium rounded-lg hover:bg-amber-100 border border-amber-200 transition-colors cursor-pointer">
                                <i class="ri-task-line me-1"></i> {{ __('teacher.calendar.manage_homework') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Content (edit mode) -->
            <template x-if="!loading && session && editMode">
                <div class="p-5 space-y-4">
                    <h4 class="text-sm font-bold text-gray-900 mb-3"><i class="ri-edit-line me-1 text-blue-600"></i>{{ __('teacher.calendar.edit_session') }}</h4>

                    <!-- Date/Time (Flatpickr for academy-timezone-aware today highlight) -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.modal_date') }} + {{ __('teacher.calendar.modal_time') }}</label>
                        <input type="text" x-ref="editDatetime" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer"
                               x-init="$nextTick(() => {
                                   if (!$refs.editDatetime) return;
                                   const tz = @js(\App\Services\AcademyContextService::getTimezone());
                                   const academyToday = new Date().toLocaleDateString('en-CA', { timeZone: tz });
                                   const fp = flatpickr($refs.editDatetime, {
                                       enableTime: true,
                                       time_24hr: false,
                                       dateFormat: 'Y-m-dTH:i',
                                       altInput: true,
                                       altFormat: 'Y-m-d h:i K',
                                       minuteIncrement: 15,
                                       defaultDate: editData.scheduled_at || null,
                                       onDayCreate: function(dObj, dStr, fp, dayElem) {
                                           dayElem.classList.remove('today');
                                           const dayDate = dayElem.dateObj.toLocaleDateString('en-CA');
                                           if (dayDate === academyToday) {
                                               dayElem.classList.add('today');
                                           }
                                       },
                                       onChange: function(selectedDates, dateStr) {
                                           editData.scheduled_at = dateStr;
                                       }
                                   });
                                   $watch('editMode', v => { if (!v && fp) fp.destroy(); });
                               })">
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.modal_duration') }}</label>
                        <select x-model="editData.duration_minutes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="30">30 {{ __('teacher.calendar.minutes_short') }}</option>
                            <option value="45">45 {{ __('teacher.calendar.minutes_short') }}</option>
                            <option value="60">60 {{ __('teacher.calendar.minutes_short') }}</option>
                            <option value="90">90 {{ __('teacher.calendar.minutes_short') }}</option>
                            <option value="120">120 {{ __('teacher.calendar.minutes_short') }}</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.notes_label') }}</label>
                        <textarea x-model="editData.teacher_notes" rows="3" maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="{{ __('teacher.calendar.notes_placeholder') }}"></textarea>
                    </div>

                    <!-- Save / Cancel -->
                    <div class="flex gap-2 pt-2">
                        <button @click="saveEdit()" :disabled="saving"
                                class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors cursor-pointer">
                            <template x-if="saving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                            {{ __('teacher.calendar.save_changes') }}
                        </button>
                        <button @click="editMode = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                            {{ __('teacher.calendar.cancel_edit') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Homework Modal -->
    <div x-show="homeworkOpen" class="fixed inset-0 bg-black/50 z-[60]" @click="homeworkOpen = false"></div>
    <div x-show="homeworkOpen" x-transition class="fixed inset-0 z-[70] flex items-center justify-center p-4" @click.self="homeworkOpen = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900"><i class="ri-task-line me-1 text-amber-600"></i>{{ __('teacher.calendar.manage_homework') }}</h3>
                <button @click="homeworkOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="ri-close-line text-xl"></i></button>
            </div>

            <!-- Quran Homework Form -->
            <template x-if="session && (session.source === 'quran_session' || session.source === 'circle_session')">
                <div class="p-5 space-y-4" @click="surahDropdownOpen = false">
                    <!-- New Memorization -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="hwData.has_new_memorization" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_new_memorization') }}</span>
                    </label>
                    <template x-if="hwData.has_new_memorization">
                        <div class="ps-6 space-y-2">
                            <select x-model="hwData.new_memorization_surah" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">{{ __('teacher.calendar.hw_select_surah') }}</option>
                                <template x-for="s in surahList" :key="s.value">
                                    <option :value="s.value" x-text="s.label"></option>
                                </template>
                            </select>
                            <input type="number" x-model.number="hwData.new_memorization_pages" min="1" placeholder="{{ __('teacher.calendar.hw_pages') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </template>

                    <!-- Review -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="hwData.has_review" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_review') }}</span>
                    </label>
                    <template x-if="hwData.has_review">
                        <div class="ps-6 space-y-2">
                            <select x-model="hwData.review_surah" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">{{ __('teacher.calendar.hw_select_surah') }}</option>
                                <template x-for="s in surahList" :key="s.value">
                                    <option :value="s.value" x-text="s.label"></option>
                                </template>
                            </select>
                            <input type="number" x-model.number="hwData.review_pages" min="1" placeholder="{{ __('teacher.calendar.hw_pages') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </template>

                    <!-- Comprehensive Review -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="hwData.has_comprehensive_review" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_comprehensive_review') }}</span>
                    </label>
                    <template x-if="hwData.has_comprehensive_review">
                        <div class="ps-6">
                            {{-- Selected tags --}}
                            <div class="flex flex-wrap gap-1 mb-2" x-show="hwData.comprehensive_review_surahs && hwData.comprehensive_review_surahs.length > 0">
                                <template x-for="sv in (hwData.comprehensive_review_surahs || [])" :key="sv">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        <span x-text="surahList.find(s => s.value == sv)?.label || sv"></span>
                                        <button type="button" @click.stop="hwData.comprehensive_review_surahs = hwData.comprehensive_review_surahs.filter(v => v !== sv)"
                                                class="text-blue-600 hover:text-blue-800 cursor-pointer">&times;</button>
                                    </span>
                                </template>
                            </div>
                            {{-- Search input --}}
                            <div class="relative" @click.stop>
                                <input type="text" x-model="surahSearch"
                                       @focus="surahDropdownOpen = true"
                                       @click.stop
                                       placeholder="{{ __('teacher.calendar.hw_search_surah') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                {{-- Dropdown list --}}
                                <div x-show="surahDropdownOpen" x-transition
                                     @click.stop
                                     class="absolute z-[60] mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <template x-for="s in surahList.filter(s => !surahSearch || s.label.includes(surahSearch) || s.value.toString().includes(surahSearch))" :key="s.value">
                                        <button type="button"
                                                @click.stop="if (hwData.comprehensive_review_surahs.includes(s.value)) { hwData.comprehensive_review_surahs = hwData.comprehensive_review_surahs.filter(v => v !== s.value); } else { hwData.comprehensive_review_surahs.push(s.value); }"
                                                class="w-full text-start px-3 py-1.5 text-sm hover:bg-blue-50 cursor-pointer flex items-center justify-between"
                                                :class="hwData.comprehensive_review_surahs.includes(s.value) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                            <span x-text="s.label"></span>
                                            <svg x-show="hwData.comprehensive_review_surahs.includes(s.value)" class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <div x-show="surahList.filter(s => !surahSearch || s.label.includes(surahSearch) || s.value.toString().includes(surahSearch)).length === 0"
                                         class="px-3 py-2 text-sm text-gray-400">{{ __('teacher.calendar.hw_no_results') }}</div>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">
                                <span x-text="(hwData.comprehensive_review_surahs || []).length"></span> {{ __('teacher.calendar.hw_selected_count') }}
                            </p>
                        </div>
                    </template>

                    <!-- Additional instructions -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.hw_instructions') }}</label>
                        <textarea x-model="hwData.additional_instructions" rows="2" maxlength="2000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                    </div>

                    <!-- Save -->
                    <button @click="saveQuranHomework()" :disabled="hwSaving"
                            class="w-full px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-colors cursor-pointer">
                        <template x-if="hwSaving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                        {{ __('teacher.calendar.hw_save') }}
                    </button>
                </div>
            </template>

            <!-- Academic Homework Form -->
            <template x-if="session && (session.source === 'academic_session' || session.source === 'course_session')">
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.hw_description') }}</label>
                        <textarea x-model="hwData.homework_description" rows="4" maxlength="5000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                  placeholder="{{ __('teacher.calendar.hw_description_placeholder') }}"></textarea>
                    </div>
                    <button @click="saveAcademicHomework()" :disabled="hwSaving || !hwData.homework_description"
                            class="w-full px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-colors cursor-pointer">
                        <template x-if="hwSaving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                        {{ __('teacher.calendar.hw_save') }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function sessionDetailModal() {
    return {
        open: false,
        loading: false,
        session: null,
        editMode: false,
        saving: false,
        editData: {},
        homeworkOpen: false,
        hwSaving: false,
        hwData: {},
        surahSearch: '',
        surahDropdownOpen: false,
        teacherId: @js($teacherId),
        calendarVarName: @js($calendarVarName),
        surahList: @js(collect(\App\Enums\QuranSurah::cases())->map(fn($s) => ['value' => $s->value, 'label' => $s->getNumber() . '. ' . $s->value])->values()),

        appendTeacherId(url, sep = '&') {
            return this.teacherId ? url + sep + 'teacher_id=' + this.teacherId : url;
        },

        async show(eventData) {
            this.open = true;
            this.loading = true;
            this.editMode = false;
            this.session = null;

            const eventId = eventData.id || '';
            const idParts = eventId.split('_');
            const sessionId = parseInt(idParts[idParts.length - 1]);
            const source = eventData.source;

            if (!sessionId || !source) {
                this.loading = false;
                return;
            }

            try {
                const url = this.appendTeacherId(
                    @js($sessionDetailRoute) + `?source=${source}&session_id=${sessionId}`
                );
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.session = data.session;
                }
            } catch (e) {
                // Failed to load
            } finally {
                this.loading = false;
            }
        },

        close() {
            this.open = false;
            this.editMode = false;
            this.homeworkOpen = false;
        },

        async refreshSession() {
            if (!this.session) return;
            try {
                const url = this.appendTeacherId(
                    @js($sessionDetailRoute) + `?source=${this.session.source}&session_id=${this.session.id}`
                );
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.session = data.session;
                }
            } catch (e) {
                // Silently fail
            }
        },

        getSourceLabel(source) {
            const labels = {
                'quran_session': @js(__('student.calendar.quran_individual_session')),
                'circle_session': @js(__('student.calendar.quran_circle_session')),
                'course_session': @js(__('student.calendar.course_session')),
                'academic_session': @js(__('student.calendar.academic_session'))
            };
            return labels[source] || source;
        },

        utcToAcademyLocal(isoStr) {
            if (!isoStr) return '';
            const academyTz = @js(\App\Services\AcademyContextService::getTimezone());
            const d = new Date(isoStr);
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: academyTz,
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', hour12: false
            }).formatToParts(d);
            const get = type => parts.find(p => p.type === type)?.value || '';
            const hour = get('hour') === '24' ? '00' : get('hour');
            return `${get('year')}-${get('month')}-${get('day')}T${hour}:${get('minute')}`;
        },

        formatModalDate(isoStr) {
            if (!isoStr) return '-';
            return new Date(isoStr).toLocaleDateString('ar-SA', {
                timeZone: @js(\App\Services\AcademyContextService::getTimezone()),
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        },

        formatModalTime(isoStr) {
            if (!isoStr) return '-';
            return new Date(isoStr).toLocaleTimeString('ar-SA', {
                timeZone: @js(\App\Services\AcademyContextService::getTimezone()),
                hour: '2-digit', minute: '2-digit', hour12: true
            });
        },

        openHomeworkModal() {
            this.homeworkOpen = true;
            this.surahSearch = '';
            this.surahDropdownOpen = false;
            // Pre-fill homework data from session
            if (this.session?.homework_data) {
                this.hwData = { ...this.session.homework_data };
            } else {
                this.hwData = {
                    has_new_memorization: false,
                    has_review: false,
                    has_comprehensive_review: false,
                    new_memorization_surah: '',
                    new_memorization_pages: null,
                    review_surah: '',
                    review_pages: null,
                    comprehensive_review_surahs: [],
                    additional_instructions: '',
                    homework_description: '',
                };
            }
        },

        async saveEdit() {
            this.saving = true;
            try {
                const body = {
                    source: this.session.source,
                    session_id: this.session.id,
                };
                if (this.teacherId) body.teacher_id = this.teacherId;
                if (this.editData.scheduled_at) body.scheduled_at = this.editData.scheduled_at;
                if (this.editData.duration_minutes) body.duration_minutes = parseInt(this.editData.duration_minutes);
                if (this.editData.teacher_notes !== undefined) body.teacher_notes = this.editData.teacher_notes;

                const response = await fetch(@js($updateSessionRoute), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json();
                if (data.success) {
                    if (window.toast) window.toast.success(data.message);
                    this.editMode = false;
                    if (window[this.calendarVarName]) window[this.calendarVarName].refetchEvents();
                    await this.refreshSession();
                } else {
                    if (window.toast) window.toast.error(data.message);
                }
            } catch (e) {
                if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
            } finally {
                this.saving = false;
            }
        },

        async saveQuranHomework() {
            this.hwSaving = true;
            try {
                const body = {
                    session_id: this.session.id,
                    ...this.hwData
                };
                if (this.teacherId) body.teacher_id = this.teacherId;
                const response = await fetch(@js($quranHomeworkRoute), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json();
                if (data.success) {
                    if (window.toast) window.toast.success(data.message);
                    this.homeworkOpen = false;
                    await this.refreshSession();
                } else {
                    if (window.toast) window.toast.error(data.message);
                }
            } catch (e) {
                if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
            } finally {
                this.hwSaving = false;
            }
        },

        async saveAcademicHomework() {
            this.hwSaving = true;
            try {
                const body = {
                    session_id: this.session.id,
                    source: this.session.source,
                    homework_description: this.hwData.homework_description,
                };
                if (this.teacherId) body.teacher_id = this.teacherId;
                const response = await fetch(@js($academicHomeworkRoute), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json();
                if (data.success) {
                    if (window.toast) window.toast.success(data.message);
                    this.homeworkOpen = false;
                    await this.refreshSession();
                } else {
                    if (window.toast) window.toast.error(data.message);
                }
            } catch (e) {
                if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
            } finally {
                this.hwSaving = false;
            }
        }
    };
}
</script>
