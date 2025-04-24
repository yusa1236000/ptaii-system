<!-- src/components/accounting/StructureTreeNode.vue -->
<template>
    <div class="structure-tree-node">
      <div class="account-node" @click="navigateToAccount">
        <div class="account-info">
          <span class="account-code">{{ account.account_code }}</span>
          <span class="account-name">{{ account.name }}</span>
          <span v-if="!account.is_active" class="inactive-tag">Inactive</span>
        </div>
        <div class="account-actions">
          <button class="node-expand-btn" v-if="hasChildren" @click.stop="toggleExpand">
            <i :class="isExpanded ? 'fas fa-minus-circle' : 'fas fa-plus-circle'"></i>
          </button>
        </div>
      </div>

      <div v-if="isExpanded && hasChildren" class="child-nodes">
        <structure-tree-node
          v-for="childAccount in childAccounts"
          :key="childAccount.account_id"
          :account="childAccount"
          :all-accounts="allAccounts"
          @navigate-to-account="$emit('navigate-to-account', $event)"
        />
      </div>
    </div>
  </template>

  <script>
  export default {
    name: 'StructureTreeNode',
    props: {
      account: {
        type: Object,
        required: true
      },
      allAccounts: {
        type: Array,
        required: true
      }
    },
    data() {
      return {
        isExpanded: false
      };
    },
    computed: {
      childAccounts() {
        return this.allAccounts.filter(acc =>
          acc.parent_account_id === this.account.account_id
        );
      },
      hasChildren() {
        return this.childAccounts.length > 0;
      }
    },
    methods: {
      toggleExpand() {
        this.isExpanded = !this.isExpanded;
      },
      navigateToAccount() {
        this.$emit('navigate-to-account', this.account.account_id);
      }
    }
  };
  </script>

  <style scoped>
  .structure-tree-node {
    margin-bottom: 0.5rem;
  }

  .account-node {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
    background-color: white;
    cursor: pointer;
    transition: all 0.2s;
  }

  .account-node:hover {
    background-color: var(--gray-50);
    border-color: var(--gray-300);
  }

  .account-info {
    display: flex;
    align-items: center;
    flex: 1;
  }

  .account-code {
    font-family: monospace;
    font-weight: 500;
    min-width: 6rem;
    color: var(--gray-600);
    margin-right: 1rem;
  }

  .account-name {
    font-weight: 500;
  }

  .inactive-tag {
    margin-left: 0.75rem;
    padding: 0.125rem 0.375rem;
    background-color: var(--gray-200);
    color: var(--gray-600);
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .account-actions {
    display: flex;
  }

  .node-expand-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 1rem;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
  }

  .node-expand-btn:hover {
    color: var(--primary-color);
  }

  .child-nodes {
    margin-left: 1.5rem;
    padding-left: 1rem;
    border-left: 1px dashed var(--gray-300);
    margin-top: 0.5rem;
  }
  </style>
