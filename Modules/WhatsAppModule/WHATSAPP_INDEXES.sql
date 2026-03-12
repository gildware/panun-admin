-- Run these in your Neon (WhatsApp) database to speed up queries.
-- Copy and run in Neon SQL Editor or psql.

-- Messages: chat list (last message per phone, last 30 days) and loading messages by phone
CREATE INDEX IF NOT EXISTS idx_messages_created_at ON messages (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_messages_phone_created_at ON messages (phone, created_at DESC);

-- Provider leads: list ordered by created_at
CREATE INDEX IF NOT EXISTS idx_provider_leads_created_at ON provider_leads (created_at DESC);

-- Bookings: list and lookup by phone
CREATE INDEX IF NOT EXISTS idx_bookings_created_at ON bookings (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_bookings_phone ON bookings (phone);

-- Conversation: lookup by phone (for chat panel state)
CREATE INDEX IF NOT EXISTS idx_conversation_phone ON conversation (phone);
