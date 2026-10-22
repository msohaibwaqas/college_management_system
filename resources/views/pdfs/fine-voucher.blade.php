@php
    $settings = $settings ?? \App\Models\InstituteSetting::getSettings();
    $target = $fine ?? ($voucher ?? null);
    $printable = ($target instanceof \App\Contracts\PrintableVoucherInterface)
        ? $target 
        : \App\Services\Voucher\VoucherFactory::create($target, $settings);
@endphp
@include('pdfs.fee-voucher', ['voucher' => $printable, 'settings' => $settings])
