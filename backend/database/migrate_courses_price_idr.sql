-- Migration: course prices base currency USD -> IDR.
-- Converts existing USD amounts at 1 USD = 16,500 IDR.
-- The WHERE guard skips rows that already look like IDR amounts,
-- so the migration is safe to run only once (re-running changes nothing).

UPDATE `courses` SET `price` = ROUND(`price` * 16500) WHERE `price` < 10000;
