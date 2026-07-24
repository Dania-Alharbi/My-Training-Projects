<?php
require_once 'db.php';

$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - الواجب</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; direction: rtl; text-align: right; }
        .container { max-width: 800px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        form { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; align-items: center; }
        input[type="text"], input[type="number"] { padding: 10px; border: 1px solid #ccc; border-radius: 6px; flex: 1; min-width: 150px; }
        .checkbox-label { display: flex; align-items: center; gap: 5px; cursor: pointer; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #333; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .btn-toggle { text-decoration: none; padding: 5px 12px; border-radius: 4px; font-size: 14px; color: white; background-color: #17a2b8; }
        .btn-toggle:hover { background-color: #138496; }
    </style>
</head>
<body>
    <div class="container">
        <h2>إضافة مستخدم جديد</h2>
        <form method="POST" action="insert.php">
            <input type="text" name="name" placeholder="اسم المستخدم" required>
            <input type="number" name="age" placeholder="العمر" required>
            <label class="checkbox-label">
                <input type="checkbox" name="status" value="1"> مفعّل
            </label>
            <button type="submit">إضافة</button>
        </form>

        <h2>قائمة المستخدمين</h2>
        <table>
            <thead>
                <tr>
                    <th>المُعرّف (ID)</th>
                    <th>الاسم</th>
                    <th>العمر</th>
                    <th>الحالة</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['age']; ?></td>
                            <td>
                                <?php if ($row['status'] == 1): ?>
                                    <span class="status-active">نشط (1)</span>
                                <?php else: ?>
                                    <span class="status-inactive">غير نشط (0)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="toggle.php?id=<?php echo $row['id']; ?>" class="btn-toggle">تغيير الحالة</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #888;">لا يوجد مستخدمون حالياً.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>