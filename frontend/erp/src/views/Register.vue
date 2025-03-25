<!-- src/views/Register.vue -->
<template>
    <div class="register-container">
      <div class="register-card">
        <div class="logo">
          <h1>Inventory ERP</h1>
        </div>
        <div v-if="error" class="error-message">
          {{ error }}
        </div>
        <form @submit.prevent="register">
          <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input
              type="text"
              id="name"
              v-model="name"
              required
              placeholder="Nama Lengkap"
            />
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              v-model="email"
              required
              placeholder="Email"
            />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              v-model="password"
              required
              placeholder="Password"
              minlength="8"
            />
          </div>
          <div class="form-group">
            <label for="password_confirmation">Konfirmasi Password</label>
            <input
              type="password"
              id="password_confirmation"
              v-model="passwordConfirmation"
              required
              placeholder="Konfirmasi Password"
              minlength="8"
            />
          </div>
          <div class="form-controls">
            <button type="submit" class="register-button" :disabled="isLoading">
              {{ isLoading ? 'Mendaftar...' : 'Daftar' }}
            </button>
          </div>
          <div class="form-footer">
            Sudah memiliki akun? <router-link to="/login">Login</router-link>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import { ref } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'RegisterView',
    setup() {
      const router = useRouter();
      const name = ref('');
      const email = ref('');
      const password = ref('');
      const passwordConfirmation = ref('');
      const error = ref('');
      const isLoading = ref(false);
  
      const register = async () => {
        // Validasi password dan konfirmasi password
        if (password.value !== passwordConfirmation.value) {
          error.value = 'Password dan konfirmasi password tidak sama';
          return;
        }
  
        try {
          isLoading.value = true;
          error.value = '';
  
          await axios.post('/api/auth/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
          });
  
          // Registrasi berhasil, redirect ke login
          alert('Registrasi berhasil! Silakan login dengan akun baru Anda.');
          router.push('/login');
        } catch (err) {
          if (err.response && err.response.data) {
            // Handle validasi error dari Laravel
            if (err.response.data.errors) {
              const validationErrors = err.response.data.errors;
              const firstError = Object.values(validationErrors)[0][0];
              error.value = firstError;
            } else {
              error.value = err.response.data.message || 'Terjadi kesalahan saat mendaftar';
            }
          } else {
            error.value = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
          }
        } finally {
          isLoading.value = false;
        }
      };
  
      return {
        name,
        email,
        password,
        passwordConfirmation,
        error,
        isLoading,
        register,
      };
    },
  };
  </script>
  
  <style scoped>
  .register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f5f5f5;
  }
  
  .register-card {
    width: 400px;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
  
  .logo {
    text-align: center;
    margin-bottom: 2rem;
  }
  
  .logo h1 {
    color: #2563eb;
    font-weight: 600;
    margin: 0;
  }
  
  .form-group {
    margin-bottom: 1.5rem;
  }
  
  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
  }
  
  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 1rem;
  }
  
  .form-controls {
    margin-top: 1.5rem;
  }
  
  .register-button {
    width: 100%;
    background-color: #2563eb;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .register-button:hover {
    background-color: #1d4ed8;
  }
  
  .register-button:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
  }
  
  .error-message {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
  }
  
  .form-footer {
    margin-top: 1.5rem;
    text-align: center;
    font-size: 0.875rem;
    color: #6b7280;
  }
  
  .form-footer a {
    color: #2563eb;
    font-weight: 500;
  }
  </style>