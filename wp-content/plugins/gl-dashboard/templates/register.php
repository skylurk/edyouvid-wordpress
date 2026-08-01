<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gld-auth-wrap"
     x-data="{
       form: { first_name: '', last_name: '', email: '', org_name: '', password: '', password2: '' },
       error: '',
       submitting: false,

       async submit() {
         this.error = '';
         this.submitting = true;
         try {
           const body = new URLSearchParams({ action: 'gld_register', nonce: GLD_AUTH.nonce });
           Object.keys(this.form).forEach(k => body.append(k, this.form[k]));

           const r    = await fetch(GLD_AUTH.ajaxUrl, { method: 'POST', body });
           const data = await r.json();

           if (!data.success) { this.error = data.data?.message || 'Registration failed.'; return; }
           window.location.href = data.data.redirect;
         } catch (_) {
           this.error = 'An unexpected error occurred. Please try again.';
         } finally {
           this.submitting = false;
         }
       }
     }">
  <div class="gld-auth-card">

    <h1 class="gld-auth-title">Create your group leader account</h1>
    <p class="gld-auth-sub">Set up your organisation's learning group in minutes.</p>

    <div class="gld-auth-error" x-show="error" x-cloak x-text="error"></div>

    <form @submit.prevent="submit()">

      <div class="gld-auth-row">
        <div class="gld-auth-field">
          <label for="gld-reg-first">First name <span class="gld-auth-hint">*</span></label>
          <input id="gld-reg-first" type="text" x-model="form.first_name"
                 required autocomplete="given-name">
        </div>
        <div class="gld-auth-field">
          <label for="gld-reg-last">Last name</label>
          <input id="gld-reg-last" type="text" x-model="form.last_name"
                 autocomplete="family-name">
        </div>
      </div>

      <div class="gld-auth-field">
        <label for="gld-reg-email">Email address <span class="gld-auth-hint">*</span></label>
        <input id="gld-reg-email" type="email" x-model="form.email"
               required autocomplete="email">
      </div>

      <div class="gld-auth-field">
        <label for="gld-reg-org">Organisation / Group name <span class="gld-auth-hint">*</span></label>
        <input id="gld-reg-org" type="text" x-model="form.org_name"
               required placeholder="e.g. Acme Corp Staff Training">
      </div>

      <div class="gld-auth-field">
        <label for="gld-reg-pass">Password <span class="gld-auth-hint">(min. 8 characters) *</span></label>
        <input id="gld-reg-pass" type="password" x-model="form.password"
               required autocomplete="new-password">
      </div>

      <div class="gld-auth-field">
        <label for="gld-reg-pass2">Confirm password <span class="gld-auth-hint">*</span></label>
        <input id="gld-reg-pass2" type="password" x-model="form.password2"
               required autocomplete="new-password">
      </div>

      <button type="submit" class="gld-auth-btn" :disabled="submitting">
        <span x-show="!submitting">Create Account &amp; Group</span>
        <span x-show="submitting">Creating your account…</span>
      </button>

    </form>

    <p class="gld-auth-footer">
      Already have an account? <a href="<?php echo esc_url( GLD_Auth::login_url() ); ?>">Sign in</a>
    </p>

  </div>
</div>
