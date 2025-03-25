<!-- src/views/Login.vue -->
<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logo">
        <h1>Inventory ERP</h1>
      </div>
      <div v-if="error" class="error-message">
        {{ error }}
      </div>
      <form @submit.prevent="login">
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
          />
        </div>
        <div class="form-controls">
          <label class="remember-me">
            <input type="checkbox" v-model="rememberMe" />
            Remember me
          </label>
          <button type="submit" class="login-button" :disabled="isLoading">
            {{ isLoading ? 'Logging in...' : 'Login' }}
          </button>
        </div>
        <div class="form-footer">
          Belum memiliki akun? <router-link to="/register">Daftar</router-link>
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
  name: 'LoginView',
  setup() {
    const router = useRouter();
    const email = ref('');
    const password = ref('');
    const rememberMe = ref(false);
    const error = ref('');
    const isLoading = ref(false);

    const login = async () => {
      try {
        isLoading.value = true;
        error.value = '';

        const response = await axios.post('/api/auth/login', {
          email: email.value,
          password: password.value,
        });

        // Store token in localStorage
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        
        // Set default axios header for future requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        
        // Redirect to dashboard
        router.push('/dashboard');
      } catch (err) {
        if (err.response && err.response.data) {
          error.value = err.response.data.message || 'Invalid credentials';
        } else {
          error.value = 'An error occurred during login. Please try again.';
        }
      } finally {
        isLoading.value = false;
      }
    };

    return {
      email,
      password,
      rememberMe,
      error,
      isLoading,
      login,
    };
  },
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}

.login-card {
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

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 1rem;
}

.form-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.login-button {
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

.login-button:hover {
  background-color: #1d4ed8;
}

.login-button:disabled {
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