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
    await page.locator(triggerSelector).first().click();
    await page.locator(`${modalSelector} .modal-dialog`).first().waitFor({ state: 'visible', timeout: 15000 });
    await page.waitForTimeout(400);
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

    try {
        await login(page);

        // Create booking — main form
        await page.goto(`${BASE}/admin/booking/create`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await shotSelector(page, 'booking-create-form.png', 'form[action*="booking"]');

        // Advance payment block (bottom of create form)
        const advanceField = page.locator('#advance_paid_amount, [name="advance_paid_amount"], input[id*="advance"]').first();
        if (await advanceField.count()) {
            await advanceField.scrollIntoViewIfNeeded();
            const advanceBlock = page.locator('.card, fieldset, .form-group').filter({ has: advanceField }).first();
            if (await advanceBlock.count()) {
                await shotLocator(advanceBlock, 'booking-create-form-advance.png');
            }
        }

        const detailsUrl = `${BASE}/admin/booking/details/${BOOKING_ID}?web_page=details`;
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);

        // After create — tabs + status actions (assignee / first clicks context)
        await shotSelector(page, 'booking-details-after-create.png', '.nav--tabs__style2');

        // Payment card on details (not full viewport)
        const paymentCard = page.locator('.booking-overview-mid-card--payment').first();
        if (await paymentCard.count()) {
            await shotLocator(paymentCard, 'booking-details-payments.png');
        }

        // Dispute button area (header action row)
        const disputeBtn = page.locator(`[data-bs-target="#reopenDisputeModal--${BOOKING_ID}"], button:has-text("Dispute and close")`).first();
        if (await disputeBtn.count()) {
            await disputeBtn.scrollIntoViewIfNeeded();
            const actionRow = page.locator('.d-flex.flex-wrap.gap-2, .booking-details-action-row').filter({ has: disputeBtn }).first();
            if (await actionRow.count()) {
                await shotLocator(actionRow, 'booking-dispute-button-area.png');
            }
        }

        // Follow-ups tab
        await page.goto(`${BASE}/admin/booking/details/${BOOKING_ID}?web_page=followups`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await shotSelector(page, 'booking-followups-tab.png', '.main-content .card, .main-content form, .main-content table');

        // Add follow-up modal
        await openModal(page, '[data-bs-target="#addFollowupModal"]', '#addFollowupModal');
        await shotModalDialog(page, 'booking-followup-add-modal.png', '#addFollowupModal');
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);

        // Take follow-up modal
        const takeBtn = page.locator('button:has-text("Take follow-up"), button:has-text("Take Follow-up"), button:has-text("Take follow up")').first();
        if (await takeBtn.count()) {
            await takeBtn.click();
            await page.locator('.modal.show .modal-dialog').first().waitFor({ state: 'visible', timeout: 10000 });
            await shotModalDialog(page, 'booking-followup-take-modal.png', '.modal.show');
            await page.keyboard.press('Escape');
        }

        // Add payment modal
        await page.goto(detailsUrl, { waitUntil: 'networkidle' });
        const payTrigger = page.locator(`[data-bs-target="#addPaymentModal-${BOOKING_ID}"], button:has-text("Add payment")`).first();
        if (await payTrigger.count()) {
            await payTrigger.scrollIntoViewIfNeeded();
            await openModal(page, `[data-bs-target="#addPaymentModal-${BOOKING_ID}"], button:has-text("Add payment")`, `#addPaymentModal-${BOOKING_ID}`);
            await shotModalDialog(page, 'booking-add-payment-modal.png', `#addPaymentModal-${BOOKING_ID}`);
            await page.keyboard.press('Escape');
        }

        // Special scenarios — overview + each scenario
        const settleTrigger = page.locator('[data-bs-target="#bookingFinancialSettlementModal"], button:has-text("Configure special scenarios")').first();
        if (await settleTrigger.count()) {
            await settleTrigger.scrollIntoViewIfNeeded();
            await openModal(page, '[data-bs-target="#bookingFinancialSettlementModal"], button:has-text("Configure special scenarios")', '#bookingFinancialSettlementModal');
            await shotModalDialog(page, 'booking-special-scenario-overview.png', '#bookingFinancialSettlementModal');

            if (await selectBfsScenario(page, 'visit_retained_cancel')) {
                await page.locator('#bfs-fields-decided-charges:not(.d-none)').first().scrollIntoViewIfNeeded().catch(() => {});
                await shotModalDialog(page, 'booking-special-scenario-cancel.png', '#bookingFinancialSettlementModal');
            }
            if (await selectBfsScenario(page, 'visit_fee_split')) {
                await page.locator('#bfs-fields-decided-charges:not(.d-none)').first().scrollIntoViewIfNeeded().catch(() => {});
                await shotModalDialog(page, 'booking-special-scenario-complete-visit.png', '#bookingFinancialSettlementModal');
            }
            if (await selectBfsScenario(page, 'scaled_to_payments')) {
                await page.locator('#bfs-fields-scaled:not(.d-none), #bfs-fields-scaled-payments:not(.d-none)').first().scrollIntoViewIfNeeded().catch(() => {});
                await shotModalDialog(page, 'booking-special-scenario-loss-making.png', '#bookingFinancialSettlementModal');
            }

            await page.keyboard.press('Escape');
        }

        // Dispute and close modal
        const disputeBtnRefresh = page.locator(`[data-bs-target="#reopenDisputeModal--${BOOKING_ID}"], button:has-text("Dispute and close")`).first();
        if (await disputeBtnRefresh.count()) {
            await disputeBtnRefresh.scrollIntoViewIfNeeded();
            await openModal(page, `[data-bs-target="#reopenDisputeModal--${BOOKING_ID}"], button:has-text("Dispute and close")`, `#reopenDisputeModal--${BOOKING_ID}`);
            await shotModalDialog(page, 'booking-dispute-close-modal.png', `#reopenDisputeModal--${BOOKING_ID}`);
        }

        // Web bookings
        await page.goto(`${BASE}/admin/booking/web-bookings`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        const webRow = page.locator('table tbody tr a, .card-body a.link-primary').first();
        if (await webRow.count()) {
            await webRow.click();
            await page.waitForLoadState('networkidle');
            await shotSelector(page, 'booking-web-booking-detail.png', '.card-body .card, .main-content .card');
        } else {
            await shotSelector(page, 'booking-web-bookings-list.png', '.main-content .card, .main-content table');
        }
    } catch (err) {
        console.error('capture failed:', err.message);
        await page.screenshot({ path: path.join(OUT_DIR, 'booking-capture-error.png'), fullPage: true });
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

main();
