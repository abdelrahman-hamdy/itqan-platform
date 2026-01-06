<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;

/**
 * Builds notification titles and messages.
 *
 * Handles localization and data interpolation for notification content.
 */
class NotificationContentBuilder
{
    /**
     * Get notification title based on type and data.
     *
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Data to interpolate
     * @return string The localized title
     */
    public function getTitle(NotificationType $type, array $data): string
    {
        return __($type->getTitleKey(), $data);
    }

    /**
     * Get notification message based on type and data.
     *
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Data to interpolate
     * @return string The localized message
     */
    public function getMessage(NotificationType $type, array $data): string
    {
        return __($type->getMessageKey(), $data);
    }

    /**
     * Build a complete notification content array.
     *
     * @param  NotificationType  $type  The notification type
     * @param  array  $data  Data to interpolate
     * @return array Array with 'title' and 'message' keys
     */
    public function build(NotificationType $type, array $data): array
    {
        return [
            'title' => $this->getTitle($type, $data),
            'message' => $this->getMessage($type, $data),
        ];
    }

    /**
     * Get a custom title without localization.
     *
     * @param  string  $title  The raw title
     * @param  array  $replacements  Key-value replacements
     */
    public function customTitle(string $title, array $replacements = []): string
    {
        return $this->interpolate($title, $replacements);
    }

    /**
     * Get a custom message without localization.
     *
     * @param  string  $message  The raw message
     * @param  array  $replacements  Key-value replacements
     */
    public function customMessage(string $message, array $replacements = []): string
    {
        return $this->interpolate($message, $replacements);
    }

    /**
     * Interpolate values into a string.
     *
     * @param  string  $text  The text with :placeholders
     * @param  array  $replacements  Key-value replacements
     */
    private function interpolate(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace(":{$key}", (string) $value, $text);
        }

        return $text;
    }
}
