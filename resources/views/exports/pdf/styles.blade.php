<style>
    @page { margin: 7mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #111; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.25; }
    table { width: 100%; border-collapse: collapse; }
    .pdf-invoice { width: 100%; }
    .pdf-header { padding-bottom: 2mm; border-bottom: 0.35mm solid #222; }
    .pdf-property { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
    .pdf-address { color: #444; font-size: 8pt; }
    .pdf-status { padding: 1mm 2mm; border: 0.25mm solid #555; font-size: 7pt; font-weight: bold; text-align: right; }
    .pdf-title { padding: 2mm 0; }
    .pdf-title-name { font-size: 12pt; font-weight: bold; text-transform: uppercase; }
    .pdf-period { font-size: 11pt; font-weight: bold; }
    .pdf-room-box { width: 35mm; padding: 1mm 2mm; border: 0.5mm solid #111; text-align: center; }
    .pdf-room-label { display: block; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .pdf-room-number { display: block; font-size: 25pt; font-weight: bold; line-height: 1; }
    .pdf-lines { table-layout: fixed; font-size: 8.5pt; }
    .pdf-lines th, .pdf-lines td { padding: 1.4mm 1.2mm; border-top: 0.2mm solid #bbb; vertical-align: middle; }
    .pdf-lines thead th { color: #fff; background: #222; font-size: 7pt; text-align: left; text-transform: uppercase; }
    .pdf-lines th:first-child { width: 38%; text-align: left; }
    .pdf-lines th:nth-child(2) { width: 19%; }
    .pdf-lines th:nth-child(3) { width: 20%; }
    .pdf-lines th:nth-child(4) { width: 23%; }
    .pdf-lines td { text-align: right; }
    .pdf-meter { display: block; margin-top: 0.4mm; font-size: 7pt; font-weight: normal; }
    .pdf-muted { color: #555; font-size: 7pt; font-weight: normal; }
    .pdf-amount { white-space: nowrap; font-weight: bold; }
    .pdf-total { margin-top: 2mm; color: #fff; background: #111; }
    .pdf-total td { padding: 2mm; font-weight: bold; text-transform: uppercase; }
    .pdf-total-amount { font-size: 19pt; text-align: right; white-space: nowrap; }
    .pdf-note { padding: 1.5mm 0; font-size: 8pt; }
    .pdf-footer { padding-top: 1.5mm; border-top: 0.2mm solid #777; font-size: 8pt; }
    .pdf-date { margin: 0 0 1.5mm; text-align: right; }
    .pdf-signature { width: 50%; min-height: 15mm; text-align: center; }
    .pdf-signature small { display: block; color: #555; font-style: italic; }

    .pdf-sheet { height: 270mm; overflow: hidden; }
    .pdf-sheet--break { page-break-after: always; }
    .pdf-invoice--compact { height: 90mm; padding: 2mm 3mm; border-bottom: 0.25mm dashed #555; overflow: hidden; font-size: 7pt; page-break-inside: avoid; }
    .pdf-invoice--compact:last-child { border-bottom: 0; }
    .pdf-invoice--compact .pdf-header { padding-bottom: 0.5mm; }
    .pdf-invoice--compact .pdf-property { font-size: 8pt; }
    .pdf-invoice--compact .pdf-address { font-size: 5.5pt; }
    .pdf-invoice--compact .pdf-status { font-size: 5.5pt; }
    .pdf-invoice--compact .pdf-title { padding: 0.6mm 0; }
    .pdf-invoice--compact .pdf-title-name { font-size: 8pt; }
    .pdf-invoice--compact .pdf-period { font-size: 7pt; }
    .pdf-invoice--compact .pdf-room-box { width: 24mm; padding: 0.4mm 1mm; border-width: 0.25mm; }
    .pdf-invoice--compact .pdf-room-label { font-size: 5pt; }
    .pdf-invoice--compact .pdf-room-number { font-size: 17pt; }
    .pdf-invoice--compact .pdf-lines { font-size: 6pt; }
    .pdf-invoice--compact .pdf-lines th, .pdf-invoice--compact .pdf-lines td { padding: 0.35mm 0.8mm; }
    .pdf-invoice--compact .pdf-lines thead th { font-size: 5pt; }
    .pdf-invoice--compact .pdf-meter, .pdf-invoice--compact .pdf-muted { display: inline; margin-left: 1mm; font-size: 5pt; }
    .pdf-invoice--compact .pdf-total { margin-top: 0.5mm; }
    .pdf-invoice--compact .pdf-total td { padding: 0.6mm 1mm; }
    .pdf-invoice--compact .pdf-total-amount { font-size: 12pt; }
    .pdf-invoice--compact .pdf-note { padding: 0.4mm 0; font-size: 5.5pt; }
    .pdf-invoice--compact .pdf-footer { padding-top: 0.4mm; font-size: 5.5pt; }
    .pdf-invoice--compact .pdf-date { margin-bottom: 0.2mm; }
    .pdf-invoice--compact .pdf-signature { min-height: 5mm; }
    .pdf-invoice--compact .pdf-signature small { display: none; }
</style>
