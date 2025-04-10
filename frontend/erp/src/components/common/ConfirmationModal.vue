```vue
<!-- src/components/common/ConfirmationModal.vue -->
<template>
  <div class="modal">
    <div class="modal-backdrop" @click="$emit('close')"></div>
    <div class="modal-content modal-sm">
      <div class="modal-header">
        <h2>{{ title }}</h2>
        <button class="close-btn" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <p v-html="message"></p>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">
            {{ cancelButtonText }}
          </button>
          <button
            type="button"
            :class="confirmButtonClass"
            @click="$emit('confirm')"
          >
            {{ confirmButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmationModal',
  props: {
    title: {
      type: String,
      default: 'Confirmation'
    },
    message: {
      type: String,
      required: true
    },
    confirmButtonText: {
      type: String,
      default: 'Confirm'
    },
    cancelButtonText: {
      type: String,
      default: 'Cancel'
    },
    confirmButtonClass: {
      type: String,
      default: 'btn btn-primary'
    }
  },
  emits: ['confirm', 'close']
};
</script>

<style scoped>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  z-index: 60;
  overflow: hidden;
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: #1e293b;
}

.close-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}
</style>
```