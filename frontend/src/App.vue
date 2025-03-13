<template>
  <div id="app">
    <header class="header">
      <div class="container">
        <h1 class="logo">Component Marketplace</h1>
        <nav class="main-nav">
          <!-- Navigation links will be added here -->
        </nav>
      </div>
    </header>

    <main class="main-content">
      <div class="container">
        <router-view></router-view>
      </div>
    </main>

    <footer class="footer">
      <div class="container">
        <p>&copy; 2025 Component Marketplace. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'App',
  components: {
    // Components will be registered here
  },
  data() {
    return {
      // Component data will be defined here
    }
  },
  computed: {
    isAuthenticated() {
      return this.$store.state.isAuthenticated
    },
    currentUser() {
      return this.$store.state.user
    }
  },
  methods: {
    logout() {
      this.$store.dispatch('logout')
      this.$router.push('/login')
    }
  }
}
</script>

<style>
/* Base styles */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: Arial, sans-serif;
  line-height: 1.6;
  color: #333;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

/* Header styles */
.header {
  background-color: #2c3e50;
  color: #fff;
  padding: 1rem 0;
}

.logo {
  font-size: 1.5rem;
}

/* Main content styles */
.main-content {
  min-height: calc(100vh - 120px);
  padding: 2rem 0;
}

/* Footer styles */
.footer {
  background-color: #f8f9fa;
  padding: 1rem 0;
  text-align: center;
  font-size: 0.9rem;
}
</style>
