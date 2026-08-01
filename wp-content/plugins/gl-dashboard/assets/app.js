/* Group Leader Dashboard — Alpine.js application */

/* ── API client ──────────────────────────────────────────────────────── */
// Build the base URL from the browser's own origin so it always matches
// the domain serving the page (handles staging / http→https mismatches).
const apiBase = window.location.origin + GLD.restPath;

const api = {
  async get(path) {
    const r = await fetch(apiBase + path, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': GLD.nonce },
    });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  },
  async post(path, body) {
    const r = await fetch(apiBase + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': GLD.nonce, 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  },
  async del(path) {
    const r = await fetch(apiBase + path, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': GLD.nonce },
    });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  },
};

/* ── Chart helpers ───────────────────────────────────────────────────── */
let chartInstances = {};

function destroyChart(id) {
  if (chartInstances[id]) {
    chartInstances[id].destroy();
    delete chartInstances[id];
  }
}

function makeLineChart(id, labels, data) {
  destroyChart(id);
  const ctx = document.getElementById(id);
  if (!ctx) return;
  chartInstances[id] = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Completions',
        data,
        fill: true,
        tension: 0.35,
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79,70,229,.08)',
        pointBackgroundColor: '#4f46e5',
        pointRadius: 3,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
      },
    },
  });
}

function makeBarChart(id, labels, data, color) {
  destroyChart(id);
  const ctx = document.getElementById(id);
  if (!ctx) return;
  chartInstances[id] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Count',
        data,
        backgroundColor: color || '#4f46e5',
        borderRadius: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
      },
    },
  });
}

function makeDoughnutChart(id, labels, data, colors) {
  destroyChart(id);
  const ctx = document.getElementById(id);
  if (!ctx) return;
  chartInstances[id] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data, backgroundColor: colors, borderWidth: 0 }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14 } },
      },
      cutout: '68%',
    },
  });
}

/* ── Utility ─────────────────────────────────────────────────────────── */
function progressColor(pct) {
  if (pct >= 80) return 'done';
  if (pct >= 40) return 'mid';
  return 'low';
}

