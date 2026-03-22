@extends('layouts.librarian')

@section('title', 'Manage Loans')

@section('content')
<div class="mb-6">
  <h1 class="text-3xl font-bold text-gray-900">Manage Book Borrowed</h1>
  <p class="text-gray-600 mt-2">Monitor all active loans</p>
</div>


<!-- Statistics -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
  <div class="flex items-center justify-between mb-4">
    <div class="flex items-center">
      <i class="fas fa-chart-bar text-gray-500 mr-2"></i>
      <h2 class="text-lg font-semibold text-gray-900">Loans Statistics</h2>
    </div>
    <div class="text-sm text-gray-500">Auto-updates with filters</div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="rounded-lg p-4 bg-blue-50 border border-blue-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-blue-700">Total Loans</p>
          <p id="stat-total-loans" class="text-2xl font-bold text-blue-900">0</p>
        </div>
        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
          <i class="fas fa-layer-group text-blue-600"></i>
        </div>
      </div>
    </div>
    <div class="rounded-lg p-4 bg-green-50 border border-green-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-green-700">Active Loans</p>
          <p id="stat-active-loans" class="text-2xl font-bold text-green-900">0</p>
        </div>
        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
          <i class="fas fa-hand-holding text-green-600"></i>
        </div>
      </div>
    </div>
    <div class="rounded-lg p-4 bg-purple-50 border border-purple-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-purple-700">Returned Loans</p>
          <p id="stat-returned-loans" class="text-2xl font-bold text-purple-900">0</p>
        </div>
        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
          <i class="fas fa-check-circle text-purple-600"></i>
        </div>
      </div>
    </div>
  </div>

<!-- Filters toolbar (match Student Browse Books style) -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6 relative">
  <div class="flex items-center justify-between gap-4">
    <button id="loan-filter-toggle" class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
      <i class="fas fa-filter text-gray-500"></i>
      <span class="text-sm font-medium">Filters</span>
    </button>
    <div class="relative">
      <input id="loan-search" type="text" placeholder="Search loans..." class="px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
      <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
  </div>

  <!-- Filter Panel (Dropdown) -->
  <div id="loan-filter-panel" class="hidden absolute left-4 top-16 z-50 w-[calc(100%-2rem)] md:w-[44rem] bg-white border border-gray-200 rounded-xl shadow-2xl p-4 animate-slide-down">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div>
        <label for="loan-status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <select id="loan-status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="all">All</option>
          <option value="borrowed">Borrowed</option>
          <option value="returned">Returned</option>
        </select>
      </div>
      <div>
        <label for="loan-course" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
        <select id="loan-course" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All</option>
          <option value="BSIT">BSIT</option>
          <option value="BSCS">BSCS</option>
          <option value="BSIS">BSIS</option>
          <option value="BSEE">BSEE</option>
          <option value="BSME">BSME</option>
          <option value="BSCE">BSCE</option>
          <option value="BSN">BSN</option>
          <option value="BSE">BSE</option>
          <option value="BSED">BSED</option>
          <option value="BEED">BEED</option>
        </select>
      </div>
      <div>
        <label for="loan-year" class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
        <select id="loan-year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All</option>
          <option value="1st Year">1st Year</option>
          <option value="2nd Year">2nd Year</option>
          <option value="3rd Year">3rd Year</option>
          <option value="4th Year">4th Year</option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label for="loan-date-from" class="block text-sm font-medium text-gray-700 mb-2">From</label>
          <input id="loan-date-from" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="loan-date-to" class="block text-sm font-medium text-gray-700 mb-2">To</label>
          <input id="loan-date-to" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>
    </div>

    <div class="flex justify-between items-center mt-4">
      <button id="loan-clear-filters" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
        <i class="fas fa-times mr-1"></i> Clear All Filters
      </button>
      <button id="loan-apply-filters" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
        Apply Filters
      </button>
    </div>
  </div>
</div>

<!-- Loans Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
  <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
    <div class="flex items-center">
      <i class="fas fa-hand-holding text-gray-500 mr-2"></i>
      <h2 class="text-lg font-semibold text-gray-900">Loans</h2>
    </div>
    <div class="text-sm text-gray-500" id="loan-count">0 loans</div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full table-auto">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Library ID</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrowed</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
        </tr>
      </thead>
      <tbody id="loans-body" class="bg-white divide-y divide-gray-200">
        <tr>
          <td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Small utility modal for confirm (reuse same from existing if needed) -->
