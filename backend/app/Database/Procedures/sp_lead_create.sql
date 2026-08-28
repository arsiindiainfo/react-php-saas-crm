CREATE PROCEDURE sp_lead_create(
  IN  p_first_name VARCHAR(80),
  IN  p_last_name VARCHAR(80),
  IN  p_email VARCHAR(190),
  IN  p_phone VARCHAR(30),
  IN  p_company_name VARCHAR(180),
  IN  p_source VARCHAR(20),
  IN  p_owner_id BIGINT UNSIGNED,
  OUT p_lead_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_lead_create: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while creating lead.'; END;

  START TRANSACTION;
  INSERT INTO leads (first_name, last_name, email, phone, company_name, source, owner_id)
    VALUES (p_first_name, p_last_name, p_email, p_phone, p_company_name, p_source, p_owner_id);
  SET p_lead_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_owner_id, 'LEAD_CREATED', 'LEAD', p_lead_id, JSON_OBJECT('firstName', p_first_name, 'lastName', p_last_name));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Lead created.';
END
