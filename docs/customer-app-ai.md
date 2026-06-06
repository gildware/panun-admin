# Panun Kaergar — Customer App AI

**One brain: Gemini understands and replies. Tools execute on live data.**

--- 

## Pipeline

```
Customer message (or button tap → bookingAction API)
  → Pending yes/no confirm? (cart_confirm, coupon_confirm, …) → server handler
  → Else: server handlers (booking wizard, cart, coupon/bid, status, triage, pricing)
  → If no match: Gemini tool loop (ambiguous / multi-intent chat)
  → Tool sets booking_draft + ui (buttons/cards) when needed
  → Flutter renders text + buttons
```

**Gemini health gate:** On chat open the app probes Gemini. If the API does not respond, `enabled=false` — the user cannot send messages (no fake “I didn't catch that” replies). When Gemini fails mid-turn, chat shows **Something went wrong** and marks the service unhealthy.

**Server-first routing** when Gemini is healthy: booking, cart, coupons, bids, status, and pricing are handled on the server; Gemini handles ambiguous chat.

---

## Three tool families

| Family | Tools | Does |
|--------|-------|------|
| **Book** | `manage_app_booking` | Full wizard: service → pick → time → address → cart |
| **Cart** | `manage_customer_cart`, `get_customer_cart_summary` | View, remove, keep one, clear, reschedule — Gemini passes `op`, `cart_line_ids`, `cart_filter`, scopes |
| **Status** | `list_my_system_bookings`, `get_booking_status_by_reference` | Booking list & PK lookup |

Cart understanding: Gemini reads session cart catalog + customer words (Hinglish/English). Server validates, confirms destructive ops, executes.

**Cart summary UI:** `get_customer_cart_summary` / cart `view` returns `ui.type=cart_summary` with line cards + Open cart footer. Confirm buttons use Hinglish labels when the customer wrote Hinglish (*Haan, kar do*).

**Booking wizard guard:** While a booking wizard step is active, Gemini stays on `manage_app_booking` unless the customer explicitly asks about cart.

---

## Button taps

Flutter calls `bookingAction` API (`confirm_cart_action`, `pick_cart_remove`, `confirm_service`, `pick`, …). Server runs tools and returns reply + `ui`.

---

## Config

```env
GEMINI_API_KEY=...
MOBILE_APP_AI_CONVERSATIONAL_AGENT=1
MOBILE_APP_AI_CART_ACTION_GEMINI=1   # cart resolver uses Gemini + live catalog
```

---

## Language

English, Hinglish, Roman Urdu. Gemini matches customer language. Server confirm/errors localize when possible.
