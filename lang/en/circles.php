<?php

return [
    'payment_required' => 'Please complete the payment to join the circle',
    'enrollment_success' => 'You have been successfully enrolled in the circle!',
    'enrollment_closed' => 'Enrollment for this circle is currently closed',
    'already_enrolled' => 'You are already enrolled in this circle',

    // Teacher transfer
    'transfer' => [
        'action_label' => 'Transfer to Another Teacher',
        'modal_heading' => 'Transfer Individual Circle',
        'modal_description' => 'The circle, active subscription, and future scheduled sessions will be transferred to the new teacher. Completed and cancelled sessions will not be affected.',
        'new_teacher_label' => 'New Teacher',
        'new_teacher_placeholder' => 'Select the new teacher',
        'reason_label' => 'Transfer Reason',
        'reason_placeholder' => 'Enter the reason for transfer (optional)',
        'confirm_button' => 'Transfer Circle',
        'success' => 'Circle successfully transferred to :teacher_name',
        'error' => 'An error occurred while transferring the circle',
        'same_teacher_error' => 'The selected teacher is the same as the current teacher',
        'log_message' => 'Individual circle transferred to a new teacher',
    ],
];
