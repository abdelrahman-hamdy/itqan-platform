<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionOperationException extends Exception
{
    protected string $sessionType;

    protected string $sessionId;

    protected string $currentStatus;

    protected string $attemptedOperation;

    protected ?array $additionalContext;

    public function __construct(
        string $message,
        string $sessionType,
        string $sessionId,
        string $currentStatus,
        string $attemptedOperation,
        ?array $additionalContext = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->sessionType = $sessionType;
        $this->sessionId = $sessionId;
        $this->currentStatus = $currentStatus;
        $this->attemptedOperation = $attemptedOperation;
        $this->additionalContext = $additionalContext;
    }

    /**
     * Create exception for invalid status transition
     */
    public static function invalidTransition(
        string $sessionType,
        string $sessionId,
        string $currentStatus,
        string $targetStatus
    ): self {
        $sessionTypeLabel = match ($sessionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'interactive' => 'التفاعلي',
            default => 'غير محدد',
        };

        $currentStatusLabel = self::getStatusLabel($currentStatus);
        $targetStatusLabel = self::getStatusLabel($targetStatus);

        $message = sprintf(
            'لا يمكن تغيير حالة جلسة %s من "%s" إلى "%s"',
            $sessionTypeLabel,
            $currentStatusLabel,
            $targetStatusLabel
        );

        $context = [
            'target_status' => $targetStatus,
            'allowed_transitions' => self::getAllowedTransitions($currentStatus),
        ];

        return new self(
            $message,
            $sessionType,
            $sessionId,
            $currentStatus,
            "transition_to_{$targetStatus}",
            $context
        );
    }

    /**
     * Create exception for already completed session
     */
    public static function alreadyCompleted(
        string $sessionType,
        string $sessionId,
        string $attemptedOperation
    ): self {
        $sessionTypeLabel = match ($sessionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'interactive' => 'التفاعلي',
            default => 'غير محدد',
        };

        $operationLabel = self::getOperationLabel($attemptedOperation);

        $message = sprintf(
            'لا يمكن %s لجلسة %s - الجلسة مكتملة بالفعل',
            $operationLabel,
            $sessionTypeLabel
        );

        return new self(
            $message,
            $sessionType,
            $sessionId,
            'completed',
            $attemptedOperation
        );
    }

    /**
     * Create exception for cancelled session
     */
    public static function sessionCancelled(
        string $sessionType,
        string $sessionId,
        string $attemptedOperation,
        ?string $cancellationReason = null
    ): self {
        $sessionTypeLabel = match ($sessionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'interactive' => 'التفاعلي',
            default => 'غير محدد',
        };

        $operationLabel = self::getOperationLabel($attemptedOperation);

        $message = sprintf(
            'لا يمكن %s لجلسة %s - الجلسة ملغاة',
            $operationLabel,
            $sessionTypeLabel
        );

        $context = $cancellationReason ? ['cancellation_reason' => $cancellationReason] : null;

        return new self(
            $message,
            $sessionType,
            $sessionId,
            'cancelled',
            $attemptedOperation,
            $context
        );
    }

    /**
     * Create exception for not started session
     */
    public static function notStarted(
        string $sessionType,
        string $sessionId,
        string $attemptedOperation
    ): self {
        $sessionTypeLabel = match ($sessionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'interactive' => 'التفاعلي',
            default => 'غير محدد',
        };

        $operationLabel = self::getOperationLabel($attemptedOperation);

        $message = sprintf(
            'لا يمكن %s لجلسة %s - الجلسة لم تبدأ بعد',
            $operationLabel,
            $sessionTypeLabel
        );

        return new self(
            $message,
            $sessionType,
            $sessionId,
            'scheduled',
            $attemptedOperation
        );
    }

    /**
     * Create exception for concurrent modification
     */
    public static function concurrentModification(
        string $sessionType,
        string $sessionId,
        string $currentStatus,
        string $attemptedOperation
    ): self {
        $message = 'الجلسة قيد التعديل من قبل مستخدم آخر. يرجى المحاولة مرة أخرى';

        return new self(
            $message,
            $sessionType,
            $sessionId,
            $currentStatus,
            $attemptedOperation,
            ['conflict' => true]
        );
    }

    /**
     * Create exception for missing prerequisites
     */
    public static function missingPrerequisites(
        string $sessionType,
        string $sessionId,
        string $currentStatus,
        string $attemptedOperation,
        array $missingPrerequisites
    ): self {
        $prerequisitesString = implode('، ', $missingPrerequisites);

        $message = sprintf(
            'لا يمكن إتمام العملية. المتطلبات المفقودة: %s',
            $prerequisitesString
        );

        $context = [
            'missing_prerequisites' => $missingPrerequisites,
        ];

        return new self(
            $message,
            $sessionType,
            $sessionId,
            $currentStatus,
            $attemptedOperation,
            $context
        );
    }

    /**
     * Get Arabic label for status
     */
    protected static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'scheduled' => 'مجدولة',
            'live' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'rescheduled' => 'معاد جدولتها',
            'pending' => 'قيد الانتظار',
            default => $status,
        };
    }

    /**
     * Get Arabic label for operation
     */
    protected static function getOperationLabel(string $operation): string
    {
        return match ($operation) {
            'start' => 'بدء الجلسة',
            'complete' => 'إنهاء الجلسة',
            'cancel' => 'إلغاء الجلسة',
            'reschedule' => 'إعادة جدولة الجلسة',
            'mark_attendance' => 'تسجيل الحضور',
            'submit_report' => 'تقديم التقرير',
            'create_meeting' => 'إنشاء الاجتماع',
            'join_meeting' => 'الانضمام للاجتماع',
            'assign_homework' => 'إسناد الواجب',
            'grade_homework' => 'تصحيح الواجب',
            default => $operation,
        };
    }

    /**
     * Get allowed transitions for current status
     */
    protected static function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'scheduled' => ['live', 'cancelled', 'rescheduled'],
            'live' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => ['rescheduled'],
            'rescheduled' => ['scheduled', 'cancelled'],
            default => [],
        };
    }

    /**
     * Get session type
     */
    public function getSessionType(): string
    {
        return $this->sessionType;
    }

    /**
     * Get session ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Get current status
     */
    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    /**
     * Get attempted operation
     */
    public function getAttemptedOperation(): string
    {
        return $this->attemptedOperation;
    }

    /**
     * Get additional context
     */
    public function getAdditionalContext(): ?array
    {
        return $this->additionalContext;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::warning('Session operation failed', [
            'session_type' => $this->sessionType,
            'session_id' => $this->sessionId,
            'current_status' => $this->currentStatus,
            'attempted_operation' => $this->attemptedOperation,
            'additional_context' => $this->additionalContext,
            'message' => $this->message,
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        // Determine status code based on operation
        $statusCode = 400; // Bad Request by default

        if (isset($this->additionalContext['conflict'])) {
            $statusCode = 409; // Conflict for concurrent modifications
        } elseif ($this->currentStatus === 'completed' || $this->currentStatus === 'cancelled') {
            $statusCode = 422; // Unprocessable Entity for state violations
        }

        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => [
                'type' => 'session_operation_error',
                'session_type' => $this->sessionType,
                'session_id' => $this->sessionId,
                'current_status' => $this->currentStatus,
                'current_status_label' => self::getStatusLabel($this->currentStatus),
                'attempted_operation' => $this->attemptedOperation,
                'allowed_transitions' => self::getAllowedTransitions($this->currentStatus),
                'additional_context' => $this->additionalContext,
            ],
        ], $statusCode);
    }
}
