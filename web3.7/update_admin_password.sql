-- 更新admin用户密码为123
UPDATE users SET password = '$2y$10$Ug.JLxJeRXGQbHa.KZpxXOGNi9DzEJFPJfyVex7P1XL3CIKdkM/Iy' WHERE username = 'admin';

-- 确认更新成功
SELECT id, username, shop_name, is_admin FROM users WHERE username = 'admin';