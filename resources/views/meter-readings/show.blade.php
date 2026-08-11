@extends('layouts.app')

@section('title', 'Phòng '.$room->room_number.' — '.$period->label())

@section('content')
    <div class="reading-shell">
        <nav class="reading-topbar" aria-label="Điều hướng">
            <a class="back-link" href="{{ route('billing-periods.show', $period) }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ $floor->name }}
            </a>
            <span>{{ $period->label() }}</span>
        </nav>

        @if (session('status'))
            <div class="notice reading-notice" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->has('period'))
            <div class="notice notice-error reading-notice" role="alert">{{ $errors->first('period') }}</div>
        @endif

        <section class="reading-progress" aria-label="Tiến độ tầng">
            <div class="progress-copy">
                <strong>Đã xử lý {{ $processed }} / {{ $rooms->count() }}</strong>
                <span>{{ $floor->name }}</span>
            </div>
            <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $rooms->count() }}"
                 aria-valuenow="{{ $processed }}" aria-label="Đã xử lý {{ $processed }} trên {{ $rooms->count() }} phòng">
                <span style="width: {{ $rooms->count() ? ($processed / $rooms->count()) * 100 : 0 }}%"></span>
            </div>
        </section>

        <header class="reading-room-header">
            <p class="eyebrow">Vị trí hiện tại</p>
            <h1>PHÒNG {{ $room->room_number }} <span>— {{ $position }}/{{ $rooms->count() }}</span></h1>
            <div class="room-state-line">
                <span class="status-badge status-{{ strtolower($room->status->value) }}">
                    {{ $room->status->label() }}
                </span>
                <span class="status-badge reading-status-{{ strtolower($reading->status->value) }}">
                    {{ $reading->status->label() }}
                </span>
            </div>
        </header>

        @if (! $period->isOpen())
            <div class="notice notice-warning reading-notice">
                Chế độ chỉ xem: kỳ này đã chốt.
            </div>
        @endif

        @if ($errors->any() && ! $errors->hasBag('skip'))
            <div class="notice notice-error reading-notice" role="alert">
                <strong>Chưa thể lưu chỉ số.</strong> Kiểm tra các trường được đánh dấu bên dưới.
            </div>
        @endif

        <form class="reading-form" method="POST"
              action="{{ route('meter-readings.update', [$period, $floor, $room]) }}"
              data-meter-reading-form>
            @csrf
            @method('PUT')

            @php
                $savedReadingNote = $reading->status->value === 'RECORDED' ? $reading->note : null;
                $meters = [
                    'electricity' => [
                        'label' => 'Điện',
                        'unit' => 'kWh',
                        'enabled' => (bool) $room->settings?->electricity_enabled,
                    ],
                    'water' => [
                        'label' => 'Nước',
                        'unit' => 'm³',
                        'enabled' => (bool) $room->settings?->water_enabled,
                    ],
                ];
            @endphp

            <div class="reading-meter-list">
                @foreach ($meters as $meter => $meta)
                    @php
                        $previousKey = $meter.'_previous';
                        $currentKey = $meter.'_current';
                        $usageKey = $meter.'_usage';
                        $previous = $reading->{$previousKey};
                        $current = old($currentKey, $reading->{$currentKey});
                    @endphp
                    <section class="card reading-meter-card" data-meter="{{ $meter }}"
                             @if($previous !== null) data-previous-value="{{ $previous }}" @endif
                             aria-labelledby="{{ $meter }}-title">
                        <header class="meter-heading">
                            <span class="meter-icon meter-icon-{{ $meter }}" aria-hidden="true">
                                @if ($meter === 'electricity')
                                    <svg viewBox="0 0 24 24"><path d="M13 2 4.5 13H11l-1 9 8.5-12H12l1-8Z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24"><path d="M12 2s7 7.2 7 13a7 7 0 0 1-14 0c0-5.8 7-13 7-13Z"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="eyebrow">Đồng hồ</p>
                                <h2 id="{{ $meter }}-title">{{ $meta['label'] }}</h2>
                            </div>
                            @if (! $meta['enabled'])
                                <span class="status-badge status-inactive">Không sử dụng</span>
                            @endif
                        </header>

                        @if ($meta['enabled'])
                            <div class="reading-values">
                                @if ($previous === null)
                                    <div class="field reading-input-field">
                                        <label for="{{ $previousKey }}">Chỉ số đầu kỳ</label>
                                        <div class="reading-input-wrap">
                                            <input id="{{ $previousKey }}" name="{{ $previousKey }}" type="number"
                                                   inputmode="numeric" min="0" step="1"
                                                   value="{{ old($previousKey) }}" data-previous-input
                                                   @disabled(! $period->isOpen()) required>
                                            <span>{{ $meta['unit'] }}</span>
                                        </div>
                                        <p class="field-help">Chưa có kỳ trước. Chỉ nhập số đầu kỳ lần này.</p>
                                        @error($previousKey)<p class="field-error">{{ $message }}</p>@enderror
                                    </div>
                                @else
                                    <div class="reading-stat">
                                        <span>Số cũ</span>
                                        <strong>{{ number_format($previous, 0, ',', '.') }}</strong>
                                        <small>{{ $meta['unit'] }}</small>
                                    </div>
                                @endif

                                <div class="field reading-input-field">
                                    <label for="{{ $currentKey }}">Số mới</label>
                                    <div class="reading-input-wrap">
                                        <input id="{{ $currentKey }}" name="{{ $currentKey }}" type="number"
                                               inputmode="numeric" min="0" step="1" value="{{ $current }}"
                                               data-current-input @disabled(! $period->isOpen()) required>
                                        <span>{{ $meta['unit'] }}</span>
                                    </div>
                                    @error($currentKey)<p class="field-error">{{ $message }}</p>@enderror
                                    <p class="field-error live-meter-error" data-meter-error hidden>
                                        Chỉ số mới nhỏ hơn số cũ. Vui lòng kiểm tra lại.
                                    </p>
                                </div>

                                <div class="usage-result" aria-live="polite">
                                    <span>Tiêu thụ</span>
                                    <strong data-usage-output>
                                        {{ $reading->{$usageKey} !== null ? number_format($reading->{$usageKey}, 0, ',', '.') : '—' }}
                                    </strong>
                                    <small>{{ $meta['unit'] }}</small>
                                </div>
                            </div>
                        @else
                            <p class="disabled-meter-copy">Phòng này không yêu cầu nhập chỉ số {{ strtolower($meta['label']) }}.</p>
                        @endif
                    </section>
                @endforeach
            </div>

            <details class="reading-note card">
                <summary>Thêm ghi chú</summary>
                <div class="field">
                    <label for="note">Ghi chú chỉ số</label>
                    <textarea id="note" name="note" rows="2" @disabled(! $period->isOpen())>{{ $errors->hasBag('skip') ? $savedReadingNote : old('note', $savedReadingNote) }}</textarea>
                    @error('note')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </details>

            <div class="reading-actions-secondary">
                <button class="button button-secondary" type="button" data-open-dialog="room-list-dialog">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    DS PHÒNG
                </button>
                @if ($period->isOpen())
                    <button class="button button-warning" type="button" data-open-dialog="skip-dialog">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 4 10 8L5 20V4Zm12 0h2v16h-2V4Z"/></svg>
                        BỎ QUA
                    </button>
                @endif
            </div>

            @if ($period->isOpen())
                <div class="reading-primary-action">
                    <button class="button button-primary button-block" type="submit" data-submit-reading>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        <span>OK &amp; TIẾP</span>
                    </button>
                </div>
            @endif
        </form>
    </div>

    <dialog class="app-dialog room-list-dialog" id="room-list-dialog" aria-labelledby="room-list-title">
        <div class="dialog-sheet">
            <header class="dialog-header">
                <div>
                    <p class="eyebrow">{{ $floor->name }}</p>
                    <h2 id="room-list-title">DS PHÒNG</h2>
                    <p>Đã xử lý {{ $processed }} / {{ $rooms->count() }}</p>
                </div>
                <button class="icon-button" type="button" data-close-dialog aria-label="Đóng danh sách phòng">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>
            <div class="dialog-room-list">
                @foreach ($rooms as $candidate)
                    @php $candidateStatus = $statuses->get($candidate->id); @endphp
                    <a class="dialog-room-row @if($candidate->is($room)) is-current @endif"
                       href="{{ route('meter-readings.show', [$period, $floor, $candidate]) }}"
                       @if($candidate->is($room)) aria-current="page" @endif>
                        <span class="room-list-status reading-status-{{ strtolower($candidateStatus->value) }}" aria-hidden="true">
                            @if ($candidateStatus->value === 'RECORDED')
                                <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
                            @elseif ($candidateStatus->value === 'SKIPPED')
                                <svg viewBox="0 0 24 24"><path d="m6 5 10 7-10 7V5Zm11 0h2v14h-2V5Z"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
                            @endif
                        </span>
                        <span class="dialog-room-identity">
                            <strong>Phòng {{ $candidate->room_number }}</strong>
                            <small>
                                {{ $candidateStatus->label() }}
                                @if ($candidate->status->value === 'VACANT')
                                    <span aria-hidden="true">·</span> Phòng trống
                                @endif
                            </small>
                        </span>
                        <span class="room-position">{{ $loop->iteration }}/{{ $rooms->count() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </dialog>

    @if ($period->isOpen())
        <dialog class="app-dialog skip-dialog" id="skip-dialog" aria-labelledby="skip-title"
                @if($errors->hasBag('skip')) data-open-on-load @endif>
            <form class="dialog-sheet skip-form" method="POST"
                  action="{{ route('meter-readings.skip', [$period, $floor, $room]) }}">
                @csrf
                <header class="dialog-header">
                    <div>
                        <p class="eyebrow">Cần có lý do</p>
                        <h2 id="skip-title">Bỏ qua phòng {{ $room->room_number }}</h2>
                    </div>
                    <button class="icon-button" type="button" data-close-dialog aria-label="Đóng hộp thoại bỏ qua">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </header>

                @if ($errors->hasBag('skip'))
                    <div class="notice notice-error" role="alert">Hãy chọn và kiểm tra lý do bỏ qua.</div>
                @endif

                <fieldset class="skip-reasons">
                    <legend>Lý do bỏ qua</legend>
                    @foreach ($skipReasons as $reason)
                        <label class="radio-card">
                            <input name="skip_reason" type="radio" value="{{ $reason->value }}"
                                   @checked(old('skip_reason') === $reason->value) required>
                            <span>{{ $reason->label() }}</span>
                        </label>
                    @endforeach
                    @error('skip_reason', 'skip')<p class="field-error">{{ $message }}</p>@enderror
                </fieldset>

                <div class="field">
                    <label for="skip-note">Ghi chú</label>
                    <textarea id="skip-note" name="note" rows="3" placeholder="Bắt buộc khi chọn Lý do khác">{{ old('note') }}</textarea>
                    @error('note', 'skip')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <p class="vacant-separation-note">
                    Chọn “Phòng đang trống” chỉ lưu lý do bỏ qua; trạng thái phòng không tự thay đổi.
                </p>

                <div class="dialog-actions">
                    <button class="button button-secondary" type="button" data-close-dialog>Hủy</button>
                    <button class="button button-warning" type="submit">Xác nhận bỏ qua</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
