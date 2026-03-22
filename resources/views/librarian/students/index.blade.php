@extends('layouts.librarian')

@section('title', 'Manage Students')

@section('content')
<!-- Success Message -->
@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center max-w-2xl mx-auto w-full">
    <div class="flex-shrink-0">
        <i class="fas fa-check-circle text-green-600 text-xl"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
    <div class="ml-auto pl-3">
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center max-w-2xl mx-auto w-full">
    <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
    <div class="ml-auto pl-3">
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

<!-- Students Management Header -->
<div class="students-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Students</h1>
            <p class="text-gray-600 mt-2">View and manage all registered student accounts</p>
        </div>
        <div class="flex gap-3">
            <button id="refreshBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 transition-all duration-200 hover:transform hover:scale-105 shadow-lg">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="students-filters">
    <div class="p-6">
        <div class="flex items-center mb-4">
            <i class="fas fa-filter text-gray-500 mr-2"></i>
            <h2 class="text-lg font-semibold text-gray-900">Search & Filters</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div class="lg:col-span-2">
                <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, library ID..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </div>
            <div>
                <label for="courseFilter" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                <select id="courseFilter" class="w-full border border-gray-300 rounded-lg px-3 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">All Courses</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSN">BSN</option>
                    <option value="BSED">BSED</option>
                    <option value="BSBM">BSBM</option>
                    <option value="BSHM">BSHM</option>
                </select>
            </div>
            <div>
                <label for="yearFilter" class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                <select id="yearFilter" class="w-full border border-gray-300 rounded-lg px-3 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">All Years</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button id="clearFiltersBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<div class="students-table">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-users text-gray-500 mr-2"></i>
                <h2 class="text-lg font-semibold text-gray-900">Registered Students</h2>
            </div>
            <div class="text-sm text-gray-600" id="studentCount">
                Loading...
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="studentsTable" class="w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[260px]">Student Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[100px]">Gender</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[220px]">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[220px]">Academic Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">Library ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[110px]">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[180px]">Registered</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <!-- DataTable will populate this -->
            </tbody>
        </table>
    </div>
</div>

<!-- Student Details Modal -->
<div id="studentModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-[9999] p-4" style="backdrop-filter: blur(2px);">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[95vh] overflow-y-auto transform transition-all duration-300 scale-95 opacity-0" id="studentModalContent">
        <!-- Modal content will be loaded here -->
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 -translate-y-2 w-[92%] sm:w-auto max-w-lg bg-white border border-gray-200 rounded-lg shadow-lg p-4 transition-all duration-300 z-50 opacity-0 pointer-events-none">
    <div class="flex items-center">
        <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3">
            <i class="fas fa-check"></i>
        </div>
        <div>
            <p id="toastMessage" class="text-sm font-medium text-gray-900"></p>
        </div>
        <button id="closeToast" class="ml-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Non-modal confirmation popover -->
<div id="confirm-popover" class="fixed z-[60] hidden">
  <div id="confirm-card" class="w-[360px] bg-white dark:bg-slate-800 rounded-xl shadow-xl ring-1 ring-black/5 transform transition-all duration-200 opacity-0 scale-95">
    <div class="p-5">
      <div class="flex items-start gap-3">
        <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
          <i class="fas fa-user-slash"></i>
        </div>
        <div class="flex-1">
          <h3 id="confirm-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Deactivate Account</h3>
          <p id="confirm-message" class="mt-1 text-sm text-slate-600 dark:text-slate-300">Deactivate this student account? They won't be able to borrow until reactivated.</p>
          <div class="mt-4 flex justify-end gap-2">
            <button id="confirm-cancel" type="button" class="px-3 py-2 rounded-md text-slate-700 bg-slate-100 hover:bg-slate-200">Cancel</button>
            <button id="confirm-okay" type="button" class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">Confirm</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
/* Students Management Page Styles */
.students-header {
    margin-bottom: 2rem;
}

.students-filters {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.students-table {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .students-header .flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .students-filters .grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
}
</style>
@endpush

@section('scripts')
<script>
// Show success from previous page action (e.g., activate/deactivate in details page)
document.addEventListener('DOMContentLoaded', function(){
  const msg = localStorage.getItem('studentsSuccess');
  if (msg) {
    if (typeof showToast === 'function') {
      showToast(msg, 'success');
    } else {
      // Fallback banner
      const banner = document.createElement('div');
      banner.className = 'bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center';
      banner.innerHTML = '<div class="flex-shrink-0"><i class="fas fa-check-circle text-green-600 text-xl"></i></div><div class="ml-3"><p class="text-sm font-medium text-green-800">'+msg+'</p></div>';
      const container = document.querySelector('.students-header') || document.body;
      container.parentNode.insertBefore(banner, container);
    }
    localStorage.removeItem('studentsSuccess');
  }
});
</script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    bindStudentFilters();
    loadStudents();
});

