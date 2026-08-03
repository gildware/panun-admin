import { chromium } from 'playwright';
import { mkdir } from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT_DIR = path.resolve(__dirname, '../public/assets/admin-module/process-guide/training');
const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const EMAIL = process.env.CAPTURE_ADMIN_EMAIL || 'aalim.hameed@gildware.com';
const PASSWORD = process.env.CAPTURE_ADMIN_PASSWORD || 'TrainingCapture2026!';
const BOOKING_ID = process.env.CAPTURE_BOOKING_ID || 'e4dbbd4b-a82e-4e92-92e7-94ab886078c3';
const ACCEPTED_BOOKING_ID = process.env.CAPTURE_ACCEPTED_BOOKING_ID || '5777fd30-1601-4ab1-8bc6-552e5a4df289';

const warnings = [];

async function login(page) {
    await page.goto(`${BASE}/admin/auth/login`, { waitUntil: 'networkidle' });
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.click('#signInBtn');
    await page.waitForURL(url => !url.pathname.includes('/admin/auth/login'), { timeout: 30000 });
}

async function shotLocator(locator, name) {
    const file = path.join(OUT_DIR, name);
    await locator.waitFor({ state: 'visible', timeout: 15000 });
    await locator.page().waitForTimeout(350);
    await locator.screenshot({ path: file });
    console.log('saved', name);
}

async function shotSelector(page, name, selector) {
    await shotLocator(page.locator(selector).first(), name);
}

async function shotModalDialog(page, name, modalSelector) {
    const dialog = page.locator(`${modalSelector} .modal-dialog`).first();
    await shotLocator(dialog, name);
}

async function openModal(page, triggerSelector, modalSelector) {
    const trigger = page.locator(triggerSelector).first();
    await trigger.waitFor({ state: 'visible', timeout: 15000 });
    await trigger.scrollIntoViewIfNeeded();
    await trigger.click();
    await page.locator(`${modalSelector} .modal-dialog`).first().waitFor({ state: 'visible', timeout: 15000 });
    await page.waitForTimeout(400);
}

async function showModalById(page, modalSelector) {
    await page.evaluate((sel) => {
        const el = document.querySelector(sel);
        if (!el) {
            return;
        }
        const Modal = window.bootstrap?.Modal;
        if (Modal) {
            Modal.getOrCreateInstance(el).show();
            return;
        }
        el.classList.add('show');
        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }, modalSelector);
    await page.locator(`${modalSelector} .modal-dialog`).first().waitFor({ state: 'visible', timeout: 15000 });
    await page.waitForTimeout(400);
}

async function closeModal(page) {
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
}

async function tryCapture(label, fn) {
    try {
        await fn();
    } catch (err) {
        warnings.push(`${label}: ${err.message}`);
        console.warn('skipped', label, '-', err.message);
    }
}

async function shotVerticalRegion(page, name, topSelector, bottomSelector) {
    const top = page.locator(topSelector).first();
    const bottom = page.locator(bottomSelector).first();
    await top.waitFor({ state: 'visible', timeout: 15000 });
    await bottom.waitFor({ state: 'visible', timeout: 15000 });
    await bottom.scrollIntoViewIfNeeded();
    await top.scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);
    const boxTop = await top.boundingBox();
    const boxBottom = await bottom.boundingBox();
    if (!boxTop || !boxBottom) {
        throw new Error('Could not measure region');
    }
    const clip = {
        x: Math.max(0, Math.min(boxTop.x, boxBottom.x)),
        y: boxTop.y,
        width: Math.max(boxTop.width, boxBottom.width),
        height: (boxBottom.y + boxBottom.height) - boxTop.y,
    };
    const file = path.join(OUT_DIR, name);
    await page.screenshot({ path: file, clip });
    console.log('saved', name);
}

