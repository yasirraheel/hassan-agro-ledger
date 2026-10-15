@extends('layouts.master')

@section('title')
    {{ __('Backup Database') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Backup Database') }}</h4>
                    </div>

                    <div class="p-16">
                        <form method="POST" action="{{ route('admin.backup.store') }}" class="ajaxform">
                            @csrf

                            <div class="row">
                                <!-- Destination Section -->
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-bold mb-3">{{ __('Destination') }}</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input destination-radio" type="radio" name="destination"
                                            id="localOnly" value="local" {{ ($settings['destination'] ?? 'local') === 'local' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="localOnly">
                                            {{ __('Local only') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input destination-radio" type="radio" name="destination"
                                            id="cloudBackup" value="cloud" {{ ($settings['destination'] ?? '') === 'cloud' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cloudBackup">
                                            {{ __('Cloud (upload after local backup)') }}
                                        </label>
                                    </div>
                                    <small class="text-muted">{{ __('Local backups path:') }} <span class="text-danger">{{ storage_path('app/backups') }}</span></small>
                                </div>

                                <!-- Auto Backup Section -->
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-bold mb-3">{{ __('Auto Backup') }}</h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="autoBackup" name="auto_backup" {{ ($settings['auto_backup'] ?? 0) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="autoBackup">{{ __('Auto Backup') }}</label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0">{{ __('Auto Backup Every') }}</label>
                                        <input type="number" class="form-control backup-form-control" name="auto_backup_interval" value="{{ $settings['auto_backup_interval'] ?? 2 }}" min="1">
                                        <select class="form-select backup-form-select" name="auto_backup_unit">
                                            <option value="hours" {{ ($settings['auto_backup_unit'] ?? '') === 'hours' ? 'selected' : '' }}>{{ __('Hours') }}</option>
                                            <option value="days" {{ ($settings['auto_backup_unit'] ?? '') === 'days' ? 'selected' : '' }}>{{ __('Days') }}</option>
                                            <option value="weeks" {{ ($settings['auto_backup_unit'] ?? '') === 'weeks' ? 'selected' : '' }}>{{ __('Weeks') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Cloud Configuration Form -->
                                <div id="cloudConfigForm" class=" mb-3 order-form-section d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Cloud provider') }}</label>
                                                <select class="form-control table-select w-100" name="cloud_provider" id="cloudProvider">
                                                    <option value="google_drive" {{ ($settings['cloud_provider'] ?? '') === 'google_drive' ? 'selected' : '' }}>{{ __('Google Drive') }}</option>
                                                    <option value="dropbox" {{ ($settings['cloud_provider'] ?? '') === 'dropbox' ? 'selected' : '' }}>{{ __('Dropbox') }}</option>
                                                    <option value="s3" {{ ($settings['cloud_provider'] ?? '') === 's3' ? 'selected' : '' }}>{{ __('S3-compatible (AWS/MinIO/etc.)') }}</option>
                                                </select>
                                                <span></span>
                                            </div>
                                            <small class="text-muted">{{ __('Cloud upload runs after the backup is generated.') }}</small>
                                        </div>
                                    </div>

                                    <!-- Google Drive Fields -->
                                    <div class="row mt-1" id="google_drive-fields">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Client ID') }}</label>
                                                <input type="text" class="form-control" name="client_id" placeholder="{{ __('OAuth client id') }}" value="{{ $settings['client_id'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Client secret (leave blank to keep current)') }}</label>
                                                <input type="password" class="form-control" name="client_secret" placeholder="{{ __('OAuth client secret') }}" value="{{ $settings['client_secret'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Refresh token (recommended)') }}</label>
                                                <div class="position-relative">
                                                    <input type="text" class="form-control refresh-token-input" name="refresh_token" placeholder="{{ __('Refresh token') }}" value="{{ $settings['refresh_token'] ?? '' }}" readonly>
                                                    <a href="{{ route('google-drive.connect') }}" class="btn btn-generate-token">{{ __('Generate Token') }}</a>
                                                </div>
                                                <small>{{ __('Before clicking on Generate Token, make sure you have saved the Client ID and Client Secret.') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Folder Name (optional)') }}</label>
                                                <input type="text" class="form-control" name="folder_id" placeholder="{{ __('Google Drive folder name') }}" value="{{ $settings['folder_id'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dropbox Fields -->
                                    <div class="row mt-1 d-none" id="dropbox-fields">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Dropbox folder path (optional)') }}</label>
                                                <input type="text" class="form-control" name="cloud_path" placeholder="{{ __('Ex: /backups') }}" value="{{ $settings['cloud_path'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Access token (leave blank to keep current)') }}</label>
                                                <input type="text" class="form-control" name="dropbox_access_token" placeholder="{{ __('Dropbox access token') }}" value="{{ $settings['dropbox_access_token'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- S3-compatible Fields -->
                                    <div class="row mt-1 d-none" id="s3-fields">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Bucket') }}</label>
                                                <input type="text" class="form-control" name="s3_bucket" placeholder="{{ __('Bucket name') }}" value="{{ $settings['s3_bucket'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Region') }}</label>
                                                <input type="text" class="form-control" name="s3_region" placeholder="{{ __('Ex: us-east-1') }}" value="{{ $settings['s3_region'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Access key') }}</label>
                                                <input type="text" class="form-control" name="s3_access_key" placeholder="{{ __('AWS access key') }}" value="{{ $settings['s3_access_key'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Secret key (leave blank to keep current)') }}</label>
                                                <input type="password" class="form-control" name="s3_secret_key" placeholder="{{ __('AWS secret key') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Endpoint (optional for MinIO)') }}</label>
                                                <input type="text" class="form-control" name="s3_endpoint" placeholder="{{ __('Ex: http://localhost:9000') }}" value="{{ $settings['s3_endpoint'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Path-style URLs (MinIO often requires this)') }}</label>
                                                <div class="form-check form-switch mt-1">
                                                    <input class="form-check-input" type="checkbox" id="s3PathStyle" name="s3_path_style" {{ ($settings['s3_path_style'] ?? 0) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="s3PathStyle">{{ __('Enable path-style URLs') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                        <!-- Action Buttons -->
                            <div class="row mb-4">
                                <div class="col-12 d-flex gap-3 justify-content-center">
                                    <button type="submit" class="btn backup-btn-save px-5 py-2 fw-semibold">{{ __('Save Backup Settings') }}</button>
                                    <a href="{{ route('admin.backup.create') }}"
                                        class="btn backup-btn-generate px-5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center"
                                        onclick="return confirm('{{ __('Are you sure you want to create a new backup?') }}')">{{ __('Generate Backup') }}</a>
                                </div>
                            </div>
                        </form>

                        <hr>

                        <!-- Backup History Table -->
                        <h6 class="fw-bold mb-3">{{ __('Backup history') }}</h6>
                        <div class="responsive-table m-0">
                            <table class="table" id="datatable">
                                <thead>
                                    <tr>
                                            <th>
                                                <div class="d-flex align-items-center gap-1">
                                                    <label class="table-custom-checkbox">
                                                        <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                    <i class="fal fa-trash-alt delete-selected"></i>
                                                </div>
                                            </th>
                                        <th>{{ __('Sl.') }}</th>
                                        <th class="text-start">{{ __('Date & Time') }}</th>
                                        <th class="text-start">{{ __('File Name') }}</th>
                                        <th class="text-start">{{ __('File Size') }}</th>
                                        <th class="text-start">{{ __('Location') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($backups as $key => $backup)
                                    <tr>
                                            <td class="w-60 checkbox text-start">
                                                <label class="table-custom-checkbox">
                                                    <input type="checkbox" name="ids[]"
                                                        class="table-hidden-checkbox checkbox-item" value="{{ $backup['filename'] }}" data-url="{{ route('admin.backup.delete-all') }}">
                                                    <span class="table-custom-checkmark custom-checkmark"></span>
                                                </label>
                                            </td>
                                        <td>{{ $key + 1 }}</td>
                                        <td class="text-start">{{ date('d M Y, h:i A', $backup['modified']) }}</td>
                                        <td class="text-start">{{ $backup['filename'] }}</td>
                                        <td class="text-start">{{ formatBytes($backup['size']) }}</td>
                                        <td class="text-start">{{ ($settings['destination'] ?? '') === 'cloud' ? ucfirst($settings['cloud_provider'] ?? 'Local') : 'Local' }}</td>
                                        <td class="d-print-none">
                                            <div class="dropdown table-action">
                                                <button type="button" data-bs-toggle="dropdown">
                                                    <i class="far fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                        <li>
                                                            <a href="{{ route('admin.backup.download', $backup['filename']) }}">
                                                                <i class="fal fa-download"></i>{{ __('Download') }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.backup.destroy', $backup['filename']) }}" class="confirm-action" data-method="DELETE">
                                                                <i class="fal fa-trash-alt"></i>{{ __('Delete') }}
                                                            </a>
                                                        </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">{{ __('No backups found.') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
@endpush
