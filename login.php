<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Aplikasi POS</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #f72585;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #4cc9f0;
            --transition: all 0.3s ease;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            padding: 40px 30px;
        }
        
        .login-form h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--primary);
            text-align: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            cursor: pointer;
            color: var(--gray);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .login-container {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>LOGIN POS</h1>
            <?php if (isset($_GET['error'])): ?>
    <div style="color: red; margin-bottom: 15px; text-align: center;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif;?>
            <form action="auth.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    
                </div>
                
                <button type="submit" class="btn">Masuk</button>
            </form>
        </div>
    </div>

    <script>
        
    </script>
</body>

</html>
