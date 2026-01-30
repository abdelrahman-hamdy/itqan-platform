<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    // Login
    'login' => [
        'title' => 'Login',
        'subtitle' => 'Welcome back! Sign in to continue',
        'welcome' => 'Welcome Back',
        'welcome_message' => 'Sign in to your account to continue',
        'email' => 'Email Address',
        'password' => 'Password',
        'password_placeholder' => 'Enter your password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Sign In',
        'login_button' => 'Login',
        'or' => 'Or',
        'register_student' => 'Register as Student',
        'register_teacher' => 'Register as Teacher',
        'register_parent' => 'Register as Parent',
        'no_account' => 'Don\'t have an account?',
        'register_link' => 'Register now',
        'or_login_with' => 'Or login with',
        'social_login' => [
            'google' => 'Login with Google',
            'facebook' => 'Login with Facebook',
            'twitter' => 'Login with Twitter',
            'apple' => 'Login with Apple',
        ],
    ],

    // Register
    'register' => [
        'title' => 'Register',
        'create_account' => 'Create Account',
        'welcome_message' => 'Create your account to get started',
        'full_name' => 'Full Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'register_as' => 'Register as',
        'role_student' => 'Student',
        'role_parent' => 'Parent',
        'role_teacher' => 'Teacher',
        'agree_to' => 'I agree to the',
        'terms_and_conditions' => 'Terms and Conditions',
        'privacy_policy' => 'Privacy Policy',
        'register_button' => 'Create Account',
        'have_account' => 'Already have an account?',
        'login_link' => 'Login here',
        'or_register_with' => 'Or register with',

        'parent' => [
            'title' => 'Register New Parent',
            'subtitle' => 'Join and follow your children\'s educational journey',
            'step1' => [
                'title' => 'Phone and Children Verification',
                'info_title' => 'Important Information',
                'info_text' => 'Enter your phone number registered with your children\'s accounts, then add student codes for verification',
                'phone_label' => 'Parent Phone Number',
                'phone_placeholder' => 'Enter phone number',
                'student_codes_label' => 'Student Codes',
                'student_code_placeholder' => 'Student code',
                'add_student' => 'Add another student',
                'max_students_info' => 'You can add up to 10 students',
                'verify_button' => 'Verify Student Codes',
                'verifying' => 'Verifying...',
            ],
            'step2' => [
                'title' => 'Personal Information',
                'first_name' => 'First Name',
                'first_name_placeholder' => 'Enter first name',
                'last_name' => 'Last Name',
                'last_name_placeholder' => 'Enter last name',
                'email' => 'Email Address',
                'password' => 'Password',
                'password_placeholder' => 'Enter password (at least 6 characters)',
                'password_helper' => 'Must be at least 6 characters with at least one letter and one number',
                'password_confirmation' => 'Confirm Password',
                'password_confirmation_placeholder' => 'Re-enter password',
                'password_mismatch' => 'Passwords do not match',
                'occupation' => 'Occupation (optional)',
                'occupation_placeholder' => 'Enter occupation',
                'address' => 'Address (optional)',
                'address_placeholder' => 'Enter address',
            ],
            'verification' => [
                'verified_title' => 'Successfully Verified',
                'already_has_parent_title' => 'Already Have Parent Account',
                'already_has_parent_info' => 'These students already have a registered parent account. They cannot be linked to a new account.',
                'unverified_title' => 'Incorrect Codes',
                'unverified_info' => 'Phone number does not match',
                'account_exists' => 'Account exists',
            ],
            'errors' => [
                'title' => 'Please correct the following errors:',
            ],
            'submit' => 'Create Account',
            'already_have_account' => 'Already have an account?',
            'login_link' => 'Login',
        ],

        'teacher' => [
            'title' => 'Register New Teacher',
            'subtitle' => 'Join our distinguished teaching team',
            'choose_type' => 'Choose Teaching Type',
            'next_button' => 'Next',
            'step1' => [
                'quran_teacher' => [
                    'title' => 'Quran Teacher',
                    'description' => 'Teaching Quran memorization and recitation',
                    'features' => [
                        'teaching' => 'Teaching Quran memorization',
                        'tajweed' => 'Tajweed and recitation rules',
                        'ijazah' => 'Quran Ijazah certificates',
                        'circles' => 'Group Quran circles',
                    ],
                ],
                'academic_teacher' => [
                    'title' => 'Academic Teacher',
                    'description' => 'Teaching various academic subjects',
                    'features' => [
                        'math_science' => 'Mathematics and Science',
                        'languages' => 'Arabic and English Language',
                        'social' => 'Social Studies',
                        'private_lessons' => 'Private tutoring',
                    ],
                ],
            ],
            'step2' => [
                'title' => 'Register New Teacher',
                'subtitle' => 'Complete your information to finish registration',
                'quran_teacher' => 'Quran Teacher',
                'academic_teacher' => 'Academic Teacher',
                'personal_info' => 'Personal Information',
                'professional_info' => 'Professional Information',
                'qualification' => 'Educational Qualification',
                'qualification_placeholder' => 'Choose educational qualification',
                'university' => 'University',
                'university_placeholder' => 'University name',
                'years_experience' => 'Years of Experience',
                'years_experience_placeholder' => 'Number of years of experience',
                'phone' => 'Phone Number',
                'phone_placeholder' => 'Enter phone number',
                'certifications' => 'Certifications & Ijazas',
                'certifications_helper' => 'Add your Quran certifications and Ijazas (press Enter after each)',
                'certifications_placeholder' => 'e.g., Ijazah in Hafs from Asim, Tajweed Certificate...',
                'subjects' => 'Subjects you can teach',
                'no_subjects' => 'No subjects available currently',
                'grade_levels' => 'Grade Levels',
                'no_grade_levels' => 'No grade levels available currently',
                'available_days' => 'Available Days',
                'security' => 'Account Security',
                'submit' => 'Submit Registration Request',
            ],
            'success' => [
                'title' => 'Registration Request Submitted Successfully!',
                'thank_you' => 'Thank you for your interest in joining our teaching team',
                'what_next_title' => 'What happens next?',
                'step1_title' => 'Review Request',
                'step1_description' => 'Your request will be reviewed by the academy management',
                'step2_title' => 'Contact You',
                'step2_description' => 'We will contact you via email or phone',
                'step3_title' => 'Account Activation',
                'step3_description' => 'After approval, your account will be activated and login credentials sent',
                'important_notes' => 'Important Notes',
                'note1' => 'Please ensure your email and phone number are correct',
                'note2' => 'Review process may take 1-3 business days',
                'note3' => 'You can follow your request status via email',
                'login_button' => 'Login',
                'home_button' => 'Back to Homepage',
                'contact_text' => 'Have questions?',
                'contact_link' => 'Contact us',
            ],
        ],

        'student' => [
            'title' => 'Register New Student',
            'subtitle' => 'Join us and start your educational journey',
            'personal_info' => 'Personal Information',
            'academic_info' => 'Academic Information',
            'first_name' => 'First Name',
            'first_name_placeholder' => 'Enter first name',
            'last_name' => 'Last Name',
            'last_name_placeholder' => 'Enter last name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'phone_placeholder' => 'Enter phone number',
            'birth_date' => 'Date of Birth',
            'gender' => 'Gender',
            'gender_placeholder' => 'Choose gender',
            'gender_male' => 'Male',
            'gender_female' => 'Female',
            'nationality' => 'Nationality',
            'nationality_placeholder' => 'Choose nationality',
            'grade_level' => 'Grade Level',
            'grade_level_placeholder' => 'Choose grade level',
            'parent_phone' => 'Parent Phone Number',
            'parent_phone_placeholder' => 'Enter parent phone number (optional)',
            'parent_phone_helper' => 'Will be used for emergency contact',
            'security' => 'Account Security',
            'password' => 'Password',
            'password_placeholder' => 'Enter password (at least 6 characters)',
            'password_helper' => 'Must be at least 6 characters with at least one letter and one number',
            'password_confirmation' => 'Confirm Password',
            'password_confirmation_placeholder' => 'Re-enter password',
            'submit' => 'Create Account',
            'already_have_account' => 'Already have an account?',
            'login_link' => 'Login',
        ],
    ],

    // Forgot Password
    'forgot_password' => [
        'title' => 'Forgot Password',
        'subtitle' => 'Enter your email to reset your password',
        'reset_password' => 'Reset Password',
        'message' => 'Enter your email address and we\'ll send you a link to reset your password',
        'info' => 'Enter your registered email and we will send you a password reset link.',
        'email' => 'Email Address',
        'email_placeholder' => 'example@domain.com',
        'send_link' => 'Send Reset Link',
        'submit' => 'Send Reset Link',
        'or' => 'Or',
        'back_to_login' => 'Back to Login',
        'link_sent' => 'Password reset link has been sent to your email',
    ],

    // Reset Password
    'reset_password' => [
        'title' => 'Reset Password',
        'subtitle' => 'Enter your new password',
        'email' => 'Email Address',
        'new_password' => 'New Password',
        'new_password_placeholder' => 'Enter new password',
        'new_password_helper' => 'Password must be at least 6 characters with at least one letter and one number',
        'confirm_password' => 'Confirm Password',
        'confirm_password_placeholder' => 'Re-enter password',
        'reset_button' => 'Reset Password',
        'submit' => 'Set New Password',
        'or' => 'Or',
        'password_reset' => 'Your password has been reset successfully',
        'back_to_login' => 'Back to Login',
    ],

    // Email Verification
    'verify_email' => [
        'title' => 'Verify Email',
        'message' => 'Please verify your email address to continue',
        'check_email' => 'We\'ve sent a verification link to your email address',
        'click_link' => 'Please click the link in the email to verify your account',
        'resend_email' => 'Didn\'t receive the email?',
        'resend_button' => 'Resend Verification Email',
        'email_sent' => 'A new verification link has been sent to your email address',
        'verified' => 'Your email has been verified successfully',
    ],

    // Two Factor Authentication
    'two_factor' => [
        'title' => 'Two Factor Authentication',
        'message' => 'Please enter the code sent to your device',
        'code' => 'Authentication Code',
        'verify_button' => 'Verify',
        'resend_code' => 'Resend Code',
        'use_backup_code' => 'Use a backup code',
        'backup_code' => 'Backup Code',
    ],

    // Logout
    'logout' => [
        'title' => 'Logout',
        'confirm' => 'Are you sure you want to logout?',
        'success' => 'You have been logged out successfully',
    ],

    // Authentication Messages
    'failed' => 'These credentials do not match our records',
    'password' => 'The provided password is incorrect',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds',
    'unauthorized' => 'You are not authorized to access this resource',
    'unauthenticated' => 'Please login to continue',
    'session_expired' => 'Your session has expired. Please login again',

    // Validation Messages
    'validation' => [
        'email_required' => 'Email address is required',
        'email_invalid' => 'Please enter a valid email address',
        'password_required' => 'Password is required',
        'password_min' => 'Password must be at least :min characters',
        'password_confirmation' => 'Password confirmation does not match',
        'name_required' => 'Name is required',
        'phone_required' => 'Phone number is required',
        'phone_invalid' => 'Please enter a valid phone number',
        'terms_required' => 'You must agree to the terms and conditions',
        'role_required' => 'Please select a role',
    ],

    // Success Messages
    'success' => [
        'login' => 'You have logged in successfully',
        'register' => 'Your account has been created successfully',
        'logout' => 'You have logged out successfully',
        'password_reset' => 'Your password has been reset successfully',
        'email_verified' => 'Your email has been verified successfully',
        'link_sent' => 'Reset link has been sent to your email',
    ],

    // Error Messages
    'errors' => [
        'invalid_credentials' => 'Invalid email or password',
        'account_disabled' => 'Your account has been disabled',
        'account_not_verified' => 'Please verify your email address first',
        'email_taken' => 'This email address is already registered',
        'phone_taken' => 'This phone number is already registered',
        'token_invalid' => 'This password reset token is invalid',
        'token_expired' => 'This password reset token has expired',
        'link_expired' => 'This verification link has expired',
        'something_wrong' => 'Something went wrong. Please try again',
    ],

    // Account Status
    'account_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending Verification',
        'suspended' => 'Suspended',
        'banned' => 'Banned',
    ],

    // Roles
    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'supervisor' => 'Supervisor',
        'teacher' => 'Teacher',
        'quran_teacher' => 'Quran Teacher',
        'academic_teacher' => 'Academic Teacher',
        'student' => 'Student',
        'parent' => 'Parent',
    ],

    // Session Management
    'sessions' => [
        'title' => 'Active Sessions',
        'current_device' => 'Current Device',
        'last_active' => 'Last Active',
        'logout_other_devices' => 'Logout Other Devices',
        'logout_all' => 'Logout All Sessions',
        'confirm_logout' => 'Are you sure you want to logout other devices?',
    ],

    // Security
    'security' => [
        'change_password' => 'Change Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'update_password' => 'Update Password',
        'password_changed' => 'Your password has been changed successfully',
        'password_requirements' => 'Password must be at least 6 characters and include at least one letter and one number',
        'enable_2fa' => 'Enable Two-Factor Authentication',
        'disable_2fa' => 'Disable Two-Factor Authentication',
        '2fa_enabled' => 'Two-factor authentication has been enabled',
        '2fa_disabled' => 'Two-factor authentication has been disabled',
    ],

    // Social Login
    'social' => [
        'login_with' => 'Login with :provider',
        'register_with' => 'Register with :provider',
        'link_account' => 'Link :provider Account',
        'unlink_account' => 'Unlink :provider Account',
        'account_linked' => ':provider account has been linked successfully',
        'account_unlinked' => ':provider account has been unlinked',
        'error' => 'Unable to authenticate with :provider',
    ],

    // Footer
    'footer' => [
        'rights' => 'All rights reserved ©',
        'platform_name' => 'Itqan Platform',
    ],

    // Email Verification Banner & Page
    'verification' => [
        'banner_message' => 'Your email address is not verified yet. Please check your inbox to verify your account.',
        'resend_link' => 'Resend verification link',
        'email_sent' => 'A verification link has been sent to your email address.',
        'already_verified' => 'Your email address has already been verified.',
        'verified_success' => 'Your email address has been verified successfully!',
        'invalid_link' => 'The verification link is invalid or has expired.',
        'page_title' => 'Verify Your Email',
        'page_message' => 'Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent to you.',
        'check_spam' => 'If you didn\'t receive the email, please check your spam folder.',
        'resend_button' => 'Resend Verification Email',
        'logout_button' => 'Logout',
    ],

    // Common
    'required' => 'Required',
    'optional' => 'Optional',

    // Circle enrollment
    'login_required_to_join' => 'You must log in first to join the circle',
];
