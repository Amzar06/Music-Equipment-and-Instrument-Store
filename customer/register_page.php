<?php
session_start();
include '../database.php';

$name = $email = $phone = $street = $city = $state = $postcode = '';
$security_question = $security_answer = '';
$password = $confirm_password = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Sanitize phone number by removing trailing or accidental whitespace
    $phone = trim($_POST['phone'] ?? '');
    
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postcode = $_POST['postcode'] ?? '';
    
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // =========================================================================
    // MALAYSIAN PHONE NUMBER PATTERN MAPPING
    // =========================================================================
    $my_phone_pattern = '/^\+60(1[0-9]|3|4|5|6|7|8|9)[0-9]{7,8}$/';

    if (empty($security_question) || empty($security_answer)) {
        $error = "Please select a security question and provide an answer.";
    }
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        $password = '';
        $confirm_password = '';
    } 
    elseif (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Password must be a minimum of 8 characters and contain at least 1 number and 1 symbol.";
        $password = '';
        $confirm_password = '';
    } 
    // Backend Validation check for Malaysian +60 structure
    elseif (!preg_match($my_phone_pattern, $phone)) {
        $error = "Invalid phone number format! Must be a valid Malaysian standard format starting with +60 (e.g., +60123456789).";
    }
    elseif (isset($conn)) {
        $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already registered.";
                $email = ''; 
            } else {
                // HASHING THE PASSWORD
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $processed_answer = strtolower($security_answer);
                $hashed_security_answer = password_hash($processed_answer, PASSWORD_BCRYPT);

                // Make sure your database table columns match these: `cust_security_question` and `cust_security_answer`
                $insert = $conn->prepare("INSERT INTO customers (cust_name, cust_email, cust_password, cust_phone_number, cust_street, cust_city, cust_state, cust_postcode, cust_security_question, cust_security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insert) {
                    $insert->bind_param("ssssssssss", $name, $email, $hashed_password, $phone, $street, $city, $state, $postcode, $security_question, $hashed_security_answer);
                    if ($insert->execute()) {
                        $success = "Registration successful! You can now login.";
                        $name = $email = $phone = $street = $city = $state = $postcode = $security_question = $security_answer = '';
                    } else {
                        $error = "Registration failed.";
                    }
                    $insert->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input {
            width: 100%;
            padding-right: 40px; 
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 38%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        .toggle-password:hover {
            color: #333;
        }
        .phone-hint {
            font-size: 0.82rem;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
            display: block;
        }
        .form-select {
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            background-color: #fff; 
            box-sizing: border-box;
        }
    </style>
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Create Account</h2>
      <p>Register a new account</p>

      <?php if ($error): ?>
          <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
              <?php echo htmlspecialchars($error); ?>
          </div>
      <?php endif; ?>
      <?php if ($success): ?>
          <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
              <?php echo htmlspecialchars($success); ?>
          </div>
      <?php endif; ?>

      <form action="" method="POST" id="registerForm">
          <label>Full Name</label>
          <input type="text" name="name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($name); ?>">

          <label>Email Address</label>
          <input type="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email); ?>">

          <label>Phone Number</label>
          <input type="text" name="phone" 
                 placeholder="e.g., +60123456789" 
                 pattern="^\+60(1[0-9]|[3-9])[0-9]{7,8}$"
                 title="Please enter a valid Malaysian phone number starting with +60 followed by 8 to 10 digits."
                 required value="<?php echo htmlspecialchars($phone); ?>" style="margin-bottom: 4px;">
          <span class="phone-hint"><i class="fa-solid fa-circle-info"></i> Format must include country code (e.g., <strong>+60171234567</strong>)</span>

          <label>Street Address</label>
          <input type="text" name="street" placeholder="No, Building, Street" required value="<?php echo htmlspecialchars($street); ?>">

          <div style="display: flex; gap: 10px;">
              <div style="flex: 1;">
                  <label>City</label>
                  <input type="text" name="city" placeholder="City" required value="<?php echo htmlspecialchars($city); ?>">
              </div>
              <div style="flex: 1;">
                  <label>Postcode</label>
                  <input type="text" name="postcode" placeholder="Postcode" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?php echo htmlspecialchars($postcode); ?>">
              </div>
          </div>

          <label for="state">State</label>
          <select name="state" id="state" class="form-select" required>
              <option value="" disabled <?php echo empty($state) ? 'selected' : ''; ?>>Select your state</option>
              <?php
              $states = ["Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang", "Penang", "Perak", "Perlis", "Sabah", "Sarawak", "Selangor", "Terengganu", "W.P. Kuala Lumpur", "W.P. Labuan", "W.P. Putrajaya"];
              foreach ($states as $s) {
                  $selected = ($state === $s) ? 'selected' : '';
                  echo "<option value=\"$s\" $selected>$s</option>";
              }
              ?>
          </select>

          <label for="security_question">Security Question</label>
          <select name="security_question" id="security_question" class="form-select" required>
              <option value="" disabled <?php echo empty($security_question) ? 'selected' : ''; ?>>Choose a Security Verification Question</option>
              <?php
              $questions = [
                  "What was the name of your first pet?",
                  "What is your mother's name?",
                  "What elementary school did you attend?",
                  "In what city were you born?",
                  "What was your favorite food as a child?"
              ];
              foreach ($questions as $q) {
                  $selected = ($security_question === $q) ? 'selected' : '';
                  echo "<option value=\"$q\" $selected>$q</option>"; // FIXED: Changed $s to $q here
              }
              ?>
          </select>

          <label for="security_answer">Security Answer</label>
          <input 
              type="text" 
              name="security_answer" 
              id="security_answer" 
              placeholder="Case-insensitive answer protection" 
              required 
              value="<?php echo htmlspecialchars($security_answer); ?>"
          >
          <label>Password</label>
          <div class="password-container">
              <input 
                  type="password" 
                  name="password" 
                  id="password"
                  placeholder="Enter your password" 
                  required
                  pattern="^(?=.*[0-9])(?=.*[!@#$%^&*(),.?\x22:{}|<>]).{8,}$"
                  style="margin-bottom: 4px;"
                  value="<?php echo htmlspecialchars($password); ?>"
              >
              <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
          </div>
          <div id="pass-hint" style="font-size: 0.85em; color: #555; margin-bottom: 15px;">
              Minimum 8 characters, 1 number, and 1 symbol.
          </div>

          <label>Confirm Password</label>
          <div class="password-container">
              <input 
                  type="password" 
                  name="confirm_password" 
                  id="confirm_password"
                  placeholder="Confirm your password" 
                  required
                  style="margin-bottom: 4px;"
                  value="<?php echo htmlspecialchars($confirm_password); ?>"
              >
              <i class="fa-solid fa-eye toggle-password" id="toggleConfirmPassword"></i>
          </div>
          <div id="match-hint" style="font-size: 0.85em; color: #555; margin-bottom: 15px;"></div>

          <button type="submit" style="width: 100%; margin-top: 12px;">Register</button>
      </form>
      
      <div style="margin-top: 20px; text-align: center; font-size: 14px;">
          Already have an account? <a href="../product/cust login.php" style="color: var(--accent); text-decoration: none;">Login here</a>
      </div>
    </div>
  </div>

  <script>
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirm_password');
      const passHint = document.getElementById('pass-hint');
      const matchHint = document.getElementById('match-hint');
      const form = document.getElementById('registerForm');
      
      const togglePassword = document.getElementById('togglePassword');
      const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

      // 1. Toggle visibility for Password field
      togglePassword.addEventListener('click', function () {
          const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
          password.setAttribute('type', type);
          this.classList.toggle('fa-eye-slash');
      });

      // 2. Toggle visibility for Confirm Password field
      toggleConfirmPassword.addEventListener('click', function () {
          const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
          confirmPassword.setAttribute('type', type);
          this.classList.toggle('fa-eye-slash');
      });

      // Live requirement validations
      password.addEventListener('input', function() {
          if (password.value.length > 0 && !password.checkValidity()) {
              passHint.style.color = '#991b1b';
              passHint.style.fontWeight = 'bold';
          } else {
              passHint.style.color = '#555';
              passHint.style.fontWeight = 'normal';
          }
      });

      function checkMatch() {
          if (confirmPassword.value.length === 0) {
              matchHint.textContent = '';
              return;
          }
          if (password.value === confirmPassword.value) {
              matchHint.textContent = '✓ Passwords match';
              matchHint.style.color = '#166534';
          } else {
              matchHint.textContent = '✗ Passwords do not match';
              matchHint.style.color = '#991b1b';
          }
      }

      password.addEventListener('input', checkMatch);
      confirmPassword.addEventListener('input', checkMatch);

      form.addEventListener('submit', function(e) {
          if (password.value !== confirmPassword.value) {
              e.preventDefault();
              alert('Passwords do not match. Please verify.');
          }
      });
  </script>

</body>
</html>