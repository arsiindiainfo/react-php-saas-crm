CREATE PROCEDURE sp_deal_create(
  IN  p_company_id BIGINT UNSIGNED,
  IN  p_contact_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_name VARCHAR(180),
  IN  p_value_amount DECIMAL(12,2),
  IN  p_expected_close_date DATE,
  IN  p_owner_id BIGINT UNSIGNED,
  OUT p_deal_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_deal_create: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while creating deal.'; END;

  START TRANSACTION;
  IF NOT EXISTS (
    SELECT 1 FROM companies WHERE id = p_company_id AND deleted_at IS NULL
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
  ) THEN
    ROLLBACK; SET p_status_code = 'COMPANY_NOT_FOUND', p_message = 'No such company.'; LEAVE sp_deal_create;
  END IF;

  INSERT INTO deals (company_id, contact_id, name, value_amount, expected_close_date, stage, owner_id)
    VALUES (p_company_id, p_contact_id, p_name, p_value_amount, p_expected_close_date, 'PROSPECTING', p_owner_id);
  SET p_deal_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_owner_id, 'DEAL_CREATED', 'DEAL', p_deal_id, JSON_OBJECT('name', p_name, 'companyId', p_company_id));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Deal created.';
END