/* ── Main Alpine component ───────────────────────────────────────────── */
document.addEventListener('alpine:init', () => {
  Alpine.data('glDashboard', () => ({

    /* state */
    view: 'overview',
    groups: [],
    activeGroupId: null,
    loading: false,
    loadingMsg: 'Loading…',
    _loadingTimer: null,
    toasts: [],

    /* per-view data */
    overview: { summary: null },
    progress: { courses: [], users: [], search: '', page: 1 },
    analytics: { summary: null, completions: [], perCourse: [], quizScores: {} },
    users: {
      list: [], search: '', page: 1, searchQuery: '',
      searchResults: [], showDropdown: false,
      adding: false, searchTimeout: null,
      showImport: false, importing: false, importFile: null, importResults: null,
    },
    selectedLearner: null,
    subscription: { sub: null, bundles: [] },
    courseStats: { rows: [], page: 1 },
    billing: {
      card: null, charges: [], creditBalance: 0,
      perSeatPrice: 0, currency: 'KES', settingUpCard: false,
    },

    /* ── init ──────────────────────────────────────────────────────── */
    async init() {
      this.loading = true;
      this.startLoadingMessages(['Loading…', 'Fetching your groups…']);
      try {
        this.groups = await api.get('/groups');
        if (this.groups.length) {
          this.activeGroupId = this.groups[0].id;
          await this.loadView();
        }
      } catch (e) {
        this.toast('Failed to load groups.', 'error');
      } finally {
        this.loading = false;
        this.stopLoadingMessages();
      }
    },

    /* ── navigation ────────────────────────────────────────────────── */
    async switchView(v) {
      this.view = v;
      await this.loadView();
    },

    async switchGroup() {
      await this.loadView();
    },

    async loadView() {
      if (!this.activeGroupId) return;
      this.loading = true;

      const viewMessages = {
        'overview':     ['Loading…', 'Fetching summary…', 'Almost done…'],
        'progress':     ['Loading…', 'Fetching learner data…', 'Calculating progress…', 'Almost done…'],
        'analytics':    ['Loading…', 'Crunching analytics…', 'Almost done…'],
        'users':        ['Loading…', 'Loading members…'],
        'subscription': ['Loading…', 'Checking subscription…'],
        'course-stats': ['Loading…', 'Loading course data…', 'Almost done…'],
        'billing':      ['Loading…', 'Fetching billing info…'],
      };
      this.startLoadingMessages(viewMessages[this.view] || ['Loading…']);

      try {
        if (this.view === 'overview') await this.loadOverview();
        else if (this.view === 'progress') await this.loadProgress();
        else if (this.view === 'analytics') await this.loadAnalytics();
        else if (this.view === 'users') await this.loadUsers();
        else if (this.view === 'subscription') await this.loadSubscription();
        else if (this.view === 'course-stats') await this.loadCourseStats();
        else if (this.view === 'billing') await this.loadBilling();
      } catch (e) {
        this.toast('Failed to load data.', 'error');
      } finally {
        this.loading = false;
        this.stopLoadingMessages();
      }
    },

    /* ── overview ──────────────────────────────────────────────────── */
    async loadOverview() {
      const data = await api.get(`/groups/${this.activeGroupId}/analytics`);
      this.overview.summary = data.summary;
      this.$nextTick(() => this.renderOverviewCharts(data));
      // Silently warm the progress cache while the user reads the overview,
      // so Course Progress loads instantly when they navigate to it.
      this.prefetchProgress();
    },

    prefetchProgress() {
      api.get(`/groups/${this.activeGroupId}/progress?page=1`).catch(() => {});
    },

    renderOverviewCharts(data) {
      if (data.completions_by_week && data.completions_by_week.length) {
        makeLineChart(
          'gld-chart-completions',
          data.completions_by_week.map(r => r.week),
          data.completions_by_week.map(r => r.completions)
        );
      }
      if (data.per_course && data.per_course.length) {
        const d = data.per_course.reduce((acc, c) => {
          acc.completed   += c.completed;
          acc.in_progress += c.in_progress;
          acc.not_started += c.not_started;
          return acc;
        }, { completed: 0, in_progress: 0, not_started: 0 });
        makeDoughnutChart(
          'gld-chart-status',
          ['Completed', 'In Progress', 'Not Started'],
          [d.completed, d.in_progress, d.not_started],
          ['#10b981', '#f59e0b', '#e5e7eb']
        );
      }
    },

    /* ── progress ──────────────────────────────────────────────────── */
    async loadProgress() {
      // Fetch page 1 — shows the first 50 learners immediately.
      const first = await api.get(`/groups/${this.activeGroupId}/progress?page=1`);
      this.progress.courses = first.courses;
      this.progress.users   = first.users;
      this.progress.search  = '';
      this.progress.page    = 1;

      // If there are more pages, fetch them sequentially and append.
      // Stop the cycling interval so we can show page-specific messages.
      if ( first.total_pages > 1 ) {
        clearInterval(this._loadingTimer);
        for ( let p = 2; p <= first.total_pages; p++ ) {
          this.loadingMsg = `Fetching learner data… (${p} of ${first.total_pages})`;
          const more = await api.get(`/groups/${this.activeGroupId}/progress?page=${p}`);
          this.progress.users = this.progress.users.concat(more.users);
        }
      }
    },

    get filteredUsers() {
      const q = this.progress.search.toLowerCase();
      if (!q) return this.progress.users;
      return this.progress.users.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
      );
    },

    progressBarClass(pct) {
      return progressColor(pct);
    },

    /* ── analytics ─────────────────────────────────────────────────── */
    async loadAnalytics() {
      const data = await api.get(`/groups/${this.activeGroupId}/analytics`);
      this.analytics.summary    = data.summary;
      this.analytics.perCourse  = data.per_course;
      this.analytics.quizScores = data.quiz_scores;
      this.$nextTick(() => this.renderAnalyticsCharts(data));
    },

    renderAnalyticsCharts(data) {
      if (data.completions_by_week && data.completions_by_week.length) {
        makeLineChart(
          'gld-chart-weekly',
          data.completions_by_week.map(r => r.week),
          data.completions_by_week.map(r => r.completions)
        );
      }
      if (data.quiz_scores && data.quiz_scores.distribution && data.quiz_scores.distribution.length) {
        makeBarChart(
          'gld-chart-quiz',
          data.quiz_scores.distribution.map(r => r.range),
          data.quiz_scores.distribution.map(r => r.count),
          '#4f46e5'
        );
      }
      if (data.per_course && data.per_course.length) {
        makeBarChart(
          'gld-chart-course-completion',
          data.per_course.map(c => c.title.length > 20 ? c.title.slice(0, 20) + '…' : c.title),
          data.per_course.map(c => c.avg_completion),
          '#10b981'
        );
      }
    },

    /* ── users ─────────────────────────────────────────────────────── */
    async loadUsers() {
      this.users.list  = await api.get(`/groups/${this.activeGroupId}/users`);
      this.users.page  = 1;
      this.users.search = '';
    },

    get filteredUserList() {
      const q = this.users.search.toLowerCase();
      if (!q) return this.users.list;
      return this.users.list.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
      );
    },

    get paginatedUsers() {
      const s = (this.progress.page - 1) * 10;
      return this.filteredUsers.slice(s, s + 10);
    },

    get progressTotalPages() {
      return Math.max(1, Math.ceil(this.filteredUsers.length / 10));
    },

    get paginatedUserList() {
      const s = (this.users.page - 1) * 10;
      return this.filteredUserList.slice(s, s + 10);
    },

    get usersTotalPages() {
      return Math.max(1, Math.ceil(this.filteredUserList.length / 10));
    },

    onSearchInput() {
      clearTimeout(this.users.searchTimeout);
      const q = this.users.searchQuery.trim();
      if (q.length < 2) { this.users.searchResults = []; this.users.showDropdown = false; return; }
      this.users.searchTimeout = setTimeout(() => this.doUserSearch(q), 300);
    },

    async doUserSearch(q) {
      try {
        this.users.searchResults = await api.get(`/users/search?q=${encodeURIComponent(q)}`);
        this.users.showDropdown  = this.users.searchResults.length > 0;
      } catch (_) {}
    },

    async addUser(user) {
      this.users.showDropdown  = false;
      this.users.searchQuery   = '';
      this.users.searchResults = [];
      this.users.adding        = true;
      try {
        const result = await api.post(`/groups/${this.activeGroupId}/users`, { user_id: user.id });
        this.toast(`${user.name} added to group.`, 'success');
        await this.loadUsers();
      } catch (err) {
        try {
          const body = JSON.parse(err.message);
          if (body.requires_card) {
            this.toast('No payment card on file. Go to Billing to set up a card first.', 'error');
          } else {
            this.toast(body.message || 'Could not add user.', 'error');
          }
        } catch (_) {
          this.toast('Could not add user.', 'error');
        }
      } finally {
        this.users.adding = false;
      }
    },

    async removeUser(user) {
      if (!confirm(`Remove ${user.name} from this group?`)) return;
      try {
        await api.del(`/groups/${this.activeGroupId}/users/${user.id}`);
        this.toast(`${user.name} removed.`, 'success');
        this.users.list = this.users.list.filter(u => u.id !== user.id);
      } catch (_) {
        this.toast('Could not remove user.', 'error');
      }
    },

    /* ── bulk import ───────────────────────────────────────────────── */
    onImportFileChange(event) {
      this.users.importFile    = event.target.files[0] || null;
      this.users.importResults = null;
    },

    async importUsers() {
      if (!this.users.importFile) return;
      this.users.importing = true;
      this.users.importResults = null;
      try {
        const form = new FormData();
        form.append('file', this.users.importFile);

        const r = await fetch(
          window.location.origin + GLD.restPath + `/groups/${this.activeGroupId}/users/import`,
          {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': GLD.nonce },
            body: form,
          }
        );
        const data = await r.json();

        if (!r.ok) {
          if (data.requires_card) {
            this.toast('No payment card on file. Go to Billing to set up a card first.', 'error');
          } else {
            this.toast(data.message || 'Import failed.', 'error');
          }
          return;
        }

        this.users.importResults = data;
        if (data.totals.succeeded > 0) {
          await this.loadUsers();
          this.toast(`Import complete: ${data.totals.succeeded} added.`, 'success');
        } else {
          this.toast('Import complete — no new learners were added.', 'info');
        }
      } catch (_) {
        this.toast('Import request failed. Please try again.', 'error');
      } finally {
        this.users.importing     = false;
        this.users.importFile    = null;
        // Reset the file input via a ref trick.
        const el = document.getElementById('gld-import-file');
        if (el) el.value = '';
      }
    },

    /* ── toast ─────────────────────────────────────────────────────── */
    /* subscription ─────────────────────────────────────────────── */
    async loadSubscription() {
      const data = await api.get(`/groups/${this.activeGroupId}/subscription`);
      this.subscription.sub     = data.subscription ?? null;
      this.subscription.bundles = data.bundles ?? [];
    },

    subStatusLabel() {
      const s = this.subscription.sub?.status;
      return { active: 'Access Active', expiring: 'Expiring Soon', expired: 'Access Expired', none: 'No Subscription' }[s] ?? 'Loading…';
    },

    subStatusDesc() {
      const d = this.subscription.sub;
      if (!d || d.status === 'none') return '';
      const bundle = d.bundle_name ? ` — ${d.bundle_name}` : '';
      if (d.status === 'active')   return `Active${bundle}. Expires ${this.formatDate(d.expiry_date)}.`;
      if (d.status === 'expiring') return `${d.bundle_name || 'Your bundle'} expires in ${d.days_remaining} day${d.days_remaining !== 1 ? 's' : ''} on ${this.formatDate(d.expiry_date)}. Renew to avoid interruption.`;
      if (d.status === 'expired')  return `${d.bundle_name || 'Access'} expired. Learner access has been paused. Choose a bundle below to renew.`;
      return '';
    },

    formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    },

    /* course stats */
    async loadCourseStats() {
      this.courseStats.rows = await api.get(`/groups/${this.activeGroupId}/course-stats`);
      this.courseStats.page = 1;
    },

    get paginatedCourseStats() {
      const s = (this.courseStats.page - 1) * 10;
      return this.courseStats.rows.slice(s, s + 10);
    },

    get courseStatsTotalPages() {
      return Math.max(1, Math.ceil(this.courseStats.rows.length / 10));
    },

    gotoCourseStatsPage(p) {
      this.courseStats.page = Math.max(1, Math.min(p, this.courseStatsTotalPages));
    },

    /* learner detail panel */
    openLearner(user) {
      this.selectedLearner = user;
    },

    closeLearner() {
      this.selectedLearner = null;
    },

    learnerProgress(course) {
      if (!this.selectedLearner) return null;
      const p = this.selectedLearner.courses[course.id];
      return p || { percent: 0, status: 'not_started', completed_steps: 0, total_steps: course.total_steps ?? 0 };
    },

    learnerOverallPct() {
      if (!this.selectedLearner || !this.progress.courses.length) return 0;
      const sum = this.progress.courses.reduce((acc, c) => {
        return acc + this.learnerProgress(c).percent;
      }, 0);
      return Math.round(sum / this.progress.courses.length);
    },

    gotoProgressPage(p) {
      this.progress.page = Math.max(1, Math.min(p, this.progressTotalPages));
    },

    gotoUsersPage(p) {
      this.users.page = Math.max(1, Math.min(p, this.usersTotalPages));
    },

    pageRange(total) {
      if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
      return null; // use prev/next only for large counts
    },

    /* ── loading messages ──────────────────────────────────────────── */
    // Cycles through messages every 1.8 s during a load, stopping on the last one.
    startLoadingMessages(messages, interval = 1800) {
      let i = 0;
      this.loadingMsg = messages[0];
      clearInterval(this._loadingTimer);
      this._loadingTimer = setInterval(() => {
        i++;
        if (i < messages.length) {
          this.loadingMsg = messages[i];
        } else {
          clearInterval(this._loadingTimer); // hold on last message
        }
      }, interval);
    },

    stopLoadingMessages() {
      clearInterval(this._loadingTimer);
      this._loadingTimer = null;
      this.loadingMsg = 'Loading…';
    },

    toast(msg, type = 'success') {
      const id = Date.now();
      this.toasts.push({ id, msg, type });
      setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3500);
    },

    /* ── billing ───────────────────────────────────────────────────── */
    async loadBilling() {
      const [cardData, billingData] = await Promise.all([
        api.get(`/groups/${this.activeGroupId}/card`),
        api.get(`/groups/${this.activeGroupId}/billing`),
      ]);
      this.billing.card          = cardData.card ?? null;
      this.billing.charges       = billingData.charges ?? [];
      this.billing.creditBalance = billingData.credit_balance ?? 0;
      this.billing.perSeatPrice  = billingData.per_seat_price ?? 0;
      this.billing.currency      = billingData.currency ?? (GLD.currency || 'KES');
    },

    openCardSetup(isReplacement = false) {
      if (!GLD.flwPublicKey) {
        this.toast('Payment gateway not configured. Contact the site administrator.', 'error');
        return;
      }
      const currentUser = this.activeGroup();
      this.billing.settingUpCard = true;

      FlutterwaveCheckout({
        public_key: GLD.flwPublicKey,
        tx_ref: 'GLD-CARD-SETUP-' + Date.now(),
        amount: 1,
        currency: GLD.currency || 'KES',
        payment_options: 'card',
        customer: {
          email: currentUser?.leader_email || '',
          name: currentUser?.leader_name || '',
        },
        customizations: {
          title: GLD.siteName || 'Card Setup',
          description: isReplacement ? 'Replace saved payment card' : 'Set up payment card for seat billing',
          logo: '',
        },
        callback: async (response) => {
          this.billing.settingUpCard = false;
          if (response.status !== 'successful') {
            this.toast('Card setup was not completed.', 'error');
            return;
          }
          try {
            const result = await api.post(`/groups/${this.activeGroupId}/card-setup`, { txn_id: response.transaction_id });
            this.billing.card = result.card;
            this.toast('Card saved successfully.', 'success');
          } catch (e) {
            this.toast('Card verification failed. Please try again.', 'error');
          }
        },
        onclose: () => {
          this.billing.settingUpCard = false;
        },
      });
    },

    async removeCard() {
      if (!confirm('Remove your saved card? You will need to add a new card before adding learners.')) return;
      try {
        await api.del(`/groups/${this.activeGroupId}/card`);
        this.billing.card = null;
        this.toast('Card removed.', 'success');
      } catch (_) {
        this.toast('Could not remove card.', 'error');
      }
    },

    /* ── helpers ───────────────────────────────────────────────────── */
    activeGroup() {
      return this.groups.find(g => g.id === this.activeGroupId) || null;
    },

    statusLabel(s) {
      return { completed: 'Completed', in_progress: 'In Progress', not_started: 'Not Started' }[s] || s;
    },
  }));
});
