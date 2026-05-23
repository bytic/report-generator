<?php

declare(strict_types=1);

/**
 * Progress-bar page for chunked report generation (Strategy B).
 *
 * Variables injected by HasReportGeneratorControllerTrait::renderChunkedReportProgressPage():
 *
 * @var string                                             $token       Initial continuation token.
 * @var string                                             $progressUrl URL of the reportProgress AJAX endpoint.
 * @var string                                             $downloadUrl URL of the reportDownload endpoint.
 * @var \ByTIC\ReportGenerator\AsyncReport\ReportJobState $state       Initial job state.
 * @var int|null                                           $totalRows   Known total rows, or null if unknown.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generating Report&hellip;</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .10);
            padding: 40px 48px;
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .card__icon {
            font-size: 2.4rem;
            margin-bottom: 16px;
        }

        .card__title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 24px;
            color: #1a1a2e;
        }

        /* ── Progress bar ──────────────────────────────────── */
        .progress-wrap {
            background: #e9ecef;
            border-radius: 99px;
            height: 14px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #4c84ff, #1ece72);
            border-radius: 99px;
            transition: width .35s ease;
        }

        /* Indeterminate animation when total is unknown */
        .progress-fill--indeterminate {
            width: 40% !important;
            animation: indeterminate 1.4s ease-in-out infinite;
        }

        @keyframes indeterminate {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(300%); }
        }

        /* ── Status text ──────────────────────────────────── */
        .progress-label {
            font-size: .875rem;
            color: #666;
            margin-bottom: 28px;
            min-height: 1.25em;
        }

        /* ── Error state ──────────────────────────────────── */
        .alert-error {
            background: #fff0f0;
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            color: #c0392b;
            padding: 12px 16px;
            font-size: .875rem;
            margin-top: 16px;
            text-align: left;
            display: none;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card__icon">📊</div>
    <h1 class="card__title">Generating your report&hellip;</h1>

    <div class="progress-wrap">
        <div class="progress-fill<?= $totalRows === null ? ' progress-fill--indeterminate' : '' ?>"
             id="rg-progress-fill"></div>
    </div>
    <p class="progress-label" id="rg-status">Starting&hellip;</p>

    <div class="alert-error" id="rg-error"></div>
</div>

<script>
    (function () {
        'use strict';

        const PROGRESS_URL  = <?= json_encode($progressUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const DOWNLOAD_URL  = <?= json_encode($downloadUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const KNOWN_TOTAL   = <?= json_encode($totalRows) ?>;

        let currentToken    = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        const fillEl   = document.getElementById('rg-progress-fill');
        const statusEl = document.getElementById('rg-status');
        const errorEl  = document.getElementById('rg-error');

        function setProgress(percent) {
            if (percent !== null) {
                fillEl.classList.remove('progress-fill--indeterminate');
                fillEl.style.width = Math.min(100, percent) + '%';
            }
        }

        function setStatus(text) {
            statusEl.textContent = text;
        }

        function showError(message) {
            fillEl.style.background = '#e74c3c';
            setStatus('An error occurred.');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        async function processChunk() {
            let data;
            try {
                const body = new URLSearchParams({ _report_token: currentToken });
                const resp = await fetch(PROGRESS_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    body.toString(),
                });

                if (!resp.ok) {
                    throw new Error('Server returned HTTP ' + resp.status);
                }

                data = await resp.json();
            } catch (err) {
                showError(err.message || 'Network error');
                return;
            }

            if (data.error) {
                showError(data.error);
                return;
            }

            currentToken = data.token;

            // Update progress bar
            if (data.percent !== null && data.percent !== undefined) {
                setProgress(data.percent);
                setStatus(data.processed + ' / ' + (data.total > 0 ? data.total : '?') + ' rows processed');
            } else if (KNOWN_TOTAL === null) {
                setStatus('Processing rows\u2026');
            }

            if (data.done) {
                setProgress(100);
                setStatus('Done! Your download will start shortly\u2026');

                // Small pause so the user sees 100 %
                setTimeout(function () {
                    const url = DOWNLOAD_URL
                        + (DOWNLOAD_URL.includes('?') ? '&' : '?')
                        + '_report_token=' + encodeURIComponent(currentToken);
                    window.location.href = url;
                }, 600);
            } else {
                // Schedule the next chunk with a small delay to stay responsive
                setTimeout(processChunk, 150);
            }
        }

        // Kick off immediately
        processChunk();
    }());
</script>
</body>
</html>
