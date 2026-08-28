CREATE PROCEDURE sp_lead_disqualify(
  IN  p_lead_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_reason VARCHAR(255),
  IN  p_disqualified_by BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_lead_disqualify: BEGIN
  DECLARE v_status VARCHAR(20);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while disqualifying lead.'; END;

  START TRANSACTION;
  SELECT status INTO v_status FROM leads
    WHERE id = p_lead_id AND deleted_at IS NULL
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
    FOR UPDATE;

  IF v_status IS NULL THEN
    ROLLBACK; SET p_status_code = 'NOT_FOUND', p_message = 'Lead not found.'; LEAVE sp_lead_disqualify;
  ELSEIF v_status IN ('CONVERTED', 'DISQUALIFIED') THEN
    ROLLBACK; SET p_status_code = 'INVALID_TRANSITION', p_message = 'This lead can no longer be disqualified.'; LEAVE sp_lead_disqualify;
  END IF;

  UPDATE leads SET status = 'DISQUALIFIED', disqualify_reason = p_reason WHERE id = p_lead_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_disqualified_by, 'LEAD_DISQUALIFIED', 'LEAD', p_lead_id, JSON_OBJECT('reason', p_reason));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Lead disqualified.';
END
