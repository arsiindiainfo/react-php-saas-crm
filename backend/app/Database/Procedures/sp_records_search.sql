CREATE PROCEDURE sp_records_search(
  IN  p_entity_name VARCHAR(20),
  IN  p_search VARCHAR(190),
  IN  p_sort VARCHAR(60),
  IN  p_direction VARCHAR(4),
  IN  p_page INT,
  IN  p_limit INT,
  IN  p_owner_scope VARCHAR(1000),
  IN  p_status VARCHAR(20),
  IN  p_stage VARCHAR(20),
  IN  p_company_id BIGINT UNSIGNED,
  IN  p_assigned_to BIGINT UNSIGNED,
  OUT p_total_count INT
)
sp_records_search: BEGIN
  DECLARE v_sort_col VARCHAR(64) DEFAULT 'id';
  DECLARE v_direction VARCHAR(4) DEFAULT 'ASC';
  DECLARE v_offset INT DEFAULT 0;
  DECLARE v_sql VARCHAR(4000);

  IF p_page IS NULL OR p_page < 1 THEN SET p_page = 1; END IF;
  IF p_limit IS NULL OR p_limit < 1 THEN SET p_limit = 20; END IF;
  SET v_offset = (p_page - 1) * p_limit;
  SET v_direction = IF(UPPER(p_direction) = 'DESC', 'DESC', 'ASC');
  SET @v_search    = COALESCE(p_search, '');
  SET @v_like      = CONCAT('%', @v_search, '%');
  SET @v_scope     = p_owner_scope;
  SET @v_status    = p_status;
  SET @v_stage     = p_stage;
  SET @v_companyId = p_company_id;
  SET @v_assignee  = p_assigned_to;
  SET @v_limit     = p_limit;
  SET @v_offset    = v_offset;

  IF p_entity_name = 'companies' THEN
    SET v_sort_col = CASE p_sort
      WHEN 'name' THEN 'name' WHEN 'industry' THEN 'industry' WHEN 'status' THEN 'status'
      WHEN 'updatedAt' THEN 'updated_at' ELSE 'created_at' END;

    SET v_sql = CONCAT(
      'SELECT COUNT(*) INTO @v_total FROM companies WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_status IS NULL OR status = @v_status)',
      ' AND (@v_search = \'\' OR name LIKE @v_like OR industry LIKE @v_like)');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET v_sql = CONCAT(
      'SELECT id, name, industry, website, phone, status, owner_id, created_at, updated_at',
      ' FROM companies WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_status IS NULL OR status = @v_status)',
      ' AND (@v_search = \'\' OR name LIKE @v_like OR industry LIKE @v_like)',
      ' ORDER BY ', v_sort_col, ' ', v_direction,
      ' LIMIT @v_limit OFFSET @v_offset');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

  ELSEIF p_entity_name = 'contacts' THEN
    SET v_sort_col = CASE p_sort
      WHEN 'firstName' THEN 'first_name' WHEN 'lastName' THEN 'last_name'
      WHEN 'updatedAt' THEN 'updated_at' ELSE 'created_at' END;

    SET v_sql = CONCAT(
      'SELECT COUNT(*) INTO @v_total FROM contacts WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_companyId IS NULL OR company_id = @v_companyId)',
      ' AND (@v_search = \'\' OR first_name LIKE @v_like OR last_name LIKE @v_like OR email LIKE @v_like)');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET v_sql = CONCAT(
      'SELECT id, company_id, first_name, last_name, email, phone, job_title, owner_id, created_at, updated_at',
      ' FROM contacts WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_companyId IS NULL OR company_id = @v_companyId)',
      ' AND (@v_search = \'\' OR first_name LIKE @v_like OR last_name LIKE @v_like OR email LIKE @v_like)',
      ' ORDER BY ', v_sort_col, ' ', v_direction,
      ' LIMIT @v_limit OFFSET @v_offset');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

  ELSEIF p_entity_name = 'leads' THEN
    SET v_sort_col = CASE p_sort
      WHEN 'firstName' THEN 'first_name' WHEN 'lastName' THEN 'last_name' WHEN 'status' THEN 'status'
      WHEN 'updatedAt' THEN 'updated_at' ELSE 'created_at' END;

    SET v_sql = CONCAT(
      'SELECT COUNT(*) INTO @v_total FROM leads WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_status IS NULL OR status = @v_status)',
      ' AND (@v_search = \'\' OR first_name LIKE @v_like OR last_name LIKE @v_like OR email LIKE @v_like OR company_name LIKE @v_like)');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET v_sql = CONCAT(
      'SELECT id, first_name, last_name, email, phone, company_name, source, status,',
      ' disqualify_reason, owner_id, converted_at, converted_company_id, converted_contact_id, converted_deal_id,',
      ' created_at, updated_at FROM leads WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_status IS NULL OR status = @v_status)',
      ' AND (@v_search = \'\' OR first_name LIKE @v_like OR last_name LIKE @v_like OR email LIKE @v_like OR company_name LIKE @v_like)',
      ' ORDER BY ', v_sort_col, ' ', v_direction,
      ' LIMIT @v_limit OFFSET @v_offset');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

  ELSEIF p_entity_name = 'deals' THEN
    SET v_sort_col = CASE p_sort
      WHEN 'name' THEN 'name' WHEN 'valueAmount' THEN 'value_amount' WHEN 'stage' THEN 'stage'
      WHEN 'expectedCloseDate' THEN 'expected_close_date' WHEN 'updatedAt' THEN 'updated_at' ELSE 'created_at' END;

    SET v_sql = CONCAT(
      'SELECT COUNT(*) INTO @v_total FROM deals WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_stage IS NULL OR stage = @v_stage)',
      ' AND (@v_companyId IS NULL OR company_id = @v_companyId)',
      ' AND (@v_search = \'\' OR name LIKE @v_like)');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET v_sql = CONCAT(
      'SELECT id, company_id, contact_id, lead_id, name, value_amount, expected_close_date, stage,',
      ' lost_reason, owner_id, closed_at, created_at, updated_at FROM deals WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(owner_id, @v_scope) > 0)',
      ' AND (@v_stage IS NULL OR stage = @v_stage)',
      ' AND (@v_companyId IS NULL OR company_id = @v_companyId)',
      ' AND (@v_search = \'\' OR name LIKE @v_like)',
      ' ORDER BY ', v_sort_col, ' ', v_direction,
      ' LIMIT @v_limit OFFSET @v_offset');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

  ELSEIF p_entity_name = 'tasks' THEN
    SET v_sort_col = CASE p_sort
      WHEN 'subject' THEN 'subject' WHEN 'priority' THEN 'priority'
      WHEN 'createdAt' THEN 'created_at' ELSE 'due_date' END;

    SET v_sql = CONCAT(
      'SELECT COUNT(*) INTO @v_total FROM tasks WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(assigned_to, @v_scope) > 0)',
      ' AND (@v_assignee IS NULL OR assigned_to = @v_assignee)',
      ' AND (@v_status IS NULL OR (@v_status = \'OPEN\' AND completed_at IS NULL) OR (@v_status = \'DONE\' AND completed_at IS NOT NULL))',
      ' AND (@v_search = \'\' OR subject LIKE @v_like)');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET v_sql = CONCAT(
      'SELECT id, subject, due_date, priority, related_to_type, related_to_id, assigned_to, created_by,',
      ' completed_at, created_at, updated_at FROM tasks WHERE deleted_at IS NULL',
      ' AND (@v_scope IS NULL OR FIND_IN_SET(assigned_to, @v_scope) > 0)',
      ' AND (@v_assignee IS NULL OR assigned_to = @v_assignee)',
      ' AND (@v_status IS NULL OR (@v_status = \'OPEN\' AND completed_at IS NULL) OR (@v_status = \'DONE\' AND completed_at IS NOT NULL))',
      ' AND (@v_search = \'\' OR subject LIKE @v_like)',
      ' ORDER BY ', v_sort_col, ' ', v_direction,
      ' LIMIT @v_limit OFFSET @v_offset');
    PREPARE stmt FROM v_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown entity_name for sp_records_search.';
  END IF;

  SET p_total_count = @v_total;
END
