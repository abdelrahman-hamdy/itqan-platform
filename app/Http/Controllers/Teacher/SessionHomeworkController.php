<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SessionHomeworkController extends Controller
{
    /**
     * Get session homework
     */
    public function show(string $subdomain, string $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة أو غير مسموح لك بالوصول إليها',
                ], 403);
            }

            $homework = $session->sessionHomework;

            if (! $homework) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد واجب لهذه الجلسة',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'homework' => [
                    'id' => $homework->id,
                    'has_new_memorization' => $homework->has_new_memorization,
                    'has_review' => $homework->has_review,
                    'has_comprehensive_review' => $homework->has_comprehensive_review,
                    'new_memorization_pages' => $homework->new_memorization_pages,
                    'new_memorization_surah' => $homework->new_memorization_surah,
                    'review_pages' => $homework->review_pages,
                    'review_surah' => $homework->review_surah,
                    'comprehensive_review_surahs' => $homework->comprehensive_review_surahs,
                    'additional_instructions' => $homework->additional_instructions,
                    'is_active' => $homework->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching session homework', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب بيانات الواجب',
            ], 500);
        }
    }

    /**
     * Create or update session homework
     */
    public function createOrUpdate(Request $request, string $subdomain, string $sessionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'has_new_memorization' => 'boolean',
                'has_review' => 'boolean',
                'has_comprehensive_review' => 'boolean',
                'new_memorization_pages' => 'nullable|numeric|min:0|max:50',
                'new_memorization_surah' => 'nullable|string|max:255',
                'review_pages' => 'nullable|numeric|min:0|max:100',
                'review_surah' => 'nullable|string|max:255',
                'comprehensive_review_surahs' => 'nullable|array',
                'comprehensive_review_surahs.*' => 'string|max:255',
                'additional_instructions' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session = QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة أو غير مسموح لك بالوصول إليها',
                ], 403);
            }

            // Validate that at least one homework type is selected
            $hasAnyHomework = $request->has_new_memorization ||
                             $request->has_review ||
                             $request->has_comprehensive_review;

            if (! $hasAnyHomework) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب اختيار نوع واحد على الأقل من الواجبات',
                ], 422);
            }

            $homeworkData = [
                'session_id' => $sessionId,
                'created_by' => Auth::id(),
                'has_new_memorization' => $request->has_new_memorization ?? false,
                'has_review' => $request->has_review ?? false,
                'has_comprehensive_review' => $request->has_comprehensive_review ?? false,
                'new_memorization_pages' => $request->new_memorization_pages,
                'new_memorization_surah' => $request->new_memorization_surah,
                'review_pages' => $request->review_pages,
                'review_surah' => $request->review_surah,
                'comprehensive_review_surahs' => $request->comprehensive_review_surahs,
                'additional_instructions' => $request->additional_instructions,
                'is_active' => true,
            ];

            // Clear unused fields based on homework types
            if (! $request->has_new_memorization) {
                $homeworkData['new_memorization_pages'] = null;
                $homeworkData['new_memorization_surah'] = null;
            }

            if (! $request->has_review) {
                $homeworkData['review_pages'] = null;
                $homeworkData['review_surah'] = null;
            }

            if (! $request->has_comprehensive_review) {
                $homeworkData['comprehensive_review_surahs'] = null;
            }

            // Use database transaction to ensure consistency
            DB::transaction(function () use ($sessionId, $homeworkData, &$homework) {
                $homework = QuranSessionHomework::updateOrCreate(
                    ['session_id' => $sessionId],
                    $homeworkData
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الواجب بنجاح',
                'homework' => [
                    'id' => $homework->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating/updating session homework', [
                'session_id' => $sessionId,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في حفظ الواجب: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete session homework
     */
    public function destroy(string $subdomain, string $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة أو غير مسموح لك بالوصول إليها',
                ], 403);
            }

            $homework = $session->sessionHomework;

            if (! $homework) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد واجب لحذفه',
                ], 404);
            }

            $homework->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الواجب بنجاح',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting session homework', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في حذف الواجب',
            ], 500);
        }
    }
}
