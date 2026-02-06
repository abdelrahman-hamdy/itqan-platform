/**
 * Homework Submission Form JavaScript
 * Handles file display, removal, and form validation
 */

function displaySelectedFiles(input) {
    var display = document.getElementById('selectedFilesDisplay');
    var files = input.files;

    if (files.length === 0) {
        display.classList.add('hidden');
        return;
    }

    display.classList.remove('hidden');
    display.innerHTML = '';

    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between bg-gray-50 rounded px-3 py-2';
        fileItem.innerHTML = '<div class="flex items-center text-sm">' +
            '<i class="ri-file-line text-blue-600 ms-2"></i>' +
            '<span class="font-medium text-gray-900">' + file.name + '</span>' +
            '<span class="text-xs text-gray-500 me-2">(' + (file.size / 1024).toFixed(2) + ' KB)</span>' +
            '</div>' +
            '<button type="button" onclick="removeFile(' + i + ')" class="text-red-600 hover:text-red-800">' +
            '<i class="ri-close-line text-lg"></i>' +
            '</button>';
        display.appendChild(fileItem);
    }
}

function removeFile(index) {
    var input = document.getElementById('submission_files');
    var dt = new DataTransfer();
    var files = input.files;

    for (var i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }

    input.files = dt.files;
    displaySelectedFiles(input);
}

// Form validation before submit
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('homeworkSubmissionForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var action = e.submitter ? e.submitter.value : 'submit';
        var textInput = document.getElementById('submission_text');
        var fileInput = document.getElementById('submission_files');
        var config = window.homeworkFormConfig || { submissionType: 'both', messages: {} };
        var submissionType = config.submissionType;

        // Only validate if submitting (not draft)
        if (action === 'submit') {
            var hasText = textInput && textInput.value.trim() !== '';
            var hasFiles = fileInput && fileInput.files.length > 0;

            if (submissionType === 'text' && !hasText) {
                e.preventDefault();
                if (window.toast && config.messages.writeSolution) {
                    window.toast.warning(config.messages.writeSolution);
                }
                textInput.focus();
                return false;
            }

            if (submissionType === 'file' && !hasFiles) {
                e.preventDefault();
                if (window.toast && config.messages.uploadFile) {
                    window.toast.warning(config.messages.uploadFile);
                }
                fileInput.focus();
                return false;
            }

            if (submissionType === 'both' && !hasText && !hasFiles) {
                e.preventDefault();
                if (window.toast && config.messages.solutionOrFile) {
                    window.toast.warning(config.messages.solutionOrFile);
                }
                return false;
            }
        }
    });
});
