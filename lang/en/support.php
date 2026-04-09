<?php

return [
    // Page titles
    'page_title' => 'Support Center',
    'my_tickets' => 'Support Tickets',
    'new_ticket' => 'Submit New Issue',
    'ticket_detail' => 'Issue Details',

    // Form
    'reason_label' => 'Issue Reason',
    'reason_placeholder' => 'Select the issue reason',
    'description_label' => 'Issue Description',
    'description_placeholder' => 'Describe the issue you are facing in detail...',
    'image_label' => 'Screenshot (optional)',
    'image_hint' => 'You can attach a screenshot to illustrate the issue (JPG, PNG, WEBP - max 5MB)',
    'submit' => 'Submit',
    'reply_placeholder' => 'Write your reply here...',
    'send_reply' => 'Send Reply',

    // List
    'no_tickets' => 'No issues submitted',
    'no_tickets_description' => 'You haven\'t submitted any issues yet. If you face any problem, you can submit it here.',
    'replies_count' => ':count replies',
    'created_at' => 'Submitted :date',
    'closed_at' => 'Closed :date',
    'closed_by' => 'Closed by :name',

    // Detail
    'ticket_info' => 'Issue Information',
    'conversation' => 'Conversation',
    'no_replies' => 'No replies yet',
    'no_replies_description' => 'Your issue will be responded to as soon as possible.',
    'ticket_closed_message' => 'This issue has been closed.',
    'admin_badge' => 'Admin',

    // Success messages
    'ticket_created' => 'Issue submitted successfully. You will receive a response soon.',
    'reply_sent' => 'Reply sent successfully.',
    'ticket_closed' => 'Issue closed successfully.',
    'settings_updated' => 'Settings updated successfully.',

    // Contact form (profile page)
    'contact_form_title' => 'Facing an issue?',
    'contact_form_default_message' => 'At Itqan, we are always striving to provide the best learning experience for Quran learners. If you face any technical issue or have any inquiry, do not hesitate to contact the administration and we will respond as soon as possible.',
    'contact_form_button' => 'Submit Your Issue',

    // Supervisor
    'supervisor' => [
        'page_title' => 'Support Tickets',
        'page_description' => 'View and manage issues submitted by teachers and students',
        'all_tickets' => 'All',
        'open_tickets' => 'Open',
        'closed_tickets' => 'Closed',
        'filter_status' => 'Status',
        'filter_reason' => 'Reason',
        'search_placeholder' => 'Search issues...',
        'reporter' => 'Reporter',
        'role' => 'Role',
        'reason' => 'Reason',
        'status' => 'Status',
        'date' => 'Date',
        'replies' => 'Replies',
        'close_ticket' => 'Close Issue',
        'close_confirm' => 'Are you sure you want to close this issue?',
        'replied_by' => 'Replied by',
        'no_tickets' => 'No issues',
        'no_tickets_description' => 'No issues have been submitted yet.',

        // Settings
        'settings_title' => 'Contact Form Settings',
        'settings_description' => 'Control the visibility of the issue submission form on the main page for students and teachers',
        'form_enabled' => 'Show contact form on main page',
        'message_ar_label' => 'Arabic Message',
        'message_en_label' => 'English Message',
        'save_settings' => 'Save Settings',
    ],

    // Validation
    'validation' => [
        'reason_required' => 'Please select the issue reason.',
        'description_required' => 'Please describe the issue.',
        'description_min' => 'Issue description must be at least 10 characters.',
        'description_max' => 'Issue description must not exceed 2000 characters.',
        'image_invalid' => 'The attached file must be an image.',
        'image_max' => 'Image size must not exceed 5MB.',
        'reply_required' => 'Please write a reply.',
        'reply_min' => 'Reply must be at least 2 characters.',
        'reply_max' => 'Reply must not exceed 2000 characters.',
    ],

    // Notifications
    'notifications' => [
        'new_ticket_title' => 'New Support Ticket',
        'new_ticket_message' => ':name submitted a new issue: :reason',
        'reply_title' => 'New Reply on Your Issue',
        'reply_message' => ':name replied to your issue',
    ],
];
