<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gld-auth-wrap"
     x-data="{
       form: { email: '', password: '', remember: false },
       error: '',
       submitting: false,

       async submit() {
         this.error = '';
         this.submitting = true;
         try {
           const body = new URLSearchParams({ action: 'gld_login', nonce: GLD_AUTH.nonce });
           body.append('email',    this.form.email);
           body.append('password', this.form.password);
           if (this.form.remember) body.append('remember', '1');

           const r    = await fetch(GLD_AUTH.ajaxUrl, { method: 'POST', body });
           const data = await r.json();

           if (!data.success) { this.error = data.data?.message || 'Sign-in failed.'; return; }
           window.location.href = data.data.redirect;
         } catch (_) {
           this.error = 'An unexpected error occurred. Please try again.';
         } finally {
           this.submitting = false;
         }
       }
     }">
  <div class="gld-auth-card">

    <h1 class="gld-auth-title">Group Leader Portal</h1>
    <p class="gld-auth-sub">Sign in to manage your group's learning access.</p>

    <div class="gld-auth-error" x-show="error" x-cloak x-text="error"></div>

    <form @submit.prevent="submit()">

      <div class="gld-auth-field">
        <label for="gld-login-email">Email address</label>
        <input id="gld-login-email" type="email" x-model="form.email"
               required autocomplete="email">
      </div>

      <div class="gld-auth-field">
        <label for="gld-login-pass">Password</label>
        <input id="gld-login-pass" type="password" x-model="form.password"
               required autocomplete="current-password">
      </div>

      <label class="gld-auth-check">
        <input type="checkbox" x-model="form.remember">
        Keep me signed in
      </label>

      <button type="submit" class="gld-auth-btn" :disabled="submitting">
        <span x-show="!submitting">Sign In</span>
        <span x-show="submitting">Signing in…</span>
      </button>

    </form>

    <p class="gld-auth-footer">
      Don't have an account? <a href="<?php echo esc_url( GLD_Auth::register_url() ); ?>">Register your group</a>
    </p>

  </div>
</div>
