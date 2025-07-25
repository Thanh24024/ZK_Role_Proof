1. Lệnh kiểm tra bộ phần IT có những gmail nào trong SQL:
SELECT u.email
FROM user u
JOIN userrole ur ON u.id = ur.user_id
JOIN role r ON ur.role_id = r.id
WHERE r.name = 'IT';
