@extends('layouts.app')
@php $title = 'Pesan'; @endphp

@section('content')
@php
  $userList = collect($users);
  $threadMap = collect($threads)->keyBy('participant_id');
  $activeThread = collect($threads)->sortByDesc('last_at')->first();
  $activeUser = $activeThread
    ? $userList->firstWhere('nim', $activeThread['participant_id'])
    : ($userList->firstWhere('nim', 'CV-0001') ?? $userList->first());
  $activeThread = $activeUser ? ($activeThread ?? $threadMap->get($activeUser['nim'])) : null;
@endphp

<div class="chat-web">
  <aside class="chat-sidebar">
    <div class="chat-sidebar-head">
      <h3>Chat Civitas</h3>
      <div class="search-input-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" id="chatSearch" placeholder="Cari nama atau NIM..." oninput="filterChatUsers()" />
      </div>
    </div>

    <div class="chat-list" id="chatList">
      @forelse($userList->sortByDesc(fn($user) => $threadMap->get($user['nim'])['last_at'] ?? '') as $i => $user)
        @php
          $thread = $threadMap->get($user['nim']);
          $lastMessage = $thread['last_message'] ?? 'Belum ada chat dengan akun ini.';
          $lastDate = isset($thread['last_at']) ? \Carbon\Carbon::parse($thread['last_at'])->isoFormat('D MMM') : '';
          $hasUnread = $thread && collect($thread['messages'] ?? [])->contains(fn($msg) => ($msg['sender_role'] ?? '') === 'civitas' && empty($msg['read_by_admin']));
          $isActive = $activeUser && $activeUser['nim'] === $user['nim'];
        @endphp
        <button class="chat-contact {{ $isActive ? 'active' : '' }}"
          type="button"
          data-name="{{ strtolower($user['nama']) }}"
          data-nim="{{ strtolower($user['nim']) }}"
          data-user='@json($user)'
          data-thread='@json($thread)'
          onclick="openChatContact(this)">
          <span class="chat-avatar">{{ substr($user['nama'], 0, 1) }}</span>
          <span class="chat-contact-main">
            <span class="chat-contact-top">
              <strong>{{ $user['nama'] }}</strong>
              <small>{{ $lastDate }}</small>
            </span>
            <span class="chat-preview">{{ $lastMessage }}</span>
            <span class="chat-meta">{{ $user['nim'] }} - {{ $user['role'] }}</span>
          </span>
          @if($hasUnread)<span class="chat-unread-dot"></span>@endif
        </button>
      @empty
        <div class="chat-empty" style="margin:1rem;">Belum ada civitas yang mengirim chat ke admin.</div>
      @endforelse
    </div>
  </aside>

  <section class="chat-room">
    @if($activeUser)
    <div class="chat-room-head">
      <div class="chat-avatar" id="roomAvatar">{{ substr($activeUser['nama'], 0, 1) }}</div>
      <div>
        <h3 id="roomName">{{ $activeUser['nama'] }}</h3>
        <p id="roomMeta">{{ $activeUser['nim'] }} - {{ $activeUser['role'] }}</p>
      </div>
    </div>

    <div class="chat-messages" id="chatMessages"></div>

    <form method="POST" action="{{ route('chat.send') }}" class="chat-composer" id="chatComposer" data-action="{{ route('chat.send') }}">
      @csrf
      <input type="hidden" name="penerima" id="penerimaInput" value="{{ $activeUser['nama'] }}">
      <input type="hidden" id="receiverIdInput" value="{{ $activeUser['nim'] }}">
      <input type="text" name="isi" id="chatInput" placeholder="Ketik pesan ke civitas..." autocomplete="off" required>
      <button class="btn btn-primary" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Kirim
      </button>
    </form>
    @else
    <div class="chat-empty">Belum ada chat masuk. Percakapan akan muncul setelah civitas mengirim pesan ke admin.</div>
    @endif
  </section>
</div>
@endsection

@push('scripts')
<script>
let activeThread = @json($activeThread);

