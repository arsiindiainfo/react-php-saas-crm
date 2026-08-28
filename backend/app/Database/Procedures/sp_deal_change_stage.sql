CREATE PROCEDURE sp_deal_change_stage(
  IN  p_deal_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_new_stage VARCHAR(20),
  IN  p_lost_reason VARCHAR(255),
  IN  p_changed_by BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_deal_change_stage: BEGIN
  DECLARE v_current VARCHAR(20);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while changing stage.'; END;

  START TRANSACTION;
  SELECT stage INTO v_current FROM deals
    WHERE id = p_deal_id AND deleted_at IS NULL
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0)
    FOR UPDATE;

  IF v_current IS NULL THEN
    ROLLBACK; SET p_status_code = 'NOT_FOUND', p_message = 'Deal not found.'; LEAVE sp_deal_change_stage;
  ELSEIF v_current IN ('WON','LOST') THEN
    ROLLBACK; SET p_status_code = 'INVALID_TRANSITION', p_message = 'A closed deal cannot change stage.'; LEAVE sp_deal_change_stage;
  ELSEIF p_new_stage = 'LOST' AND (p_lost_reason IS NULL OR p_lost_reason = '') THEN
    ROLLBACK; SET p_status_code = 'LOST_REASON_REQUIRED', p_message = 'A reason is required when marking a deal as lost.'; LEAVE sp_deal_change_stage;
  END IF;

  UPDATE deals SET stage = p_new_stage, lost_reason = IF(p_new_stage = 'LOST', p_lost_reason, NULL),
    closed_at = IF(p_new_stage IN ('WON','LOST'), NOW(), NULL)
    WHERE id = p_deal_id;

  IF p_new_stage = 'WON' THEN
    UPDATE companies SET status = 'CUSTOMER' WHERE id = (SELECT company_id FROM deals WHERE id = p_deal_id);
  END IF;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_changed_by, 'DEAL_STAGE_CHANGED', 'DEAL', p_deal_id, JSON_OBJECT('from', v_current, 'to', p_new_stage));

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Stage updated.';
END
