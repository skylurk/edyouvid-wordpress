<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$learner_redirect = function_exists( 'wc_get_page_permalink' )
    ? wc_get_page_permalink( 'myaccount' )
    : home_url( '/my-account/' );
?>
<div class="gld-auth-wrap"
     x-data="{
       tab: 'learner',

       learner: { email: '', password: '', remember: false },
       leader:  { email: '', password: '', remember: false },

       learnerError: '',
       leaderError:  '',
       learnerBusy:  false,
       leaderBusy:   false,

       async submitLearner() {
         this.learnerError = '';
         this.learnerBusy  = true;
         try {
           const body = new URLSearchParams({
             action:   'gld_login_standard',
             nonce:    GLD_AUTH.nonce,
             email:    this.learner.email,
             password: this.learner.password,
           });
           if (this.learner.remember) body.append('remember', '1');

           const r    = await fetch(GLD_AUTH.ajaxUrl, { method: 'POST', body });
           const data = await r.json();
           if (!data.success) { this.learnerError = data.data?.message || 'Sign-in failed.'; return; }
           window.location.href = data.data.redirect;
         } catch (_) {
           this.learnerError = 'An unexpected error occurred. Please try again.';
         } finally {
           this.learnerBusy = false;
         }
       },

       async submitLeader() {
         this.leaderError = '';
         this.leaderBusy  = true;
         try {
           const body = new URLSearchParams({
             action:   'gld_login',
             nonce:    GLD_AUTH.nonce,
             email:    this.leader.email,
             password: this.leader.password,
           });
           if (this.leader.remember) body.append('remember', '1');

           const r    = await fetch(GLD_AUTH.ajaxUrl, { method: 'POST', body });
           const data = await r.json();
           if (!data.success) { this.leaderError = data.data?.message || 'Sign-in failed.'; return; }
           window.location.href = data.data.redirect;
         } catch (_) {
           this.leaderError = 'An unexpected error occurred. Please try again.';
         } finally {
           this.leaderBusy = false;
         }
       }
     }">

  <div class="gld-auth-card" style="max-width:520px">

    <h1 class="gld-auth-title">Sign in to your account</h1>
    <p class="gld-auth-sub">Choose your account type below.</p>

    <!-- Tab switcher -->
    <div class="gld-portal-tabs">
      <button class="gld-portal-tab" :class="{ active: tab === 'learner' }" @click="tab = 'learner'; learnerError = ''">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
        Learner
      </button>
      <button class="gld-portal-tab" :class="{ active: tab === 'leader' }" @click="tab = 'leader'; leaderError = ''">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
        Group Leader
      </button>
    </div>

    <!-- ── Learner login ──────────────────────────────────────────────── -->
    <div x-show="tab === 'learner'" x-transition.opacity>
      <div class="gld-auth-error" x-show="learnerError" x-cloak x-text="learnerError"></div>
      <form @submit.prevent="submitLearner()">
        <div class="gld-auth-field">
          <label for="gl-l-email">Email address</label>
          <input id="gl-l-email" type="email" x-model="learner.email" required autocomplete="email">
        </div>
        <div class="gld-auth-field">
          <label for="gl-l-pass">Password</label>
          <input id="gl-l-pass" type="password" x-model="learner.password" required autocomplete="current-password">
        </div>
        <label class="gld-auth-check">
          <input type="checkbox" x-model="learner.remember"> Keep me signed in
        </label>
        <button type="submit" class="gld-auth-btn" :disabled="learnerBusy">
          <span x-show="!learnerBusy">Sign In</span>
          <span x-show="learnerBusy">Signing in…</span>
        </button>
      </form>
      <p class="gld-auth-footer" style="margin-top:18px">
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot your password?</a>
      </p>
    </div>

    <!-- ── Group Leader login ─────────────────────────────────────────── -->
    <div x-show="tab === 'leader'" x-transition.opacity>
      <div class="gld-auth-error" x-show="leaderError" x-cloak x-text="leaderError"></div>
      <form @submit.prevent="submitLeader()">
        <div class="gld-auth-field">
          <label for="gl-g-email">Email address</label>
          <input id="gl-g-email" type="email" x-model="leader.email" required autocomplete="email">
        </div>
        <div class="gld-auth-field">
          <label for="gl-g-pass">Password</label>
          <input id="gl-g-pass" type="password" x-model="leader.password" required autocomplete="current-password">
        </div>
        <label class="gld-auth-check">
          <input type="checkbox" x-model="leader.remember"> Keep me signed in
        </label>
        <button type="submit" class="gld-auth-btn" :disabled="leaderBusy">
          <span x-show="!leaderBusy">Sign In to Dashboard</span>
          <span x-show="leaderBusy">Signing in…</span>
        </button>
      </form>
      <p class="gld-auth-footer" style="margin-top:18px">
        Don't have a group yet?
        <a href="<?php echo esc_url( GLD_Auth::register_url() ); ?>">Register your group</a>
      </p>
    </div>

  </div>
</div>
