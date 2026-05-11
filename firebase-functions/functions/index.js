// =============================================================================
// crashlyticsToTelegram — bridges Firebase Crashlytics velocity alerts to
// the @itqan_platform_debug_bot Telegram chat. Uses Node 20's global fetch
// so we don't carry node-fetch as a dependency.
//
// Secrets (set via `firebase functions:secrets:set`):
//   TELEGRAM_BOT_TOKEN
//   TELEGRAM_CHAT_ID
//
// Deploy: `firebase deploy --only functions:crashlyticsToTelegram`
// =============================================================================
const { onVelocityAlertPublished } = require('firebase-functions/v2/alerts/crashlytics');
const { defineSecret } = require('firebase-functions/params');

const TELEGRAM_BOT_TOKEN = defineSecret('TELEGRAM_BOT_TOKEN');
const TELEGRAM_CHAT_ID = defineSecret('TELEGRAM_CHAT_ID');

exports.crashlyticsToTelegram = onVelocityAlertPublished(
  { secrets: [TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID] },
  async (event) => {
    // Velocity alert payload shape:
    // https://firebase.google.com/docs/functions/alerts-events?gen=2nd#crashlytics
    const payload = event.data && event.data.payload ? event.data.payload : {};
    const appId = event.appId || payload.appId || 'unknown';
    const projectId = process.env.GCLOUD_PROJECT || event.projectId || 'unknown';

    const issueTitle = payload.issueTitle || payload.issueSubtitle || 'crash';
    const issueId = payload.issueId || '';
    const crashCount = payload.crashCount || 0;
    const distinctUsers = payload.distinctUsers || 0;
    const crashPercentage = payload.crashPercentage || 0;
    const buildVersion = payload.buildVersion || 'unknown';

    const consoleUrl = issueId
      ? `https://console.firebase.google.com/project/${projectId}/crashlytics/app/${appId}/issues/${issueId}`
      : `https://console.firebase.google.com/project/${projectId}/crashlytics`;

    const timestamp = new Date().toISOString().replace('T', ' ').replace(/\..+/, '') + ' UTC';

    const text = [
      '🚨 [CRIT] mobile-crash @ firebase',
      timestamp,
      '',
      issueTitle,
      `crashes=${crashCount} users=${distinctUsers} ` +
        `pct=${Number(crashPercentage).toFixed(2)}% build=${buildVersion}`,
      consoleUrl,
    ].join('\n');

    const token = TELEGRAM_BOT_TOKEN.value();
    const chatId = TELEGRAM_CHAT_ID.value();

    if (!token || !chatId) {
      console.warn('crashlyticsToTelegram: missing TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID secret');
      return;
    }

    const body = new URLSearchParams({
      chat_id: chatId,
      text,
      disable_web_page_preview: 'true',
    });

    try {
      const resp = await fetch(
        `https://api.telegram.org/bot${token}/sendMessage`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body,
        },
      );

      if (!resp.ok) {
        const errText = await resp.text();
        console.error('crashlyticsToTelegram: Telegram API rejected', {
          status: resp.status,
          body: errText,
        });
      }
    } catch (err) {
      console.error('crashlyticsToTelegram: fetch failed', err);
    }
  },
);
