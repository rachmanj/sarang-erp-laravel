@push('styles')
    <style>
        #helpAssistantModal .modal-body {
            min-height: 280px;
            overflow-x: hidden;
        }

        /* Single scroll container: do not use modal-dialog-scrollable (nested scroll breaks wheel/touch) */
        #help-answer {
            display: block;
            max-height: min(420px, 55vh);
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
            position: relative;
            border-radius: 0.35rem;
            border: 1px solid #dee2e6;
            border-left: 4px solid #17a2b8;
            background: #fff;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        #help-answer:focus {
            outline: 0;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04), 0 0 0 2px rgba(23, 162, 184, 0.35);
        }

        .help-answer-inner {
            padding: 1rem 1.1rem;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #2c3e50;
        }

        .help-answer-inner .help-answer-p {
            margin-bottom: 0.85rem;
        }

        .help-answer-inner .help-answer-p:last-child {
            margin-bottom: 0;
        }

        .help-answer-inner .help-answer-ol,
        .help-answer-inner .help-answer-ul {
            margin: 0 0 0.85rem 0;
            padding-left: 1.35rem;
        }

        .help-answer-inner .help-answer-ol li,
        .help-answer-inner .help-answer-ul li {
            margin-bottom: 0.4rem;
        }

        .help-answer-inner .help-answer-ol li:last-child,
        .help-answer-inner .help-answer-ul li:last-child {
            margin-bottom: 0;
        }

        .help-answer-inner strong {
            color: #1a252f;
            font-weight: 600;
        }

        .help-answer-sources-inline {
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px dashed #ced4da;
            font-size: 0.88rem;
            color: #5a6c7d;
        }

        .help-answer-sources-inline .help-answer-sources-label {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 0.35rem;
        }

        #help-sources {
            padding: 0.5rem 0 0;
        }

        #help-sources .badge {
            font-weight: 500;
        }
    </style>
@endpush

