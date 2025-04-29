// Template Section
<template>
  <div class="reports-configuration">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="page-title">Financial Reports Configuration</h1>
      <button class="btn btn-primary" @click="saveConfiguration">
        <i class="fas fa-save mr-1"></i> Save Changes
      </button>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Company Information</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Company Name</label>
              <input type="text" v-model="config.companyName" class="form-control">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Company Logo</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="companyLogo" @change="handleLogoUpload">
                <label class="custom-file-label" for="companyLogo">Choose file...</label>
              </div>
              <div v-if="config.logoUrl" class="mt-2 text-center">
                <img :src="config.logoUrl" alt="Company Logo" style="max-height: 80px;">
                <button class="btn btn-sm btn-outline-danger d-block mt-2 mx-auto" @click="removeImage">
                  Remove Logo
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Address</label>
              <textarea v-model="config.address" class="form-control" rows="3"></textarea>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Contact Information</label>
              <input type="text" v-model="config.phone" class="form-control mb-2" placeholder="Phone">
              <input type="email" v-model="config.email" class="form-control" placeholder="Email">
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Tax ID</label>
              <input type="text" v-model="config.taxId" class="form-control">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Fiscal Year End</label>
              <input type="date" v-model="config.fiscalYearEnd" class="form-control">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Report Settings</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Default Currency</label>
              <select v-model="config.defaultCurrency" class="form-control">
                <option value="IDR">Indonesian Rupiah (IDR)</option>
                <option value="USD">US Dollar (USD)</option>
                <option value="EUR">Euro (EUR)</option>
                <option value="SGD">Singapore Dollar (SGD)</option>
                <option value="JPY">Japanese Yen (JPY)</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Decimal Precision</label>
              <select v-model="config.decimalPrecision" class="form-control">
                <option value="0">0 (No decimal places)</option>
                <option value="2">2 decimal places</option>
                <option value="3">3 decimal places</option>
                <option value="4">4 decimal places</option>
              </select>
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Date Format</label>
              <select v-model="config.dateFormat" class="form-control">
                <option value="dd/MM/yyyy">DD/MM/YYYY (31/12/2023)</option>
                <option value="MM/dd/yyyy">MM/DD/YYYY (12/31/2023)</option>
                <option value="yyyy-MM-dd">YYYY-MM-DD (2023-12-31)</option>
                <option value="d MMMM yyyy">D MMMM YYYY (31 December 2023)</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Number Format</label>
              <select v-model="config.numberFormat" class="form-control">
                <option value="id-ID">Indonesian (1.234.567,89)</option>
                <option value="en-US">US/UK (1,234,567.89)</option>
                <option value="de-DE">German/EU (1.234.567,89)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Default Comparison Type</label>
              <select v-model="config.defaultComparisonType" class="form-control">
                <option value="none">No Comparison</option>
                <option value="previous_period">Previous Period</option>
                <option value="previous_year">Previous Year</option>
                <option value="budget">Budget (for Income Statement)</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Paper Size</label>
              <select v-model="config.paperSize" class="form-control">
                <option value="a4">A4</option>
                <option value="letter">Letter</option>
                <option value="legal">Legal</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Account Grouping Configuration</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle mr-2"></i>
          Configure how accounts are grouped in the Balance Sheet and Income Statement reports.
        </div>

        <h6 class="mt-4 mb-3">Balance Sheet Groups</h6>

        <div class="account-group-section">
          <h6>Asset Groups</h6>
          <div v-for="(group, index) in config.balanceSheet.assetGroups" :key="`asset-${index}`" class="account-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">{{ group.name }}</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary mr-2" @click="editGroup('asset', index)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup('asset', index)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="account-list">
              <div v-for="account in group.accounts" :key="account.id" class="account-item">
                {{ account.code }} - {{ account.name }}
              </div>
              <div v-if="group.accounts.length === 0" class="text-muted small">
                No accounts assigned to this group
              </div>
            </div>
          </div>

          <button class="btn btn-sm btn-outline-success mt-2" @click="addGroup('asset')">
            <i class="fas fa-plus mr-1"></i> Add Asset Group
          </button>
        </div>

        <div class="account-group-section mt-4">
          <h6>Liability Groups</h6>
          <div v-for="(group, index) in config.balanceSheet.liabilityGroups" :key="`liability-${index}`" class="account-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">{{ group.name }}</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary mr-2" @click="editGroup('liability', index)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup('liability', index)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="account-list">
              <div v-for="account in group.accounts" :key="account.id" class="account-item">
                {{ account.code }} - {{ account.name }}
              </div>
              <div v-if="group.accounts.length === 0" class="text-muted small">
                No accounts assigned to this group
              </div>
            </div>
          </div>

          <button class="btn btn-sm btn-outline-success mt-2" @click="addGroup('liability')">
            <i class="fas fa-plus mr-1"></i> Add Liability Group
          </button>
        </div>

        <div class="account-group-section mt-4">
          <h6>Equity Groups</h6>
          <div v-for="(group, index) in config.balanceSheet.equityGroups" :key="`equity-${index}`" class="account-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">{{ group.name }}</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary mr-2" @click="editGroup('equity', index)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup('equity', index)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="account-list">
              <div v-for="account in group.accounts" :key="account.id" class="account-item">
                {{ account.code }} - {{ account.name }}
              </div>
              <div v-if="group.accounts.length === 0" class="text-muted small">
                No accounts assigned to this group
              </div>
            </div>
          </div>

          <button class="btn btn-sm btn-outline-success mt-2" @click="addGroup('equity')">
            <i class="fas fa-plus mr-1"></i> Add Equity Group
          </button>
        </div>

        <h6 class="mt-5 mb-3">Income Statement Groups</h6>

        <div class="account-group-section">
          <h6>Revenue Groups</h6>
          <div v-for="(group, index) in config.incomeStatement.revenueGroups" :key="`revenue-${index}`" class="account-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">{{ group.name }}</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary mr-2" @click="editGroup('revenue', index)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup('revenue', index)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="account-list">
              <div v-for="account in group.accounts" :key="account.id" class="account-item">
                {{ account.code }} - {{ account.name }}
              </div>
              <div v-if="group.accounts.length === 0" class="text-muted small">
                No accounts assigned to this group
              </div>
            </div>
          </div>

          <button class="btn btn-sm btn-outline-success mt-2" @click="addGroup('revenue')">
            <i class="fas fa-plus mr-1"></i> Add Revenue Group
          </button>
        </div>

        <div class="account-group-section mt-4">
          <h6>Expense Groups</h6>
          <div v-for="(group, index) in config.incomeStatement.expenseGroups" :key="`expense-${index}`" class="account-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">{{ group.name }}</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary mr-2" @click="editGroup('expense', index)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup('expense', index)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="account-list">
              <div v-for="account in group.accounts" :key="account.id" class="account-item">
                {{ account.code }} - {{ account.name }}
              </div>
              <div v-if="group.accounts.length === 0" class="text-muted small">
                No accounts assigned to this group
              </div>
            </div>
          </div>

          <button class="btn btn-sm btn-outline-success mt-2" @click="addGroup('expense')">
            <i class="fas fa-plus mr-1"></i> Add Expense Group
          </button>
        </div>
      </div>
    </div>

    <!-- Group Edit Modal -->
    <div class="modal fade" id="groupEditModal" tabindex="-1" role="dialog" aria-labelledby="groupEditModalLabel" aria-hidden="true" v-if="showGroupModal">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="groupEditModalLabel">Edit {{ currentGroupType }} Group</h5>
            <button type="button" class="close" @click="closeGroupModal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Group Name</label>
              <input type="text" v-model="editingGroup.name" class="form-control">
            </div>

            <div class="form-group">
              <label>Display Order</label>
              <input type="number" v-model="editingGroup.order" class="form-control">
            </div>

            <div class="form-group">
              <label>Select Accounts</label>
              <div class="account-selector">
                <div class="row">
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <h6 class="mb-0">Available Accounts</h6>
                        <input type="text" v-model="accountSearchQuery" class="form-control form-control-sm mt-2" placeholder="Search accounts...">
                      </div>
                      <div class="card-body accounts-list">
                        <div v-for="account in filteredAvailableAccounts" :key="account.id" class="account-item selectable" @click="addAccountToGroup(account)">
                          {{ account.code }} - {{ account.name }}
                        </div>
                        <div v-if="filteredAvailableAccounts.length === 0" class="text-muted text-center p-3">
                          No available accounts found
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <h6 class="mb-0">Selected Accounts</h6>
                      </div>
                      <div class="card-body accounts-list">
                        <div v-for="(account, idx) in editingGroup.accounts" :key="account.id" class="account-item selectable d-flex justify-content-between align-items-center">
                          <span>{{ account.code }} - {{ account.name }}</span>
                          <button class="btn btn-sm btn-outline-danger" @click="removeAccountFromGroup(idx)">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                        <div v-if="editingGroup.accounts.length === 0" class="text-muted text-center p-3">
                          No accounts selected
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeGroupModal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveGroupChanges">Save Changes</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

