CREATE PROCEDURE sp_user_authenticate(
  IN  p_email VARCHAR(190),
  OUT p_user_id BIGINT UNSIGNED,
  OUT p_name VARCHAR(120),
  OUT p_password_hash VARCHAR(255),
  OUT p_role VARCHAR(20),
  OUT p_manager_id BIGINT UNSIGNED,
  OUT p_status VARCHAR(20)
)
BEGIN
  SELECT id, name, password_hash, role, manager_id, status
    INTO p_user_id, p_name, p_password_hash, p_role, p_manager_id, p_status
    FROM users
    WHERE email = p_email AND deleted_at IS NULL
    LIMIT 1;
END
