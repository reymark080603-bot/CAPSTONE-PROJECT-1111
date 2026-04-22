/**
 * Librarian Loans Page JS
 * Clean, Tailwind-first UI with fetch-based rendering
 */
(function() {
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  const state = {
    search: '',
    status: 'all',
    course: '',
    year: '',
    campus: '',
    dateFrom: '',
    dateTo: ''
  };
  
  // prevent overlapping fetches
  let loading = false;

  function init() {
    bindFilters();
    loadLoans();
    bindHeaderSearch();
    setupAutoRefresh();
  }

  function bindHeaderSearch() {
    const hs = $('#header-search');
    const btn = $('#header-search-btn');
    const apply = () => {
      if (!hs) return;
      state.search = hs.value.trim();
      $('#loan-search') && ($('#loan-search').value = state.search);
      loadLoans();
    };
    if (btn) btn.addEventListener('click', apply);
    if (hs) hs.addEventListener('keypress', e => { if (e.key === 'Enter') apply(); });
  }

  function bindFilters() {
    const inputMap = {
      '#loan-search': 'search',
      '#loan-status': 'status',
      '#loan-course': 'course',
      '#loan-year': 'year',
      '#loan-campus': 'campus',
      '#loan-date-from': 'dateFrom',
      '#loan-date-to': 'dateTo'
    };

    Object.entries(inputMap).forEach(([sel, key]) => {
      const el = $(sel);
      if (!el) return;
      const handler = () => {
        state[key] = el.value;
        loadLoans();
      };
      el.addEventListener('change', handler);
      if (sel === '#loan-search') el.addEventListener('input', debounce(handler, 300));
    });
  }

  async function loadLoans() {
    if (loading) return;
    loading = true;
    const body = $('#loans-body');
    const count = $('#loan-count');
    if (!body) { loading = false; return; }

    body.innerHTML = `<tr><td colspan=\"6\" class=\"px-6 py-8 text-center text-gray-500\">Loading...</td></tr>`;
    try {
      const params = new URLSearchParams({
        search: state.search,
        status: state.status,
        course: state.course,
        year: state.year,
        campus: state.campus,
        date_from: state.dateFrom,
        date_to: state.dateTo
      });
      const res = await fetch(`/librarian/loans/data?${params.toString()}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      if (!res.ok) throw new Error('Failed to load loans');
      const data = await res.json();
      renderLoans(data.loans || []);
      if (count) count.textContent = `${(data.loans || []).length} borrowed e-resource${(data.loans || []).length === 1 ? '' : 's'}`;
    } catch (e) {
      console.error(e);
      body.innerHTML = `<tr><td colspan=\"6\" class=\"px-6 py-8 text-center text-red-600\">Failed to load loans</td></tr>`;
    } finally {
      loading = false;
    }
  }

  function renderLoans(loans) {
    const body = $('#loans-body');
    if (!loans.length) {
      body.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No borrowed e-resources found</td></tr>`;
      return;
    }

    const rows = loans.map(l => {
      const statusBadge = badge(l.status, l.days_remaining);
      const academicInfo = [l.year, l.program, l.campus].filter(Boolean).join(' | ');
      return `
        <tr>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${escapeHtml(l.student || 'Unknown')}</div>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm text-gray-700">${escapeHtml(academicInfo || 'N/A')}</div>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900">${escapeHtml(l.book_title || 'Unknown')}</div>
            <div class="text-xs text-gray-500">by ${escapeHtml(l.author || '')}</div>
          </td>
          <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(l.borrowed_date || '')}</td>
          <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(l.due_date || '')}</td>
          <td class="px-6 py-4">${statusBadge}</td>
        </tr>
      `;
    }).join('');
    body.innerHTML = rows;
  }

  function badge(status, daysRemaining) {
    let cls = 'bg-gray-100 text-gray-800';
    let label = status;
    
    if (status === 'borrowed') {
      cls = 'bg-blue-100 text-blue-800';
      if (daysRemaining !== undefined && daysRemaining <= 0) {
        cls = 'bg-orange-100 text-orange-800';
        label = 'Due for Auto-Return';
      } else if (daysRemaining !== undefined && daysRemaining <= 1) {
        cls = 'bg-yellow-100 text-yellow-800';
        label = `Due Soon (${daysRemaining}d)`;
      }
    }
    
    if (status === 'returned') {
      cls = 'bg-green-100 text-green-800';
    }
    
    return `<span class="px-2 py-1 rounded-full text-xs font-medium ${cls}">${escapeHtml(label)}</span>`;
  }

  function actionButtons(l) {
    if (l.status === 'borrowed') {
      const dueSoonClass = l.days_remaining <= 1 ? 'ring-2 ring-yellow-300' : '';
      return `
        <div class="flex gap-2">
          <button class="btn-return px-3 py-1.5 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs" data-id="${l.id}"><i class="fas fa-undo mr-1"></i>Return</button>
          <button class="btn-renew px-3 py-1.5 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs ${dueSoonClass}" data-id="${l.id}"><i class="fas fa-refresh mr-1"></i>Renew</button>
        </div>
      `;
    }
    return `<span class="text-gray-400 text-xs">No actions</span>`;
  }

  function confirmReturn(id) {
    openConfirm('Mark as Returned', 'Confirm marking this e-resource as returned?', async () => {
      await postJson(`/librarian/loans/${id}/return`, {});
      await loadLoans();
    });
  }

  function confirmRenew(id) {
    openConfirm('Renew Borrowed E-Resource', 'Renew this borrowed e-resource by 7 days?', async () => {
      await postJson(`/librarian/loans/${id}/renew`, { days: 7 });
      await loadLoans();
    });
  }

  function openConfirm(title, text, onOk) {
    const modal = $('#loan-confirm');
    $('#loan-confirm-title').textContent = title;
    $('#loan-confirm-text').textContent = text;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    const ok = $('#loan-confirm-ok');
    const cancel = $('#loan-cancel');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); ok.replaceWith(ok.cloneNode(true)); cancel.replaceWith(cancel.cloneNode(true)); };
    $('#loan-cancel').addEventListener('click', close, { once: true });
    $('#loan-confirm-ok').addEventListener('click', async () => { try { await onOk(); } finally { close(); } }, { once: true });
  }

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || 'Request failed');
    }
    return data;
  }

  function escapeHtml(t) { const d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
  function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

  // Auto-refresh loans table every 2 minutes and on focus/visibility
  function setupAutoRefresh() {
    const REFRESH_MS = 120000; // 2 minutes
    setInterval(loadLoans, REFRESH_MS);
    window.addEventListener('focus', loadLoans);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) loadLoans(); });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
