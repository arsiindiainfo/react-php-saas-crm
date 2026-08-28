CREATE PROCEDURE sp_contact_create(
  IN  p_company_id BIGINT UNSIGNED,
  IN  p_first_name VARCHAR(80),
  IN  p_last_name VARCHAR(80),
  IN  p_email VARCHAR(190),
  IN  p_phone VARCHAR(30),
  IN  p_job_title VARCHAR(100),
  IN  p_owner_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  OUT p_contact_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_contact_create: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while creating contact.'; END;

  START TRANSACTION;
  IF NOT EXISTS (
    SELECT 1 FROM companies
      WHERE id = p_company_id AND deleted_at IS NULL
        AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
  ) THEN
    ROLLBACK; SET p_status_code = 'COMPANY_NOT_FOUND', p_message = 'No such company.'; LEAVE sp_contact_create;
  END IF;

  INSERT INTO contacts (company_id, first_name, last_name, email, phone, job_title, owner_id)
    VALUES (p_company_id, p_first_name, p_last_name, p_email, p_phone, p_job_title, p_owner_id);
  SET p_contact_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_owner_id, 'CONTACT_CREATED', 'CONTACT', p_contact_id, JSON_OBJECT('companyId', p_company_id));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Contact created.';
END
