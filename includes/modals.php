<!-- Add Income Modal -->
<div id="incomeModal" class="modal">
    <div class="modal-content">
        <h3 class="text-lg font-semibold mb-4">Add Income</h3>
        <form id="incomeForm" onsubmit="addTransaction(event, 'income')">
            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Amount (Rs)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Remarks</label>
                    <textarea name="remarks" rows="3" required 
                              class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Date</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('incomeModal')" 
                        class="border border-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    Add Income
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="expenseModal" class="modal">
    <div class="modal-content">
        <h3 class="text-lg font-semibold mb-4">Add Expense</h3>
        <form id="expenseForm" onsubmit="addTransaction(event, 'expense')">
            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Amount (Rs)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Remarks</label>
                    <textarea name="remarks" rows="3" required 
                              class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Date</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('expenseModal')" 
                        class="border border-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                    Add Expense
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 class="text-lg font-semibold mb-4">Edit Transaction</h3>
        <form id="editForm" onsubmit="updateTransaction(event)">
            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
            <input type="hidden" id="editId" name="id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select id="editType" name="type" required 
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Amount (Rs)</label>
                    <input type="number" id="editAmount" name="amount" step="0.01" min="0.01" required 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Remarks</label>
                    <textarea id="editRemarks" name="remarks" rows="3" required 
                              class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Date</label>
                    <input type="date" id="editDate" name="date" required 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('editModal')" 
                        class="border border-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Update Transaction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <h3 class="text-lg font-semibold mb-4">Edit Profile</h3>
        <form id="profileForm" onsubmit="updateProfile(event)">
            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Full Name</label>
                    <input type="text" id="profileName" name="full_name" required 
                           value="<?php echo escape($_SESSION['full_name'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" id="profileEmail" name="email" required 
                           value="<?php echo escape($_SESSION['email'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" required 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">New Password (optional)</label>
                    <input type="password" id="newPassword" name="new_password"
                           placeholder="Leave blank to keep current password"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                    <div class="text-xs text-gray-500 mt-1">
                        Must be 8+ chars with uppercase, lowercase, and number
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('profileModal')" 
                        class="border border-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>
