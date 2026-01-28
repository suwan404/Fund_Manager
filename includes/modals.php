<!-- Add Income Modal -->
<aside id="incomeModal" class="modal" role="dialog" aria-labelledby="income-modal-title" aria-modal="true">
    <div class="modal-content">
        <h3 id="income-modal-title">Add Income</h3>
        <form method="POST" action="index.php">
            <?php echo CSRF::field(); ?>
            <input type="hidden" name="add" value="1">
            <input type="hidden" name="type" value="income">
            
            <div class="form-group">
                <label for="income-amount" class="form-label">Amount (Rs)</label>
                <input type="number" id="income-amount" name="amount" step="0.01" min="0.01" required class="form-input" aria-required="true">
            </div>
            
            <div class="form-group">
                <label for="income-remarks" class="form-label">Remarks</label>
                <textarea id="income-remarks" name="remarks" required class="form-textarea" aria-required="true"></textarea>
            </div>
            
            <div class="form-group">
                <label for="income-date" class="form-label">Date</label>
                <input type="date" id="income-date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="form-input" aria-required="true">
            </div>
            
            <footer class="modal-footer">
                <button type="button" onclick="closeModal('incomeModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-green">Add Income</button>
            </footer>
        </form>
    </div>
</aside>

<!-- Add Expense Modal -->
<aside id="expenseModal" class="modal" role="dialog" aria-labelledby="expense-modal-title" aria-modal="true">
    <div class="modal-content">
        <h3 id="expense-modal-title">Add Expense</h3>
        <form method="POST" action="index.php">
            <?php echo CSRF::field(); ?>
            <input type="hidden" name="add" value="1">
            <input type="hidden" name="type" value="expense">
            
            <div class="form-group">
                <label for="expense-amount" class="form-label">Amount (Rs)</label>
                <input type="number" id="expense-amount" name="amount" step="0.01" min="0.01" required class="form-input" aria-required="true">
            </div>
            
            <div class="form-group">
                <label for="expense-remarks" class="form-label">Remarks</label>
                <textarea id="expense-remarks" name="remarks" required class="form-textarea" aria-required="true"></textarea>
            </div>
            
            <div class="form-group">
                <label for="expense-date" class="form-label">Date</label>
                <input type="date" id="expense-date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="form-input" aria-required="true">
            </div>
            
            <footer class="modal-footer">
                <button type="button" onclick="closeModal('expenseModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-red">Add Expense</button>
            </footer>
        </form>
    </div>
</aside>

<!-- Edit Transaction Modal -->
<aside id="editModal" class="modal" role="dialog" aria-labelledby="edit-modal-title" aria-modal="true">
    <div class="modal-content">
        <h3 id="edit-modal-title">Edit Transaction</h3>
        <form method="POST" action="index.php" id="editForm">
            <?php echo CSRF::field(); ?>
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="id" id="editId">
            
            <div class="form-group">
                <label for="editType" class="form-label">Type</label>
                <select name="type" id="editType" required class="form-input" aria-required="true">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editAmount" class="form-label">Amount (Rs)</label>
                <input type="number" name="amount" id="editAmount" step="0.01" min="0.01" required class="form-input" aria-required="true">
            </div>
            
            <div class="form-group">
                <label for="editRemarks" class="form-label">Remarks</label>
                <textarea name="remarks" id="editRemarks" required class="form-textarea" aria-required="true"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editDate" class="form-label">Date</label>
                <input type="date" name="date" id="editDate" required class="form-input" aria-required="true">
            </div>
            
            <footer class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-blue">Update</button>
            </footer>
        </form>
    </div>
</aside>

<!-- Delete Confirmation Modal -->
<aside id="deleteModal" class="modal" role="dialog" aria-labelledby="delete-modal-title" aria-modal="true">
    <div class="modal-content">
        <h3 id="delete-modal-title">Delete Transaction</h3>
        <p>Are you sure you want to delete this transaction?</p>
        <form method="POST" action="index.php" id="deleteForm">
            <?php echo CSRF::field(); ?>
            <input type="hidden" name="delete" value="1">
            <input type="hidden" name="id" id="deleteId">
            
            <footer class="modal-footer">
                <button type="button" onclick="closeModal('deleteModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-red">Delete</button>
            </footer>
        </form>
    </div>
</aside>