function bindStudentFilters() {
    const searchInput = document.getElementById('searchInput');
    const courseFilter = document.getElementById('courseFilter');
    const yearFilter = document.getElementById('yearFilter');
    const clearBtn = document.getElementById('clearFiltersBtn');
    const refreshBtn = document.getElementById('refreshBtn');

    let timer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(loadStudents, 300);
        });
    }
    [courseFilter, yearFilter].forEach(el => el && el.addEventListener('change', loadStudents));
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (courseFilter) courseFilter.value = '';
            if (yearFilter) yearFilter.value = '';
            loadStudents();
        });
    }
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadStudents();
            showToast('Student data refreshed', 'success');
        });
    }
}

async function loadStudents() {
    const tbody = document.querySelector('#studentsTable tbody');
    const count = document.getElementById('studentCount');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-gray-500">Loading...</td></tr>`;

    const params = new URLSearchParams();
    const sVal = document.getElementById('searchInput')?.value || '';
    const course = document.getElementById('courseFilter')?.value || '';
    const year = document.getElementById('yearFilter')?.value || '';
    if (sVal) params.set('search', sVal);
    if (course) params.set('course', course);
    if (year) params.set('year', year);
    params.set('simple', '1');

    try {
        const res = await fetch(`/librarian/students/data?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();
        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Failed to load');
        }
        const students = json.students || [];
        if (count) count.textContent = `Total: ${(json.total ?? students.length)} students`;
        if (!students.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-gray-500">No students found</td></tr>`;
            return;
        }
        const escapeAttr = (s) => String(s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const rows = students.map(row => {
            const initials = `${(row.firstname || (row.name || '')).toString().charAt(0)}${(row.lastname || '').toString().charAt(0)}`;
            const fullName = row.name || `${row.firstname || ''} ${row.lastname || ''}`.trim();
            const status = row.status || (row.email_verified_at ? 'Active' : 'Pending');
            const statusClass = status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
            const created = row.created_at || '';
            const genderIcon = row.gender === 'male' ? '<i class="fas fa-mars text-blue-500 mr-1"></i>' : row.gender === 'female' ? '<i class="fas fa-venus text-pink-500 mr-1"></i>' : '<i class="fas fa-question text-gray-400 mr-1"></i>';
            const genderText = row.gender ? row.gender.charAt(0).toUpperCase() + row.gender.slice(1) : 'Not specified';
            const genderClass = row.gender === 'male' ? 'text-blue-700' : row.gender === 'female' ? 'text-pink-700' : 'text-gray-500';
            return `
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 font-medium text-sm">${initials}</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">${fullName || '-'}</h4>
                                <p class="text-sm text-gray-600">${(row.firstname || '')} ${(row.mi || '')} ${(row.lastname || '')}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            ${genderIcon}
                            <span class="text-sm font-medium ${genderClass}">${genderText}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><div class="font-medium text-gray-900">${row.email || '-'}</div></td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-medium text-gray-900">${row.course || 'Not specified'}</div>
                            <div class="text-gray-500">${row.year || 'Not specified'}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-md text-sm font-mono">${row.library_id || '-'}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}">${status}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm">
                            <div>${created ? new Date(created).toLocaleDateString() : '-'}</div>
                            <div class="text-gray-500">${created ? new Date(created).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex gap-3 items-center">
                            <button data-action="view" data-id="${row.id}" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${(status === 'Pending') ? `
                            <button data-action=\"activate\" data-id=\"${row.id}\" class=\"text-green-600 hover:text-green-800\" title=\"Accept\">
                            <i class=\"fas fa-check\"></i>
                            </button>` : `
                            <button data-action=\"deactivate\" data-id=\"${row.id}\" class=\"text-yellow-600 hover:text-yellow-800\" title=\"Deactivate\">
                            <i class=\"fas fa-pause-circle\"></i>
                            </button>`}

                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        tbody.innerHTML = rows;
    } catch (e) {
        console.error('Load students error:', e);
        tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-red-500">Failed to load students</td></tr>`;
    }
}

// Event delegation for action buttons
(function bindActions(){
    const tbody = document.querySelector('#studentsTable tbody');
    if (!tbody) return;
    tbody.addEventListener('click', function(e){
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (!id || !action) return;
        if (action === 'view') {
            window.location.href = `/librarian/students/${id}`;
            return;
        }
        if (action === 'activate') {
            activateStudent(id);
            return;
        }
        if (action === 'deactivate') {
            deactivateStudent(id, btn);
            return;
        }
    });
})();

// Legacy modal code retained below but unused


function showStudentModal() {
    const modal = document.getElementById('studentModal');
    const content = document.getElementById('studentModalContent');
    modal.classList.remove('hidden');
    // add flex when showing so layout utilities (items-center / justify-center) work
    modal.classList.add('flex');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

window.hideStudentModal = function() {
    const modal = document.getElementById('studentModal');
    const content = document.getElementById('studentModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        // remove flex after hiding to avoid conflicting display utilities
        modal.classList.remove('flex');
    }, 250);
};

window.activateStudent = function(studentId) {
    fetch(`/librarian/students/${studentId}/activate`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadStudents();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
};

// Non-modal confirmation popover helpers
const ConfirmPopover = (function(){
  const wrap = document.getElementById('confirm-popover');
  const card = document.getElementById('confirm-card');
  const titleEl = document.getElementById('confirm-title');
  const msgEl = document.getElementById('confirm-message');
  const cancelBtn = document.getElementById('confirm-cancel');
  const okBtn = document.getElementById('confirm-okay');
  let onConfirm = null;
  let outsideHandler = null;

  function open(anchorEl, {title, message, confirmText='Confirm', cancelText='Cancel', onOk}){
    if (!wrap || !card) return;
    titleEl.textContent = title || 'Confirm';
    msgEl.textContent = message || '';
    okBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;
    onConfirm = onOk;

    // Show to measure size
    wrap.classList.remove('hidden');
    card.style.visibility = 'hidden';
    card.classList.remove('opacity-0','scale-95'); // ensure size is measured

    // Compute position near the anchor
    const rect = anchorEl.getBoundingClientRect();
    const spacing = 10;
    const top = rect.bottom + spacing + window.scrollY;
    let left = rect.left + window.scrollX - 200 + rect.width/2; // center relative to anchor
    // Keep within viewport
    const maxLeft = window.scrollX + window.innerWidth - 16 - 360;
    const minLeft = window.scrollX + 16;
    left = Math.max(minLeft, Math.min(maxLeft, left));

    wrap.style.top = `${top}px`;
    wrap.style.left = `${left}px`;

    // Reveal with animation
    card.style.visibility = '';
    card.classList.add('opacity-0','scale-95');
    requestAnimationFrame(()=>{
      card.classList.remove('opacity-0','scale-95');
    });

    // Outside click to close
    outsideHandler = (e)=>{
      if (!card.contains(e.target)) close();
    };
    setTimeout(()=>document.addEventListener('mousedown', outsideHandler), 0);

    cancelBtn.onclick = close;
    okBtn.onclick = ()=>{ if (typeof onConfirm === 'function') onConfirm(); };
  }
  function close(){
    card.classList.add('opacity-0','scale-95');
    setTimeout(()=>{
      wrap.classList.add('hidden');
      card.classList.remove('opacity-0','scale-95');
    }, 150);
    if (outsideHandler) {
      document.removeEventListener('mousedown', outsideHandler);
      outsideHandler = null;
    }
  }
  return { open, close };
})();

window.deactivateStudent = function(studentId, el) {
  const anchor = el || document.querySelector('[onclick^="deactivateStudent(' + studentId + ',"]');
  ConfirmPopover.open(anchor, {
    title: 'Deactivate Account',
    message: 'Deactivate this student account? They will not be able to borrow until reactivated.',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    onOk: function(){
      fetch(`/librarian/students/${studentId}/deactivate`, {
        method: 'PUT',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          ConfirmPopover.close();
          showToast(data.message, 'success');
          loadStudents();
        } else {
          showToast(data.message || 'Failed to deactivate', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
      });
    }
  });
};

window.deleteStudent = function(studentId, studentName) {
    if (!confirm(`Delete student \"${studentName}\"? This action cannot be undone.`)) {
        return;
    }
    fetch(`/librarian/students/${studentId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Student deleted successfully', 'success');
            loadStudents();
        } else {
            showToast(data.message || 'Failed to delete student', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
};


// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    const messageEl = document.getElementById('toastMessage');
    
    // Set message
    messageEl.textContent = message;
    
    // Set icon and colors based on type
    if (type === 'success') {
        icon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100';
        icon.innerHTML = '<i class="fas fa-check text-green-600"></i>';
    } else if (type === 'error') {
        icon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-red-100';
        icon.innerHTML = '<i class="fas fa-times text-red-600"></i>';
    }
    
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
        toast._hideTimeout = null;
    }
    toast.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
    toast.classList.add('opacity-100');
    
    // Auto-hide after 5 seconds
    toast._hideTimeout = setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
    }, 5000);
    
    // Close button
    document.getElementById('closeToast').onclick = function() {
        if (toast._hideTimeout) {
            clearTimeout(toast._hideTimeout);
            toast._hideTimeout = null;
        }
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
    };
}
</script>
@endsection
