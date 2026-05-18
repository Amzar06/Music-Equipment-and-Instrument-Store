<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile - Admin Portal</title>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f2f2f2;
    }

    /* Header */
    .header {
        background-color: #4a9b6f;
        color: white;
        padding: 15px 30px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    /* Container */
    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 90vh;
    }

    /* Profile Box */
    .profile-box {
        background: white;
        padding: 30px;
        width: 380px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .profile-box h2 {
        margin-bottom: 5px;
    }

    .profile-box p {
        font-size: 13px;
        color: gray;
        margin-bottom: 20px;
    }

    /* Input Fields */
    .profile-box input {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        border: 1px solid #ddd;
        border-radius: 6px;
        outline: none;
    }

    .profile-box input:focus {
        border-color: #4a9b6f;
    }

    /* Button */
    .btn {
        width: 100%;
        background-color: #4a9b6f;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 6px;
        margin-top: 10px;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn:hover {
        background-color: #3d8460;
        transform: translateY(-2px);
    }

    /* Optional: profile image */
    .profile-img {
        display: block;
        margin: 0 auto 15px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #ddd;
        object-fit: cover;
    }
</style>
</head>

<body>

<div class="header">ADMIN PORTAL</div>

<div class="container">
    <div class="profile-box">
        <h2>Edit Profile</h2>
        <p>Update your account information</p>

        <!-- Optional profile picture -->
        <img src="https://via.placeholder.com/80" class="profile-img" alt="Profile Image">

        <form>
            <input type="text" placeholder="Full Name" value="John Doe" required>
            <input type="email" placeholder="Email Address" value="john@example.com" required>
            <input type="text" placeholder="Phone Number" value="+60123456789">
            <input type="text" placeholder="Username" value="admin123">

            <button class="btn">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>