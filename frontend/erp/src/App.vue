<!-- src/App.vue -->
<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
/* Global styles */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

:root {
  --primary-color: #2563eb;
  --primary-dark: #1d4ed8;
  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-300: #cbd5e1;
  --gray-400: #94a3b8;
  --gray-500: #64748b;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;
  --success-color: #059669;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --body-bg: #f1f5f9;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html, body {
  height: 100%;
  font-family: 'Inter', sans-serif;
  font-size: 16px;
  line-height: 1.5;
  color: var(--gray-800);
  background-color: var(--body-bg);
}

#app {
  height: 100%;
}

h1, h2, h3, h4, h5, h6 {
  color: var(--gray-800);
  font-weight: 600;
  line-height: 1.3;
}

h1 {
  font-size: 1.875rem;
}

h2 {
  font-size: 1.5rem;
}

h3 {
  font-size: 1.25rem;
}

/* Link styles */
a {
  color: var(--primary-color);
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

/* Focus styles for accessibility */
button:focus, input:focus, select:focus, textarea:focus {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

::-webkit-scrollbar-track {
  background: var(--gray-100);
}

::-webkit-scrollbar-thumb {
  background: var(--gray-300);
  border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--gray-400);
}
</style>