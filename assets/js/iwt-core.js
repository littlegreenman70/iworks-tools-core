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

	function cardHtml(result) {
		var html =
			'<article class="iwt-result iwt-result--' +
			result.status +
			'" data-status="' +
			result.status +
			'">';
		html += '<header class="iwt-result__header">';
		html += '<h3 class="iwt-result__title">' + escapeHtml(result.label || '') + '</h3>';
		html += badgeHtml(result.status || 'info');
		html += '</header><div class="iwt-result__body"><p>' + escapeHtml(result.description || '') + '</p>';
		if (result.why) {
			html +=
				'<details class="iwt-result__details"><summary>' +
				escapeHtml(strings.why || 'Why it matters') +
				'</summary><p>' +
				escapeHtml(result.why) +
				'</p></details>';
		}
		if (result.fix) {
			html +=
				'<details class="iwt-result__details"><summary>' +
				escapeHtml(strings.fix || 'How to fix') +
				'</summary><p>' +
				escapeHtml(result.fix) +
				'</p></details>';
		}
		html += '</div></article>';
		return html;
	}

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function updateSummary(container) {
		var cards = container.querySelectorAll('.iwt-result');
		var good = 0;
		var recommended = 0;
		var critical = 0;
		cards.forEach(function (card) {
			var status = card.getAttribute('data-status');
			if (status === 'good') {
				good += 1;
			} else if (status === 'recommended') {
				recommended += 1;
			} else if (status === 'critical') {
				critical += 1;
			}
		});
		var total = good + recommended + critical;
		var score = total === 0 ? 100 : Math.round((good / total) * 100);
		var scoreClass = 'iwt-summary__score--good';
		if (critical > 0) {
			scoreClass = 'iwt-summary__score--critical';
		} else if (recommended > 0) {
			scoreClass = 'iwt-summary__score--recommended';
		}
		var summary = document.querySelector('[data-iwt-summary]');
		if (!summary) {
			return;
		}
		summary.innerHTML =
			'<div class="iwt-summary__score ' +
			scoreClass +
			'" aria-hidden="true"><span>' +
			score +
			'</span></div><ul class="iwt-summary__counts">' +
			'<li><span class="iwt-summary__count">' +
			good +
			'</span> ' +
			escapeHtml(strings.good || 'Good') +
			'</li>' +
			'<li><span class="iwt-summary__count">' +
			recommended +
			'</span> ' +
			escapeHtml(strings.recommended || 'Recommended') +
			'</li>' +
			'<li><span class="iwt-summary__count">' +
			critical +
			'</span> ' +
			escapeHtml(strings.critical || 'Critical') +
			'</li></ul>';
	}

	function runChecks() {
		var list = document.querySelector('[data-iwt-results]');
		var button = document.querySelector('[data-iwt-run-checks]');
		if (!list || !cfg.checks || !cfg.checks.length) {
			return;
		}
		if (button) {
			button.disabled = true;
			button.textContent = strings.running || 'Running…';
		}
		list.innerHTML = '';
		var reportLines = [];
		var chain = Promise.resolve();
		cfg.checks.forEach(function (check) {
			chain = chain.then(function () {
				return post(cfg.prefix + '_run_check', {
					nonce: cfg.checksNonce,
					check_id: check.id
				}).then(function (json) {
					var result = json && json.success ? json.data : {
						status: 'critical',
						label: check.label,
						description: 'Request failed'
					};
					list.insertAdjacentHTML('beforeend', cardHtml(result));
					updateSummary(list);
					reportLines.push('## ' + (result.label || check.label));
					reportLines.push(result.description || '');
					reportLines.push('');
				});
			});
		});
		chain.finally(function () {
			if (button) {
				button.disabled = false;
				button.textContent = button.getAttribute('data-label') || 'Run checks';
			}
			var reportEl = document.getElementById('iwt-report-source');
			if (reportEl) {
				reportEl.textContent = reportLines.join('\n');
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
		var value = Math.max(0, Math.min(100, parseInt(progress, 10) || 0));
		if (bar) {
			bar.style.width = value + '%';
		}
		if (track) {
			track.setAttribute('aria-valuenow', String(value));
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
			// Report body is stored server-side (store_for_download); never POST it.
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
	});
})();
