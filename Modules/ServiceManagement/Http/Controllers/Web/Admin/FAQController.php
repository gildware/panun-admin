<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ServiceManagement\Entities\Faq;

class FAQController extends Controller
{
    private Faq $faq;

    public function __construct(Faq $faq)
    {
        $this->faq = $faq;
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all',
            'service_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $faq = $this->faq->ordered()
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'active') {
                    return $query->where(['is_active' => 1]);
                }

                return $query->where(['is_active' => 0]);
            })->when($request->has('service_id'), function ($query) use ($request) {
                return $query->where('service_id', $request->service_id);
            })->paginate(pagination_limit(), ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $faq), 200);
    }

    public function store(Request $request, $service_id): JsonResponse
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
        ]);

        $faq = DB::transaction(function () use ($request, $service_id) {
            $this->faq->where('service_id', $service_id)->increment('sort_order');

            $faq = new Faq;
            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->service_id = $service_id;
            $faq->is_active = 1;
            $faq->sort_order = 0;
            $faq->save();

            return $faq;
        });

        $faqs = $this->orderedFaqsForService($service_id);

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._faq-list', compact('faqs'))->render()]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
        ]);

        $faq = $this->faq->find($id);
        if (! $faq) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_204['message'])], 404);
        }

        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->is_active = 1;
        $faq->save();

        $faqs = $this->orderedFaqsForService($faq->service_id);

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._faq-list', compact('faqs'))->render()]);
    }

    public function destroy(Request $request, $faq_id, $service_id): JsonResponse
    {
        $this->faq->where(['id' => $faq_id])->first()?->delete();
        $faqs = $this->orderedFaqsForService($service_id);

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._faq-list', compact('faqs'))->render()]);
    }

    public function statusUpdate(Request $request, $id): JsonResponse
    {
        $faq = $this->faq->where('id', $id)->first();
        if ($faq) {
            $faq->is_active = ! $faq->is_active;
            $faq->save();
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    public function reorder(Request $request, string $service_id): JsonResponse
    {
        $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $orderedIds = array_values($request->input('order', []));
        $existingIds = $this->faq->where('service_id', $service_id)->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count($orderedIds) !== count($existingIds)
            || count(array_diff($orderedIds, $existingIds)) > 0
            || count(array_diff($existingIds, $orderedIds)) > 0) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $faqId) {
                $this->faq->where('id', $faqId)->update(['sort_order' => $index]);
            }
        });

        $faqs = $this->orderedFaqsForService($service_id);

        return response()->json([
            'flag' => 1,
            'message' => translate(DEFAULT_UPDATE_200['message']),
            'template' => view('servicemanagement::admin.partials._faq-list', compact('faqs'))->render(),
        ]);
    }

    private function orderedFaqsForService(string $serviceId)
    {
        return $this->faq->ordered()->where('service_id', $serviceId)->get();
    }
}
