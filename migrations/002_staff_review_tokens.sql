ALTER TABLE documents ADD COLUMN staff_review_token TEXT;

UPDATE documents
SET staff_review_token = lower(hex(randomblob(16)))
WHERE staff_review_token IS NULL;

CREATE UNIQUE INDEX idx_documents_staff_review_token ON documents(staff_review_token);
