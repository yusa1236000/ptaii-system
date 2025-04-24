<!-- src/components/accounting/AccountTreeItem.vue -->
<template>
    <div class="account-tree-item">
      <div class="account-row">
        <div class="account-info">
          <span class="toggle-icon" v-if="hasChildren" @click="toggleExpand">
            <i :class="isExpanded ? 'fas fa-caret-down' : 'fas fa-caret-right'"></i>
          </span>
          <span class="account-code">{{ account.account_code }}</span>
          <span class="account-name">{{ account.name }}</span>
          <span :class="['account-type-label', 'type-' + account.account_type.toLowerCase()]">
            {{ account.account_type }}
          </span>
          <span v-if="!account.is_active" class="inactive-tag">Inactive</span>
        </div>
        <div class="account-actions">
          <button class="btn btn-sm btn-secondary mr-1" @click="viewAccount">
            <i class="fas fa-eye"></i>
          </button>
          <button class="btn btn-sm btn-primary mr-1" @click="editAccount">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-danger" @click="deleteAccount">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>

      <!-- Child accounts (recursive) -->
      <div v-if="isExpanded && childAccounts.length > 0" class="child-accounts">
        <account-tree-item
          v-for="childAccount in childAccounts"
          :key="childAccount.account_id"
          :account="childAccount"
          :all-accounts="allAccounts"
          :expanded-nodes="expandedNodes"
          @toggle-expand="$emit('toggle-expand', $event)"
          @view-account="$emit('view-account', $event)"
          @edit-account="$emit('edit-account', $event)"
          @delete-account="$emit('delete-account', $event)"
        />
      </div>
    </div>
  </template>

  <script>
  export default {
    name: 'AccountTreeItem',
    props: {
      account: {
        type: Object,
        required: true
      },
      allAccounts: {
        type: Array,
        required: true
      },
      expandedNodes: {
        type: Set,
        required: true
      }
    },
    computed: {
      childAccounts() {
        return this.allAccounts.filter(acc =>
          acc.parent_account_id === this.account.account_id
        );
      },
      hasChildren() {
        return this.childAccounts.length > 0;
      },
      isExpanded() {
        return this.expandedNodes.has(this.account.account_id);
      }
    },
    methods: {
      toggleExpand() {
        this.$emit('toggle-expand', this.account.account_id);
      },
      viewAccount() {
        this.$emit('view-account', this.account);
      },
      editAccount() {
        this.$emit('edit-account', this.account);
      },
      deleteAccount() {
        this.$emit('delete-account', this.account);
      }
    }
  };
  </script>

  <style scoped>
  .account-tree-item {
    margin-bottom: 0.25rem;
  }

  .account-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.375rem;
    background-color: white;
    border: 1px solid var(--gray-200);
    transition: background-color 0.2s;
  }

  .account-row:hover {
    background-color: var(--gray-50);
  }

  .account-info {
    display: flex;
    align-items: center;
    flex: 1;
  }

  .toggle-icon {
    width: 1.5rem;
    text-align: center;
    cursor: pointer;
    margin-right: 0.5rem;
  }

  .account-code {
    font-family: monospace;
    font-weight: 500;
    min-width: 6rem;
    margin-right: 1rem;
  }

  .account-name {
    font-weight: 500;
    margin-right: 1rem;
    flex: 1;
  }

  .account-type-label {
    margin-right: 1rem;
  }

  .inactive-tag {
    background-color: var(--gray-300);
    color: var(--gray-700);
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
  }

  .account-actions {
    display: flex;
  }

  .child-accounts {
    margin-left: 1.5rem;
    padding-left: 1rem;
    border-left: 1px dashed var(--gray-300);
    margin-top: 0.25rem;
  }
  </style>
