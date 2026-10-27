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

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .button {
            border: 0;
            border-radius: 6px;
            background: #4c84ff;
            color: #fff;
            cursor: pointer;
            font: inherit;
            padding: 10px 18px;
            text-decoration: none;
        }

        .button--secondary { background: #e9ecef; color: #333; }
        .button[hidden] { display: none; }
        .return-countdown { margin-top: 16px; font-size: .875rem; color: #666; }

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

    <div class="actions">
        <a class="button" id="rg-download" hidden>Download report</a>
        <button class="button button--secondary" id="rg-back" type="button">Back</button>
    </div>
    <p class="return-countdown" id="rg-countdown" role="status" hidden></p>
    <div class="alert-error" id="rg-error" role="alert"></div>
</div>

<script>
    (function () {
        'use strict';

        const PROGRESS_URL  = <?= json_encode($progressUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const DOWNLOAD_URL  = <?= json_encode($downloadUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const KNOWN_TOTAL   = <?= json_encode($totalRows) ?>;
        const DEFAULT_FILENAME = <?= json_encode('report.' . $state->format, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        let currentToken    = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        const fillEl   = document.getElementById('rg-progress-fill');
        const statusEl = document.getElementById('rg-status');
        const errorEl  = document.getElementById('rg-error');
        const downloadEl = document.getElementById('rg-download');
        const countdownEl = document.getElementById('rg-countdown');
        let downloadObjectUrl = null;
        let returnTimer = null;

        function goBack() {
            if (document.referrer) {
                const originalUrl = new URL(document.referrer);
                if (originalUrl.origin === window.location.origin) {
                    window.location.href = originalUrl.href;
                    return;
                }
            }

            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }

        document.getElementById('rg-back').addEventListener('click', goBack);
        window.addEventListener('pagehide', function () {
            clearInterval(returnTimer);
            if (downloadObjectUrl !== null) {
                URL.revokeObjectURL(downloadObjectUrl);
            }
        });

        function startReturnCountdown() {
            const returnAt = Date.now() + 15000;
            countdownEl.hidden = false;

            function updateCountdown() {
                const seconds = Math.max(0, Math.ceil((returnAt - Date.now()) / 1000));
                countdownEl.textContent = 'Returning to the original page in ' + seconds + ' seconds\u2026';
                if (seconds === 0) {
                    clearInterval(returnTimer);
                    goBack();
                }
            }

            updateCountdown();
            returnTimer = setInterval(updateCountdown, 1000);
        }

        async function startDownload() {
            try {
                const url = DOWNLOAD_URL
                    + (DOWNLOAD_URL.includes('?') ? '&' : '?')
                    + '_report_token=' + encodeURIComponent(currentToken);
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Download failed: server returned HTTP ' + response.status);
                }

                const disposition = response.headers.get('Content-Disposition') || '';
                const encodedFilename = disposition.match(/filename\*=utf-8''([^;]+)/i);
                const filename = disposition.match(/filename="((?:\\.|[^"])*)"|filename=([^;\s]+)/i);
                downloadEl.download = encodedFilename
                    ? decodeURIComponent(encodedFilename[1])
                    : (filename ? (filename[1] || filename[2]).replace(/\\(.)/g, '$1') : DEFAULT_FILENAME);

                // Keep a local copy: the server removes the report after this request.
                downloadObjectUrl = URL.createObjectURL(await response.blob());
                downloadEl.href = downloadObjectUrl;
                downloadEl.hidden = false;
                document.querySelector('.card__title').textContent = 'Your report is ready';
                document.title = 'Report ready';
                setStatus('Your download has started. Use Download report if it does not start automatically.');
                downloadEl.click();
                startReturnCountdown();
            } catch (err) {
                showError(err.message || 'Download failed');
            }
        }

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
                setTimeout(startDownload, 600);
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
