{{-- Create / edit trader modal --}}
<div class="modal fade" id="traderModal" tabindex="-1" role="dialog" aria-labelledby="traderModalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="traderForm" class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="traderModalTitle">New Trader</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="traderId">
                <div class="form-group">
                    <label for="traderName">Name <span class="text-danger">*</span></label>
                    <input type="text" id="traderName" name="name" class="form-control" maxlength="255" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="traderPhone">Phone</label>
                        <input type="text" id="traderPhone" name="phone" class="form-control" maxlength="30">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="traderWhatsapp">WhatsApp</label>
                        <input type="text" id="traderWhatsapp" name="whatsapp" class="form-control" maxlength="30">
                    </div>
                </div>
                <div class="form-group">
                    <label for="traderStatus">Status</label>
                    <select id="traderStatus" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="traderNotes">Notes</label>
                    <textarea id="traderNotes" name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                </div>
                {{-- Opening balance is only captured on create; afterwards it is edited from the profile overview --}}
                <div class="form-row" id="traderOpeningBalanceRow">
                    <div class="form-group col-md-6">
                        <label for="traderOpeningBalance">Opening balance (EGP)</label>
                        <input type="number" step="0.01" id="traderOpeningBalance" name="opening_balance" class="form-control" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="traderOpeningBalanceDate">Opening balance date</label>
                        <input type="date" id="traderOpeningBalanceDate" name="opening_balance_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="traderSaveBtn">Save</button>
            </div>
        </form>
    </div>
</div>
