<?php $siteName = "ময়মনসিংহ হোম সার্ভিস"; ?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অর্ডার করুন - <?php echo $siteName; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; }
        .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }
        .container { max-width: 500px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #1e3a8a; margin-bottom: 20px; text-align: center; }
        label { display: block; margin: 15px 0 5px; color: #333; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        textarea { height: 80px; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #1e3a8a; color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; }
        .back { display: block; text-align: center; margin-top: 15px; color: #1e3a8a; }
    </style>
</head>
<body>
    <div class="header"><h2><?php echo $siteName; ?></h2></div>
    <div class="container">
        <h2>অর্ডার ফর্ম</h2>
        <form action="save_order.php" method="POST">
            <label>আপনার নাম</label>
            <input type="text" name="customer_name" required>

            <label>মোবাইল নম্বর</label>
            <input type="tel" name="phone" required pattern="01[0-9]{9}" placeholder="01XXXXXXXXX">

            <label>ঠিকানা</label>
            <input type="text" name="address" required>

            <label>কী ধরনের সার্ভিস?</label>
            <select name="service_type" required>
                <option value="">-- সিলেক্ট করুন --</option>
                <option value="electric">⚡ ইলেকট্রিক</option>
                <option value="plumbing">🚿 প্লাম্বিং</option>
                <option value="ac_fridge">❄️ এসি/ফ্রিজ</option>
                <option value="tv">📺 টিভি</option>
                <option value="medicine">💊 ঔষধ</option>
                <option value="ambulance">🚑 অ্যাম্বুলেন্স</option>
            </select>

            <label>সমস্যার বিবরণ</label>
            <textarea name="problem" required></textarea>

            <button type="submit">অর্ডার করুন</button>
        </form>
        <a href="index.php" class="back">← হোমে ফিরে যান</a>
    </div>
</body>
</html>