// Script Section
<script>
import axios from 'axios';
import { ref, reactive, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap';

export default {
  name: 'ReportsConfiguration',
  setup() {
    const config = reactive({
      companyName: '',
      logoUrl: '',
      address: '',
      phone: '',
      email: '',
      taxId: '',
      fiscalYearEnd: '',
      defaultCurrency: 'IDR',
      decimalPrecision: '0',
      dateFormat: 'dd/MM/yyyy',
      numberFormat: 'id-ID',
      defaultComparisonType: 'none',
      paperSize: 'a4',
      balanceSheet: {
        assetGroups: [],
        liabilityGroups: [],
        equityGroups: []
      },
      incomeStatement: {
        revenueGroups: [],
        expenseGroups: []
      }
    });

    const allAccounts = ref([]);
    const showGroupModal = ref(false);
    const currentGroupType = ref('');
    const currentGroupIndex = ref(-1);
    const accountSearchQuery = ref('');

    const editingGroup = reactive({
      name: '',
      order: 0,
      accounts: []
    });

    let groupModal = null;

    // Computed properties
    const filteredAvailableAccounts = computed(() => {
      if (!accountSearchQuery.value) {
        // Return appropriate accounts based on the current group type
        return getAvailableAccountsForCurrentGroupType();
      }

      const query = accountSearchQuery.value.toLowerCase();
      return getAvailableAccountsForCurrentGroupType().filter(account =>
        account.code.toLowerCase().includes(query) ||
        account.name.toLowerCase().includes(query)
      );
    });

    // Methods
    const getAvailableAccountsForCurrentGroupType = () => {
      // Get accounts that aren't already in the current group
      const selectedAccountIds = editingGroup.accounts.map(account => account.id);

      // Filter accounts based on the account type appropriate for the current group type
      let accountTypes = [];

      switch (currentGroupType.value) {
        case 'asset':
          accountTypes = ['asset'];
          break;
        case 'liability':
          accountTypes = ['liability'];
          break;
        case 'equity':
          accountTypes = ['equity'];
          break;
        case 'revenue':
          accountTypes = ['revenue', 'income'];
          break;
        case 'expense':
          accountTypes = ['expense'];
          break;
        default:
          accountTypes = [];
      }

      return allAccounts.value.filter(account =>
        !selectedAccountIds.includes(account.id) &&
        accountTypes.includes(account.account_type?.toLowerCase())
      );
    };

    const loadConfiguration = async () => {
      try {
        const response = await axios.get('/api/accounting/reports/configuration');

        // Apply configuration from the backend
        Object.assign(config, response.data.data);
      } catch (error) {
        console.error('Error loading report configuration:', error);
        // Initialize with default configuration if failed to load
        initializeDefaultConfiguration();
      }
    };

    const loadAccounts = async () => {
      try {
        const response = await axios.get('/api/accounting/chart-of-accounts');
        allAccounts.value = response.data.data;
      } catch (error) {
        console.error('Error loading accounts:', error);
        allAccounts.value = [];
      }
    };

    const initializeDefaultConfiguration = () => {
      // Reset configuration with defaults
      config.companyName = 'Your Company Name';
      config.logoUrl = '';
      config.address = '';
      config.phone = '';
      config.email = '';
      config.taxId = '';
      config.fiscalYearEnd = '';
      config.defaultCurrency = 'IDR';
      config.decimalPrecision = '0';
      config.dateFormat = 'dd/MM/yyyy';
      config.numberFormat = 'id-ID';
      config.defaultComparisonType = 'none';
      config.paperSize = 'a4';

      // Default balance sheet groups
      config.balanceSheet.assetGroups = [
        { name: 'Current Assets', order: 1, accounts: [] },
        { name: 'Fixed Assets', order: 2, accounts: [] },
        { name: 'Other Assets', order: 3, accounts: [] }
      ];

      config.balanceSheet.liabilityGroups = [
        { name: 'Current Liabilities', order: 1, accounts: [] },
        { name: 'Long-term Liabilities', order: 2, accounts: [] }
      ];

      config.balanceSheet.equityGroups = [
        { name: 'Owner\'s Equity', order: 1, accounts: [] },
        { name: 'Retained Earnings', order: 2, accounts: [] }
      ];

      // Default income statement groups
      config.incomeStatement.revenueGroups = [
        { name: 'Operating Revenue', order: 1, accounts: [] },
        { name: 'Other Revenue', order: 2, accounts: [] }
      ];

      config.incomeStatement.expenseGroups = [
        { name: 'Cost of Goods Sold', order: 1, accounts: [] },
        { name: 'Operating Expenses', order: 2, accounts: [] },
        { name: 'Financial Expenses', order: 3, accounts: [] },
        { name: 'Tax Expenses', order: 4, accounts: [] }
      ];
    };

    const handleLogoUpload = (event) => {
      const file = event.target.files[0];
      if (!file) return;

      // Check file type
      if (!file.type.match('image.*')) {
        alert('Please select an image file');
        return;
      }

      // Check file size (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        alert('File size should not exceed 2MB');
        return;
      }

      const formData = new FormData();
      formData.append('logo', file);

      // Upload logo
      axios.post('/api/accounting/reports/upload-logo', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
        .then(response => {
          config.logoUrl = response.data.url;
        })
        .catch(error => {
          console.error('Error uploading logo:', error);
          alert('Failed to upload logo. Please try again.');
        });
    };

    const removeImage = () => {
      if (confirm('Are you sure you want to remove the company logo?')) {
        // Remove logo from server (if needed)
        axios.delete('/api/accounting/reports/remove-logo')
          .then(() => {
            config.logoUrl = '';
          })
          .catch(error => {
            console.error('Error removing logo:', error);
            alert('Failed to remove logo. Please try again.');
          });
      }
    };

    const saveConfiguration = async () => {
      try {
        await axios.post('/api/accounting/reports/configuration', config);
        alert('Configuration saved successfully');
      } catch (error) {
        console.error('Error saving configuration:', error);
        alert('Failed to save configuration. Please try again.');
      }
    };

    const addGroup = (type) => {
      currentGroupType.value = type;
      currentGroupIndex.value = -1;

      // Initialize empty editing group
      editingGroup.name = '';
      editingGroup.order = getNextOrderForType(type);
      editingGroup.accounts = [];

      showGroupModal.value = true;
      setTimeout(() => {
        if (!groupModal) {
          groupModal = new Modal(document.getElementById('groupEditModal'));
        }
        groupModal.show();
      }, 100);
    };

    const editGroup = (type, index) => {
      currentGroupType.value = type;
      currentGroupIndex.value = index;

      // Get the appropriate group based on type
      const group = getGroupByTypeAndIndex(type, index);

      // Initialize editing group with selected group data
      editingGroup.name = group.name;
      editingGroup.order = group.order;
      editingGroup.accounts = [...group.accounts];

      showGroupModal.value = true;
      setTimeout(() => {
        if (!groupModal) {
          groupModal = new Modal(document.getElementById('groupEditModal'));
        }
        groupModal.show();
      }, 100);
    };

    const removeGroup = (type, index) => {
      if (confirm('Are you sure you want to remove this group? Any accounts assigned to this group will need to be reassigned.')) {
        // Remove group from the appropriate array
        switch (type) {
          case 'asset':
            config.balanceSheet.assetGroups.splice(index, 1);
            break;
          case 'liability':
            config.balanceSheet.liabilityGroups.splice(index, 1);
            break;
          case 'equity':
            config.balanceSheet.equityGroups.splice(index, 1);
            break;
          case 'revenue':
            config.incomeStatement.revenueGroups.splice(index, 1);
            break;
          case 'expense':
            config.incomeStatement.expenseGroups.splice(index, 1);
            break;
        }
      }
    };

    const getGroupByTypeAndIndex = (type, index) => {
      switch (type) {
        case 'asset':
          return config.balanceSheet.assetGroups[index];
        case 'liability':
          return config.balanceSheet.liabilityGroups[index];
        case 'equity':
          return config.balanceSheet.equityGroups[index];
        case 'revenue':
          return config.incomeStatement.revenueGroups[index];
        case 'expense':
          return config.incomeStatement.expenseGroups[index];
        default:
          return { name: '', order: 0, accounts: [] };
      }
    };

    const getNextOrderForType = (type) => {
      let groups = [];

      switch (type) {
        case 'asset':
          groups = config.balanceSheet.assetGroups;
          break;
        case 'liability':
          groups = config.balanceSheet.liabilityGroups;
          break;
        case 'equity':
          groups = config.balanceSheet.equityGroups;
          break;
        case 'revenue':
          groups = config.incomeStatement.revenueGroups;
          break;
        case 'expense':
          groups = config.incomeStatement.expenseGroups;
          break;
      }

      return groups.length > 0 ? Math.max(...groups.map(g => g.order)) + 1 : 1;
    };

    const closeGroupModal = () => {
      if (groupModal) {
        groupModal.hide();
      }
      showGroupModal.value = false;
    };

    const saveGroupChanges = () => {
      // Create new group object
      const updatedGroup = {
        name: editingGroup.name,
        order: editingGroup.order,
        accounts: [...editingGroup.accounts]
      };

      // Update or add group based on current group type and index
      if (currentGroupIndex.value === -1) {
        // Add new group
        switch (currentGroupType.value) {
          case 'asset':
            config.balanceSheet.assetGroups.push(updatedGroup);
            break;
          case 'liability':
            config.balanceSheet.liabilityGroups.push(updatedGroup);
            break;
          case 'equity':
            config.balanceSheet.equityGroups.push(updatedGroup);
            break;
          case 'revenue':
            config.incomeStatement.revenueGroups.push(updatedGroup);
            break;
          case 'expense':
            config.incomeStatement.expenseGroups.push(updatedGroup);
            break;
        }
      } else {
        // Update existing group
        switch (currentGroupType.value) {
          case 'asset':
            config.balanceSheet.assetGroups[currentGroupIndex.value] = updatedGroup;
            break;
          case 'liability':
            config.balanceSheet.liabilityGroups[currentGroupIndex.value] = updatedGroup;
            break;
          case 'equity':
            config.balanceSheet.equityGroups[currentGroupIndex.value] = updatedGroup;
            break;
          case 'revenue':
            config.incomeStatement.revenueGroups[currentGroupIndex.value] = updatedGroup;
            break;
          case 'expense':
            config.incomeStatement.expenseGroups[currentGroupIndex.value] = updatedGroup;
            break;
        }
      }

      // Sort groups by order
      sortGroupsByOrder();

      // Close modal
      closeGroupModal();
    };

    const sortGroupsByOrder = () => {
      config.balanceSheet.assetGroups.sort((a, b) => a.order - b.order);
      config.balanceSheet.liabilityGroups.sort((a, b) => a.order - b.order);
      config.balanceSheet.equityGroups.sort((a, b) => a.order - b.order);
      config.incomeStatement.revenueGroups.sort((a, b) => a.order - b.order);
      config.incomeStatement.expenseGroups.sort((a, b) => a.order - b.order);
    };

    const addAccountToGroup = (account) => {
      // Add account to the editing group
      editingGroup.accounts.push(account);
    };

    const removeAccountFromGroup = (index) => {
      // Remove account from the editing group
      editingGroup.accounts.splice(index, 1);
    };

    // Initialize component
    onMounted(async () => {
      await Promise.all([
        loadConfiguration(),
        loadAccounts()
      ]);
    });

    return {
      config,
      allAccounts,
      showGroupModal,
      currentGroupType,
      currentGroupIndex,
      editingGroup,
      accountSearchQuery,
      filteredAvailableAccounts,
      handleLogoUpload,
      removeImage,
      saveConfiguration,
      addGroup,
      editGroup,
      removeGroup,
      closeGroupModal,
      saveGroupChanges,
      addAccountToGroup,
      removeAccountFromGroup
    };
  }
};
</script>

// Style Section
<style scoped>
.reports-configuration {
  padding: 1rem;
}

.page-title {
  margin-bottom: 1.5rem;
}

.account-group {
  border: 1px solid var(--gray-200);
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: var(--gray-50);
}

.account-list {
  max-height: 150px;
  overflow-y: auto;
  padding: 0.5rem;
  background-color: white;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
}

.account-item {
  padding: 0.5rem;
  border-bottom: 1px solid var(--gray-100);
  font-size: 0.875rem;
}

.account-item:last-child {
  border-bottom: none;
}

.account-item.selectable {
  cursor: pointer;
}

.account-item.selectable:hover {
  background-color: var(--primary-bg);
}

.accounts-list {
  max-height: 300px;
  overflow-y: auto;
  padding: 0;
}

.account-group-section {
  margin-bottom: 2rem;
}

/* Modal styles */
.modal-body .form-group {
  margin-bottom: 1.5rem;
}

.account-selector {
  border: 1px solid var(--gray-200);
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: var(--gray-50);
}

@media (max-width: 767px) {
  .account-selector .row {
    flex-direction: column;
  }

  .account-selector .col-md-6:first-child {
    margin-bottom: 1rem;
  }
}
</style>
