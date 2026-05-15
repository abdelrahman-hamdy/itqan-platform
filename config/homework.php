<?php

/**
 * Homework configuration.
 *
 * Default attachment constraints used by the student API when a homework row
 * does not carry its own per-assignment overrides (currently: academic + the
 * legacy interactive rows that pre-date max_files / max_file_size_mb).
 */
return [
    'attachments' => [
        'max_files' => 5,
        'max_file_size_mb' => 10,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'],
        'submission_types' => ['text', 'file'],
    ],
];
