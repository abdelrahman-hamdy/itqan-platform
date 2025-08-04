@props([
    'sessionId' => null,
    'trialRequestId' => null,
    'currentLink' => null,
    'currentPassword' => null,
    'currentMeetingId' => null,
    'academySubdomain' => null,
    'showTitle' => true,
    'mode' => 'form' // 'form' or 'ajax'
])

<div class="meeting-link-manager">
  @if($showTitle)
    <h4 class="font-medium text-gray-900 mb-4">
      <i class="ri-video-line text-primary ml-2"></i>
      إدارة رابط الاجتماع
    </h4>
  @endif

  <!-- Meeting Platform Selection -->
  <div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">
      <i class="ri-apps-line ml-1"></i>
      اختر منصة الاجتماع
    </label>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="platform-selector">
      <!-- Will be populated by JavaScript -->
    </div>
  </div>

  <!-- Manual Link Input -->
  <div class="space-y-4">
    <div>
      <label for="meeting_link_input" class="block text-sm font-medium text-gray-700 mb-2">
        <i class="ri-link ml-1"></i>
        رابط الاجتماع *
      </label>
      <div class="relative">
        <input type="url" 
               id="meeting_link_input" 
               name="meeting_link" 
               value="{{ $currentLink }}"
               placeholder="https://meet.google.com/xxx-xxx-xxx"
               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
               required>
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
          <i class="ri-external-link-line text-gray-400"></i>
        </div>
      </div>
      <div class="mt-1 text-xs text-gray-500">
        يُقبل روابط من: Google Meet، Zoom، Microsoft Teams، Webex، Jitsi Meet
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="meeting_password_input" class="block text-sm font-medium text-gray-700 mb-2">
          <i class="ri-lock-line ml-1"></i>
          كلمة مرور الاجتماع (اختياري)
        </label>
        <input type="text" 
               id="meeting_password_input" 
               name="meeting_password" 
               value="{{ $currentPassword }}"
               placeholder="كلمة المرور"
               maxlength="50"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
      </div>

      <div>
        <label for="meeting_id_input" class="block text-sm font-medium text-gray-700 mb-2">
          <i class="ri-hashtag ml-1"></i>
          معرف الاجتماع (تلقائي)
        </label>
        <input type="text" 
               id="meeting_id_input" 
               name="meeting_id" 
               value="{{ $currentMeetingId }}"
               placeholder="سيتم استخراجه تلقائياً"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50"
               readonly>
      </div>
    </div>
  </div>

  @if($mode === 'ajax')
    <!-- AJAX Mode Buttons -->
    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
      <button type="button" 
              id="generate-meeting-btn"
              class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
        <i class="ri-magic-line ml-1"></i>
        إنشاء رابط تلقائي
      </button>
      
      <button type="button" 
              id="save-meeting-link-btn"
              class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-secondary transition-colors">
        <i class="ri-save-line ml-2"></i>
        حفظ الرابط
      </button>
    </div>

    <!-- Loading Indicator -->
    <div id="meeting-loading" class="hidden text-center py-4">
      <div class="inline-flex items-center">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary ml-2"></div>
        <span class="text-sm text-gray-600">جارٍ المعالجة...</span>
      </div>
    </div>

    <!-- Success Message -->
    <div id="meeting-success" class="hidden bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
      <div class="flex items-center">
        <i class="ri-check-circle-line text-lg ml-2"></i>
        <span id="meeting-success-message">تم حفظ رابط الاجتماع بنجاح</span>
      </div>
    </div>

    <!-- Error Message -->
    <div id="meeting-error" class="hidden bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
      <div class="flex items-center">
        <i class="ri-error-warning-line text-lg ml-2"></i>
        <span id="meeting-error-message">حدث خطأ في معالجة الطلب</span>
      </div>
    </div>
  @endif

  <!-- Quick Help -->
  <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-start">
      <i class="ri-lightbulb-line text-blue-600 text-lg ml-2 mt-0.5"></i>
      <div class="text-sm text-blue-800">
        <h5 class="font-semibold mb-1">نصائح للاجتماعات الناجحة</h5>
        <ul class="list-disc list-inside space-y-1">
          <li>تأكد من اختبار الرابط قبل موعد الجلسة</li>
          <li>شارك الرابط مع الطالب قبل الجلسة بوقت كافٍ</li>
          <li>احتفظ بكلمة المرور في مكان آمن إذا كانت مطلوبة</li>
          <li>تأكد من استقرار الإنترنت قبل بدء الجلسة</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const meetingLinkInput = document.getElementById('meeting_link_input');
  const meetingIdInput = document.getElementById('meeting_id_input');
  const platformSelector = document.getElementById('platform-selector');
  
  @if($mode === 'ajax')
    const saveBtn = document.getElementById('save-meeting-link-btn');
    const generateBtn = document.getElementById('generate-meeting-btn');
    const loadingDiv = document.getElementById('meeting-loading');
    const successDiv = document.getElementById('meeting-success');
    const errorDiv = document.getElementById('meeting-error');
  @endif

  // Load meeting platforms
  fetch('{{ route("teacher.meetings.platforms", ["subdomain" => $academySubdomain]) }}')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populatePlatforms(data.data);
      }
    })
    .catch(error => console.error('Error loading platforms:', error));

  // Auto-extract meeting ID when link changes
  meetingLinkInput.addEventListener('input', function() {
    const url = this.value.trim();
    if (url) {
      const meetingId = extractMeetingId(url);
      meetingIdInput.value = meetingId || '';
    }
  });

  @if($mode === 'ajax')
    // Save meeting link
    saveBtn.addEventListener('click', function() {
      saveMeetingLink();
    });

    // Generate meeting link
    generateBtn.addEventListener('click', function() {
      generateMeetingLink();
    });
  @endif

  function populatePlatforms(platforms) {
    platformSelector.innerHTML = '';
    
    Object.entries(platforms).forEach(([key, platform]) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'flex items-center p-3 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary/5 transition-colors text-right';
      button.innerHTML = `
        <i class="${platform.icon} text-xl ${platform.color} ml-2"></i>
        <div>
          <div class="font-medium text-gray-900">${platform.name}</div>
          <div class="text-xs text-gray-500">${platform.url_pattern}</div>
        </div>
      `;
      
      button.addEventListener('click', function() {
        meetingLinkInput.value = platform.example;
        meetingLinkInput.focus();
        meetingLinkInput.dispatchEvent(new Event('input'));
      });
      
      platformSelector.appendChild(button);
    });
  }

  function extractMeetingId(url) {
    try {
      const urlObj = new URL(url);
      const host = urlObj.hostname;
      const path = urlObj.pathname;
      
      // Google Meet
      if (host.includes('meet.google.com')) {
        return path.split('/').pop();
      }
      
      // Zoom
      if (host.includes('zoom.us')) {
        const match = path.match(/\/j\/(\d+)/);
        return match ? match[1] : null;
      }
      
      // Jitsi
      if (host.includes('meet.jit.si')) {
        return path.split('/').pop();
      }
      
      // Generic
      return path.split('/').pop() || null;
    } catch (e) {
      return null;
    }
  }

  @if($mode === 'ajax')
    function saveMeetingLink() {
      const meetingLink = meetingLinkInput.value.trim();
      const meetingPassword = document.getElementById('meeting_password_input').value.trim();
      const meetingId = meetingIdInput.value.trim();

      if (!meetingLink) {
        showError('يرجى إدخال رابط الاجتماع');
        return;
      }

      showLoading(true);
      hideMessages();

      const url = @if($sessionId)
        '{{ route("teacher.meetings.session.update", ["subdomain" => $academySubdomain, "session" => $sessionId]) }}'
      @elseif($trialRequestId)
        '{{ route("teacher.meetings.trial.update", ["subdomain" => $academySubdomain, "trialRequest" => $trialRequestId]) }}'
      @else
        null
      @endif;

      if (!url) {
        showError('خطأ في النظام: لم يتم تحديد الجلسة');
        showLoading(false);
        return;
      }

      fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          meeting_link: meetingLink,
          meeting_password: meetingPassword,
          meeting_id: meetingId
        })
      })
      .then(response => response.json())
      .then(data => {
        showLoading(false);
        if (data.success) {
          showSuccess(data.message);
          // Update display values
          if (data.data) {
            meetingIdInput.value = data.data.meeting_id || '';
          }
        } else {
          showError(data.error || 'حدث خطأ غير متوقع');
        }
      })
      .catch(error => {
        showLoading(false);
        showError('حدث خطأ في الاتصال بالخادم');
      });
    }

    function generateMeetingLink() {
      @if($sessionId)
        const url = '{{ route("teacher.meetings.session.generate", ["subdomain" => $academySubdomain, "session" => $sessionId]) }}';
      @else
        showError('إنشاء الروابط التلقائية متاح فقط للجلسات المجدولة');
        return;
      @endif

      showLoading(true);
      hideMessages();

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
        }
      })
      .then(response => response.json())
      .then(data => {
        showLoading(false);
        if (data.success && data.data) {
          meetingLinkInput.value = data.data.meeting_link;
          meetingIdInput.value = data.data.meeting_id || '';
          showSuccess(data.message);
        } else {
          showError(data.error || 'فشل في إنشاء رابط الاجتماع');
        }
      })
      .catch(error => {
        showLoading(false);
        showError('حدث خطأ في الاتصال بالخادم');
      });
    }

    function showLoading(show) {
      loadingDiv.classList.toggle('hidden', !show);
      saveBtn.disabled = show;
      generateBtn.disabled = show;
    }

    function showSuccess(message) {
      document.getElementById('meeting-success-message').textContent = message;
      successDiv.classList.remove('hidden');
      setTimeout(() => successDiv.classList.add('hidden'), 5000);
    }

    function showError(message) {
      document.getElementById('meeting-error-message').textContent = message;
      errorDiv.classList.remove('hidden');
      setTimeout(() => errorDiv.classList.add('hidden'), 8000);
    }

    function hideMessages() {
      successDiv.classList.add('hidden');
      errorDiv.classList.add('hidden');
    }
  @endif
});
</script>