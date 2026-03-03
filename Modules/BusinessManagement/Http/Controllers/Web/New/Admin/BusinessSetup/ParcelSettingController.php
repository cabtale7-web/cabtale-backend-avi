<?php

namespace Modules\BusinessManagement\Http\Controllers\Web\New\Admin\BusinessSetup;

use App\Http\Controllers\BaseController;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\BusinessManagement\Http\Requests\ParcelCancellationReasonStoreOrUpdateRequest;
use Modules\BusinessManagement\Http\Requests\ParcelSettingStoreOrUpdateRequest;
use Modules\BusinessManagement\Service\Interface\BusinessSettingServiceInterface;
use Modules\BusinessManagement\Service\Interface\ParcelCancellationReasonServiceInterface;

class ParcelSettingController extends BaseController
{
    protected $businessSettingService;
    protected $parcelCancellationReasonService;

    public function __construct(BusinessSettingServiceInterface $businessSettingService,ParcelCancellationReasonServiceInterface $parcelCancellationReasonService)
    {
        parent::__construct($businessSettingService);
        $this->businessSettingService = $businessSettingService;
        $this->parcelCancellationReasonService = $parcelCancellationReasonService;
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $this->authorize('business_view');

        $settings = $this->businessSettingService->getBy(criteria: ['settings_type' => PARCEL_SETTINGS]);
        $cancellationReasons = $this->parcelCancellationReasonService->getBy(orderBy: ['created_at'=>'desc'], limit: paginationLimit(),offset: $request?->page??1);
        return view('businessmanagement::admin.business-setup.parcel', compact('settings','cancellationReasons'));
    }

    public function store(ParcelSettingStoreOrUpdateRequest $request): RedirectResponse|Renderable
    {
        $this->authorize('business_view');
        $this->businessSettingService->storeParcelSetting($request->validated());
        Toastr::success(BUSINESS_SETTING_UPDATE_200['message']);
        return back();
    }

    #cancellation reason
    public function storeCancellationReason(ParcelCancellationReasonStoreOrUpdateRequest $request)
    {
        $this->authorize('business_edit');
        $this->parcelCancellationReasonService->create(data: $request->validated());
        Toastr::success(translate('Cancellation message stored successfully'));
        return redirect()->back();
    }
    public function editCancellationReason($id)
    {
        $this->authorize('business_edit');
        $cancellationReason = $this->parcelCancellationReasonService->findOne(id: $id);
        if (!$cancellationReason){
            Toastr::error(translate('Cancellation reason not found'));
            return redirect()->back();
        }
        return view('businessmanagement::admin.business-setup.edit-parcel-cancellation-reason', compact('cancellationReason'));
    }
    public function updateCancellationReason($id, ParcelCancellationReasonStoreOrUpdateRequest $request)
    {
        #TODO
        $this->authorize('business_edit');
        $this->parcelCancellationReasonService->update(id: $id,data: $request->validated());
        Toastr::success(translate('Cancellation message updated successfully'));
        return redirect()->back();
    }

    public function destroyCancellationReason(string $id)
    {
        $this->authorize('vehicle_delete');
        $this->parcelCancellationReasonService->delete(id: $id);
        Toastr::success(translate('Cancellation message deleted successfully.'));
        return redirect()->route('admin.business.setup.parcel.index');
    }

    public function statusCancellationReason(Request $request): JsonResponse
    {
        $this->authorize('vehicle_edit');
        $request->validate([
            'status' => 'boolean'
        ]);
        $model = $this->parcelCancellationReasonService->statusChange(id: $request->id, data: $request->all());
        return response()->json($model);
    }
}
