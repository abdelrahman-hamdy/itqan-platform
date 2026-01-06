<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\BusinessServiceCategory;
use App\Models\BusinessServiceRequest;
use App\Models\PortfolioItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BusinessServiceController extends Controller
{
    use ApiResponses;

    /**
     * Show the business services page
     */
    public function index(): View
    {
        $categories = BusinessServiceCategory::active()->get();
        $portfolioItems = PortfolioItem::active()->ordered()->with('serviceCategory')->get();

        return view('platform.business-services', compact('categories', 'portfolioItems'));
    }

    /**
     * Show the portfolio page
     */
    public function portfolio(): View
    {
        $categories = BusinessServiceCategory::active()->get();
        $portfolioItems = PortfolioItem::active()->ordered()->with('serviceCategory')->get();

        return view('platform.portfolio', compact('categories', 'portfolioItems'));
    }

    /**
     * Store a new business service request
     */
    public function storeRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'service_category_id' => 'required|exists:business_service_categories,id',
            'project_budget' => 'nullable|string|max:255',
            'project_deadline' => 'nullable|string|max:255',
            'project_description' => 'required|string|max:5000',
        ], [
            'client_name.required' => 'اسم العميل مطلوب',
            'client_phone.required' => 'رقم الهاتف مطلوب',
            'client_email.required' => 'البريد الإلكتروني مطلوب',
            'client_email.email' => 'البريد الإلكتروني غير صحيح',
            'service_category_id.required' => 'نوع الخدمة مطلوب',
            'service_category_id.exists' => 'نوع الخدمة غير صحيح',
            'project_description.required' => 'وصف المشروع مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $serviceRequest = BusinessServiceRequest::create($request->all());

            return $this->success([
                'success' => true,
                'message' => 'تم إرسال طلبك بنجاح! سنتواصل معك قريباً.',
                'data' => null,
                'request_id' => $serviceRequest->id,
            ], true, 200);

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * Get service categories for AJAX requests
     */
    public function getCategories(): JsonResponse
    {
        $categories = BusinessServiceCategory::active()->get(['id', 'name', 'description', 'color', 'icon']);

        return $this->success($categories);
    }

    /**
     * Get portfolio items for AJAX requests
     */
    public function getPortfolioItems(Request $request): JsonResponse
    {
        $query = PortfolioItem::active()->with('serviceCategory');

        if ($request->has('category_id')) {
            $query->where('service_category_id', $request->category_id);
        }

        $items = $query->ordered()->get();

        return $this->success($items);
    }
}
