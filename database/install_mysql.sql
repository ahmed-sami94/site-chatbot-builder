-- Adaptive Local Chatbot standalone MySQL installer.
-- Safe to import in phpMyAdmin/cPanel: creates tables if missing and seeds configuration id=1.

CREATE TABLE IF NOT EXISTS chatbot_configuration (
  id INT UNSIGNED NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  provider_mode VARCHAR(40) NOT NULL DEFAULT 'local_rules',
  ollama_base_url VARCHAR(500) DEFAULT 'http://127.0.0.1:11434',
  ollama_model VARCHAR(200) DEFAULT '',
  ollama_timeout INT NOT NULL DEFAULT 30,
  max_results INT NOT NULL DEFAULT 8,
  save_chat_history TINYINT(1) NOT NULL DEFAULT 1,
  allow_ollama TINYINT(1) NOT NULL DEFAULT 0,
  handoff_label VARCHAR(190) DEFAULT 'Human support',
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  context_type VARCHAR(80) DEFAULT NULL,
  context_id VARCHAR(190) DEFAULT NULL,
  context_title VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_context (context_type, context_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id INT UNSIGNED DEFAULT NULL,
  role VARCHAR(20) NOT NULL,
  message_text MEDIUMTEXT,
  metadata_json MEDIUMTEXT,
  created_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_tool_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id INT UNSIGNED DEFAULT NULL,
  tool_name VARCHAR(120) NOT NULL,
  arguments_json MEDIUMTEXT,
  result_summary MEDIUMTEXT,
  created_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_session (session_id),
  KEY idx_tool (tool_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_sources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_type VARCHAR(40) NOT NULL,
  title VARCHAR(255) NOT NULL,
  uri VARCHAR(800) DEFAULT NULL,
  trust_level VARCHAR(40) NOT NULL DEFAULT 'public',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  allowed_roles TEXT,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_active_type (is_active, source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_source_snapshots (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  content_hash VARCHAR(64) DEFAULT NULL,
  language VARCHAR(20) DEFAULT 'mixed',
  excerpt MEDIUMTEXT,
  content_text LONGTEXT,
  fetched_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_source (source_id),
  KEY idx_hash (content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_pending_actions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id INT UNSIGNED DEFAULT NULL,
  action_type VARCHAR(80) NOT NULL,
  payload_json MEDIUMTEXT,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT NULL,
  confirmed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_session (session_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO chatbot_configuration (
  id,
  enabled,
  provider_mode,
  ollama_base_url,
  ollama_model,
  ollama_timeout,
  max_results,
  save_chat_history,
  allow_ollama,
  handoff_label,
  created_at,
  updated_at
) VALUES (
  1,
  1,
  'local_rules',
  'http://127.0.0.1:11434',
  '',
  30,
  8,
  1,
  0,
  'Human support',
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE updated_at = NOW();
