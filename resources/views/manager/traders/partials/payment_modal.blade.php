{{-- Add payment modal (payments are not linked to purchase orders) --}}
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="paymentForm" class="modal-content" enctype="multipart/form-data" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalTitle">Add Payment — {{ $trader->name }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="paymentAmount">Amount (EGP) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="paymentAmount" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="paymentDate">Payment date <span class="text-danger">*</span></label>
                        <input type="date" id="paymentDate" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="paymentMethod">Method <span class="text-danger">*</span></label>
                        <select id="paymentMethod" name="method" class="form-control" required>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="paymentReference">Reference number</label>
                        <input type="text" id="paymentReference" name="reference_number" class="form-control" maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="paymentNotes">Notes</label>
                    <textarea id="paymentNotes" name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                </div>
                <div class="form-group mb-0">
                    <label for="paymentAttachment">Attachment <small class="text-muted">(image or PDF, max 5 MB)</small></label>
                    <input type="file" id="paymentAttachment" name="attachment" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" id="paymentSaveBtn">Save Payment</button>
            </div>
        </form>
    </div>
</div>
