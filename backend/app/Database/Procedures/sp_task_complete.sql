CREATE PROCEDURE sp_task_complete(
  IN  p_task_id BIGINT UNSIGNED,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_completed_by BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_task_complete: BEGIN
  DECLARE v_exists INT DEFAULT 0;
  DECLARE v_already_done DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while completing task.'; END;

  START TRANSACTION;
  SELECT 1, completed_at INTO v_exists, v_already_done FROM tasks
    WHERE id = p_task_id AND deleted_at IS NULL
      AND (p_owner_scope IS NULL OR FIND_IN_SET(assigned_to, p_owner_scope) > 0)
    FOR UPDATE;

  IF v_exists = 0 THEN
    ROLLBACK; SET p_status_code = 'NOT_FOUND', p_message = 'Task not found.'; LEAVE sp_task_complete;
  END IF;

  IF v_already_done IS NULL THEN
    UPDATE tasks SET completed_at = NOW() WHERE id = p_task_id;
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
      VALUES (p_completed_by, 'TASK_COMPLETED', 'TASK', p_task_id, JSON_OBJECT());
  END IF;

  COMMIT;
  SET p_status_code = 'OK', p_message = 'Task completed.';
END
