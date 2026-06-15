<?php
require_once 'database.php';
$res = $conn->query("DESCRIBE product_returns");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
