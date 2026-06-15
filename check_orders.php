<?php
require_once 'database.php';
$res = $conn->query("DESCRIBE orders");
while($row = $res->fetch_assoc()) {
    if($row['Field'] == 'status') {
        echo $row['Type'] . "\n";
    }
}
?>