function escapeHtml(text) {
  return String(text).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

function filterChatUsers() {
  const q = document.getElementById('chatSearch').value.toLowerCase();
  document.querySelectorAll('.chat-contact').forEach(item => {
    item.style.display = item.dataset.name.includes(q) || item.dataset.nim.includes(q) ? 'flex' : 'none';
  });
}

window.onTopbarSearch = function(value) {
  const input = document.getElementById('chatSearch');
  input.value = value;
  filterChatUsers();
}

function renderMessages(thread) {
  const wrap = document.getElementById('chatMessages');
  const messages = thread?.messages || [];
  if (messages.length === 0) {
    wrap.innerHTML = '<div class="chat-empty">Belum ada pesan. Admin bisa mulai chat dari kolom bawah.</div>';
    return;
  }

  wrap.innerHTML = messages.map(message => {
    const cls = message.sender_role === 'admin' ? 'bubble bubble-admin' : 'bubble bubble-user';
    return `<div class="${cls}"><p>${escapeHtml(message.body)}</p><span>${escapeHtml(message.sender_name || '')}</span></div>`;
  }).join('');
  wrap.scrollTop = wrap.scrollHeight;
}

function openChatContact(button) {
  const user = JSON.parse(button.dataset.user);
  activeThread = button.dataset.thread && button.dataset.thread !== 'null' ? JSON.parse(button.dataset.thread) : null;
  document.querySelectorAll('.chat-contact').forEach(item => item.classList.remove('active'));
  button.classList.add('active');
  button.querySelector('.chat-unread-dot')?.remove();
  document.getElementById('roomAvatar').textContent = user.nama.charAt(0);
  document.getElementById('roomName').textContent = user.nama;
  document.getElementById('roomMeta').textContent = `${user.nim} - ${user.role}`;
  document.getElementById('penerimaInput').value = user.nama;
  document.getElementById('receiverIdInput').value = user.nim;
  document.getElementById('chatInput').focus();
  renderMessages(activeThread);

  fetch(`/chat/thread/${encodeURIComponent(user.nim)}/read`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json'
    }
  }).then(r => r.json()).then(data => {
    const badge = document.querySelector('.notif-count');
    if (badge) {
      if (data.unread > 0) badge.textContent = data.unread;
      else badge.remove();
    }
    const sidebarBadge = document.querySelector('.sidebar-badge');
    if (sidebarBadge) {
      if (data.unread > 0) sidebarBadge.textContent = data.unread;
      else sidebarBadge.remove();
    }
  }).catch(() => {});
}

const chatComposer = document.getElementById('chatComposer');
if (chatComposer) chatComposer.addEventListener('submit', function(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const input = document.getElementById('chatInput');
  const receiver = document.getElementById('penerimaInput').value;
  const receiverId = document.getElementById('receiverIdInput').value;
  const text = input.value.trim();
  if (!text) return;
  const adminId = @json(session('auth_username', 'admin1'));
  const adminName = @json(session('auth_name', 'Admin Kampus'));
  const campusKey = @json(session('auth_kode_kampus') ?: session('auth_kampus') ?: session('auth_username', 'admin1'));

  const message = {
    sender_id: adminId,
    sender_name: adminName,
    sender_role: 'admin',
    admin_username: adminId,
    campus_key: campusKey,
    receiver_id: receiverId,
    receiver_identifier: receiverId,
    receiver_name: receiver,
    body: text
  };

  activeThread = activeThread || { participant_id: receiverId, messages: [] };
  activeThread.messages.push(message);
  renderMessages(activeThread);
  input.value = '';

  fetch(form.dataset.action, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json'
    },
    body: JSON.stringify(message)
  }).then(r => {
    if (!r.ok) throw new Error('Gagal mengirim pesan');
    return r.json();
  }).then(data => {
    const savedMessage = data.message || data.chat_message;
    if (savedMessage) {
      activeThread = activeThread || { participant_id: receiverId, messages: [] };
      activeThread.messages = activeThread.messages.filter(item => item.id || item.body !== text || item.sender_role !== 'admin');
      activeThread.messages.push(savedMessage);
      activeThread.last_message = savedMessage.body;
      activeThread.last_at = savedMessage.created_at;
      renderMessages(activeThread);
    }
  }).catch(() => {
    activeThread.messages = activeThread.messages.filter(item => item.id || item.body !== text || item.sender_role !== 'admin');
    renderMessages(activeThread);
    alert('Pesan gagal dikirim. Coba refresh halaman lalu kirim ulang.');
  });
});

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const nim = urlParams.get('nim');
  let selected = null;
  if (nim) {
    selected = document.querySelector(`.chat-contact[data-nim="${nim.toLowerCase()}"]`);
  }
  const first = selected || document.querySelector('.chat-contact.active') || document.querySelector('.chat-contact');
  if (first) openChatContact(first);
});

setInterval(function() {
  const receiverInput = document.getElementById('receiverIdInput');
  if (!receiverInput) return;
  const receiverId = receiverInput.value;
  if (!receiverId) return;
  fetch(`/chat/thread/${encodeURIComponent(receiverId)}`, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(data => {
      if (!data.thread) return;
      activeThread = data.thread;
      renderMessages(activeThread);
    })
    .catch(() => {});
}, 5000);
</script>
@endpush
