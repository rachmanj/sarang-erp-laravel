@extends('layouts.main')

@section('title_page', 'Domain Assistant')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .assistant-terminal {
            font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
            background: #0d1117;
            color: #00ff41;
            border: 1px solid #1a3a2a;
            border-radius: 0.35rem;
            min-height: calc(100vh - 200px);
            position: relative;
            overflow: hidden;
        }

        .assistant-terminal::before {
            content: '';
            pointer-events: none;
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(0deg,
                    rgba(0, 255, 65, 0.03),
                    rgba(0, 255, 65, 0.03) 2px,
                    transparent 2px,
                    transparent 4px);
            z-index: 0;
        }

        .assistant-terminal-inner {
            position: relative;
            z-index: 1;
        }

        .assistant-dim {
            color: #4a7c59;
        }

        .assistant-cyan {
            color: #00d4ff;
        }

        .assistant-amber {
            color: #ff9500;
            font-size: 0.85rem;
        }

        .assistant-err {
            color: #ff453a;
        }

        .assistant-thread-list {
            border-right: 1px solid #1a3a2a;
            min-height: 520px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .assistant-thread-item {
            cursor: pointer;
            padding: 0.5rem 0.65rem;
            border-bottom: 1px solid #1a3a2a;
            color: #4a7c59;
            position: relative;
        }

        .assistant-thread-item:hover {
            color: #00ff41;
            background: rgba(0, 255, 65, 0.06);
        }

        .assistant-thread-item.active {
            color: #00ff41;
            background: rgba(0, 255, 65, 0.1);
        }

        .assistant-thread-item .del-thread {
            display: none;
            position: absolute;
            right: 0.35rem;
            top: 0.35rem;
            color: #ff453a;
            cursor: pointer;
            padding: 0 0.25rem;
        }

        .assistant-thread-item:hover .del-thread {
            display: inline;
        }

        .assistant-chat-scroll {
            min-height: 360px;
            max-height: calc(100vh - 340px);
            overflow-y: auto;
            padding: 0.75rem;
        }

        .assistant-msg-user {
            text-align: right;
            margin-bottom: 1rem;
        }

        .assistant-msg-user-inner {
            display: inline-block;
            text-align: left;
            max-width: 92%;
            border-left: 3px solid #00d4ff;
            padding: 0.5rem 0.75rem;
            background: rgba(0, 212, 255, 0.06);
        }

        .assistant-msg-assistant {
            margin-bottom: 1rem;
        }

        .assistant-msg-assistant-hdr {
            border-bottom: 1px dashed #1a3a2a;
            margin-bottom: 0.35rem;
            padding-bottom: 0.25rem;
        }

        .assistant-input-wrap {
            border-top: 1px solid #1a3a2a;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.35);
        }

        .assistant-input-wrap textarea {
            background: #0d1117 !important;
            color: #00ff41 !important;
            border: 1px solid #1a3a2a !important;
            caret-color: #00ff41;
        }

        .assistant-input-wrap textarea:focus {
            box-shadow: 0 0 0 2px rgba(0, 255, 65, 0.25) !important;
            border-color: #1a3a2a !important;
        }

        .assistant-btn-exec {
            font-family: inherit;
            background: transparent;
            color: #00ff41;
            border: 1px solid #1a3a2a;
            padding: 0.35rem 0.65rem;
            cursor: pointer;
        }

        .assistant-btn-exec:hover {
            background: rgba(0, 255, 65, 0.08);
        }

        .assistant-btn-new {
            font-family: inherit;
            background: transparent;
            color: #00ff41;
            border: 1px solid #1a3a2a;
            padding: 0.35rem 0.65rem;
            width: 100%;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }

        .assistant-toggle-all {
            font-family: inherit;
            background: transparent;
            border: none;
            color: #4a7c59;
            cursor: pointer;
            padding: 0;
        }

        .assistant-toggle-all.on {
            color: #00ff41;
        }

        .cursor-blink::after {
            content: '_';
            animation: assistant-blink 1s step-end infinite;
        }

        @keyframes assistant-blink {
            50% {
                opacity: 0;
            }
        }

        .assistant-tool-block {
            margin: 0.35rem 0 0.75rem;
            padding-left: 0.5rem;
            border-left: 2px solid #ff9500;
        }

        .assistant-tool-block.collapsed .assistant-tool-detail {
            display: none;
        }

        .assistant-tool-toggle {
            cursor: pointer;
            user-select: none;
        }
    </style>
@endpush

@section('content')
    <div class="assistant-terminal mb-3">
        <div class="assistant-terminal-inner row no-gutters">
            <div class="col-md-3 assistant-thread-list p-2">
                <div class="assistant-dim small mb-2">SARANG-ERP ASSISTANT v1.0</div>
                <button type="button" class="assistant-btn-new" id="assistant-new-session">[+] NEW SESSION</button>
                <div id="assistant-thread-list"></div>
            </div>
            <div class="col-md-9">
                <div class="p-2 border-bottom border-secondary" style="border-color:#1a3a2a!important;">
                    <span class="assistant-dim small">SESSION</span>
                    @if ($canShowAllRecords)
                        <button type="button" class="assistant-toggle-all" id="assistant-toggle-all"
                            data-on="0">[ALL BRANCHES: OFF]</button>
                    @endif
                </div>
                <div class="assistant-chat-scroll" id="assistant-chat" tabindex="0" aria-live="polite"></div>
                <div class="assistant-input-wrap">
                    <div class="d-flex align-items-start">
                        <span class="assistant-dim small mr-2 pt-1">[sarang-erp:assistant]&gt;</span>
                        <textarea id="assistant-input" class="form-control form-control-sm flex-grow-1" rows="2"
                            placeholder="Query ERP data…"></textarea>
                        <button type="button" class="assistant-btn-exec ml-2" id="assistant-send">[▶ EXECUTE]</button>
                    </div>
                    <div class="assistant-dim small mt-1">Shift+Enter newline · Enter send</div>
                    <div id="assistant-error" class="assistant-err small mt-2 d-none"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const routes = {
                conversations: @json(route('assistant.conversations.index')),
                create: @json(route('assistant.conversations.store')),
                messages: (id) => @json(url('/assistant/conversations')) + '/' + id + '/messages',
                select: (id) => @json(url('/assistant/conversations')) + '/' + id + '/select',
                destroy: (id) => @json(url('/assistant/conversations')) + '/' + id,
                chat: @json(route('assistant.chat')),
            };
            const canShowAll = @json($canShowAllRecords);

            let activeId = null;
            let showAllRecords = false;

            const chatEl = document.getElementById('assistant-chat');
            const inputEl = document.getElementById('assistant-input');
            const errEl = document.getElementById('assistant-error');

            function escapeHtml(s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function formatInline(s) {
                const parts = String(s).split(/\*\*/);
                return parts.map(function(p, i) {
                    return i % 2 === 1 ? '<strong>' + escapeHtml(p) + '</strong>' : escapeHtml(p);
                }).join('');
            }

            function formatBlock(block) {
                const lines = block.split('\n');
                const nonEmpty = lines.filter(function(l) {
                    return l.trim().length > 0;
                });
                if (nonEmpty.length === 0) return '';
                const allNumbered = nonEmpty.every(function(l) {
                    return /^\s*\d+\.\s/.test(l);
                });
                if (allNumbered) {
                    return '<ol class="pl-3 mb-2">' + nonEmpty.map(function(l) {
                        const m = l.match(/^\s*\d+\.\s+(.*)$/);
                        return '<li>' + formatInline(m ? m[1] : l) + '</li>';
                    }).join('') + '</ol>';
                }
                const allBullet = nonEmpty.every(function(l) {
                    return /^\s*[-*]\s/.test(l);
                });
                if (allBullet) {
                    return '<ul class="pl-3 mb-2">' + nonEmpty.map(function(l) {
                        const m = l.match(/^\s*[-*]\s+(.*)$/);
                        return '<li>' + formatInline(m ? m[1] : l) + '</li>';
                    }).join('') + '</ul>';
                }
                return '<p class="mb-2">' + lines.map(function(line) {
                    return formatInline(line);
                }).join('<br>') + '</p>';
            }

            function formatAnswerHtml(raw) {
                if (!raw || !String(raw).trim()) return '';
                const blocks = String(raw).trim().split(/\n\n+/);
                return blocks.map(function(b) {
                    return formatBlock(b);
                }).join('');
            }

            function showErr(msg) {
                errEl.textContent = msg;
                errEl.classList.remove('d-none');
            }

            function clearErr() {
                errEl.textContent = '';
                errEl.classList.add('d-none');
            }

            async function api(url, opts = {}) {
                const headers = Object.assign({
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                }, opts.headers || {});
                if (opts.body && !(opts.body instanceof FormData) && !headers['Content-Type']) {
                    headers['Content-Type'] = 'application/json';
                }
                return fetch(url, Object.assign({}, opts, {
                    headers
                }));
            }

            function renderThreads(rows, active) {
                const wrap = document.getElementById('assistant-thread-list');
                wrap.innerHTML = '';
                rows.forEach(function(r) {
                    const div = document.createElement('div');
                    div.className = 'assistant-thread-item' + (r.id === active ? ' active' : '');
                    div.dataset.id = r.id;
                    const title = r.title || ('Session #' + r.id);
                    const when = r.updated_at || r.created_at;
                    div.innerHTML =
                        '<div><span class="assistant-cyan">' + (r.id === active ? '&gt; ' : '&nbsp; ') +
                        '</span>' + escapeHtml(title) + '</div>' +
                        '<div class="small assistant-dim">' + escapeHtml(when || '') + '</div>' +
                        '<span class="del-thread" title="Delete">&times;</span>';
                    div.addEventListener('click', function(e) {
                        if (e.target.classList.contains('del-thread')) {
                            e.stopPropagation();
                            deleteThread(r.id);
                            return;
                        }
                        selectThread(r.id);
                    });
                    wrap.appendChild(div);
                });
            }

            async function loadThreads() {
                const res = await api(routes.conversations);
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showErr(data.message || 'Could not load sessions.');
                    return;
                }
                activeId = data.active_id;
                let list = data.conversations || [];
                if (list.length === 0) {
                    const c = await api(routes.create, {
                        method: 'POST'
                    });
                    const created = await c.json().catch(() => ({}));
                    if (!c.ok) {
                        showErr(created.message || 'Could not create session.');
                        return;
                    }
                    activeId = created.id;
                    list = [created];
                }
                renderThreads(list, activeId);
                await loadMessages(activeId);
            }

            async function selectThread(id) {
                clearErr();
                const res = await api(routes.select(id), {
                    method: 'PATCH'
                });
                if (!res.ok) return;
                activeId = id;
                const data = await api(routes.conversations).then(r => r.json());
                renderThreads(data.conversations || [], activeId);
                await loadMessages(id);
            }

            async function deleteThread(id) {
                if (!confirm('Delete this session?')) return;
                clearErr();
                const res = await api(routes.destroy(id), {
                    method: 'DELETE'
                });
                if (!res.ok) return;
                await loadThreads();
            }

            async function loadMessages(id) {
                chatEl.innerHTML = '';
                if (!id) return;
                const res = await api(routes.messages(id));
                const data = await res.json().catch(() => ({}));
                if (!res.ok) return;
                (data.messages || []).forEach(function(m) {
                    appendMessage(m.role, m.content, [], false);
                });
                chatEl.scrollTop = chatEl.scrollHeight;
            }

            function appendMessage(role, content, toolTraces, scroll) {
                const wrap = document.createElement('div');
                if (role === 'user') {
                    wrap.className = 'assistant-msg-user';
                    const userBody = escapeHtml(content).replace(/\n/g, '<br>');
                    wrap.innerHTML = '<div class="assistant-msg-user-inner">' +
                        '<span class="assistant-cyan">[USER@sarang ~]$</span><br>' +
                        userBody + '</div>';
                } else {
                    wrap.className = 'assistant-msg-assistant';
                    let toolsHtml = '';
                    if (toolTraces && toolTraces.length) {
                        toolsHtml = '<div class="assistant-tool-block" data-collapsed="0">';
                        toolTraces.forEach(function(t, idx) {
                            const args = JSON.stringify(t.arguments || {});
                            toolsHtml += '<div class="assistant-tool-toggle assistant-amber" data-idx="' + idx +
                                '">&gt; ' + escapeHtml(t.name) + ' ✓</div>' +
                                '<div class="assistant-tool-detail assistant-amber">' +
                                '&gt; parameters: ' + escapeHtml(args) + '<br>' +
                                '&gt; ' + escapeHtml(t.result_summary || '') + '</div>';
                        });
                        toolsHtml += '</div>';
                    }
                    wrap.innerHTML = '<div class="assistant-msg-assistant-hdr assistant-dim small">[ASSISTANT]</div>' +
                        toolsHtml +
                        '<div class="text-assistant-body">' + formatAnswerHtml(content) + '</div>';
                    wrap.querySelectorAll('.assistant-tool-block').forEach(function(block) {
                        block.addEventListener('click', function() {
                            block.classList.toggle('collapsed');
                        });
                    });
                }
                chatEl.appendChild(wrap);
                if (scroll !== false) chatEl.scrollTop = chatEl.scrollHeight;
            }

            function showLoading() {
                const el = document.createElement('div');
                el.className = 'assistant-msg-assistant';
                el.id = 'assistant-loading';
                const msgs = ['> connecting to ERP...', '> querying database...', '> processing...'];
                let i = 0;
                el.innerHTML = '<div class="assistant-amber" id="assistant-loading-text">' + msgs[0] +
                    '<span class="cursor-blink"></span></div>';
                chatEl.appendChild(el);
                chatEl.scrollTop = chatEl.scrollHeight;
                const t = setInterval(function() {
                    i = (i + 1) % msgs.length;
                    const te = document.getElementById('assistant-loading-text');
                    if (te) te.innerHTML = msgs[i] + '<span class="cursor-blink"></span>';
                }, 700);
                el._timer = t;
            }

            function removeLoading() {
                const el = document.getElementById('assistant-loading');
                if (el && el._timer) clearInterval(el._timer);
                if (el) el.remove();
            }

            async function send() {
                const text = inputEl.value.trim();
                if (!text) return;
                clearErr();
                inputEl.value = '';
                appendMessage('user', text, [], true);

                const body = {
                    message: text,
                    conversation_id: activeId
                };
                if (canShowAll) body.show_all_records = showAllRecords;

                showLoading();
                try {
                    const res = await api(routes.chat, {
                        method: 'POST',
                        body: JSON.stringify(body)
                    });
                    const data = await res.json().catch(() => ({}));
                    removeLoading();
                    if (!res.ok) {
                        showErr(data.message || 'Request failed.');
                        return;
                    }
                    if (data.conversation_id) activeId = data.conversation_id;
                    appendMessage('assistant', data.answer || '', data.tool_traces || [], true);
                    const listRes = await api(routes.conversations);
                    const listData = await listRes.json();
                    if (listRes.ok) renderThreads(listData.conversations || [], activeId);
                } catch (e) {
                    removeLoading();
                    showErr('Network error.');
                }
            }

            document.getElementById('assistant-new-session').addEventListener('click', async function() {
                clearErr();
                const c = await api(routes.create, {
                    method: 'POST'
                });
                const created = await c.json().catch(() => ({}));
                if (!c.ok) {
                    showErr(created.message || 'Could not create session.');
                    return;
                }
                activeId = created.id;
                await loadThreads();
            });

            document.getElementById('assistant-send').addEventListener('click', send);
            inputEl.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    send();
                }
            });

            const toggleAll = document.getElementById('assistant-toggle-all');
            if (toggleAll) {
                toggleAll.addEventListener('click', function() {
                    showAllRecords = !showAllRecords;
                    toggleAll.dataset.on = showAllRecords ? '1' : '0';
                    toggleAll.textContent = showAllRecords ? '[ALL BRANCHES: ON]' : '[ALL BRANCHES: OFF]';
                    toggleAll.classList.toggle('on', showAllRecords);
                });
            }

            loadThreads();
        })();
    </script>
@endpush
