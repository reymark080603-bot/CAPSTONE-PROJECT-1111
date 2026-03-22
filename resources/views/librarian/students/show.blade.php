@extends('layouts.librarian')

@section('title', 'Student Details')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Student Details</h1>
            <p class="text-gray-600 mt-2">Full information about this student</p>
        </div>
        <div class="flex gap-3">
            @php($isActive = !empty($user->email_verified_at))
<form id="student-status-form" method="POST" action="{{ $isActive ? route('librarian.students.deactivate', $user->id) : route('librarian.students.activate', $user->id) }}" onsubmit="return false;">
                @csrf
                @method('PUT')
<button type="submit" class="{{ $isActive ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-5 py-3 rounded-lg flex items-center gap-2 transition-all duration-200 hover:transform hover:scale-105 shadow">
                    <i class="fas {{ $isActive ? 'fa-pause-circle' : 'fa-check' }}"></i>
                    {{ $isActive ? 'Deactivate' : 'Accept' }}
                </button>
            </form>
            <a href="{{ route('librarian.students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-3 rounded-lg flex items-center gap-2 transition-all duration-200">
                <i class="fas fa-arrow-left"></i>
                Back to List
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Avatar/Initials -->
        <div>
            <div class="aspect-square w-full rounded-lg overflow-hidden bg-blue-50 flex items-center justify-center border">
                <div class="text-blue-600 flex flex-col items-center">
                    <div class="text-6xl font-bold">{{ strtoupper(substr($user->firstname,0,1) . substr($user->lastname,0,1)) }}</div>
                    <div class="text-sm mt-2 text-blue-700">{{ $user->library_id ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) }}</h2>
                        <p class="text-gray-600 mt-1">{{ $user->email }}</p>
                    </div>
                    <div>
                        @php($status = $user->email_verified_at ? 'Active' : 'Pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}
                        ">
                            <i class="fas fa-circle mr-1 text-[10px]"></i>
                            {{ $status }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Course</div>
                    <div class="text-gray-900 font-medium">{{ $user->course ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Year Level</div>
                    <div class="text-gray-900 font-medium">{{ $user->year ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Registered</div>
                    <div class="text-gray-900 font-medium">{{ optional($user->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">Last Updated</div>
                    <div class="text-gray-900 font-medium">{{ optional($user->updated_at)->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Activity Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Total Borrowed</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $statistics['total_borrowed'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Currently Borrowed</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $statistics['currently_borrowed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Recent Borrows</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="py-2 pr-4">Book</th>
                                <th class="py-2 pr-4">Borrowed</th>
                                <th class="py-2 pr-4">Due</th>
                                <th class="py-2 pr-4">Returned</th>
                                <th class="py-2 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-800">
                            @forelse(($user->borrowRecords ?? collect())->sortByDesc('borrowed_date')->take(10) as $borrow)
                                <tr class="border-t">
                                    <td class="py-2 pr-4">{{ optional($borrow->book)->title ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $borrow->borrowed_date ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $borrow->due_date ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $borrow->returned_date ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $borrow->status ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-gray-500">No borrow records</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top-right toast notification (slides in from right) -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 -translate-y-2 w-[92%] sm:w-auto max-w-lg bg-white border border-gray-200 rounded-lg shadow-lg p-4 transition-all duration-300 z-50 opacity-0 pointer-events-none">
  <div class="flex items-center">
    <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100">
      <i class="fas fa-check text-green-600"></i>
    </div>
    <div class="flex-1">
      <p id="toastMessage" class="text-sm font-medium text-gray-900">Success</p>
    </div>
    <button id="closeToast" class="ml-4 text-gray-400 hover:text-gray-600" aria-label="Close">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>
@endsection


@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('student-status-form');
  if (!form) return;
  const url = form.getAttribute('action');
  const isDeactivate = url.includes('/deactivate');

  // Toast helpers (match Students index styling)
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    const messageEl = document.getElementById('toastMessage');

    messageEl.textContent = message;

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

    // Close button action
    const closeBtn = document.getElementById('closeToast');
    if (closeBtn) {
      closeBtn.onclick = function() {
        if (toast._hideTimeout) {
          clearTimeout(toast._hideTimeout);
          toast._hideTimeout = null;
        }
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
      };
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(url, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
        }
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok || data.success === false) throw new Error(data.message || 'Action failed');

      const msg = data.message || (isDeactivate ? 'Student deactivated successfully' : 'Student account activated successfully');

      // Show top-right toast and then redirect shortly after
      showToast(msg, 'success');

      // Persist for index page toast as well
      localStorage.setItem('studentsSuccess', msg);

      setTimeout(() => {
        window.location.href = '{{ route('librarian.students.index') }}';
      }, 900);
    } catch (err) {
      showToast(err.message || 'Action failed', 'error');
    }
  });
});
</script>
@endsection