<div id="loan-confirm" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 shadow-xl w-full max-w-md">
    <h3 id="loan-confirm-title" class="text-lg font-semibold text-gray-900 mb-2">Confirm Action</h3>
    <p id="loan-confirm-text" class="text-sm text-gray-600 mb-4">Are you sure?</p>
    <div class="flex justify-end gap-2">
      <button id="loan-cancel" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</button>
      <button id="loan-confirm-ok" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Confirm</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/librarian-loans.js') }}"></script>
<script>
(function() {
  const statusEl = document.getElementById('loan-status');
  const fromEl = document.getElementById('loan-date-from');
  const toEl = document.getElementById('loan-date-to');
  const courseEl = document.getElementById('loan-course');
  const yearEl = document.getElementById('loan-year');

  function number(n) { return typeof n === 'number' ? n : (parseInt(n, 10) || 0); }

  async function fetchLoanStats() {
    const params = new URLSearchParams();
    if (statusEl && statusEl.value && statusEl.value !== 'all') params.set('status', statusEl.value);
    if (fromEl && fromEl.value) params.set('date_from', fromEl.value);
    if (toEl && toEl.value) params.set('date_to', toEl.value);
    if (courseEl && courseEl.value) params.set('course', courseEl.value);
    if (yearEl && yearEl.value) params.set('year', yearEl.value);

    const res = await fetch(`/librarian/loans/statistics?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return;
    const json = await res.json();
    if (!json.success) return;

    // Summary cards
    document.getElementById('stat-total-loans').textContent = number(json.summary.total_loans);
    document.getElementById('stat-active-loans').textContent = number(json.summary.active_loans);
    document.getElementById('stat-returned-loans').textContent = number(json.summary.returned_loans);
  }

  // Hook filters for stats refresh
  [statusEl, fromEl, toEl, courseEl, yearEl].forEach(el => {
    if (!el) return;
    el.addEventListener('change', fetchLoanStats);
    el.addEventListener('input', fetchLoanStats);
  });

  document.addEventListener('DOMContentLoaded', fetchLoanStats);
  if (document.readyState === 'interactive' || document.readyState === 'complete') {
    fetchLoanStats();
  }

  // Lightweight auto-refresh: every 15s and on focus/visibility
  const REFRESH_MS = 15000;
  let statsRefreshTimer = setInterval(fetchLoanStats, REFRESH_MS);

  // Refresh when tab gains focus
  window.addEventListener('focus', fetchLoanStats);
  // Refresh when tab becomes visible again
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) fetchLoanStats();
  });
  // Cleanup on unload
  window.addEventListener('beforeunload', () => {
    try { clearInterval(statsRefreshTimer); } catch (e) {}
  });
})();
// Filters panel toggle (match Student Browse Books)
(function(){
  const toggleBtn = document.getElementById('loan-filter-toggle');
  const panel = document.getElementById('loan-filter-panel');
  if (!toggleBtn || !panel) return;
  const close = ()=> panel.classList.add('hidden');
  const open = ()=> panel.classList.remove('hidden');
  toggleBtn.addEventListener('click', (e)=>{ e.stopPropagation(); panel.classList.toggle('hidden'); });
  panel.addEventListener('click', (e)=> e.stopPropagation());
  document.addEventListener('click', close);
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') close(); });
  // Apply & Clear
  document.getElementById('loan-apply-filters')?.addEventListener('click', ()=> close());
  document.getElementById('loan-clear-filters')?.addEventListener('click', ()=>{
    const ids = ['loan-status','loan-course','loan-year','loan-date-from','loan-date-to'];
    ids.forEach(id => { const el = document.getElementById(id); if (el) { el.value = ''; el.dispatchEvent(new Event('change', { bubbles: true })); } });
    const search = document.getElementById('loan-search'); if (search) { search.value=''; search.dispatchEvent(new Event('input', { bubbles: true })); }
    open(); // keep open so user sees cleared state
  });
})();
</script>
@endsection
