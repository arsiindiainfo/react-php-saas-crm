CREATE PROCEDURE sp_company_create(
  IN  p_name VARCHAR(180),
  IN  p_industry VARCHAR(100),
  IN  p_website VARCHAR(200),
  IN  p_phone VARCHAR(30),
  IN  p_owner_id BIGINT UNSIGNED,
  OUT p_company_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_company_create: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while creating company.'; END;

  START TRANSACTION;
  IF EXISTS (SELECT 1 FROM companies WHERE name = p_name AND deleted_at IS NULL FOR UPDATE) THEN
    ROLLBACK; SET p_status_code = 'DUPLICATE_NAME', p_message = 'A company with this name already exists.'; LEAVE sp_company_create;
  END IF;

  INSERT INTO companies (name, industry, website, phone, owner_id)
    VALUES (p_name, p_industry, p_website, p_phone, p_owner_id);
  SET p_company_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_owner_id, 'COMPANY_CREATED', 'COMPANY', p_company_id, JSON_OBJECT('name', p_name));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Company created.';
END
