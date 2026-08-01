<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="gld-app" x-data="glDashboard" x-init="init()">

  <!-- ── Sidebar ────────────────────────────────────────────────────── -->
  <div class="gld-layout">
    <aside class="gld-sidebar">
      <div class="gld-logo">
        Group Leader
        <span>Dashboard</span>
      </div>

      <!-- Group selector -->
      <template x-if="groups.length > 1">
        <div class="gld-group-select-wrap" style="margin-top:16px;">
          <div class="gld-group-label">Active Group</div>
          <select class="gld-group-select"
                  x-model.number="activeGroupId"
                  @change="switchGroup()">
            <template x-for="g in groups" :key="g.id">
              <option :value="g.id" x-text="g.name"></option>
            </template>
          </select>
        </div>
      </template>

      <!-- Nav -->
      <nav class="gld-nav">
        <div class="gld-nav-item" :class="{ active: view === 'overview' }" @click="switchView('overview')">
          <!-- home icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
          Overview
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'progress' }" @click="switchView('progress')">
          <!-- chart icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
          Course Progress
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'analytics' }" @click="switchView('analytics')">
          <!-- analytics icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/></svg>
          Analytics
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'users' }" @click="switchView('users')">
          <!-- users icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
          Manage Users
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'subscription' }" @click="switchView('subscription')">
          <!-- credit card icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
          Subscription
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'billing' }" @click="switchView('billing')">
          <!-- receipt icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
          Billing
        </div>
        <div class="gld-nav-item" :class="{ active: view === 'course-stats' }" @click="switchView('course-stats')">
          <!-- table/stats icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-5.25A2.25 2.25 0 014.5 11.25h15a2.25 2.25 0 012.25 2.25v5.25m-18-.125A2.25 2.25 0 013.375 19.5m18 0a1.125 1.125 0 001.125-1.125M21.375 19.5h-1.5a1.125 1.125 0 01-1.125-1.125M3.375 4.5h17.25m0 0a1.125 1.125 0 011.125 1.125M20.625 4.5h-1.5A1.125 1.125 0 0018 5.625m3.75-.125v5.25A2.25 2.25 0 0119.5 12.75h-15A2.25 2.25 0 012.25 10.5V5.625A2.25 2.25 0 014.5 3.375h15A2.25 2.25 0 0121.75 5.625"/></svg>
          Course Stats
        </div>
      </nav>
    </aside>

    <!-- ── Main content ──────────────────────────────────────────────── -->
    <main class="gld-content">

      <!-- Loading state -->
      <div x-show="loading" class="gld-loading">
        <div class="gld-spinner"></div>
        <span x-text="loadingMsg">Loading…</span>
      </div>

      <!-- No groups -->
      <div x-show="!loading && groups.length === 0" class="gld-empty">
        You are not currently assigned as a leader of any group.
      </div>

      <!-- ── WELCOME banner (shown after self-registration) ───────── -->
      <template x-if="!loading && showWelcome">
        <div style="background:#ede9fe;border:1px solid #c4b5fd;border-radius:10px;padding:20px 24px;margin-bottom:24px;display:flex;gap:16px;align-items:flex-start">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#7c3aed" style="width:24px;height:24px;flex-shrink:0;margin-top:2px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>
          <div style="flex:1">
            <div style="font-weight:700;color:#4c1d95;font-size:15px;margin-bottom:6px">Welcome to your group leader dashboard!</div>
            <div style="font-size:13px;color:#5b21b6;line-height:1.6">
              Your group <strong x-text="activeGroup()?.name"></strong> is ready. Here's how to get started:
              <ol style="margin:8px 0 0 18px;padding:0">
                <li>Purchase a <strong>subscription bundle</strong> to assign courses to your group.</li>
                <li>Go to <strong>Billing</strong> and set up your payment card for per-seat charging.</li>
                <li>Add learners individually or use <strong>Bulk Import</strong> in the Users tab.</li>
              </ol>
            </div>
          </div>
          <button @click="showWelcome = false" style="background:none;border:none;cursor:pointer;color:#7c3aed;padding:0;line-height:1;font-size:18px" title="Dismiss">&times;</button>
        </div>
      </template>

      <!-- ── OVERVIEW ──────────────────────────────────────────────── -->
      <div x-show="!loading && view === 'overview'">
        <div class="gld-page-header">
          <div class="gld-page-title" x-text="activeGroup() ? activeGroup().name : 'Overview'"></div>
          <div class="gld-page-sub">Group summary at a glance</div>
        </div>

        <!-- Stat cards -->
        <div class="gld-cards" x-show="overview.summary">
          <div class="gld-card">
            <div class="gld-card-label">Total Learners</div>
            <div class="gld-card-value accent" x-text="overview.summary?.total_users ?? '—'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Avg Completion</div>
            <div class="gld-card-value" x-text="(overview.summary?.avg_completion ?? '—') + '%'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Completions</div>
            <div class="gld-card-value green" x-text="overview.summary?.total_completions ?? '—'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Active This Week</div>
            <div class="gld-card-value yellow" x-text="overview.summary?.active_this_week ?? '—'"></div>
          </div>
        </div>

        <!-- Charts -->
        <div class="gld-charts">
          <div class="gld-panel">
            <div class="gld-panel-header"><div class="gld-panel-title">Course Completions (12 weeks)</div></div>
            <div class="gld-panel-body"><canvas id="gld-chart-completions" class="gld-chart-canvas"></canvas></div>
          </div>
          <div class="gld-panel">
            <div class="gld-panel-header"><div class="gld-panel-title">Learner Status</div></div>
            <div class="gld-panel-body"><canvas id="gld-chart-status" class="gld-chart-canvas"></canvas></div>
          </div>
        </div>

        <!-- Group cards (multi-group leaders) -->
        <template x-if="groups.length > 1">
          <div class="gld-panel">
            <div class="gld-panel-header"><div class="gld-panel-title">All Groups</div></div>
            <div class="gld-table-wrap">
              <table class="gld-table">
                <thead>
                  <tr>
                    <th>Group</th>
                    <th>Learners</th>
                    <th>Courses</th>
                    <th>Avg Completion</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="g in groups" :key="g.id">
                    <tr @click="activeGroupId = g.id; switchView('overview')" style="cursor:pointer">
                      <td x-text="g.name"></td>
                      <td x-text="g.user_count"></td>
                      <td x-text="g.course_count"></td>
                      <td>
                        <div class="gld-progress-wrap">
                          <div class="gld-progress-bar">
                            <div class="gld-progress-fill"
                                 :class="progressBarClass(g.avg_completion)"
                                 :style="`width:${g.avg_completion}%`"></div>
                          </div>
                          <span class="gld-progress-pct" x-text="g.avg_completion + '%'"></span>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </template>
      </div>

      <!-- ── COURSE PROGRESS ───────────────────────────────────────── -->
      <div x-show="!loading && view === 'progress'">
        <div class="gld-page-header">
          <div class="gld-page-title">Course Progress</div>
          <div class="gld-page-sub">Per-learner completion across all courses</div>
        </div>

        <div class="gld-input-row">
          <input class="gld-input" type="search" placeholder="Search learners…"
                 x-model="progress.search"
               @input="progress.page = 1">
        </div>

        <div class="gld-panel">
          <div class="gld-table-wrap">
            <table class="gld-table">
              <thead>
                <tr>
                  <th>Learner</th>
                  <template x-for="c in progress.courses" :key="c.id">
                    <th x-text="c.title.length > 22 ? c.title.slice(0,22)+'…' : c.title"></th>
                  </template>
                  <th>Last Active</th>
                </tr>
              </thead>
              <tbody>
                <template x-if="filteredUsers.length === 0">
                  <tr><td :colspan="progress.courses.length + 2" class="gld-empty">No learners found.</td></tr>
                </template>
                <template x-for="u in paginatedUsers" :key="u.id">
                  <tr class="gld-progress-row-clickable" @click="openLearner(u)">
                    <td>
                      <div class="gld-user-cell">
                        <img class="gld-avatar" :src="u.avatar" :alt="u.name">
                        <div>
                          <div class="gld-user-name" x-text="u.name"></div>
                          <div class="gld-user-email" x-text="u.email"></div>
                        </div>
                      </div>
                    </td>
                    <template x-for="c in progress.courses" :key="c.id">
                      <td>
                        <template x-if="u.courses[c.id]">
                          <div class="gld-progress-wrap">
                            <div class="gld-progress-bar">
                              <div class="gld-progress-fill"
                                   :class="progressBarClass(u.courses[c.id].percent)"
                                   :style="`width:${u.courses[c.id].percent}%`"></div>
                            </div>
                            <span class="gld-progress-pct" x-text="u.courses[c.id].percent + '%'"></span>
                          </div>
                        </template>
                        <template x-if="!u.courses[c.id]">
                          <span class="gld-badge not_started">—</span>
                        </template>
                      </td>
                    </template>
                    <td style="color:var(--gld-muted);font-size:12px" x-text="u.last_active || '—'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <!-- Progress pagination -->
          <div class="gld-pagination" style="padding:12px 16px 14px" x-show="progressTotalPages > 1">
            <div class="gld-pagination-info"
                 x-text="`${Math.min((progress.page-1)*10+1, filteredUsers.length)}–${Math.min(progress.page*10, filteredUsers.length)} of ${filteredUsers.length} learners`">
            </div>
            <div class="gld-pagination-controls">
              <button class="gld-page-btn" @click="gotoProgressPage(progress.page-1)" :disabled="progress.page <= 1">&#8592;</button>
              <template x-for="p in progressTotalPages" :key="p">
                <button class="gld-page-btn" :class="{ active: p === progress.page }"
                        @click="gotoProgressPage(p)"
                        x-show="progressTotalPages <= 7 || p === 1 || p === progressTotalPages || Math.abs(p - progress.page) <= 1"
                        x-text="p"></button>
              </template>
              <button class="gld-page-btn" @click="gotoProgressPage(progress.page+1)" :disabled="progress.page >= progressTotalPages">&#8594;</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ANALYTICS ─────────────────────────────────────────────── -->
      <div x-show="!loading && view === 'analytics'">
        <div class="gld-page-header">
          <div class="gld-page-title">Analytics</div>
          <div class="gld-page-sub">Trends, quiz scores and per-course breakdown</div>
        </div>

        <!-- Summary cards -->
        <div class="gld-cards" x-show="analytics.summary">
          <div class="gld-card">
            <div class="gld-card-label">Avg Completion</div>
            <div class="gld-card-value accent" x-text="(analytics.summary?.avg_completion ?? '—') + '%'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Avg Quiz Score</div>
            <div class="gld-card-value" x-text="(analytics.quizScores?.avg ?? '—') + '%'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Total Completions</div>
            <div class="gld-card-value green" x-text="analytics.summary?.total_completions ?? '—'"></div>
          </div>
          <div class="gld-card">
            <div class="gld-card-label">Active This Week</div>
            <div class="gld-card-value yellow" x-text="analytics.summary?.active_this_week ?? '—'"></div>
          </div>
        </div>

        <!-- Charts row 1 -->
        <div class="gld-charts">
          <div class="gld-panel">
            <div class="gld-panel-header"><div class="gld-panel-title">Weekly Completions</div></div>
            <div class="gld-panel-body"><canvas id="gld-chart-weekly" class="gld-chart-canvas"></canvas></div>
          </div>
          <div class="gld-panel">
            <div class="gld-panel-header"><div class="gld-panel-title">Quiz Score Distribution</div></div>
            <div class="gld-panel-body"><canvas id="gld-chart-quiz" class="gld-chart-canvas"></canvas></div>
          </div>
        </div>

        <!-- Course avg completion bar chart -->
        <div class="gld-panel">
          <div class="gld-panel-header"><div class="gld-panel-title">Avg Completion per Course</div></div>
          <div class="gld-panel-body"><canvas id="gld-chart-course-completion" class="gld-chart-canvas"></canvas></div>
        </div>

        <!-- Per-course table -->
        <div class="gld-panel">
          <div class="gld-panel-header"><div class="gld-panel-title">Course Breakdown</div></div>
          <div class="gld-table-wrap">
            <table class="gld-table">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Enrolled</th>
                  <th>Completed</th>
                  <th>In Progress</th>
                  <th>Not Started</th>
                  <th>Avg Completion</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="c in analytics.perCourse" :key="c.id">
                  <tr>
                    <td x-text="c.title"></td>
                    <td x-text="c.enrolled"></td>
                    <td><span class="gld-badge completed" x-text="c.completed"></span></td>
                    <td><span class="gld-badge in_progress" x-text="c.in_progress"></span></td>
                    <td><span class="gld-badge not_started" x-text="c.not_started"></span></td>
                    <td>
                      <div class="gld-progress-wrap">
                        <div class="gld-progress-bar">
                          <div class="gld-progress-fill"
                               :class="progressBarClass(c.avg_completion)"
                               :style="`width:${c.avg_completion}%`"></div>
                        </div>
                        <span class="gld-progress-pct" x-text="c.avg_completion + '%'"></span>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── MANAGE USERS ──────────────────────────────────────────── -->
      <div x-show="!loading && view === 'users'">
        <div class="gld-page-header">
          <div class="gld-page-title">Manage Users</div>
          <div class="gld-page-sub">Add or remove learners from this group</div>
        </div>

        <!-- Add user -->
        <div class="gld-panel" style="margin-bottom:20px">
          <div class="gld-panel-header"><div class="gld-panel-title">Add a Learner</div></div>
          <div class="gld-panel-body">
            <div class="gld-input-row">
              <div class="gld-autocomplete" style="flex:1;position:relative">
                <input class="gld-input" type="search"
                       placeholder="Search by name or email…"
                       x-model="users.searchQuery"
                       @input="onSearchInput()"
                       @keydown.escape="users.showDropdown = false"
                       autocomplete="off">
                <div class="gld-autocomplete-list" x-show="users.showDropdown" @click.outside="users.showDropdown = false">
                  <template x-for="u in users.searchResults" :key="u.id">
                    <div class="gld-autocomplete-item" @click="addUser(u)">
                      <img class="gld-avatar" :src="u.avatar" :alt="u.name">
                      <div>
                        <div class="gld-user-name" x-text="u.name"></div>
                        <div class="gld-user-email" x-text="u.email"></div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <p style="font-size:12px;color:var(--gld-muted)">Type at least 2 characters to search all site users.</p>
          </div>
        </div>

        <!-- Bulk import -->
        <div class="gld-panel" style="margin-bottom:20px">
          <div class="gld-panel-header" style="cursor:pointer" @click="users.showImport = !users.showImport; users.importResults = null">
            <div class="gld-panel-title">Bulk Import from CSV / Excel</div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                 style="width:18px;height:18px;transition:transform .2s"
                 :style="users.showImport ? 'transform:rotate(180deg)' : ''">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
          </div>
          <div x-show="users.showImport" x-transition style="padding:16px 20px 20px">

            <!-- Instructions -->
            <p style="font-size:13px;color:var(--gld-muted);margin:0 0 14px">
              Upload a <strong>.csv</strong> or <strong>.xlsx</strong> file with an <code>email</code> column (required) and optional
              <code>first_name</code> / <code>last_name</code> columns. New emails will have WordPress accounts created automatically.
              Each new seat is charged to your saved card.
            </p>

            <!-- File picker + button -->
            <div class="gld-input-row" style="align-items:center;gap:12px;flex-wrap:wrap">
              <input id="gld-import-file" type="file" accept=".csv,.xlsx"
                     @change="onImportFileChange($event)"
                     style="font-size:13px;flex:1;min-width:200px">
              <button class="gld-btn gld-btn-primary"
                      @click="importUsers()"
                      :disabled="!users.importFile || users.importing">
                <span x-show="!users.importing">Upload &amp; Import</span>
                <span x-show="users.importing">Importing…</span>
              </button>
            </div>

            <!-- Results -->
            <template x-if="users.importResults">
              <div style="margin-top:20px">

                <!-- Summary chips -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                  <span class="gld-badge gld-badge-success" x-text="`${users.importResults.totals.succeeded} added`"></span>
                  <span class="gld-badge gld-badge-muted"   x-text="`${users.importResults.totals.skipped} skipped`"></span>
                  <span class="gld-badge gld-badge-danger"  x-text="`${users.importResults.totals.failed} failed`" x-show="users.importResults.totals.failed > 0"></span>
                </div>

                <!-- Succeeded -->
                <template x-if="users.importResults.succeeded.length > 0">
                  <div style="margin-bottom:14px">
                    <div style="font-size:12px;font-weight:600;color:var(--gld-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Added</div>
                    <div class="gld-table-wrap">
                      <table class="gld-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Account</th></tr></thead>
                        <tbody>
                          <template x-for="r in users.importResults.succeeded" :key="r.email">
                            <tr>
                              <td x-text="r.name || '—'"></td>
                              <td style="color:var(--gld-muted)" x-text="r.email"></td>
                              <td><span :class="r.new_account ? 'gld-badge gld-badge-info' : 'gld-badge gld-badge-muted'" x-text="r.new_account ? 'New' : 'Existing'"></span></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>

                <!-- Skipped -->
                <template x-if="users.importResults.skipped.length > 0">
                  <div style="margin-bottom:14px">
                    <div style="font-size:12px;font-weight:600;color:var(--gld-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Skipped (already enrolled)</div>
                    <div class="gld-table-wrap">
                      <table class="gld-table">
                        <thead><tr><th>Name</th><th>Email</th></tr></thead>
                        <tbody>
                          <template x-for="r in users.importResults.skipped" :key="r.email">
                            <tr>
                              <td x-text="r.name || '—'"></td>
                              <td style="color:var(--gld-muted)" x-text="r.email"></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>

                <!-- Failed -->
                <template x-if="users.importResults.failed.length > 0">
                  <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gld-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Failed</div>
                    <div class="gld-table-wrap">
                      <table class="gld-table">
                        <thead><tr><th>Email</th><th>Reason</th></tr></thead>
                        <tbody>
                          <template x-for="r in users.importResults.failed" :key="r.email">
                            <tr>
                              <td x-text="r.email"></td>
                              <td style="color:var(--gld-danger,#e74c3c)" x-text="r.reason"></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>

              </div>
            </template>
          </div>
        </div>

        <!-- Members list -->
        <div class="gld-panel">
          <div class="gld-panel-header">
            <div class="gld-panel-title">Current Members <span style="color:var(--gld-muted);font-weight:400" x-text="'(' + users.list.length + ')'"></span></div>
            <input class="gld-input" style="width:200px" type="search"
                   placeholder="Filter list…"
                   x-model="users.search"
                   @input="users.page = 1">
          </div>
          <div class="gld-table-wrap">
            <table class="gld-table">
              <thead>
                <tr>
                  <th>Learner</th>
                  <th>Email</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <template x-if="filteredUserList.length === 0">
                  <tr><td colspan="3" class="gld-empty">No members yet.</td></tr>
                </template>
                <template x-for="u in paginatedUserList" :key="u.id">
                  <tr>
                    <td>
                      <div class="gld-user-cell">
                        <img class="gld-avatar" :src="u.avatar" :alt="u.name">
                        <div class="gld-user-name" x-text="u.name"></div>
                      </div>
                    </td>
                    <td style="color:var(--gld-muted)" x-text="u.email"></td>
                    <td>
                      <button class="gld-btn gld-btn-danger" @click="removeUser(u)">Remove</button>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <!-- Users pagination -->
          <div class="gld-pagination" style="padding:12px 16px 14px" x-show="usersTotalPages > 1">
            <div class="gld-pagination-info"
                 x-text="`${Math.min((users.page-1)*10+1, filteredUserList.length)}–${Math.min(users.page*10, filteredUserList.length)} of ${filteredUserList.length} members`">
            </div>
            <div class="gld-pagination-controls">
              <button class="gld-page-btn" @click="gotoUsersPage(users.page-1)" :disabled="users.page <= 1">&#8592;</button>
              <template x-for="p in usersTotalPages" :key="p">
                <button class="gld-page-btn" :class="{ active: p === users.page }"
                        @click="gotoUsersPage(p)"
                        x-show="usersTotalPages <= 7 || p === 1 || p === usersTotalPages || Math.abs(p - users.page) <= 1"
                        x-text="p"></button>
              </template>
              <button class="gld-page-btn" @click="gotoUsersPage(users.page+1)" :disabled="users.page >= usersTotalPages">&#8594;</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── SUBSCRIPTION ─────────────────────────────────────────────── -->
      <div x-show="!loading && view === 'subscription'">
        <div class="gld-page-header">
          <div class="gld-page-title">Subscription</div>
          <div class="gld-page-sub">Annual course access bundles for your group</div>
        </div>

        <!-- Active subscription status banner -->
        <template x-if="subscription.sub && subscription.sub.status !== 'none'">
          <div class="gld-sub-banner" :class="'gld-sub-' + subscription.sub.status">
            <div class="gld-sub-banner-icon">
              <template x-if="subscription.sub.status === 'active' || subscription.sub.status === 'expiring'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </template>
              <template x-if="subscription.sub.status === 'expired'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
              </template>
            </div>
            <div class="gld-sub-banner-body">
              <div class="gld-sub-banner-title" x-text="subStatusLabel()"></div>
              <div class="gld-sub-banner-desc" x-text="subStatusDesc()"></div>
            </div>
            <div class="gld-sub-banner-right">
              <template x-if="subscription.sub.status === 'active' || subscription.sub.status === 'expiring'">
                <div class="gld-sub-days-wrap">
                  <div class="gld-sub-days-num" x-text="subscription.sub.days_remaining"></div>
                  <div class="gld-sub-days-label">days left</div>
                </div>
              </template>
            </div>
          </div>
        </template>

        <!-- No subscription notice -->
        <template x-if="!subscription.sub || subscription.sub.status === 'none'">
          <div class="gld-sub-empty-notice">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color:var(--gld-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            <div>
              <div style="font-weight:600;margin-bottom:4px">No active subscription</div>
              <div style="font-size:12px;color:var(--gld-muted)">Choose a bundle below to get started.</div>
            </div>
          </div>
        </template>

        <!-- Bundle cards -->
        <template x-if="subscription.bundles.length === 0">
          <div class="gld-empty">No course bundles have been set up yet. Contact your administrator.</div>
        </template>

        <div class="gld-bundle-grid">
          <template x-for="b in subscription.bundles" :key="b.id">
            <div class="gld-bundle-card" :class="{ 'gld-bundle-current': b.is_current }">

              <!-- Card header -->
              <div class="gld-bundle-card-header">
                <div>
                  <div class="gld-bundle-name" x-text="b.name"></div>
                  <template x-if="b.is_current && subscription.sub">
                    <div class="gld-bundle-active-tag">
                      <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                      Current plan
                    </div>
                  </template>
                </div>
                <div class="gld-bundle-price">
                  <span x-html="b.price_html"></span>
                  <span class="gld-bundle-period">/yr</span>
                </div>
              </div>

              <!-- Course list -->
              <div class="gld-bundle-courses">
                <div class="gld-bundle-courses-label" x-text="b.courses.length + ' course' + (b.courses.length !== 1 ? 's' : '') + ' included'"></div>
                <ul class="gld-bundle-course-list">
                  <template x-for="c in b.courses.slice(0, 8)" :key="c.id">
                    <li>
                      <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--gld-green);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                      <span x-text="c.title"></span>
                    </li>
                  </template>
                  <template x-if="b.courses.length > 8">
                    <li style="color:var(--gld-muted);font-style:italic" x-text="'+ ' + (b.courses.length - 8) + ' more courses'"></li>
                  </template>
                </ul>
              </div>

              <!-- Action button -->
              <div class="gld-bundle-card-footer">
                <template x-if="b.is_current && subscription.sub && subscription.sub.status !== 'expired'">
                  <div class="gld-bundle-status-info">
                    <div style="font-size:12px;color:var(--gld-muted)">
                      Expires <strong x-text="formatDate(subscription.sub.expiry_date)"></strong>
                    </div>
                    <a :href="b.checkout_url" class="gld-btn gld-btn-ghost" style="margin-top:10px">
                      Renew
                    </a>
                  </div>
                </template>
                <template x-if="!b.is_current || (subscription.sub && subscription.sub.status === 'expired')">
                  <a :href="b.checkout_url" class="gld-btn gld-btn-primary" style="width:100%;justify-content:center">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                    <span x-text="b.is_current ? 'Renew Access' : 'Purchase'"></span>
                  </a>
                </template>
              </div>
            </div>
          </template>
        </div>
      </div><!-- /subscription -->

      <!-- ── COURSE STATS ────────────────────────────────────────────── -->
      <div x-show="!loading && view === 'course-stats'">
        <div class="gld-page-header">
          <div class="gld-page-title">Course Stats</div>
          <div class="gld-page-sub">Enrolment and progress breakdown per course</div>
        </div>

        <template x-if="courseStats.rows.length === 0">
          <div class="gld-empty">No courses are assigned to this group.</div>
        </template>

        <template x-if="courseStats.rows.length > 0">
          <div class="gld-table-wrap">
            <table class="gld-table gld-stats-table">
              <thead>
                <tr>
                  <th>Course</th>
                  <th class="gld-stat-num">Members</th>
                  <th class="gld-stat-num">Started</th>
                  <th class="gld-stat-num">In Progress</th>
                  <th class="gld-stat-num">Completed</th>
                  <th class="gld-stat-num">Avg Quiz Score</th>
                  <th class="gld-stat-completion">Avg Completion</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="c in paginatedCourseStats" :key="c.id">
                  <tr>
                    <td class="gld-stat-title" x-text="c.title"></td>
                    <td class="gld-stat-num" x-text="c.total_members"></td>
                    <td class="gld-stat-num" x-text="c.started"></td>
                    <td class="gld-stat-num">
                      <span class="gld-badge in_progress" x-show="c.in_progress > 0" x-text="c.in_progress"></span>
                      <span x-show="c.in_progress === 0" style="color:var(--gld-muted)">—</span>
                    </td>
                    <td class="gld-stat-num">
                      <span class="gld-badge completed" x-show="c.completed > 0" x-text="c.completed"></span>
                      <span x-show="c.completed === 0" style="color:var(--gld-muted)">—</span>
                    </td>
                    <td class="gld-stat-num">
                      <span x-show="c.avg_quiz_score > 0" x-text="c.avg_quiz_score + '%'"></span>
                      <span x-show="c.avg_quiz_score === 0" style="color:var(--gld-muted)">—</span>
                    </td>
                    <td class="gld-stat-completion">
                      <div class="gld-stat-bar-wrap">
                        <div class="gld-progress-bar gld-stat-bar">
                          <div class="gld-progress-fill"
                               :class="progressBarClass(c.avg_completion)"
                               :style="`width:${c.avg_completion}%`"></div>
                        </div>
                        <span class="gld-stat-pct" x-text="c.avg_completion + '%'"></span>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <!-- Course stats pagination -->
          <div class="gld-pagination" style="padding:12px 16px 14px" x-show="courseStatsTotalPages > 1">
            <div class="gld-pagination-info"
                 x-text="`${Math.min((courseStats.page-1)*10+1, courseStats.rows.length)}–${Math.min(courseStats.page*10, courseStats.rows.length)} of ${courseStats.rows.length} courses`">
            </div>
            <div class="gld-pagination-controls">
              <button class="gld-page-btn" @click="gotoCourseStatsPage(courseStats.page-1)" :disabled="courseStats.page <= 1">&#8592;</button>
              <template x-for="p in courseStatsTotalPages" :key="p">
                <button class="gld-page-btn" :class="{ active: p === courseStats.page }"
                        @click="gotoCourseStatsPage(p)"
                        x-show="courseStatsTotalPages <= 7 || p === 1 || p === courseStatsTotalPages || Math.abs(p - courseStats.page) <= 1"
                        x-text="p"></button>
              </template>
              <button class="gld-page-btn" @click="gotoCourseStatsPage(courseStats.page+1)" :disabled="courseStats.page >= courseStatsTotalPages">&#8594;</button>
            </div>
          </div>
        </template>
      </div><!-- /course-stats -->

      <!-- ── BILLING ──────────────────────────────────────────────────── -->
      <div x-show="!loading && view === 'billing'">
        <div class="gld-page-header">
          <div class="gld-page-title">Billing</div>
          <div class="gld-page-sub">Payment card, credit balance and seat charge history</div>
        </div>

        <!-- Credit balance -->
        <template x-if="billing.perSeatPrice > 0">
          <div class="gld-cards" style="margin-bottom:20px">
            <div class="gld-card">
              <div class="gld-card-label">Per-Seat Price</div>
              <div class="gld-card-value accent" x-text="billing.currency + ' ' + billing.perSeatPrice.toFixed(2)"></div>
            </div>
            <div class="gld-card">
              <div class="gld-card-label">Credit Balance</div>
              <div class="gld-card-value green" x-text="billing.currency + ' ' + billing.creditBalance.toFixed(2)"></div>
            </div>
          </div>
        </template>

        <!-- Saved card panel -->
        <div class="gld-panel" style="margin-bottom:20px">
          <div class="gld-panel-header"><div class="gld-panel-title">Payment Card</div></div>
          <div class="gld-panel-body">

            <!-- Has saved card -->
            <template x-if="billing.card">
              <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;padding:18px 24px;color:#fff;min-width:220px">
                  <div style="font-size:11px;opacity:.7;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em" x-text="billing.card.brand"></div>
                  <div style="font-size:18px;letter-spacing:.15em">•••• •••• •••• <span x-text="billing.card.last4"></span></div>
                  <div style="font-size:11px;opacity:.7;margin-top:8px">Expires <span x-text="billing.card.exp_month + '/' + billing.card.exp_year"></span></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px">
                  <button class="gld-btn gld-btn-ghost" @click="openCardSetup(true)">Replace Card</button>
                  <button class="gld-btn gld-btn-danger" @click="removeCard()">Remove Card</button>
                </div>
              </div>
            </template>

            <!-- No saved card -->
            <template x-if="!billing.card">
              <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <div style="color:var(--gld-muted);font-size:13px">No payment card saved. Add a card to enable automatic per-seat billing when adding learners.</div>
                <button class="gld-btn gld-btn-primary" @click="openCardSetup(false)">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                  Set Up Card
                </button>
              </div>
            </template>

            <!-- Card setup in progress -->
            <template x-if="billing.settingUpCard">
              <div style="margin-top:16px;padding:12px 16px;background:var(--gld-bg);border-radius:8px;font-size:13px;color:var(--gld-muted)">
                A payment window will open. Complete the card entry there — the first charge of <strong x-text="billing.currency + ' 1'"></strong> will be immediately refunded.
              </div>
            </template>
          </div>
        </div>

        <!-- Charge history -->
        <div class="gld-panel">
          <div class="gld-panel-header"><div class="gld-panel-title">Seat Charge History</div></div>
          <template x-if="billing.charges.length === 0">
            <div class="gld-empty" style="padding:24px">No seat charges yet.</div>
          </template>
          <template x-if="billing.charges.length > 0">
            <div class="gld-table-wrap">
              <table class="gld-table">
                <thead>
                  <tr>
                    <th>Learner</th>
                    <th>Amount</th>
                    <th>Period</th>
                    <th>Date</th>
                    <th>Ref</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="c in billing.charges" :key="c.id">
                    <tr>
                      <td>
                        <div class="gld-user-name" x-text="c.user_name"></div>
                        <div class="gld-user-email" x-text="c.user_email"></div>
                      </td>
                      <td style="font-weight:600" x-text="c.currency + ' ' + c.amount.toFixed(2)"></td>
                      <td style="font-size:12px;color:var(--gld-muted)" x-text="formatDate(c.period_start) + ' → ' + formatDate(c.period_end)"></td>
                      <td style="font-size:12px;color:var(--gld-muted)" x-text="formatDate(c.charged_at.split(' ')[0])"></td>
                      <td style="font-size:11px;color:var(--gld-muted);font-family:monospace" x-text="c.flw_txn_ref || '—'"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div><!-- /billing -->

    </main><!-- /gld-content -->
  </div><!-- /gld-layout -->

  <!-- ── Toast notifications ──────────────────────────────────────── -->
  <div class="gld-toast-wrap">
    <template x-for="t in toasts" :key="t.id">
      <div class="gld-toast" :class="t.type" x-text="t.msg"></div>
    </template>
  </div>

  <!-- ── Learner detail panel ────────────────────────────────────────── -->
  <template x-if="selectedLearner">
    <div class="gld-overlay" @click.self="closeLearner()">
      <div class="gld-learner-panel">

        <!-- Header -->
        <div class="gld-learner-header">
          <button class="gld-panel-close" @click="closeLearner()" aria-label="Close">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div class="gld-learner-hero">
            <img class="gld-learner-avatar" :src="selectedLearner.avatar" :alt="selectedLearner.name">
            <div>
              <div class="gld-learner-name" x-text="selectedLearner.name"></div>
              <div class="gld-learner-email" x-text="selectedLearner.email"></div>
            </div>
          </div>
          <div class="gld-learner-meta">
            <div class="gld-learner-meta-item">
              <span class="gld-learner-meta-label">Overall Progress</span>
              <span class="gld-learner-meta-value" x-text="learnerOverallPct() + '%'"></span>
            </div>
            <div class="gld-learner-meta-item">
              <span class="gld-learner-meta-label">Courses</span>
              <span class="gld-learner-meta-value" x-text="progress.courses.length"></span>
            </div>
            <div class="gld-learner-meta-item">
              <span class="gld-learner-meta-label">Last Active</span>
              <span class="gld-learner-meta-value" x-text="selectedLearner.last_active ? formatDate(selectedLearner.last_active) : 'Never'"></span>
            </div>
          </div>
        </div>

        <!-- Course list -->
        <div class="gld-learner-courses">
          <div class="gld-learner-courses-title">Course Progress</div>

          <template x-for="c in progress.courses" :key="c.id">
            <div class="gld-learner-course-row">
              <div class="gld-learner-course-top">
                <div class="gld-learner-course-title" x-text="c.title"></div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                  <span class="gld-badge" :class="learnerProgress(c).status" x-text="{ completed: 'Completed', in_progress: 'In Progress', not_started: 'Not Started' }[learnerProgress(c).status] || learnerProgress(c).status"></span>
                </div>
              </div>
              <div class="gld-progress-wrap">
                <div class="gld-progress-bar">
                  <div class="gld-progress-fill"
                       :class="progressBarClass(learnerProgress(c).percent)"
                       :style="`width:${learnerProgress(c).percent}%`"></div>
                </div>
                <span class="gld-progress-pct" x-text="learnerProgress(c).percent + '%'"></span>
              </div>
              <div class="gld-learner-course-steps" x-show="learnerProgress(c).total_steps > 0"
                   x-text="learnerProgress(c).completed_steps + ' of ' + learnerProgress(c).total_steps + ' steps completed'"></div>
            </div>
          </template>

          <template x-if="progress.courses.length === 0">
            <p style="color:var(--gld-muted);font-size:13px;padding-top:8px">No courses assigned to this group.</p>
          </template>
        </div>

      </div>
    </div>
  </template>

</div><!-- /#gld-app -->
