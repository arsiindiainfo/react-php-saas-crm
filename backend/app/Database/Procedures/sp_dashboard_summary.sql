CREATE PROCEDURE sp_dashboard_summary(
  IN  p_owner_scope VARCHAR(1000),
  OUT p_total_companies INT,
  OUT p_new_leads_this_month INT,
  OUT p_conversion_rate DECIMAL(5,2),
  OUT p_open_deals_count INT,
  OUT p_open_deals_value DECIMAL(14,2),
  OUT p_revenue_this_month DECIMAL(14,2)
)
BEGIN
  DECLARE v_leads_created_this_month INT DEFAULT 0;
  DECLARE v_leads_converted_this_month INT DEFAULT 0;

  SELECT COUNT(*) INTO p_total_companies
    FROM companies
    WHERE deleted_at IS NULL AND status = 'CUSTOMER'
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0);

  SELECT COUNT(*) INTO p_new_leads_this_month
    FROM leads
    WHERE deleted_at IS NULL
      AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0);
  SET v_leads_created_this_month = p_new_leads_this_month;

  SELECT COUNT(*) INTO v_leads_converted_this_month
    FROM leads
    WHERE deleted_at IS NULL AND status = 'CONVERTED'
      AND converted_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0);

  SET p_conversion_rate = IF(v_leads_created_this_month = 0, 0,
    ROUND(v_leads_converted_this_month / v_leads_created_this_month * 100, 2));

  SELECT COUNT(*), COALESCE(SUM(value_amount), 0) INTO p_open_deals_count, p_open_deals_value
    FROM deals
    WHERE deleted_at IS NULL AND stage NOT IN ('WON', 'LOST')
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0);

  SELECT COALESCE(SUM(value_amount), 0) INTO p_revenue_this_month
    FROM deals
    WHERE deleted_at IS NULL AND stage = 'WON'
      AND closed_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND (p_owner_scope IS NULL OR FIND_IN_SET(owner_id, p_owner_scope) > 0);

  -- trend result set: won-deal revenue per month for the last 12 months
  SELECT
      DATE_FORMAT(m.month_start, '%Y-%m') AS month,
      COALESCE(SUM(d.value_amount), 0) AS revenue
    FROM (
      SELECT DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL n MONTH) AS month_start
        FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
                UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11) seq
    ) m
    LEFT JOIN deals d
      ON d.deleted_at IS NULL AND d.stage = 'WON'
      AND d.closed_at >= m.month_start AND d.closed_at < DATE_ADD(m.month_start, INTERVAL 1 MONTH)
      AND (p_owner_scope IS NULL OR FIND_IN_SET(d.owner_id, p_owner_scope) > 0)
    GROUP BY m.month_start
    ORDER BY m.month_start ASC;
END
