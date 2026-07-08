@extends('adminmodule::layouts.master')

@section('title', translate('firebase_phone_auth'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="fs-22 mb-4 text-capitalize">{{translate('3rd_party')}}</h2>
            @include('businessmanagement::admin.configuration.partials._third_party_inline_menu')

            <div class="card">
                <div class="card-body">
                    <h5 class="text-primary text-uppercase mb-4">{{translate('firebase_phone_auth_setup')}}</h5>

                    <div
                        class="media align-items-center gap-3 px-3 py-2 rounded border border-primary-light border-start-5 mb-30">
                        <i class="bi bi-info-circle-fill fs-20 text-primary"></i>
                        <p class="media-body">
                            <strong>{{translate('NB')}}:</strong>
                            {{translate('Use the Firebase project id from the same Firebase project configured in the customer and driver apps. The app verifies phone OTP with Firebase, then backend verifies the Firebase ID token before resetting password')}}.
                        </p>
                    </div>

                    <form action="{{route('admin.business.configuration.third-party.firebase-phone-auth.update')}}"
                          method="post" id="firebase_phone_auth_form">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                                    <label for="firebase_phone_auth_status"
                                           class="mb-0">{{translate('status')}}</label>
                                    <label class="switcher">
                                        <input id="firebase_phone_auth_status" class="switcher_input" type="checkbox"
                                               name="status" {{($setting['status'] ?? 0) == 1 ? 'checked' : ''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="project_id" class="mb-2">{{translate('firebase_project_id')}}
                                        <span class="text-danger">*</span></label>
                                    <input required type="text" name="project_id"
                                           value="{{$setting['project_id'] ?? config('services.firebase.project_id')}}"
                                           class="form-control" id="project_id" placeholder="cabtale-app">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="auth_cert_url"
                                           class="mb-2">{{translate('firebase_auth_certificate_url')}}</label>
                                    <input type="url" name="auth_cert_url"
                                           value="{{$setting['auth_cert_url'] ?? config('services.firebase.auth_cert_url')}}"
                                           class="form-control" id="auth_cert_url"
                                           placeholder="{{config('services.firebase.auth_cert_url')}}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{translate('save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content -->
@endsection


@push('script')

    <script>
        "use strict";

        let permission = false;
        @can('business_edit')
            permission = true;
        @endcan

        $('#firebase_phone_auth_form').on('submit', function (e) {
            if (!permission) {
                toastr.error('{{ translate('you_do_not_have_enough_permission_to_update_this_settings') }}');
                e.preventDefault();
            }
        });
    </script>

@endpush
