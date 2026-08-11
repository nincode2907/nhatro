@extends('layouts.app')

@php($editing = $floor !== null)

@section('title', ($editing ? 'Sửa tầng' : 'Thêm tầng').' — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('property-structure.index') }}">← Tầng & phòng</a>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">Cấu trúc nhà trọ</p>
            <h1>{{ $editing ? 'Sửa '.$floor->name : 'Thêm tầng' }}</h1>
            <p class="muted property-summary">Mã tầng phải duy nhất trong nhà trọ.</p>
        </div>
    </header>

    <form class="card structure-form" method="POST"
          action="{{ $editing ? route('property-structure.floors.update', $floor) : route('property-structure.floors.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="form-grid two-columns">
            <div class="field">
                <label for="code">Mã tầng</label>
                <input id="code" name="code" type="text" maxlength="30" required
                       value="{{ old('code', $floor?->code) }}" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}">
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="name">Tên hiển thị</label>
                <input id="name" name="name" type="text" maxlength="100" required
                       value="{{ old('name', $floor?->name) }}" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="sort_order">Thứ tự tầng</label>
                <input id="sort_order" name="sort_order" type="number" inputmode="numeric" min="1" max="10000" required
                       value="{{ old('sort_order', $suggestedSortOrder) }}" aria-invalid="{{ $errors->has('sort_order') ? 'true' : 'false' }}">
                @error('sort_order') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <input type="hidden" name="is_active" value="0">
        <label class="checkbox-row">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $floor?->is_active ?? true))>
            Tầng đang được sử dụng
        </label>
        @error('is_active') <p class="field-error">{{ $message }}</p> @enderror

        @if ($editing)
            <p class="field-help">Tắt tầng sẽ ẩn toàn bộ phòng của tầng khỏi luồng ghi chỉ số, nhưng không xóa dữ liệu.</p>
        @endif

        <div class="structure-form-actions">
            <a class="button button-secondary" href="{{ route('property-structure.index') }}">Hủy</a>
            <button class="button button-primary" type="submit">{{ $editing ? 'Lưu tầng' : 'Thêm tầng' }}</button>
        </div>
    </form>
@endsection
