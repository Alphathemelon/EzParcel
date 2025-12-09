<?php
include_once 'database.php';

try {
    // Connect
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read all parcels
    $stmt = $conn->prepare("
        SELECT 
            fld_parcel_ID,
            fld_parcel_status,
            fld_parcel_storage,
            fld_parcel_date,
            fld_parcel_amount,
            fld_parcel_weight,
            fld_parcel_location,
            fld_user_phone
        FROM tbl_parcel_ezparcel
        ORDER BY fld_parcel_date DESC
    ");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylesearch.css"> 
    <title>Order History</title>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="tabs" style="margin-bottom:20px;">
    <div class="tab blue active" onclick="filterParcels('all')">ALL</div>
    <div class="tab green" onclick="filterParcels('green')">COMPLETE</div>
    <div class="tab red" onclick="filterParcels('red')">INCOMPLETE</div>
</div>

<div class="search-wrapper">
    <form class="search-form" onsubmit="event.preventDefault();" role="search">
        <input id="search" class="search-input" type="search" placeholder="Search Order ID..." autofocus required />
        <button type="submit" class="search-btn">Go</button>    
    </form>
</div>

<div id="parcelList"></div>

<script>
// ===================================
// CLEAN PHP → JS data conversion
// ===================================
let parcels = <?php
    $clean = [];

    foreach ($parcels as $p) {
        $clean[] = [
            "code" => $p["fld_parcel_storage"] . $p["fld_parcel_location"],
            "price"   => (float)$p["fld_parcel_amount"],
            "phone"   => $p["fld_user_phone"],
            "orderid" => $p["fld_parcel_ID"],
            "weight"  => $p["fld_parcel_weight"],
            "color"   => ($p["fld_parcel_status"] === "Complete" ? "green" : "red")
        ];
    }

    echo json_encode($clean, JSON_UNESCAPED_SLASHES);
?>;


// ===================================
// DISPLAY LIST
// ===================================
function displayParcels(list) {
    const parcelList = document.getElementById("parcelList");
    parcelList.innerHTML = "";

    list.forEach((p, i) => {
        parcelList.innerHTML += `
            <div class="order-card ${p.color}">
                <div class="order-header">
                    <div>${p.code}<br>
                        <span style="font-size:15px;font-weight:500;">RM${p.price.toFixed(2)}</span>
                    </div>
                    <div id="arrow-${i}" class="toggle-btn" onclick="toggleDetails(${i})">⌄</div>
                </div>

                <div class="details" id="details-${i}">
                    <b>Phone:</b> ${p.phone}<br>
                    <b>Order ID:</b> ${p.orderid}<br>
                    <b>Weight:</b> ${p.weight}<br>
                </div>
            </div>
        `;
    });
}


// FILTER
function filterParcels(type) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));

    if (type === "all") {
        document.querySelector(".tab.blue").classList.add("active");
        displayParcels(parcels);
    } 
    else if (type === "green") {
        document.querySelector(".tab.green").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "green"));
    } 
    else if (type === "red") {
        document.querySelector(".tab.red").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "red"));
    }
}


// TOGGLE
function toggleDetails(id) {
    const box = document.getElementById("details-" + id);
    const arrow = document.getElementById("arrow-" + id);

    if (box.style.display === "block") {
        box.style.display = "none";
        arrow.classList.remove("open");
    } else {
        box.style.display = "block";
        arrow.classList.add("open");
    }
}


// First load
displayParcels(parcels);
</script>

</body>
</html>
