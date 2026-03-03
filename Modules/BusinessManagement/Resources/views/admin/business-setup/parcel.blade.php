@extends('adminmodule::layouts.master')

@section('title', translate('Parcel Settings'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="fs-22 mb-4 text-capitalize">{{translate('business_management')}}</h2>

            <div class="col-12 mb-3">
                <div class="">
                    @include('businessmanagement::admin.business-setup.partials._business-setup-inline')
                </div>
            </div>
            <div class="card mb-3 text-capitalize">
                <form action="{{route('admin.business.setup.parcel.store')."?type=".PARCEL_SETTINGS}}" id="parcel_form"
                      method="POST">
                    @csrf
                    <div class="collapsible-card-body">
                        <div class="card-header d-flex align-items-center justify-content-between gap-2">
                            <div class="w-0 flex-grow-1">
                                <h5 class="mb-2">{{translate('Parcel Return Time & Fee')}}</h5>
                                <div class="fs-12">
                                    {{translate("When the toggle is turned ON, the parcel return time and fee are activated; when turned OFF, they are deactivated.")}}
                                </div>
                            </div>
                            <label class="switcher">
                                <input class="switcher_input collapsible-card-switcher update-business-setting"
                                       id="parcelReturnTimeFeeStatus"
                                       type="checkbox"
                                       name="parcel_return_time_fee_status"
                                       data-name="parcel_return_time_fee_status"
                                       data-type="{{PARCEL_SETTINGS}}"
                                       data-url="{{route('admin.business.setup.update-business-setting')}}"
                                       data-icon=" {{asset('public/assets/admin-module/img/parcel_return.png')}}"
                                       data-title="{{translate('Are you sure?') .'?'}}"
                                       data-sub-title="{{($settings->firstWhere('key_name', 'parcel_return_time_fee_status')->value?? 0) == 1 ? translate('Do you want to turn OFF Parcel Return Time & Fee for driver? When it’s off the driver don’t need to pay return fee for delay. ') : translate('Do you want to turn ON Parcel Return Time & Fee for driver? When it’s ON, the driver need to pay parcel return delay fee. ')}}"
                                       data-confirm-btn="{{($settings->firstWhere('key_name', 'parcel_return_time_fee_status')->value?? 0) == 1 ? translate('Turn Off') : translate('Turn On')}}"
                                    {{($settings->firstWhere('key_name', 'parcel_return_time_fee_status')->value?? 0) == 1? 'checked' : ''}}
                                >
                                <span class="switcher_control"></span>
                            </label>
                        </div>


                        <div class="card-body collapsible-card-content">
                            <div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="returnTimeForDriver"
                                               class="form-label">{{translate("Return Time for Driver")}} <i
                                                class="bi bi-info-circle-fill text-primary cursor-pointer"
                                                data-bs-toggle="tooltip"
                                                data-bs-title="{{translate("Set the maximum time after a parcel delivery is canceled that the driver must return the parcel to the customer.")}}"></i></label>
                                        <div class="input-group input--group">
                                            <input type="number" name="return_time_for_driver" id="returnTimeForDriver"
                                                   step="1" min="1" max="99999999"
                                                   class="form-control"
                                                   value="{{$settings->firstWhere('key_name', 'return_time_for_driver')?->value}}"
                                                   placeholder="Ex : 5">
                                            <select class="form-select" name="return_time_type_for_driver">
                                                <option
                                                    value="day" {{$settings->firstWhere('key_name', 'return_time_type_for_driver')?->value == 'day' ? 'selected' : ''}}>{{translate("Day")}}</option>
                                                <option
                                                    value="hour" {{$settings->firstWhere('key_name', 'return_time_type_for_driver')?->value == 'hour' ? 'selected' : ''}}>{{translate("Hour")}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="returnFeeForDriverTimeExceed"
                                               class="form-label">{{translate("Return Fee for Driver if Time Exceeds")}}
                                            ({{getSession('currency_symbol')}}) <i
                                                class="bi bi-info-circle-fill text-primary cursor-pointer"
                                                data-bs-toggle="tooltip"
                                                data-bs-title="{{translate("Set the charge that drivers will pay if they fail to return a canceled parcel within the specified time limit.")}}"></i></label>
                                        <input type="number" name="return_fee_for_driver_time_exceed" min="0"
                                               max="99999999" step="{{stepValue()}}"
                                               id="returnFeeForDriverTimeExceed" class="form-control"
                                               placeholder="Ex : 2.50"
                                               value="{{$settings->firstWhere('key_name', 'return_fee_for_driver_time_exceed')?->value}}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit"
                                            class="btn btn-primary text-uppercase">{{translate('submit')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card mb-3 text-capitalize">
                <div class="card-header">
                    <h5 class="mb-2">
                        <i class="bi bi-person-fill-gear"></i>
                        {{ translate('Parcel cancellation Reason') }}
                        <i class="bi bi-info-circle-fill text-primary cursor-pointer"
                           data-bs-toggle="tooltip"
                           title="{{ translate('changes_may_take_some_hours_in_app') }}"></i>
                    </h5>
                    <div class="fs-12">
                        {{translate("Here you can add the reasons that customer & user will select for cancel parcel")}}
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.business.setup.parcel.cancellation_reason.store') }}"
                          method="post">
                        @csrf
                        <div class="row gy-3 pt-3 align-items-start">
                            <div class="col-sm-6 col-md-6">
                                <label for="title" class="mb-3 d-flex align-items-center fw-medium gap-2">
                                    {{ translate('parcel_cancellation_reason') }}
                                    <small>({{translate('Max 255 character')}})</small>
                                    <i class="bi bi-info-circle-fill text-primary cursor-pointer"
                                       data-bs-toggle="tooltip"
                                       title="{{ translate('Include the proper reasons for parcel delivery cancellation') }}">
                                    </i>
                                </label>
                                <div class="character-count">
                                    <input id="title" name="title" type="text"
                                           placeholder="{{translate('Ex : vehicle problem')}}"
                                           class="form-control character-count-field"
                                           maxlength="255" data-max-character="255" required>
                                    <span>{{translate('0/255')}}</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="cancellationType" class="mb-3 d-flex align-items-center fw-medium gap-2">
                                    {{ translate('cancellation_type') }}
                                </label>
                                <select class="js-select" id="cancellationType" name="cancellation_type"
                                        required>
                                    <option value="" disabled
                                            selected>{{translate('select_cancellation_type')}}</option>
                                    @foreach(CANCELLATION_TYPE as $key=> $item)
                                        <option value="{{$key}}">{{translate($item)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="userType" class="mb-3 d-flex align-items-center fw-medium gap-2">
                                    {{ translate('user_type') }}
                                </label>
                                <select class="js-select" id="userType" name="user_type" required>
                                    <option value="" disabled selected>{{translate('select_user_type')}}</option>
                                    <option value="driver">{{translate('driver')}}</option>
                                    <option value="customer">{{translate('customer')}}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-3 flex-wrap justify-content-end">
                                    <button type="submit"
                                            class="btn btn-primary text-uppercase">{{ translate('submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
            <div class="card">
                <div class="card-header border-0 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <h5 class="d-flex align-items-center gap-2 m-0">
                        <i class="bi bi-person-fill-gear"></i>
                        {{ translate('Parcel Cancellation Reason List') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle">
                            <thead class="table-light align-middle">
                            <tr>
                                <th class="sl">{{translate('SL')}}</th>
                                <th class="text-capitalize">{{translate('Reason')}}</th>
                                <th class="text-capitalize">{{translate('cancellation_type')}}</th>
                                <th class="text-capitalize">{{translate('user_type')}}</th>
                                <th class="text-capitalize">{{translate('Status')}}</th>
                                <th class="text-center action">{{translate('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($cancellationReasons as $key => $cancellationReason)
                                <tr>
                                    <td class="sl">{{ $key + $cancellationReasons->firstItem() }}</td>
                                    <td>
                                        {{$cancellationReason->title}}
                                    </td>
                                    <td>
                                        {{ CANCELLATION_TYPE[$cancellationReason->cancellation_type] }}
                                    </td>
                                    <td>
                                        {{ $cancellationReason->user_type == 'driver' ? translate('driver') : translate('customer') }}
                                        {{$cancellationReason->status}}
                                    </td>
                                    <td class="text-center">
                                        <label class="switcher mx-auto">
                                            <input class="switcher_input status-change"
                                                   data-url="{{ route('admin.business.setup.parcel.cancellation_reason.status') }}"
                                                   id="{{ $cancellationReason->id }}"
                                                   type="checkbox"
                                                   name="status" {{ $cancellationReason->is_active == 1 ? "checked": ""  }} >
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2 align-items-center">
                                            <button class="btn btn-outline-primary btn-action editData"
                                                    data-id="{{$cancellationReason->id}}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button data-id="delete-{{ $cancellationReason?->id }}"
                                                    data-message="{{ translate('want_to_delete_this_cancellation_reason?') }}"
                                                    type="button"
                                                    class="btn btn-outline-danger btn-action form-alert">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <form
                                                action="{{ route('admin.business.setup.parcel.cancellation_reason.delete', ['id' => $cancellationReason?->id]) }}"
                                                id="delete-{{ $cancellationReason?->id }}" method="post">
                                                @csrf
                                                @method('delete')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div
                                            class="d-flex flex-column justify-content-center align-items-center gap-2 py-3">
                                            <img
                                                src="{{ asset('public/assets/admin-module/img/empty-icons/no-data-found.svg') }}"
                                                alt="" width="100">
                                            <p class="text-center">{{translate('no_data_available')}}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content -->
    <div class="d-flex justify-content-end mt-3">
        {{ $cancellationReasons->links() }}
    </div>

    <div class="modal fade" id="editDataModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- End Main Content -->
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        function collapsibleCard(thisInput) {
            let $card = thisInput.closest('.collapsible-card-body');
            let $content = $card.children('.collapsible-card-content');
            if (thisInput.prop('checked')) {
                $content.slideDown();
            } else {
                $content.slideUp();
            }
        }

        // $('.collapsible-card-switcher').on('change', function () {
        //     collapsibleCard($(this))
        // });
        $('.collapsible-card-switcher').each(function () {
            collapsibleCard($(this))
        });


        function initialCharacterCount(item) {
            let str = item.val();
            let maxCharacterCount = item.data('max-character');
            let characterCount = str.length;
            if (characterCount > maxCharacterCount) {
                item.val(str.substring(0, maxCharacterCount));
                characterCount = maxCharacterCount;
            }
            item.closest('.character-count').find('span').text(characterCount + '/' + maxCharacterCount);
        }

        let permission = false;
        @can('business_edit')
            permission = true;
        @endcan

        $('#trips_form').on('submit', function (e) {
            if (!permission) {
                toastr.error('{{ translate('you_do_not_have_enough_permission_to_update_this_settings') }}');
                e.preventDefault();
            }
        });
        $(document).ready(function () {
            $('.editData').click(function () {
                let id = $(this).data('id');
                let url = "{{ route('admin.business.setup.parcel.cancellation_reason.edit', ':id') }}";
                url = url.replace(':id', id);
                $.get({
                    url: url,
                    success: function (data) {
                        $('#editDataModal .modal-content').html(data);
                        $('#updateForm').removeClass('d-none');
                        $('#editDataModal').modal('show');
                        $('.character-count-field').on('keyup change', function () {
                            initialCharacterCount($(this));
                        });
                        $('.character-count-field').each(function () {
                            initialCharacterCount($(this));
                        });
                    },
                    error: function (xhr, status, error) {
                        console.log(error);
                    }
                });
            });
        });


    </script>
@endpush
