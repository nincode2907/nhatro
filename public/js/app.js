const openDialog = (dialog) => {
    if (!dialog) return;

    if (typeof dialog.showModal === 'function') {
        if (!dialog.open) dialog.showModal();
    } else {
        dialog.setAttribute('open', '');
    }
};

document.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
        openDialog(document.getElementById(button.dataset.openDialog));
    });
});

document.querySelectorAll('[data-close-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = button.closest('dialog');
        if (dialog?.open && typeof dialog.close === 'function') dialog.close();
        else dialog?.removeAttribute('open');
    });
});

document.querySelectorAll('dialog.app-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
});

document.querySelectorAll('dialog[data-open-on-load]').forEach(openDialog);

const firstInvalidField = document.querySelector('[aria-invalid="true"], .field-error:not([hidden])');
if (firstInvalidField) {
    const field = firstInvalidField.matches('input, select, textarea')
        ? firstInvalidField
        : firstInvalidField.closest('.field')?.querySelector('input, select, textarea');
    field?.focus({ preventScroll: true });
}

const periodSwitch = document.querySelector('[data-period-switch]');
periodSwitch?.addEventListener('change', () => {
    if (periodSwitch.value) window.location.assign(periodSwitch.value);
});

const readingForm = document.querySelector('[data-meter-reading-form]');

if (readingForm) {
    const submitButton = readingForm.querySelector('[data-submit-reading]');
    const numberFormatter = new Intl.NumberFormat('vi-VN');

    const parseReading = (value) => {
        if (value === '' || value === null || value === undefined) return null;
        const parsed = Number(value);
        return Number.isSafeInteger(parsed) && parsed >= 0 ? parsed : null;
    };

    const refreshUsage = () => {
        let hasNegativeUsage = false;

        readingForm.querySelectorAll('[data-meter]').forEach((meter) => {
            const currentInput = meter.querySelector('[data-current-input]');
            if (!currentInput) return;

            const previousInput = meter.querySelector('[data-previous-input]');
            const previous = parseReading(previousInput?.value ?? meter.dataset.previousValue);
            const current = parseReading(currentInput.value);
            const output = meter.querySelector('[data-usage-output]');
            const error = meter.querySelector('[data-meter-error]');
            const isNegative = previous !== null && current !== null && current < previous;

            hasNegativeUsage ||= isNegative;
            currentInput.setAttribute('aria-invalid', isNegative ? 'true' : 'false');
            if (error) error.hidden = !isNegative;

            if (output) {
                output.textContent = previous !== null && current !== null && !isNegative
                    ? numberFormatter.format(current - previous)
                    : '—';
            }
        });

        if (submitButton) submitButton.disabled = hasNegativeUsage;
    };

    readingForm.querySelectorAll('[data-current-input], [data-previous-input]').forEach((input) => {
        input.addEventListener('input', refreshUsage);
        input.addEventListener('focus', () => input.select());
    });

    readingForm.addEventListener('submit', () => {
        if (!submitButton || submitButton.disabled) return;
        submitButton.disabled = true;
        const label = submitButton.querySelector('span');
        if (label) label.textContent = 'ĐANG LƯU…';
    });

    refreshUsage();
}
