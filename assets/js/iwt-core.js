/**
 * iWorks Tools core admin JS (vanilla).
 */
(function () {
	'use strict';

	if (typeof window.iwtCore === 'undefined') {
		return;
	}

	var cfg = window.iwtCore;
	var strings = cfg.strings || {};

	function post(action, data) {
		var body = new window.FormData();
		body.append('action', action);
		body.append('nonce', data.nonce || '');
		Object.keys(data).forEach(function (key) {
			if (key === 'nonce') {
				return;
			}
			var value = data[key];
			if (value !== null && typeof value === 'object') {
				body.append(key, JSON.stringify(value));
			} else {
				body.append(key, value);
			}
		});
		return window.fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (res) {
			return res.json();
		});
	}

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function badgeHtml(status) {
		var label = strings[status] || status;
		return (
			'<span class="iwt-badge iwt-badge--' +
			status +
			'"><span class="screen-reader-text">' +
			label +
			': </span>' +
			label +
			'</span>'
		);
	}

	var resultUid = 0;

	function cardHtml(result, open) {
		resultUid += 1;
		var status = result.status || 'info';
		var panelId = 'iwt-result-js-' + resultUid;
		var expanded = open ? 'true' : 'false';
		var hidden = open ? '' : ' hidden';
		var html =
			'<article class="iwt-result iwt-result--' +
			status +
			'" data-status="' +
			status +
			'">';
		html += '<h3 class="iwt-result__heading">';
		html +=
			'<button type="button" class="iwt-result__toggle" aria-expanded="' +
			expanded +
			'" aria-controls="' +
			panelId +
			'">';
		html += '<span class="iwt-result__title">' + escapeHtml(result.label || '') + '</span>';
		html += '<span class="iwt-result__meta">' + badgeHtml(status);
		html += '<span class="iwt-result__chevron" aria-hidden="true"></span></span></button></h3>';
		html += '<div class="iwt-result__body" id="' + panelId + '"' + hidden + '>';
		html += '<p>' + escapeHtml(result.description || '') + '</p>';
		html += matchesHtml(result.data || {});
		if (result.why) {
			html +=
				'<div class="iwt-result__section"><h4>' +
				escapeHtml(strings.why || 'Why it matters') +
				'</h4><p>' +
				escapeHtml(result.why) +
				'</p></div>';
		}
		if (result.fix) {
			html +=
				'<div class="iwt-result__section"><h4>' +
				escapeHtml(strings.fix || 'How to fix') +
				'</h4><p>' +
				escapeHtml(result.fix) +
				'</p></div>';
		}
		html += '</div></article>';
		return html;
	}

	function matchesHtml(data) {
		var matches = data.matches || data.files || [];
		if (!matches.length) {
			return '';
		}
		var html = '<ul class="iwt-result__matches">';
		var shown = 0;
		matches.forEach(function (match) {
			if (shown >= 10) {
				return;
			}
			var path = '';
			var size = '';
			var kind = '';
			if (typeof match === 'string') {
				path = match;
			} else if (match && typeof match === 'object') {
				path = match.relative || match.path || '';
				size = match.size || '';
				kind = match.kind || '';
			}
			if (!path) {
				return;
			}
			var label = path;
			if (kind) {
				label = kind + ': ' + path;
			}
			if (size) {
				label += ' (' + size + ')';
			}
			html += '<li>' + escapeHtml(label) + '</li>';
			shown += 1;
		});
		html += '</ul>';
		var more = matches.length - shown;
		if (more > 0) {
			html +=
				'<p class="iwt-result__matches-more">' +
				escapeHtml((strings.andNMore || 'and {count} more').replace('{count}', String(more))) +
				'</p>';
		}
		if (data.scan_label) {
			html += '<p class="iwt-result__scan-source">' + escapeHtml(String(data.scan_label)) + '</p>';
		}
		return html;
	}

	function groupLabel(status, count) {
		var map = {
			critical: strings.groupCritical || 'Critical ({count})',
			recommended: strings.groupRecommended || 'Recommended ({count})',
			info: strings.groupInfo || 'Info ({count})',
			good: strings.passedChecks || 'Passed checks ({count})'
		};
		var template = map[status] || status + ' ({count})';
		return template.replace('{count}', String(count));
	}

	function renderGrouped(results) {
		var buckets = { critical: [], recommended: [], info: [], good: [] };
		results.forEach(function (result) {
			var status = result.status || 'info';
			if (!buckets[status]) {
				buckets[status] = [];
			}
			buckets[status].push(result);
		});
		var html = '';
		['critical', 'recommended', 'info', 'good'].forEach(function (status) {
			var items = buckets[status];
			if (!items.length) {
				return;
			}
			var collapsed = status === 'good';
			html +=
				'<section class="iwt-group iwt-group--' +
				status +
				(collapsed ? ' is-collapsed' : '') +
				'" data-group="' +
				status +
				'">';
			if (collapsed) {
				html +=
					'<button type="button" class="iwt-group__toggle" aria-expanded="false" data-iwt-group-toggle>' +
					escapeHtml(groupLabel(status, items.length)) +
					'</button>';
			} else {
				html += '<h2 class="iwt-group__heading">' + escapeHtml(groupLabel(status, items.length)) + '</h2>';
			}
			html += '<div class="iwt-group__results">';
			items.forEach(function (result) {
				html += cardHtml(result, status === 'critical');
			});
			html += '</div></section>';
		});
		return html;
	}

	function updateSummary(container) {
		var cards = container.querySelectorAll('.iwt-result');
		var good = 0;
		var recommended = 0;
		var critical = 0;
		var info = 0;
		cards.forEach(function (card) {
			var status = card.getAttribute('data-status');
			if (status === 'good') {
				good += 1;
			} else if (status === 'recommended') {
				recommended += 1;
			} else if (status === 'critical') {
				critical += 1;
			} else if (status === 'info') {
				info += 1;
			}
		});
		var total = good + recommended + critical;
		var score = total === 0 ? 100 : Math.round((good / total) * 100);
		var scoreClass = 'iwt-summary__score--critical';
		if (score >= 90) {
			scoreClass = 'iwt-summary__score--good';
		} else if (score >= 60) {
			scoreClass = 'iwt-summary__score--recommended';
		}
		var summary = document.querySelector('[data-iwt-summary]');
		if (!summary) {
			return;
		}
		summary.setAttribute('data-score', String(score));
		summary.innerHTML =
			'<div class="iwt-summary__score ' +
			scoreClass +
			'" style="--iwt-score:' +
			score +
			'" aria-hidden="true"><span class="iwt-summary__score-value">' +
			score +
			'</span></div><ul class="iwt-summary__counts">' +
			'<li><button type="button" class="iwt-summary__filter is-active" data-iwt-filter="all">' +
			escapeHtml(strings.filterAll || 'All') +
			'</button></li>' +
			'<li><button type="button" class="iwt-summary__filter" data-iwt-filter="critical"><span class="iwt-summary__count">' +
			critical +
			'</span> ' +
			escapeHtml(strings.critical || 'Critical') +
			'</button></li>' +
			'<li><button type="button" class="iwt-summary__filter" data-iwt-filter="recommended"><span class="iwt-summary__count">' +
			recommended +
			'</span> ' +
			escapeHtml(strings.recommended || 'Recommended') +
			'</button></li>' +
			'<li><button type="button" class="iwt-summary__filter" data-iwt-filter="good"><span class="iwt-summary__count">' +
			good +
			'</span> ' +
			escapeHtml(strings.good || 'Good') +
			'</button></li>' +
			(info
				? '<li><button type="button" class="iwt-summary__filter" data-iwt-filter="info"><span class="iwt-summary__count">' +
				  info +
				  '</span> ' +
				  escapeHtml(strings.info || 'Info') +
				  '</button></li>'
				: '') +
			'</ul>';
		bindFilters(summary);
	}

	function bindFilters(root) {
		var summary = root || document.querySelector('[data-iwt-summary]');
		if (!summary) {
			return;
		}
		summary.querySelectorAll('[data-iwt-filter]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var filter = btn.getAttribute('data-iwt-filter') || 'all';
				summary.querySelectorAll('[data-iwt-filter]').forEach(function (el) {
					el.classList.toggle('is-active', el === btn);
				});
				document.querySelectorAll('[data-iwt-results] .iwt-result').forEach(function (card) {
					var status = card.getAttribute('data-status');
					var show = filter === 'all' || status === filter;
					card.classList.toggle('is-filtered-out', !show);
				});
				document.querySelectorAll('[data-iwt-results] .iwt-group').forEach(function (group) {
					var visible = group.querySelectorAll('.iwt-result:not(.is-filtered-out)');
					group.classList.toggle('is-filtered-out', visible.length === 0);
				});
			});
		});
	}

	function bindAccordions(root) {
		(root || document).querySelectorAll('.iwt-result__toggle').forEach(function (btn) {
			if (btn.getAttribute('data-iwt-bound')) {
				return;
			}
			btn.setAttribute('data-iwt-bound', '1');
			btn.addEventListener('click', function () {
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				var panelId = btn.getAttribute('aria-controls');
				var panel = panelId ? document.getElementById(panelId) : null;
				btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				if (panel) {
					if (expanded) {
						panel.setAttribute('hidden', '');
					} else {
						panel.removeAttribute('hidden');
					}
				}
			});
		});
		(root || document).querySelectorAll('[data-iwt-group-toggle]').forEach(function (btn) {
			if (btn.getAttribute('data-iwt-bound')) {
				return;
			}
			btn.setAttribute('data-iwt-bound', '1');
			btn.addEventListener('click', function () {
				var group = btn.closest('.iwt-group');
				if (!group) {
					return;
				}
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				group.classList.toggle('is-collapsed', expanded);
			});
		});
	}

	function progressLabel(current, total) {
		var template = strings.checkingProgress || 'Checking {current} of {total}…';
		return template.replace('{current}', String(current)).replace('{total}', String(total));
	}

	function runChecks() {
		var list = document.querySelector('[data-iwt-results]');
		var button = document.querySelector('[data-iwt-run-checks], [data-iwiehc-run-checks]');
		var empty = document.querySelector('[data-iwt-empty]');
		var summaryWrap = document.querySelector('[data-iwt-summary-wrap]');
		if (!list || !cfg.checks || !cfg.checks.length) {
			return;
		}
		if (empty) {
			empty.hidden = true;
		}
		if (summaryWrap) {
			summaryWrap.hidden = false;
		}
		if (button) {
			button.disabled = true;
			button.innerHTML =
				'<span class="spinner is-active" aria-hidden="true"></span> ' +
				escapeHtml(strings.running || 'Running…');
		}
		list.innerHTML = '';
		list.setAttribute('aria-busy', 'true');
		var collected = [];
		var total = cfg.checks.length;
		var chain = Promise.resolve();
		cfg.checks.forEach(function (check, index) {
			chain = chain.then(function () {
				if (button) {
					button.innerHTML =
						'<span class="spinner is-active" aria-hidden="true"></span> ' +
						escapeHtml(progressLabel(index + 1, total));
				}
				return post(cfg.prefix + '_run_check', {
					nonce: cfg.checksNonce,
					check_id: check.id
				}).then(function (json) {
					var result = json && json.success ? json.data : {
						status: 'critical',
						label: check.label,
						description: 'Request failed'
					};
					collected.push(result);
					list.innerHTML = renderGrouped(collected);
					bindAccordions(list);
					updateSummary(list);
				});
			});
		});
		chain.finally(function () {
			list.removeAttribute('aria-busy');
			if (button) {
				button.disabled = false;
				button.textContent = button.getAttribute('data-label') || 'Run checks';
			}
			var reportEl = document.getElementById('iwt-report-source');
			if (reportEl) {
				reportEl.textContent = collected
					.map(function (result) {
						return '## ' + (result.label || '') + '\n' + (result.description || '') + '\n';
					})
					.join('\n');
			}
		});
	}

	function setProgress(id, progress, message) {
		var root = document.querySelector('[data-iwt-progress="' + id + '"]');
		if (!root) {
			return;
		}
		var bar = root.querySelector('.iwt-progress__bar');
		var track = root.querySelector('.iwt-progress__track');
		var msg = root.querySelector('.iwt-progress__message');
		var pct = root.querySelector('.iwt-progress__pct');
		var value = Math.max(0, Math.min(100, parseInt(progress, 10) || 0));
		if (bar) {
			bar.style.width = value + '%';
		}
		if (track) {
			track.setAttribute('aria-valuenow', String(value));
		}
		if (pct) {
			pct.textContent = value + '%';
		}
		if (msg) {
			msg.textContent = message || '';
		}
	}

	var jobRunning = false;
	var jobCancel = false;

	function stepJob(jobId, progressId) {
		if (jobCancel) {
			return post(cfg.prefix + '_job_cancel', {
				nonce: cfg.jobsNonce,
				job_id: jobId
			}).then(function (json) {
				jobRunning = false;
				var state = json.data || {};
				setProgress(progressId, state.progress || 0, state.message || strings.jobCancelled);
			});
		}
		return post(cfg.prefix + '_job_step', {
			nonce: cfg.jobsNonce,
			job_id: jobId
		}).then(function (json) {
			var state = (json && json.data) || {};
			setProgress(progressId, state.progress || 0, state.message || '');
			if (state.done || state.cancelled) {
				jobRunning = false;
				return state;
			}
			return stepJob(jobId, progressId);
		});
	}

	function startJob(jobId, progressId, resume) {
		if (jobRunning) {
			return;
		}
		jobRunning = true;
		jobCancel = false;
		var starter = resume
			? post(cfg.prefix + '_job_status', { nonce: cfg.jobsNonce, job_id: jobId }).then(function (json) {
					var state = json.data || {};
					if (state.exists && !state.done && !state.cancelled && !state.stale) {
						return state;
					}
					return post(cfg.prefix + '_job_start', {
						nonce: cfg.jobsNonce,
						job_id: jobId,
						params: {}
					}).then(function (r) {
						return r.data;
					});
			  })
			: post(cfg.prefix + '_job_start', {
					nonce: cfg.jobsNonce,
					job_id: jobId,
					params: {}
			  }).then(function (r) {
					return r.data;
			  });

		starter
			.then(function (state) {
				setProgress(progressId, (state && state.progress) || 0, (state && state.message) || '');
				if (state && state.done) {
					jobRunning = false;
					return state;
				}
				return stepJob(jobId, progressId);
			})
			.catch(function () {
				jobRunning = false;
			});
	}

	function bindCopy() {
		document.querySelectorAll('[data-iwt-copy]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = btn.getAttribute('data-iwt-copy');
				var el = document.getElementById(targetId);
				if (!el) {
					return;
				}
				var text = el.textContent || '';
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						btn.textContent = strings.copied || 'Copied';
						window.setTimeout(function () {
							btn.textContent = strings.copy || 'Copy';
						}, 1500);
					});
				}
			});
		});
	}

	function bindDownload() {
		var btn = document.querySelector('[data-iwt-download-report]');
		if (!btn) {
			return;
		}
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-url');
			if (!url) {
				return;
			}
			window.location.href = url;
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var runBtn = document.querySelector('[data-iwt-run-checks]');
		if (runBtn) {
			runBtn.setAttribute('data-label', runBtn.textContent);
			runBtn.addEventListener('click', runChecks);
		}
		var startBtn = document.querySelector('[data-iwt-job-start]');
		if (startBtn) {
			startBtn.addEventListener('click', function () {
				startJob(startBtn.getAttribute('data-job'), startBtn.getAttribute('data-progress') || 'job', false);
			});
		}
		var resumeBtn = document.querySelector('[data-iwt-job-resume]');
		if (resumeBtn) {
			resumeBtn.addEventListener('click', function () {
				startJob(resumeBtn.getAttribute('data-job'), resumeBtn.getAttribute('data-progress') || 'job', true);
			});
		}
		var cancelBtn = document.querySelector('[data-iwt-job-cancel]');
		if (cancelBtn) {
			cancelBtn.addEventListener('click', function () {
				jobCancel = true;
			});
		}
		bindCopy();
		bindDownload();
		bindAccordions(document);
		bindFilters();
	});

	window.iwtCoreUi = {
		runChecks: runChecks,
		cardHtml: cardHtml,
		renderGrouped: renderGrouped,
		updateSummary: updateSummary,
		bindAccordions: bindAccordions,
		setProgress: setProgress
	};
})();
