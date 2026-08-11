@extends('layouts.app')

@section('title', 'Hóa đơn phòng '.$invoice->room->room_number.' — '.config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/invoice-print.css') }}">
@endpush

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('invoices.index', $period) }}">← Hóa đơn {{ $period->label() }}</a>
    </nav>

    <header class="page-header invoice-detail-header">
        <div>
            <p class="eyebrow">Hóa đơn đã lưu</p>
            <h1>Phòng {{ $invoice->room->room_number }}</h1>
            <p class="muted property-summary">{{ $period->label() }} · {{ $invoice->room->floor->name }}</p>
        </div>
        <div class="invoice-screen-actions">
            <span class="status-badge invoice-status-{{ strtolower($invoice->status->value) }}">
                {{ $invoice->status->label() }}
            </span>
            <a class="button button-secondary" href="{{ route('invoice-exports.pdf-single', [$period, $invoice]) }}">
                Tải PDF
            </a>
            <a class="button button-secondary" href="{{ route('invoice-exports.docx-single', [$period, $invoice]) }}">
                Tải Word
            </a>
            <a class="button button-primary" href="{{ route('invoices.print-single', [$period, $invoice]) }}">
                In khổ A5
            </a>
        </div>
    </header>

    <div class="invoice-screen-document">
        @include('invoices.partials.document', ['document' => $document, 'variant' => 'single'])
    </div>
@endsection