<div class="modal fade" id="helpAssistantModal" tabindex="-1" role="dialog" aria-labelledby="helpAssistantModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white" id="helpAssistantModalLabel">
                    <i class="fas fa-question-circle mr-2"></i>Help — Sarang ERP
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="helpTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="help-howto-tab" data-toggle="tab" href="#help-howto" role="tab"
                            aria-controls="help-howto" aria-selected="true">How-to</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="help-feedback-tab" data-toggle="tab" href="#help-feedback" role="tab"
                            aria-controls="help-feedback" aria-selected="false">Report / request</a>
                    </li>
                </ul>
                <div class="tab-content pt-3" id="helpTabContent">
                    <div class="tab-pane fade show active" id="help-howto" role="tabpanel"
                        aria-labelledby="help-howto-tab">
                        <div class="form-row mb-2">
                            <div class="col-md-4 mb-2">
                                <label for="help-locale" class="small text-muted mb-0">Answer language</label>
                                <select id="help-locale" class="form-control form-control-sm">
                                    <option value="auto">Auto (from app)</option>
                                    <option value="en">English</option>
                                    <option value="id">Bahasa Indonesia</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="help-question" class="small text-muted mb-0">Your question</label>
                            <textarea id="help-question" class="form-control" rows="3"
                                placeholder="e.g. How do I transfer stock between warehouses?"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="help-send">
                            <i class="fas fa-paper-plane mr-1"></i>Ask
                        </button>
                        <div id="help-loading" class="text-muted small mt-2 d-none">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Thinking…
                        </div>
                        <div class="mt-3 border-top pt-3">
                            <div id="help-answer-wrap" class="d-none">
                                <div class="small text-muted mb-1 font-weight-bold">Answer</div>
                                <div id="help-answer" class="border-0 p-0 bg-transparent" tabindex="0"
                                    role="region" aria-label="Help answer">
                                    <div id="help-answer-inner" class="help-answer-inner" aria-live="polite"></div>
                                </div>
                                <div id="help-sources" class="mt-2 small"></div>
                            </div>
                            <div id="help-error" class="alert alert-warning d-none mt-2 mb-0"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="help-feedback" role="tabpanel" aria-labelledby="help-feedback-tab">
                        <p class="small text-muted">Submit bugs or feature ideas. This is stored for triage only—not a formal SLA.
                        </p>
                        <div class="form-group">
                            <label for="fb-type">Type</label>
                            <select id="fb-type" class="form-control form-control-sm">
                                <option value="bug">Bug</option>
                                <option value="feature">Feature request</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fb-title">Title</label>
                            <input type="text" id="fb-title" class="form-control form-control-sm" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="fb-body">Description</label>
                            <textarea id="fb-body" class="form-control form-control-sm" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="fb-steps">Steps to reproduce (bugs)</label>
                            <textarea id="fb-steps" class="form-control form-control-sm" rows="3"
                                placeholder="Optional"></textarea>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="fb-send">
                            <i class="fas fa-send mr-1"></i>Send
                        </button>
                        <div id="fb-msg" class="small mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const askUrl = @json(route('help.ask'));
            const feedbackUrl = @json(route('help.feedback'));

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
                if (nonEmpty.length === 0) {
                    return '';
                }
                const allNumbered = nonEmpty.every(function(l) {
                    return /^\s*\d+\.\s/.test(l);
                });
                if (allNumbered) {
                    return '<ol class="help-answer-ol">' + nonEmpty.map(function(l) {
                        const m = l.match(/^\s*\d+\.\s+(.*)$/);
                        return '<li>' + formatInline(m ? m[1] : l) + '</li>';
                    }).join('') + '</ol>';
                }
                const allBullet = nonEmpty.every(function(l) {
                    return /^\s*[-*]\s/.test(l);
                });
                if (allBullet) {
                    return '<ul class="help-answer-ul">' + nonEmpty.map(function(l) {
                        const m = l.match(/^\s*[-*]\s+(.*)$/);
                        return '<li>' + formatInline(m ? m[1] : l) + '</li>';
                    }).join('') + '</ul>';
                }
                return '<p class="help-answer-p">' + lines.map(function(line) {
                    return formatInline(line);
                }).join('<br>') + '</p>';
            }

            function formatHelpAnswerHtml(raw) {
                if (!raw || !String(raw).trim()) {
                    return '';
                }
                let text = String(raw).trim();
                let sourcesSuffix = '';
                const sourcesMatch = text.match(/\n(?:Sources|Source):\s*([\s\S]*)$/i);
                if (sourcesMatch) {
                    text = text.slice(0, sourcesMatch.index).trim();
                    sourcesSuffix = '<div class="help-answer-sources-inline"><span class="help-answer-sources-label">Sources</span><div>' +
                        formatInline(sourcesMatch[1].trim().replace(/\n/g, ' ')) + '</div></div>';
                }
                const blocks = text.split(/\n\n+/);
                const body = blocks.map(function(b) {
                    return formatBlock(b);
                }).join('');
                return body + sourcesSuffix;
            }

            function showError(el, msg) {
                el.textContent = msg;
                el.classList.remove('d-none');
            }

            document.getElementById('help-send')?.addEventListener('click', async function() {
                const q = document.getElementById('help-question')?.value?.trim();
                const locale = document.getElementById('help-locale')?.value || 'auto';
                const loading = document.getElementById('help-loading');
                const err = document.getElementById('help-error');
                const wrap = document.getElementById('help-answer-wrap');
                const ansInner = document.getElementById('help-answer-inner');
                const src = document.getElementById('help-sources');

                err.classList.add('d-none');
                wrap.classList.add('d-none');
                if (!q) {
                    showError(err, 'Please enter a question.');
                    return;
                }

                loading.classList.remove('d-none');
                try {
                    const res = await fetch(askUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            message: q,
                            locale: locale
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showError(err, data.message || 'Help request failed.');
                        return;
                    }
                    if (ansInner) {
                        ansInner.innerHTML = formatHelpAnswerHtml(data.answer || '');
                    }
                    src.innerHTML = '';
                    if (Array.isArray(data.sources) && data.sources.length) {
                        const label = document.createElement('span');
                        label.className = 'text-muted mr-1';
                        label.textContent = 'Sources:';
                        src.appendChild(label);
                        data.sources.forEach(function(s) {
                            const b = document.createElement('span');
                            b.className = 'badge badge-secondary mr-1 mb-1';
                            b.textContent = (s.heading ? s.heading + ' — ' : '') + (s.title || s.path);
                            src.appendChild(b);
                        });
                    }
                    wrap.classList.remove('d-none');
                    const answerBox = document.getElementById('help-answer');
                    if (answerBox) {
                        answerBox.scrollTop = 0;
                        try {
                            answerBox.focus({ preventScroll: true });
                        } catch (e) {}
                    }
                } catch (e) {
                    showError(err, 'Network error. Try again.');
                } finally {
                    loading.classList.add('d-none');
                }
            });

            document.getElementById('fb-send')?.addEventListener('click', async function() {
                const type = document.getElementById('fb-type')?.value;
                const title = document.getElementById('fb-title')?.value?.trim();
                const body = document.getElementById('fb-body')?.value?.trim();
                const steps = document.getElementById('fb-steps')?.value?.trim();
                const msg = document.getElementById('fb-msg');
                msg.textContent = '';
                msg.className = 'small mt-2';

                if (!title || !body) {
                    msg.className = 'small mt-2 text-danger';
                    msg.textContent = 'Title and description are required.';
                    return;
                }

                try {
                    const res = await fetch(feedbackUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            type: type,
                            title: title,
                            body: body,
                            steps_to_reproduce: steps || null
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        msg.className = 'small mt-2 text-danger';
                        msg.textContent = data.message || 'Could not submit.';
                        return;
                    }
                    msg.className = 'small mt-2 text-success';
                    msg.textContent = 'Thank you. Your feedback was recorded.';
                    document.getElementById('fb-title').value = '';
                    document.getElementById('fb-body').value = '';
                    document.getElementById('fb-steps').value = '';
                } catch (e) {
                    msg.className = 'small mt-2 text-danger';
                    msg.textContent = 'Network error. Try again.';
                }
            });
        })();
    </script>
@endpush
