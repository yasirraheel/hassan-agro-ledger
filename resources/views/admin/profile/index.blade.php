@extends('layouts.master')

@section('title')
    {{__('User Profile')}}
@endsection

@php
    $user = auth()->user();
@endphp

@section('main_content')
<div class="erp-state-overview-section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4">
                <div class="erp-dashboard-profile card">
                    <div class="profile-bg">
                        <img src="{{ asset('assets/images/profile/cover-photo.jpg') }}" alt="profile-bg">
                    </div>
                    <div class="profile-img">
                        <img id="profile_picture" src="{{ asset(Auth::user()->image ?? 'assets/images/profile/profile-img.png') }}" alt="user avatar">
                    </div>
                    <div class="profile-details card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><span>{{ __('Name') }}: </span>{{ ucwords($user->name) }}</li>
                            <li class="list-group-item"><span>{{ __('Email') }}: </span>{{ $user->email }}</li>
                            <li class="list-group-item"><span>{{ __('Registration Date') }}:</span> {{ formatted_date($user->created_at) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="erp-dashboard-profile-section card">
                    <div class="table-header">
                        <h4>{{__('User Profile')}}</h4>
                    </div>
                    <form action="{{ route('admin.profiles.update',$user->id) }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-lg-4 mt-3">
                                <label>{{__('Name')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <input type="text" name="name" value="{{ $user->name }}" required class="form-control" placeholder="{{ __('Enter Your Name') }}">
                            </div>
                            <div class="col-lg-4 mt-3">
                                <label>{{__('Email')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <input type="email" name="email" value="{{ $user->email }}" required class="form-control" placeholder="{{ __('Enter Your Email') }}">
                            </div>
                            <div class="col-lg-4 mt-3">
                                <label>{{__('Profile Picture')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <div class="custom-upload-wrapper">
                                    <div class="custom-image-box">
                                        <div class="custom-image-content">
                                            <svg width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18.3327 7.5026V12.5026C18.3327 14.8596 18.3327 16.0381 17.6004 16.7704C16.8682 17.5026 15.6897 17.5026 13.3327 17.5026H6.66602C4.309 17.5026 3.13048 17.5026 2.39825 16.7704C1.66602 16.0381 1.66602 14.8596 1.66602 12.5026V9.21404C1.66602 8.39729 1.66602 7.98892 1.76053 7.65502C1.99698 6.81974 2.64982 6.1669 3.48509 5.93046C3.819 5.83594 4.22736 5.83594 5.04409 5.83594C5.34907 5.83594 5.50157 5.83594 5.64361 5.81118C5.99556 5.74983 6.31847 5.577 6.56476 5.3182C6.66415 5.21375 6.91352 4.8397 7.08268 4.58594C7.413 4.09048 7.57815 3.84275 7.80393 3.67233C7.94181 3.56826 8.09502 3.48627 8.25809 3.42927C8.52512 3.33594 8.82287 3.33594 9.41837 3.33594H10.8327" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M13.3327 11.2474C13.3327 13.0883 11.8403 14.5807 9.99937 14.5807C8.1584 14.5807 6.66602 13.0883 6.66602 11.2474C6.66602 9.40641 8.1584 7.91406 9.99937 7.91406C11.8403 7.91406 13.3327 9.40641 13.3327 11.2474Z" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M13.334 4.58333H17.5007M15.4173 6.66667V2.5" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="custom-upload-text">{{ __('Add Image') }}</span>
                                        </div>

                                        <!-- Preview image -->
                                        <img class="preview-image d-none" id="image" src="{{ asset($user->image) }}" alt="Preview">
                                        <input type="file" name="image" class="preview-image-input" data-id="#image" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 mt-3">
                                <label>{{__('Current Password')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <input type="password" name="current_password" class="form-control" placeholder="{{ __('Enter Your Current Password') }}">
                            </div>
                            <div class="col-lg-4 mt-3">
                                <label>{{__('New Password')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <input type="password" name="password" class="form-control" placeholder="{{ __('Enter New Password') }}">
                            </div>
                            <div class="col-lg-4 mt-3">
                                <label>{{__('Confirm password')}}</label>
                            </div>
                            <div class="col-lg-8 mt-3">
                                <input type="password" name="password_confirmation" class="form-control" placeholder="{{ __('Enter Confirm password') }}">
                            </div>
                            <div class="col-lg-12 mt-5">
                                <button type="submit" class="theme-btn submit-btn">{{__('Save Changes')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
