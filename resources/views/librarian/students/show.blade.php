@extends('layouts.librarian')

@section('title', 'Student Details')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 shadow-sm">
                    <i class="fas fa-user-graduate text-lg"></i>
                </span>
                Student Details
            </h1>
            <p class="text-sm text-gray-500 mt-1">Full academic information and borrowing activity log</p>
        </div>
        <div class="flex items-center gap-3">
            @php($isActive = !empty($user->email_verified_at))
            <form id="student-status-form" method="POST" action="{{ $isActive ? route('librarian.students.deactivate', $user->id) : route('librarian.students.activate', $user->id) }}" onsubmit="return false;">
                @csrf
                @method('PUT')
                <button type="submit" class="{{ $isActive ? 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500' : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500' }} text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-150 border border-transparent shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1">
                    <i class="fas {{ $isActive ? 'fa-pause-circle' : 'fa-check-circle' }}"></i>
                    <span>{{ $isActive ? 'Deactivate Account' : 'Activate Account' }}</span>
                </button>
            </form>
            <a href="{{ route('librarian.students.index') }}" class="bg-white hover:bg-gray-100 text-gray-800 border border-gray-300 text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-150 shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
                <i class="fas fa-arrow-left text-gray-500"></i>
                <span>Back to List</span>
            </a>
        </div>
    </div>

    <!-- Compact & Modern Profile Banner -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6">
        <div class="pb-6 border-b border-gray-100">
            <!-- Profile Info Header -->
            <div class="space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900">
                            {{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <i class="far fa-envelope text-emerald-600"></i>
                                {{ $user->email }}
                            </span>
                            <span class="text-gray-300">•</span>
                            <span class="font-mono text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-100">
                                {{ $user->library_id ?? 'No Library ID' }}
                            </span>
                        </div>
                    </div>

                    @php($status = $user->email_verified_at ? 'Active' : 'Pending')
                    <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wide {{ $status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        <span class="w-2 h-2 rounded-full mr-2 {{ $status === 'Active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        {{ $status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Academic & System Metadata Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 pt-6">
            <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3.5">
                <div class="text-[11px] font-bold tracking-wider uppercase text-gray-400 mb-1 flex items-center gap-1.5">
                    <i class="fas fa-graduation-cap text-emerald-600"></i> Program
                </div>
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ $user->course?->name ?? $user->course ?? '-' }}
                </div>
            </div>

            <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3.5">
                <div class="text-[11px] font-bold tracking-wider uppercase text-gray-400 mb-1 flex items-center gap-1.5">
                    <i class="fas fa-layer-group text-emerald-600"></i> Year Level
                </div>
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ $user->yearLevel?->level ?? $user->year ?? '-' }}
                </div>
            </div>

            <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3.5">
                <div class="text-[11px] font-bold tracking-wider uppercase text-gray-400 mb-1 flex items-center gap-1.5">
                    <i class="fas fa-building-columns text-emerald-600"></i> Campus
                </div>
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ $user->campus ?? '-' }}
                </div>
            </div>

            <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3.5">
                <div class="text-[11px] font-bold tracking-wider uppercase text-gray-400 mb-1 flex items-center gap-1.5">
                    <i class="far fa-calendar-check text-emerald-600"></i> Registered
                </div>
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ optional($user->created_at)->format('Y-m-d H:i') ?? '-' }}
                </div>
            </div>

            <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3.5 col-span-2 sm:col-span-1">
                <div class="text-[11px] font-bold tracking-wider uppercase text-gray-400 mb-1 flex items-center gap-1.5">
                    <i class="far fa-clock text-emerald-600"></i> Last Updated
                </div>
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ optional($user->updated_at)->format('Y-m-d H:i') ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Summary Section -->
    <div>
        <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
            <i class="fas fa-chart-pie text-emerald-600"></i> Activity Summary
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 flex items-center justify-between shadow-sm hover:shadow transition-all duration-200">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Borrowed</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ $statistics['total_borrowed'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-sm">
                    <i class="fas fa-book"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200/80 p-5 flex items-center justify-between shadow-sm hover:shadow transition-all duration-200">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Currently Borrowed</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $statistics['currently_borrowed'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-sm">
                    <i class="fas fa-book-reader"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200/80 p-5 flex items-center justify-between shadow-sm hover:shadow transition-all duration-200">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Returned</div>
                    <div class="text-2xl font-bold text-purple-600 mt-1">{{ $statistics['total_returned'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shadow-sm">
                    <i class="fas fa-undo-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Borrows Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-history text-emerald-600"></i> Recent Borrows
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4 rounded-l-xl">Book</th>
                        <th class="py-3 px-4">Borrowed</th>
                        <th class="py-3 px-4">Due</th>
                        <th class="py-3 px-4">Returned</th>
                        <th class="py-3 px-4 rounded-r-xl">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse(($user->borrowRecords ?? collect())->sortByDesc('borrowed_date')->take(10) as $borrow)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-4 font-semibold text-gray-900">
                                {{ optional($borrow->book)->title ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ $borrow->borrowed_date ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ $borrow->due_date ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ $borrow->returned_date ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @php($bStatus = strtolower($borrow->status ?? ''))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ $bStatus === 'returned' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                                       ($bStatus === 'borrowed' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-600') }}">
                                    {{ ucfirst($borrow->status ?? '-') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-400">
                                No borrow records
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 -translate-y-2 w-[92%] sm:w-auto max-w-lg bg-white border border-gray-200 rounded-xl shadow-xl p-4 transition-all duration-300 z-50 opacity-0 pointer-events-none">
    <div class="flex items-center">
        <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100">
            <i class="fas fa-check text-green-600"></i>
        </div>
        <div class="flex-1">
            <p id="toastMessage" class="text-sm font-semibold text-gray-900">Success</p>
        </div>
        <button id="closeToast" class="ml-4 text-gray-400 hover:text-gray-600" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('student-status-form');
    if (!form) return;

    const url = form.getAttribute('action');
    const isDeactivate = url.includes('/deactivate');

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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) throw new Error(data.message || 'Action failed');

            const msg = data.message || (isDeactivate ? 'Student deactivated successfully' : 'Student account activated successfully');

            showToast(msg, 'success');
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
