ALTER TABLE users
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE orders
  ADD COLUMN idempotency_key CHAR(64) NULL,
  ADD UNIQUE KEY idempotency_key (idempotency_key),
  ADD KEY status_order_date (status, order_date, id),
  ADD KEY user_status_order_date (user_id, status, order_date, id);

ALTER TABLE transactions
  ADD KEY transaction_date_id (transaction_date, id);