async function selectBfsScenario(page, value) {
    const modal = page.locator('#bookingFinancialSettlementModal');
    const radio = modal.locator(`#bfs-outcome-${value}`).first();
    if (!(await radio.count())) {
        console.warn('BFS radio not found:', value);
        return false;
    }
    if (await radio.isDisabled()) {
        console.warn('BFS radio disabled:', value);
        return false;
    }
    await page.evaluate((scenarioValue) => {
        const input = document.querySelector(`#bfs-outcome-${scenarioValue}`);
        if (!input || input.disabled) {
            return;
        }
        input.checked = true;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('click', { bubbles: true }));
    }, value);
    await page.waitForTimeout(700);
    return true;
}

async function main() {
    await mkdir(OUT_DIR, { recursive: true });
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();

    await login(page);

    // ── Create booking form ─────────────────────────────────────────────
    await tryCapture('booking-create-form', async () => {
        await page.goto(`${BASE}/admin/booking/create`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await shotSelector(page, 'booking-create-form.png', 'form[action*="booking"]');
    });

    await tryCapture('booking-create-form-advance', async () => {
        const advanceField = page.locator('#advance_paid_amount, [name="advance_paid_amount"]').first();
        if (!(await advanceField.count())) {
            throw new Error('Advance field not found');
        }
        await advanceField.scrollIntoViewIfNeeded();
        const advanceBlock = page.locator('.card, fieldset').filter({ has: advanceField }).first();
        await shotLocator(advanceBlock, 'booking-create-form-advance.png');
    });

    // ── Booking list — compact UI (Pending tab for app bookings slide) ──
    await tryCapture('booking-pending-list', async () => {
        await page.goto(`${BASE}/admin/booking/list?booking_status=pending&service_type=all`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await shotVerticalRegion(
            page,
            'booking-pending-list.png',
            '.booking-list-compact-tabs',
            '.booking-compact-list',
        );
    });

    // ── Web bookings list (website form submissions) ────────────────────
    await tryCapture('booking-web-bookings-list', async () => {
        await page.goto(`${BASE}/admin/booking/web-bookings`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await shotSelector(page, 'booking-web-bookings-list.png', '.main-content .card:has(table)');
    });

    // ── Accepted booking details — “after create” hero + overview ─────────
    await tryCapture('booking-details-after-create', async () => {
        await page.goto(`${BASE}/admin/booking/details/${ACCEPTED_BOOKING_ID}?web_page=details`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await shotVerticalRegion(
            page,
            'booking-details-after-create.png',
            '.booking-detail-v2 .booking-header',
            '.booking-detail-v2 .booking-details-overview-row, .booking-detail-v2 .booking-overview-trio',
        );
    });

    const detailsUrl = `${BASE}/admin/booking/details/${BOOKING_ID}?web_page=details`;

    // ── Ongoing booking details — payments, dispute, modals ─────────────
    await tryCapture('booking-details-payments', async () => {
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        const paymentCard = page.locator('.booking-detail-v2 .party-card--payment').first();
        if (!(await paymentCard.count())) {
            throw new Error('Payment card not found');
        }
        await paymentCard.scrollIntoViewIfNeeded();
        await shotLocator(paymentCard, 'booking-details-payments.png');
    });

    await tryCapture('booking-dispute-button-area', async () => {
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        const actions = page.locator('#booking-status-overview-actions, .booking-header__status-actions').first();
        await actions.scrollIntoViewIfNeeded();
        await shotLocator(actions, 'booking-dispute-button-area.png');
    });

    // ── Follow-ups subpage (v2 layout) ──────────────────────────────────
    await tryCapture('booking-followups-tab', async () => {
        await page.setViewportSize({ width: 1440, height: 1600 });
        await page.goto(`${BASE}/admin/booking/details/${BOOKING_ID}?web_page=followups`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await page.locator('.booking-detail-v2 .booking-subpage-panel').first().scrollIntoViewIfNeeded();
        await page.waitForTimeout(300);
        await shotVerticalRegion(
            page,
            'booking-followups-tab.png',
            '.booking-detail-v2 .booking-detail-nav-wrap',
            '.booking-detail-v2 .booking-subpage-panel',
        );
        await page.setViewportSize({ width: 1440, height: 900 });
    });

    await tryCapture('booking-followup-add-modal', async () => {
        await page.goto(`${BASE}/admin/booking/details/${BOOKING_ID}?web_page=followups`, { waitUntil: 'networkidle' });
        await openModal(page, '[data-bs-target="#addFollowupModal"]', '#addFollowupModal');
        const addChannel = page.locator('#booking-add-followup-contact-channel');
        if (await addChannel.count()) {
            await addChannel.selectOption('call');
            await page.waitForTimeout(200);
        }
        await shotModalDialog(page, 'booking-followup-add-modal.png', '#addFollowupModal');
        await closeModal(page);
    });

    await tryCapture('booking-followup-take-modal', async () => {
        await page.goto(`${BASE}/admin/booking/details/${BOOKING_ID}?web_page=followups`, { waitUntil: 'networkidle' });
        const takeBtn = page.locator('[data-booking-take-followup], button:has-text("Take follow-up"), button:has-text("Take Follow-up")').first();
        if (!(await takeBtn.count())) {
            throw new Error('No Take follow-up button on booking');
        }
        await takeBtn.click();
        await page.locator('#takeFollowupModal .modal-dialog').first().waitFor({ state: 'visible', timeout: 10000 });
        const takeChannel = page.locator('#booking-followup-contact-channel');
        if (await takeChannel.count()) {
            await takeChannel.selectOption('call');
            await page.waitForTimeout(200);
        }
        await shotModalDialog(page, 'booking-followup-take-modal.png', '#takeFollowupModal');
        await closeModal(page);
    });

    // ── Add payment modal ─────────────────────────────────────────────────
    await tryCapture('booking-add-payment-modal', async () => {
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        const modalId = `#addPaymentModal-${BOOKING_ID}`;
        await showModalById(page, modalId);
        await shotModalDialog(page, 'booking-add-payment-modal.png', modalId);
        await closeModal(page);
    });

    // ── Special financial settlement scenarios ────────────────────────────
    await tryCapture('booking-special-scenario-overview', async () => {
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        const settleTrigger = page.locator('[data-bs-target="#bookingFinancialSettlementModal"], button:has-text("Configure special scenarios")').first();
        if (await settleTrigger.count()) {
            await openModal(page, '[data-bs-target="#bookingFinancialSettlementModal"], button:has-text("Configure special scenarios")', '#bookingFinancialSettlementModal');
        } else {
            await showModalById(page, '#bookingFinancialSettlementModal');
        }
        await shotModalDialog(page, 'booking-special-scenario-overview.png', '#bookingFinancialSettlementModal');

        if (await selectBfsScenario(page, 'visit_retained_cancel')) {
            await shotModalDialog(page, 'booking-special-scenario-cancel.png', '#bookingFinancialSettlementModal');
        }
        if (await selectBfsScenario(page, 'visit_fee_split')) {
            await shotModalDialog(page, 'booking-special-scenario-complete-visit.png', '#bookingFinancialSettlementModal');
        }
        if (await selectBfsScenario(page, 'scaled_to_payments')) {
            await shotModalDialog(page, 'booking-special-scenario-loss-making.png', '#bookingFinancialSettlementModal');
        }
        await closeModal(page);
    });

    // ── Dispute and close modal ───────────────────────────────────────────
    await tryCapture('booking-dispute-close-modal', async () => {
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        const disputeModal = `#reopenDisputeModal--${BOOKING_ID}`;
        const disputeBtn = page.locator(`[data-bs-target="${disputeModal}"], button:has-text("Dispute and close")`).first();
        if (await disputeBtn.count()) {
            await openModal(page, `[data-bs-target="${disputeModal}"], button:has-text("Dispute and close")`, disputeModal);
        } else {
            await showModalById(page, disputeModal);
        }
        await shotModalDialog(page, 'booking-dispute-close-modal.png', disputeModal);
    });

    await browser.close();

    if (warnings.length) {
        console.warn('\nWarnings:');
        warnings.forEach(w => console.warn(' -', w));
        process.exitCode = 1;
    } else {
        console.log('\nAll booking training screenshots captured.');
    }
}

main().catch(err => {
    console.error('capture failed:', err.message);
    process.exitCode = 1;
});
