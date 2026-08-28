CREATE PROCEDURE sp_lead_convert(
  IN  p_lead_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_existing_company_id BIGINT UNSIGNED,
  IN  p_deal_name VARCHAR(180),
  IN  p_deal_value DECIMAL(12,2),
  IN  p_converted_by BIGINT UNSIGNED,
  OUT p_company_id BIGINT UNSIGNED,
  OUT p_contact_id BIGINT UNSIGNED,
  OUT p_deal_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_lead_convert: BEGIN
  DECLARE v_status VARCHAR(20);
  DECLARE v_first VARCHAR(80);
  DECLARE v_last VARCHAR(80);
  DECLARE v_email VARCHAR(190);
  DECLARE v_phone VARCHAR(30);
  DECLARE v_company_name VARCHAR(180);
  DECLARE v_owner BIGINT UNSIGNED;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while converting lead.'; END;

  START TRANSACTION;

  -- lock the lead so it cannot be converted twice by a double-click / race
  SELECT status, first_name, last_name, email, phone, company_name, owner_id
    INTO v_status, v_first, v_last, v_email, v_phone, v_company_name, v_owner
    FROM leads
    WHERE id = p_lead_id AND deleted_at IS NULL
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
    FOR UPDATE;

  IF v_status IS NULL THEN
    ROLLBACK; SET p_status_code = 'NOT_FOUND', p_message = 'Lead not found.'; LEAVE sp_lead_convert;
  ELSEIF v_status = 'CONVERTED' THEN
    ROLLBACK; SET p_status_code = 'ALREADY_CONVERTED', p_message = 'This lead has already been converted.'; LEAVE sp_lead_convert;
  ELSEIF v_status NOT IN ('QUALIFIED') THEN
    ROLLBACK; SET p_status_code = 'NOT_QUALIFIED', p_message = 'Only a qualified lead can be converted.'; LEAVE sp_lead_convert;
  END IF;

  IF p_existing_company_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM companies WHERE id = p_existing_company_id AND deleted_at IS NULL
        AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
    ) THEN
      ROLLBACK; SET p_status_code = 'COMPANY_NOT_FOUND', p_message = 'No such company.'; LEAVE sp_lead_convert;
    END IF;
    SET p_company_id = p_existing_company_id;
  ELSE
    INSERT INTO companies (name, owner_id) VALUES (COALESCE(v_company_name, CONCAT(v_last, ' Household')), v_owner);
    SET p_company_id = LAST_INSERT_ID();
  END IF;

  INSERT INTO contacts (company_id, first_name, last_name, email, phone, owner_id)
    VALUES (p_company_id, v_first, v_last, v_email, v_phone, v_owner);
  SET p_contact_id = LAST_INSERT_ID();

  INSERT INTO deals (company_id, contact_id, lead_id, name, value_amount, stage, owner_id)
    VALUES (p_company_id, p_contact_id, p_lead_id, p_deal_name, p_deal_value, 'PROSPECTING', v_owner);
  SET p_deal_id = LAST_INSERT_ID();

  UPDATE leads SET status = 'CONVERTED', converted_at = NOW(),
    converted_company_id = p_company_id, converted_contact_id = p_contact_id, converted_deal_id = p_deal_id
    WHERE id = p_lead_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_converted_by, 'LEAD_CONVERTED', 'LEAD', p_lead_id,
      JSON_OBJECT('companyId', p_company_id, 'contactId', p_contact_id, 'dealId', p_deal_id));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Lead converted.';
END
