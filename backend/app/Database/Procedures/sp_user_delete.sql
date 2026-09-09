CREATE PROCEDURE sp_user_delete(
  IN  p_user_id BIGINT UNSIGNED,
  IN  p_acting_admin_id BIGINT UNSIGNED,
  OUT p_status_code VARCHAR(30),
  OUT p_message VARCHAR(255)
)
sp_user_delete: BEGIN
  DECLARE v_owned_count INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN ROLLBACK; SET p_status_code = 'INTERNAL_ERROR', p_message = 'Unexpected database error while removing user.'; END;

  START TRANSACTION;

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id AND deleted_at IS NULL FOR UPDATE) THEN
    ROLLBACK; SET p_status_code = 'USER_NOT_FOUND', p_message = 'No such user.'; LEAVE sp_user_delete;
  END IF;

  IF p_user_id = p_acting_admin_id THEN
    ROLLBACK; SET p_status_code = 'FORBIDDEN_ROLE', p_message = 'You cannot remove your own account.'; LEAVE sp_user_delete;
  END IF;

  -- companies/contacts/leads/deals.owner_id and tasks/activities.created_by
  -- (plus tasks.assigned_to) are all RESTRICT foreign keys to users.id --
  -- a bare DELETE would fail with a generic FK-violation error. Check up
  -- front so the caller gets a specific, actionable message instead.
  SELECT
      (SELECT COUNT(*) FROM companies WHERE owner_id = p_user_id)
    + (SELECT COUNT(*) FROM contacts WHERE owner_id = p_user_id)
    + (SELECT COUNT(*) FROM leads WHERE owner_id = p_user_id)
    + (SELECT COUNT(*) FROM deals WHERE owner_id = p_user_id)
    + (SELECT COUNT(*) FROM tasks WHERE created_by = p_user_id OR assigned_to = p_user_id)
    + (SELECT COUNT(*) FROM activities WHERE created_by = p_user_id)
  INTO v_owned_count;

  IF v_owned_count > 0 THEN
    ROLLBACK;
    SET p_status_code = 'USER_HAS_CONTENT',
        p_message = 'This user owns companies, contacts, leads, deals, tasks or activity records. Reassign or remove those first.';
    LEAVE sp_user_delete;
  END IF;

  -- manager_id (users self-reference) is ON DELETE SET NULL, refresh_tokens
  -- is ON DELETE CASCADE, and audit_logs.user_id is ON DELETE SET NULL --
  -- all handled automatically by the DELETE below, no manual cleanup needed.
  DELETE FROM users WHERE id = p_user_id;

  COMMIT;
  SET p_status_code = 'OK', p_message = 'User removed.';
END
