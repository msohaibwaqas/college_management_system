@extends('layouts.app')

@section('title', 'Review Fee Voucher - ' . $application->full_name)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Review & Adjust Fee Voucher
                    </h5>
                    <a href="{{ route('admission-applications.show', $application) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Application
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <div class="d-flex">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <strong>Applicant:</strong> {{ $application->full_name }} ({{ $application->subject_group }} Group)<br>
                                <small class="text-muted">Review the calculated fees below. You can remove items, change amounts, or apply a discount.</small>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admission-applications.generate-voucher-custom', $application) }}" method="POST" id="voucherForm">
                        @csrf
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border" id="feeTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input select-all" type="checkbox" checked>
                                            </div>
                                        </th>
                                        <th>Fee Item</th>
                                        <th style="width: 250px;">Original Amount (Rs.)</th>
                                        <th style="width: 150px;">Discount (%)</th>
                                        <th style="width: 200px;">Final Amount (Rs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                    <tr class="fee-row">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input item-checkbox" type="checkbox" name="items[{{ $index }}][enabled]" value="1" checked>
                                            </div>
                                            <input type="hidden" name="items[{{ $index }}][fee_head_id]" value="{{ $item['fee_head_id'] }}">
                                            <input type="hidden" name="items[{{ $index }}][is_refundable]" value="{{ $item['is_refundable'] ? 1 : 0 }}">
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $index }}][name]" class="form-control form-control-sm border-0 bg-transparent fw-bold" value="{{ $item['name'] }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm original-amount" value="{{ $item['amount'] }}" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="1" min="0" max="100" class="form-control form-control-sm item-discount" value="0">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][amount]" step="0.01" class="form-control form-control-sm final-amount fw-bold text-primary" value="{{ $item['amount'] }}">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Payable Balance:</strong></td>
                                        <td>
                                            <h4 class="mb-0 text-primary">Rs. <span id="grandTotal">0.00</span></h4>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6><i class="fas fa-percentage me-2 text-primary"></i>Bulk Discount</h6>
                                        <p class="small text-muted">Apply a percentage discount to all selected items below.</p>
                                        <div class="input-group">
                                            <input type="number" id="bulkDiscountPercent" class="form-control" placeholder="Discount % (e.g. 20)">
                                            <button type="button" class="btn btn-primary" id="applyBulkDiscount">Apply to Selected</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm">
                                    <i class="fas fa-check-circle me-2"></i>Confirm & Generate Voucher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Calculate Totals
        function calculateGrandTotal() {
            let total = 0;
            $('.fee-row').each(function() {
                const checkbox = $(this).find('.item-checkbox');
                if (checkbox.is(':checked')) {
                    const amount = parseFloat($(this).find('.final-amount').val()) || 0;
                    total += amount;
                }
            });
            $('#grandTotal').text(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }

        // Handle Item Checkbox Change
        $('.item-checkbox').on('change', function() {
            const row = $(this).closest('.fee-row');
            if ($(this).is(':checked')) {
                row.removeClass('opacity-50');
                row.find('input:not(.item-checkbox):not(.original-amount)').prop('disabled', false);
            } else {
                row.addClass('opacity-50');
                row.find('input:not(.item-checkbox):not(.original-amount)').prop('disabled', true);
            }
            calculateGrandTotal();
        });

        // Handle Discount Change per Item
        $('.item-discount').on('input', function() {
            const row = $(this).closest('.fee-row');
            const original = parseFloat(row.find('.original-amount').val()) || 0;
            const discountPercent = parseFloat($(this).val()) || 0;
            const final = original * (1 - (discountPercent / 100));
            row.find('.final-amount').val(final.toFixed(2));
            calculateGrandTotal();
        });

        // Handle Final Amount Manual Edit
        $('.final-amount').on('input', function() {
             calculateGrandTotal();
        });

        // Bulk Discount Logic
        $('#applyBulkDiscount').on('click', function() {
            const bulkPercent = parseFloat($('#bulkDiscountPercent').val()) || 0;
            if (bulkPercent < 0 || bulkPercent > 100) {
                alert('Please enter a valid percentage between 0 and 100');
                return;
            }

            $('.fee-row').each(function() {
                const checkbox = $(this).find('.item-checkbox');
                if (checkbox.is(':checked')) {
                    const row = $(this);
                    row.find('.item-discount').val(bulkPercent);
                    const original = parseFloat(row.find('.original-amount').val()) || 0;
                    const final = original * (1 - (bulkPercent / 100));
                    row.find('.final-amount').val(final.toFixed(2));
                }
            });
            calculateGrandTotal();
        });

        // Select All Checkbox
        $('.select-all').on('change', function() {
            const checked = $(this).is(':checked');
            $('.item-checkbox').prop('checked', checked).trigger('change');
        });

        // Initial Calculation
        calculateGrandTotal();

        // Form Submit Validation
        $('#voucherForm').on('submit', function(e) {
             const checkedItems = $('.item-checkbox:checked').length;
             if (checkedItems === 0) {
                 e.preventDefault();
                 alert('Please select at least one fee item to generate a voucher.');
             }
        });
    });
</script>

<style>
    .fee-row { transition: all 0.2s; }
    .opacity-50 { filter: grayscale(100%); }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }
    .card { border-radius: 12px; }
    #feeTable input.border-0:focus { box-shadow: none; outline: none; }
</style>
@endsection
